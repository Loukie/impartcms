<?php

namespace App\Support\Ai;

use App\Models\Page;
use App\Models\Setting;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AiSiteBuilder
{
    public function __construct(
        private readonly AiPageGenerator $pageGenerator,
    ) {}

    /**
     * Build pages from a blueprint JSON.
     *
     * Options:
     * - style_mode: inline|classes (default inline)
     * - template: default page template key (default blank)
     * - action: draft|publish (default draft)
     * - publish_homepage: bool (if true, homepage will be published even if action=draft)
     * - set_homepage: bool (if true, homepage selection is stored in settings; requires published homepage)
     *
     * @return array{
     *   created:int,
     *   pages: array<int, array{title:string, slug:string, status:string, id:int|null, error:string|null}>,
     *   homepage_id:int|null,
     *   warnings: array<int,string>
     * }
     */
    public function buildFromBlueprintJson(string $blueprintJson, array $options = []): array
    {
        $bp = $this->decodeBlueprint($blueprintJson);
        $pages = $bp['pages'] ?? null;
        if (!is_array($pages) || count($pages) === 0) {
            throw new \RuntimeException('Blueprint has no pages.');
        }

        $styleMode = ((string) ($options['style_mode'] ?? 'inline')) === 'classes' ? 'classes' : 'inline';
        $template = trim((string) ($options['template'] ?? 'blank'));
        if ($template === '') $template = 'blank';

        $action = (string) ($options['action'] ?? 'draft');
        if (!in_array($action, ['draft', 'publish'], true)) $action = 'draft';

        $publishHomepage = (bool) ($options['publish_homepage'] ?? false);
        $setHomepage = (bool) ($options['set_homepage'] ?? false);

        $reportRows = [];
        $warnings = [];

        $canonicalNavHtml = $this->buildCanonicalNavigationHtml(
            $pages,
            (array) ($options['design_system'] ?? [])
        );

        // Published homepage ID (only set if the homepage ends up published)
        $homepageId = null;
        // Track intended homepage (even if draft) for helpful warnings
        $homepageCandidateTitle = null;

        DB::beginTransaction();
        try {
            foreach ($pages as $p) {
                if (!is_array($p)) {
                    $warnings[] = 'Skipped non-object page entry.';
                    continue;
                }

                $title = trim((string) ($p['title'] ?? ''));
                if ($title === '') {
                    $warnings[] = 'Skipped a page with no title.';
                    continue;
                }

                $slug = $this->normaliseSlug((string) ($p['slug'] ?? Str::slug($title)));
                $slug = $this->ensureUniqueSlug($slug);

                $isHomepage = (bool) ($p['is_homepage'] ?? false);
                if ($isHomepage) {
                    $homepageCandidateTitle = $homepageCandidateTitle ?? $title;
                }

                $pageTemplate = trim((string) ($p['template'] ?? ''));
                if ($pageTemplate === '') $pageTemplate = $template;

                $metaTitle = trim((string) ($p['meta_title'] ?? ''));
                if ($metaTitle === '') $metaTitle = $title;

                $metaDesc = trim((string) ($p['meta_description'] ?? ''));
                if (mb_strlen($metaDesc) > 160) {
                    $metaDesc = mb_substr($metaDesc, 0, 157) . '…';
                }

                $brief = trim((string) ($p['brief'] ?? ''));
                if ($brief === '') {
                    $brief = 'Create a page for: ' . $title;
                }

                // Replace external media URLs with internal storage URLs
                if (!empty($options['media_mapping']) && is_array($options['media_mapping'])) {
                    $brief = $this->replaceMediaUrls($brief, $options['media_mapping']);
                }

                $status = $action === 'publish' ? 'published' : 'draft';
                $publishedAt = $status === 'published' ? now() : null;

                // If the build is draft, optionally publish just the homepage.
                if ($status === 'draft' && $isHomepage && $publishHomepage) {
                    $status = 'published';
                    $publishedAt = now();
                }

                $page = new Page();
                $page->title = $title;
                $page->slug = $slug;
                $page->template = $pageTemplate;
                $page->status = $status;
                $page->published_at = $publishedAt;

                // IMPORTANT:
                // Never mark drafts as homepage. Also avoid creating multiple homepage flags.
                // The homepage flag is only applied at the end if “Set homepage as active” is enabled
                // and the homepage is published.
                $page->is_homepage = false;

                // Generate page HTML (sanitised)
                try {
                    $gen = $this->pageGenerator->generateHtml($brief, [
                        'title' => $title,
                        'style_mode' => $styleMode,
                        'full_document' => false,
                        'design_system' => $options['design_system'] ?? [],
                    ]);

                    $body = (string) ($gen['clean_html'] ?? '');
                    
                    // Warn if HTML is empty or very short
                    if (strlen($body) < 50) {
                        $warnings[] = 'Page "' . $title . '" generated very little content (brief may be too vague).';
                        \Log::warning('AiSiteBuilder: Short HTML content', [
                            'title' => $title,
                            'brief' => $brief,
                            'html_length' => strlen($body),
                        ]);
                    }
                    
                    $page->body = $this->applyCanonicalNavigation($body, $canonicalNavHtml);
                } catch (\Throwable $e) {
                    // We still create the page, but leave it blank so nothing breaks.
                    $page->body = '';
                    $errorMsg = 'HTML generation failed: ' . $e->getMessage();
                    $warnings[] = 'Page "' . $title . '" failed: ' . $e->getMessage();
                    
                    \Log::error('AiSiteBuilder: Page generation failed', [
                        'title' => $title,
                        'brief' => $brief,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    $reportRows[] = [
                        'title' => $title,
                        'slug' => $slug,
                        'status' => $status,
                        'id' => null,
                        'error' => $errorMsg,
                    ];
                    $page->save();
                    $page->seo()->create([
                        'meta_title' => $metaTitle,
                        'meta_description' => $metaDesc,
                        'robots' => 'index,follow',
                    ]);
                    if ($isHomepage && $status === 'published') {
                        $homepageId = (int) $page->id;
                    }
                    continue;
                }

                $page->save();

                $page->seo()->create([
                    'meta_title' => $metaTitle,
                    'meta_description' => $metaDesc,
                    'robots' => 'index,follow',
                ]);

                if ($isHomepage && $status === 'published') {
                    $homepageId = (int) $page->id;
                }

                $reportRows[] = [
                    'title' => $title,
                    'slug' => $slug,
                    'status' => $status,
                    'id' => (int) $page->id,
                    'error' => null,
                ];
            }

            // If requested, set homepage selection (requires a published homepage)
            if ($setHomepage) {
                if (!$homepageId) {
                    $warnings[] = 'Homepage was not published, so it cannot be set as the active homepage. Either publish all pages, or enable “Publish homepage”.';
                } else {
                    // Clear existing homepage flags, keep it single.
                    Page::withTrashed()->where('id', '!=', $homepageId)->update(['is_homepage' => false]);
                    Page::whereKey($homepageId)->update(['is_homepage' => true]);
                    Setting::set('homepage_page_id', (string) $homepageId);
                }
            } else {
                // Helpful nudge so users know where the intended homepage is.
                if ($homepageCandidateTitle && !$homepageId) {
                    $warnings[] = 'Blueprint marked “' . $homepageCandidateTitle . '” as the homepage, but it was created as a draft. Publish it, then use Pages → Set Home to make it the active homepage.';
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'created' => count($reportRows),
            'pages' => $reportRows,
            'homepage_id' => $homepageId,
            'warnings' => $warnings,
        ];
    }

    private function decodeBlueprint(string $blueprintJson): array
    {
        $json = trim($blueprintJson);
        if ($json === '') {
            throw new \RuntimeException('Blueprint JSON is empty.');
        }

        try {
            $bp = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Blueprint JSON is invalid: ' . $e->getMessage());
        }

        if (!is_array($bp)) {
            throw new \RuntimeException('Blueprint JSON did not decode into an object.');
        }

        return $bp;
    }

    private function normaliseSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/\s+/', '-', $slug) ?? $slug;
        $slug = preg_replace('/[^a-z0-9\-\/]/', '', $slug) ?? $slug;
        $slug = preg_replace('#/+#', '/', $slug) ?? $slug;
        $slug = trim($slug, '/');

        return $slug !== '' ? $slug : 'page-' . Str::lower(Str::random(6));
    }

    private function ensureUniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $i = 2;
        while (Page::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i;
            $i++;
        }
        return $slug;
    }

    /**
     * Build a single shared nav HTML block for all generated pages.
     */
    private function buildCanonicalNavigationHtml(array $pages, array $designSystem = []): string
    {
        if (count($pages) === 0) {
            return '';
        }

        $primaryColor = trim((string) ($designSystem['primary_color'] ?? '#2563eb'));
        if ($primaryColor === '') {
            $primaryColor = '#2563eb';
        }

        $textColor = trim((string) ($designSystem['text_color'] ?? '#1f2937'));
        if ($textColor === '') {
            $textColor = '#1f2937';
        }

        $navItems = [];
        foreach ($pages as $page) {
            if (!is_array($page)) {
                continue;
            }

            $title = trim((string) ($page['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $isHomepage = (bool) ($page['is_homepage'] ?? false);
            $slug = trim((string) ($page['slug'] ?? Str::slug($title)));
            $slug = $this->normaliseSlug($slug);

            $href = $isHomepage ? '/' : '/' . ltrim($slug, '/');
            $navItems[] = [
                'title' => $title,
                'href' => $href,
            ];
        }

        if (count($navItems) === 0) {
            return '';
        }

        $links = '';
        foreach ($navItems as $item) {
            $title = e($item['title']);
            $href = e($item['href']);
            $links .= '<a href="' . $href . '" style="text-decoration:none;color:' . e($textColor) . ';font-weight:600;padding:8px 10px;border-radius:8px;">' . $title . '</a>';
        }

        return '<nav data-ai-shared-nav="1" style="position:sticky;top:0;z-index:40;background:#ffffff;border-bottom:1px solid #e5e7eb;padding:12px 16px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">'
            . '<span style="display:inline-block;width:10px;height:10px;border-radius:999px;background:' . e($primaryColor) . ';"></span>'
            . $links
            . '</nav>';
    }

    /**
     * Ensure each page uses exactly one canonical nav at the top.
     */
    private function applyCanonicalNavigation(string $bodyHtml, string $canonicalNavHtml): string
    {
        if (trim($canonicalNavHtml) === '') {
            return $bodyHtml;
        }

        $body = trim($bodyHtml);
        if ($body === '') {
            return $canonicalNavHtml;
        }

        // Remove the first existing nav block if present to avoid duplicate menu bars.
        $body = preg_replace('/<nav\\b[^>]*>.*?<\\/nav>/is', '', $body, 1) ?? $body;

        // Inject global CSS for professional polish
        $css = $this->buildGlobalStyling();

        return $canonicalNavHtml . "\n" . $css . "\n" . ltrim($body);
    }

    /**
     * Generate global CSS for professional visual polish across all cloned pages.
     */
    private function buildGlobalStyling(): string
    {
        return <<<'CSS'
<style>
    :root {
        --spacing-xs: 8px;
        --spacing-sm: 16px;
        --spacing-md: 24px;
        --spacing-lg: 32px;
        --spacing-xl: 48px;
        --spacing-2xl: 64px;
        --border-radius: 8px;
        --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
        --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05);
        --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.15);
    }

    /* Global section styling */
    section {
        scroll-margin-top: 80px;
    }

    /* Alternating section backgrounds */
    section:nth-child(even) {
        background-color: #f9fafb;
    }

    /* Container max-width for readability */
    section > div:first-child {
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Responsive grid defaults */
    [style*="grid-template-columns"] {
        gap: var(--spacing-lg);
    }

    /* Card hover effects */
    [style*="box-shadow"] {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    [style*="box-shadow"]:hover {
        box-shadow: var(--shadow-xl) !important;
        transform: translateY(-4px);
    }

    /* Link/button hover effects */
    a[style*="padding"][style*="background"],
    a[style*="padding"][style*="color"] {
        transition: all 0.3s ease;
    }

    a[style*="padding"][style*="background"]:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }

    /* Typography hierarchy */
    h1 {
        line-height: 1.2;
        letter-spacing: -0.02em;
        margin-bottom: var(--spacing-md);
    }

    h2 {
        line-height: 1.3;
        letter-spacing: -0.01em;
        margin-bottom: var(--spacing-md);
    }

    h3, h4, h5, h6 {
        line-height: 1.4;
        margin-bottom: var(--spacing-sm);
    }

    p {
        line-height: 1.6;
        margin-bottom: var(--spacing-md);
        color: #4b5563;
    }

    /* Image responsiveness and styling */
    img {
        max-width: 100% !important;
        height: auto !important;
        display: block;
    }

    /* Blockquote styling */
    blockquote {
        margin: var(--spacing-lg) 0;
        padding: var(--spacing-md) var(--spacing-lg);
        border-left: 4px solid currentColor;
        background: rgba(255, 255, 255, 0.5);
        opacity: 0.85;
        font-style: italic;
        border-radius: 4px;
    }

    /* List improvements */
    ul, ol {
        margin-bottom: var(--spacing-md);
        padding-left: var(--spacing-lg);
        line-height: 1.8;
    }

    li {
        margin-bottom: var(--spacing-sm);
    }

    /* Responsive design for mobile */
    @media (max-width: 768px) {
        section {
            padding: 40px 16px;
        }

        h1 {
            font-size: 32px;
        }

        h2 {
            font-size: 24px;
        }

        [style*="grid-template-columns"],
        [style*="grid"] {
            grid-template-columns: 1fr !important;
        }

        [style*="display: grid; gap"],
        [style*="display: flex; gap"] {
            flex-direction: column;
        }
    }

    /* Print styles */
    @media print {
        section {
            page-break-inside: avoid;
        }

        a {
            text-decoration: underline;
        }
    }
</style>
CSS;
    }

    /**
     * Replace external media URLs with internal storage URLs.
     *
     * @param string $brief The page brief text
     * @param array $mapping Array mapping external URL => internal URL
     * @return string Brief with replaced URLs
     */
    private function replaceMediaUrls(string $brief, array $mapping): string
    {
        foreach ($mapping as $external => $internal) {
            $brief = str_replace($external, $internal, $brief);
        }
        return $brief;
    }
}
