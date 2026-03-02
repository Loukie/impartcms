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
                    ]);

                    $page->body = (string) ($gen['clean_html'] ?? '');
                } catch (\Throwable $e) {
                    // We still create the page, but leave it blank so nothing breaks.
                    $page->body = '';
                    $reportRows[] = [
                        'title' => $title,
                        'slug' => $slug,
                        'status' => $status,
                        'id' => null,
                        'error' => 'HTML generation failed: ' . $e->getMessage(),
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
}
