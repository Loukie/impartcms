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
    * - fallback_image_url: string (optional, stored media URL used when images are missing/broken)
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

        $extractAssets = (bool) ($options['extract_layout_assets'] ?? false);

        $canonicalNavHtml = $this->buildCanonicalNavigationHtml(
            $pages,
            (array) ($options['design_system'] ?? []),
            (string) ($options['nav_logo_url'] ?? ''),
            true
        );

        $innerNavHtml = $extractAssets
            ? $this->buildCanonicalNavigationHtml(
                $pages,
                (array) ($options['design_system'] ?? []),
                (string) ($options['nav_logo_url'] ?? ''),
                false
            )
            : '';

        $canonicalFooterHtml = $extractAssets
            ? $this->buildCanonicalFooterHtml($pages, (array) ($options['design_system'] ?? []))
            : '';

        // Published homepage ID (only set if the homepage ends up published)
        $homepageId = null;
        // Track intended homepage (even if draft) for helpful warnings
        $homepageCandidateTitle = null;

        // Collect <style> blocks extracted from each page body in extract mode.
        // Keyed by page ID so each page gets its own CSS snippet (no cross-page conflicts).
        $perPageCss = [];

        DB::beginTransaction();
        try {
            foreach ($pages as $p) {
                if (!is_array($p)) {
                    $warnings[] = 'Skipped non-object page entry.';
                    continue;
                }
                $currentPageCss = '';

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

                if (!empty($options['page_media_hints']) && is_array($options['page_media_hints'])) {
                    $brief = $this->injectPageMediaHintsIntoBrief(
                        $brief,
                        $title,
                        $slug,
                        (array) $options['page_media_hints']
                    );
                }

                if ($isHomepage) {
                    $brief = $this->buildHomepageHeroBrief($brief, $title);
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
                        'business_context' => (string) ($options['business_context'] ?? ''),
                    ]);

                    $body = (string) ($gen['clean_html'] ?? '');

                    // Single quality gate covers both thin and low-quality output.
                    // Threshold 55: only retry truly poor pages, avoiding unnecessary extra calls.
                    $quality = $this->assessPageQuality($body);
                    if ((int) ($quality['score'] ?? 0) < 55) {
                        $qualityRetryBrief = $this->buildQualityGateRetryBrief($brief, $title, (array) ($quality['issues'] ?? []));
                        $qualityRetry = $this->pageGenerator->generateHtml($qualityRetryBrief, [
                            'title' => $title,
                            'style_mode' => $styleMode,
                            'full_document' => false,
                            'design_system' => $options['design_system'] ?? [],
                            'business_context' => (string) ($options['business_context'] ?? ''),
                        ]);

                        $qualityRetryBody = (string) ($qualityRetry['clean_html'] ?? '');
                        $retryQuality = $this->assessPageQuality($qualityRetryBody);
                        if ((int) ($retryQuality['score'] ?? 0) >= (int) ($quality['score'] ?? 0)) {
                            $body = $qualityRetryBody;
                        }
                    }

                    // Ensure imported media URLs are used in final HTML where possible.
                    if (!empty($options['media_mapping']) && is_array($options['media_mapping'])) {
                        $body = $this->replaceMediaUrls($body, $options['media_mapping']);
                    }

                    // Guarantee image resilience: missing/broken images fall back to a generated placeholder.
                    $body = $this->applyImageFallbacks($body, [
                        'design_system' => (array) ($options['design_system'] ?? []),
                        'source_url' => (string) ($options['source_url'] ?? ''),
                        'fallback_image_url' => (string) ($options['fallback_image_url'] ?? ''),
                    ]);
                    
                    // Warn if HTML is empty or very short
                    if (strlen($body) < 50) {
                        $warnings[] = 'Page "' . $title . '" generated very little content (brief may be too vague).';
                        \Log::warning('AiSiteBuilder: Short HTML content', [
                            'title' => $title,
                            'brief' => $brief,
                            'html_length' => strlen($body),
                        ]);
                    }
                    
                    // Run audit/fix on generated HTML before saving
                    $auditContext = [
                        'nav_logo_url' => $options['nav_logo_url'] ?? '',
                        'allowed_colors' => array_map('strtolower', array_filter([
                            $options['design_system']['primary_color'] ?? null,
                            $options['design_system']['secondary_color'] ?? null,
                            $options['design_system']['accent_color'] ?? null,
                            $options['design_system']['background_color'] ?? null,
                            $options['design_system']['text_color'] ?? null,
                        ])),
                    ];
                    $auditor = new \App\Support\Ai\AiSiteAudit();
                    // In extract mode: strip nav/footer and pull out <style> blocks from body
                    // (nav/footer go into LayoutBlocks, CSS into a shared CustomSnippet).
                    // In embed mode: inject canonical nav + global styling directly into page body.
                    if ($extractAssets) {
                        $stripped = $this->stripNavigationAndFooter($body);
                        $extracted = $this->extractAndStripStyleBlocks($stripped);
                        $processedBody = $extracted['html'];
                        if ($extracted['css'] !== '') {
                            $currentPageCss = $extracted['css'];
                        }
                    } else {
                        $processedBody = $this->applyCanonicalNavigation($body, $canonicalNavHtml);
                    }
                    $audited = $auditor->auditAndFix($processedBody, $auditContext);
                    $page->body = $audited['html'];
                    foreach (($audited['issues'] ?? []) as $issue) {
                        $warnings[] = 'Audit: ' . $issue;
                    }
                    foreach (($audited['fixes'] ?? []) as $fix) {
                        $warnings[] = 'Auto-fix: ' . $fix;
                    }
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

                if ($currentPageCss !== '') {
                    $perPageCss[(int) $page->id] = $currentPageCss;
                }

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

        $result = [
            'created' => count($reportRows),
            'pages' => $reportRows,
            'homepage_id' => $homepageId,
            'warnings' => $warnings,
        ];

        if ($extractAssets) {
            $result['canonical_nav_html'] = $canonicalNavHtml;
            $result['inner_nav_html']     = $innerNavHtml;
            $result['canonical_footer_html'] = $canonicalFooterHtml;
            $result['reveal_css'] = $this->buildRevealCss();
            $result['reveal_js'] = $this->buildRevealJs();
            $result['page_css_map'] = $perPageCss; // page_id => css_string, one entry per page
        }

        return $result;
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
        // Slug has a DB-level unique index, so we must consider trashed rows as well.
        while (Page::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private function isThinPageHtml(string $html): bool
    {
        $content = trim($html);
        if ($content === '' || strlen($content) < 1400) {
            return true;
        }

        $sectionCount = preg_match_all('/<section\b/i', $content);
        $headingCount = preg_match_all('/<h[1-3]\b/i', $content);
        $paragraphCount = preg_match_all('/<p\b/i', $content);

        return ($sectionCount < 5) || ($headingCount < 4) || ($paragraphCount < 6);
    }

    private function buildDepthRetryBrief(string $brief, string $title): string
    {
        $parts = [];
        $parts[] = $brief;
        $parts[] = '';
        $parts[] = 'Quality retry requirements for this page:';
        $parts[] = '- Expand this page into a premium long-form layout with 6-8 meaningful sections.';
        $parts[] = '- Include concrete, domain-specific details and avoid generic statements.';
        $parts[] = '- Add depth: process steps, service detail, use-case examples, and trust/proof sections.';
        $parts[] = '- Include robust copy in each section (not one-line blurbs).';
        $parts[] = '- Keep content and imagery aligned with page title: ' . $title . '.';

        return implode("\n", $parts);
    }

    private function buildHomepageHeroBrief(string $brief, string $title): string
    {
        $parts = [];
        $parts[] = $brief;
        $parts[] = '';
        $parts[] = 'Homepage composition requirements:';
        $parts[] = '- Start with a full-viewport hero section at the very top of the page (min-height: 90vh on desktop). Do NOT wrap the hero in a <nav> element.';
        $parts[] = '- Use a strong visual background with readable overlay contrast and premium spacing.';
        $parts[] = '- Include one H1, one supporting paragraph, and 1-2 meaningful CTAs in the hero.';
        $parts[] = '- Do not start the page with a small plain heading block or thin top section.';
        $parts[] = '- Keep section content and visuals tightly aligned to page title: ' . $title . '.';

        return implode("\n", $parts);
    }

    /**
     * @return array{score:int,issues:array<int,string>}
     */
    private function assessPageQuality(string $html): array
    {
        $score = 100;
        $issues = [];

        $len = strlen(trim($html));
        if ($len < 2500) {
            $score -= 25;
            $issues[] = 'content too short (need 2500+ chars)';
        } elseif ($len < 4000) {
            $score -= 10;
            $issues[] = 'content could be deeper (target 4000+ chars)';
        }

        $sections = (int) preg_match_all('/<section\b/i', $html);
        if ($sections < 5) {
            $score -= 20;
            $issues[] = 'insufficient sections (need 5+)';
        } elseif ($sections < 7) {
            $score -= 8;
            $issues[] = 'could use more section variety (target 7+)';
        }

        $paragraphs = (int) preg_match_all('/<p\b/i', $html);
        if ($paragraphs < 8) {
            $score -= 15;
            $issues[] = 'not enough explanatory copy (need 8+ paragraphs)';
        }

        if (preg_match('/\b(innovation|quality|trusted|modern solutions|cutting-edge)\b/i', $html) === 1) {
            $score -= 8;
            $issues[] = 'generic filler language detected';
        }



        // Check for CSS custom properties usage — premium output should define them
        if (!str_contains($html, '--color-primary') && !str_contains($html, '--color-secondary')) {
            $score -= 10;
            $issues[] = 'missing CSS custom properties (design tokens not defined)';
        }

        // Check for responsive media queries
        if (!str_contains($html, '@media')) {
            $score -= 10;
            $issues[] = 'no responsive media queries';
        }

        // Check for layout variety — detect repeated two-column splits
        $twoColCount = (int) preg_match_all('/grid-template-columns\s*:\s*1fr\s+1fr\b/i', $html);
        if ($twoColCount >= 4) {
            $score -= 12;
            $issues[] = 'too many identical two-column splits (' . $twoColCount . 'x) — need layout variety';
        }

        // Check for section background variety (not all same background)
        $darkSections = (int) preg_match_all('/background(?:-color)?\s*:\s*(?:#0[0-9a-f]{5}|#1[0-9a-f]{5}|rgb\s*\(\s*[0-2]\d)/i', $html);
        $hasBgVariety = $darkSections >= 1 && $sections >= 5;
        if ($sections >= 5 && $darkSections === 0) {
            $score -= 8;
            $issues[] = 'no dark background sections — missing visual contrast';
        }

        // Check for heading font usage
        if (!str_contains($html, 'font-family') && !str_contains($html, '--font-heading')) {
            $score -= 5;
            $issues[] = 'no explicit font-family declarations';
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
        ];
    }

    /**
     * @param array<int,string> $issues
     */
    private function buildQualityGateRetryBrief(string $brief, string $title, array $issues): string
    {
        $parts = [];
        $parts[] = $brief;
        $parts[] = '';
        $parts[] = '⚠️ QUALITY GATE RETRY — Previous output was rejected for page: ' . $title;
        $parts[] = '';
        $parts[] = 'SPECIFIC FAILURES TO FIX:';
        foreach ($issues as $issue) {
            $parts[] = '- ❌ ' . $issue;
        }
        $parts[] = '';
        $parts[] = 'REQUIREMENTS FOR THIS RETRY:';
        $parts[] = '1. Start output with a <style> tag defining :root CSS custom properties using the design system colors.';
        $parts[] = '2. Include responsive @media queries in the <style> tag for mobile (768px) and small mobile (480px).';
        $parts[] = '2b. Every CSS rule must use a single class selector only — NO compound/descendant selectors like .parent .child { }.';
        $parts[] = '3. Build 7-10 sections using at least 5 DIFFERENT section layout types.';
        $parts[] = '4. Use the actual brand colors (var(--color-primary), var(--color-secondary)) — NOT generic grays or defaults.';
        $parts[] = '5. Include dark-background sections for visual contrast variety.';
        $parts[] = '6. Each section needs substantial copy — not one-line descriptions.';
        $parts[] = '7. Use the heading font for all h1/h2/h3 elements.';
        $parts[] = '8. This output must look like a premium $50k custom website — not a starter template.';

        return implode("\n", $parts);
    }

    /**
     * Build a shared nav HTML block.
     *
     * @param bool $forHomepage  true → use design-system style (overlay-centered etc.)
     *                           false → force compact modern top-bar for inner pages
     */
    private function buildCanonicalNavigationHtml(array $pages, array $designSystem = [], string $navLogoUrl = '', bool $forHomepage = true): string
    {
        if (count($pages) === 0) {
            return '';
        }

        $primaryColor = $this->safeHexColor((string) ($designSystem['primary_color'] ?? '#2563eb'), '#2563eb');
        $textColor = $this->safeHexColor((string) ($designSystem['text_color'] ?? '#1f2937'), '#1f2937');
        $secondaryColor = $this->safeHexColor((string) ($designSystem['secondary_color'] ?? '#111827'), '#111827');
        $headingFont = $this->safeFontStack((string) ($designSystem['heading_font'] ?? 'Georgia, serif'));
        $bodyFont = $this->safeFontStack((string) ($designSystem['body_font'] ?? 'system-ui, sans-serif'));
        $navStyleRaw = strtolower(trim((string) ($designSystem['nav_style'] ?? 'top-bar')));
        $layoutRaw = strtolower(trim((string) ($designSystem['layout_pattern'] ?? 'modern')));

        $navItems = [];
        $seen = [];
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

            $key = strtolower($href);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $navItems[] = [
                'title' => $title,
                'href' => $href,
            ];
        }

        if (count($navItems) === 0) {
            return '';
        }

        $homeItem = $navItems[0];
        foreach ($navItems as $item) {
            if ($item['href'] === '/') {
                $homeItem = $item;
                break;
            }
        }

        $brandLabel = trim((string) ($designSystem['brand_name'] ?? ''));
        if ($brandLabel === '') {
            $brandLabel = trim((string) $homeItem['title']);
        }
        if ($brandLabel === '') {
            $brandLabel = 'Brand';
        }

        $ctaText = trim((string) ($designSystem['primary_cta'] ?? 'Get Started'));
        if ($ctaText === '') {
            $ctaText = 'Get Started';
        }

        $navLogoUrl = trim($navLogoUrl);

        if (!$forHomepage) {
            // Inner-page nav: always a clean compact top-bar regardless of design system style.
            $styleVariant = 'modern';
        } else {
            $styleVariant = 'modern';
            if (str_contains($navStyleRaw, 'center')) {
                $styleVariant = 'centered';
            } elseif (str_contains($navStyleRaw, 'sidebar')) {
                $styleVariant = 'split';
            } elseif (str_contains($layoutRaw, 'minimal')) {
                $styleVariant = 'minimal';
            }

            if ($navLogoUrl !== '') {
                $styleVariant = 'overlay-centered';
            }
        }

        $links = '';
        foreach ($navItems as $item) {
            $title = e($item['title']);
            $href = e($item['href']);
            $links .= '<a class="ai-nav-link" href="' . $href . '">' . $title . '</a>';
        }

        $brand = '<a class="ai-nav-brand" href="/">' . e($brandLabel) . '</a>';
        $logo = $navLogoUrl !== ''
            ? '<a class="ai-nav-logo-wrap" href="/"><img class="ai-nav-logo" src="' . e($navLogoUrl) . '" alt="' . e($brandLabel) . ' logo" /></a>'
            : '';
        $cta = '<a class="ai-nav-cta" href="/contact">' . e($ctaText) . '</a>';

        $navInner = '<div class="ai-nav-inner ' . e('variant-' . $styleVariant) . '">';
        if ($styleVariant === 'overlay-centered') {
            $navInner .= '<div class="ai-nav-top">'
                . '<div class="ai-nav-top-left">&nbsp;</div>'
                . '<div class="ai-nav-top-center">' . ($logo !== '' ? $logo : $brand) . '</div>'
                . '<div class="ai-nav-top-right">' . $cta . '</div>'
                . '</div>'
                . '<div class="ai-nav-divider"></div>'
                . '<div class="ai-nav-links ai-nav-links-center">' . $links . '</div>';
        } elseif ($styleVariant === 'centered') {
            $navInner .= '<div class="ai-nav-center-wrap">' . $brand . '<div class="ai-nav-links">' . $links . '</div>' . $cta . '</div>';
        } elseif ($styleVariant === 'split') {
            $half = (int) floor(count($navItems) / 2);
            $leftLinks = '';
            $rightLinks = '';
            foreach ($navItems as $i => $item) {
                $one = '<a class="ai-nav-link" href="' . e($item['href']) . '">' . e($item['title']) . '</a>';
                if ($i < $half) {
                    $leftLinks .= $one;
                } else {
                    $rightLinks .= $one;
                }
            }
            $navInner .= '<div class="ai-nav-links ai-nav-left">' . $leftLinks . '</div>' . $brand . '<div class="ai-nav-links ai-nav-right">' . $rightLinks . $cta . '</div>';
        } else {
            $brandOrLogo = $logo !== '' ? $logo : $brand;
            $navInner .= $brandOrLogo . '<div class="ai-nav-links">' . $links . '</div>' . $cta;
        }
        $navInner .= '</div>';

        $css = '<style>'
            . ':root{--ai-nav-primary:' . e($primaryColor) . ';--ai-nav-text:' . e($textColor) . ';--ai-nav-ink:' . e($secondaryColor) . ';--ai-nav-head:' . e($headingFont) . ';--ai-nav-body:' . e($bodyFont) . ';}'
            . '.ai-shared-nav{position:fixed;top:0;left:0;right:0;z-index:1000;transition:background-color .3s ease,border-color .3s ease,box-shadow .3s ease,backdrop-filter .3s ease;}'
            . '.ai-shared-nav.nav-transparent{background:linear-gradient(to bottom,rgba(17,24,39,.56),rgba(17,24,39,.18));border-bottom:1px solid rgba(255,255,255,.12);backdrop-filter:blur(2px);}'
            . '.ai-shared-nav.nav-solid{background:#ffffff;border-bottom:1px solid rgba(15,23,42,.12);box-shadow:0 8px 30px rgba(15,23,42,.08);backdrop-filter:blur(8px);}'
            . '.ai-nav-inner{max-width:1200px;margin:0 auto;padding:14px 20px;display:flex;align-items:center;gap:18px;}'
            . '.ai-nav-brand{font-family:var(--ai-nav-head);font-size:1.15rem;font-weight:700;text-decoration:none;letter-spacing:.02em;transition:color .3s ease;}'
            . '.ai-shared-nav.nav-transparent .ai-nav-brand{color:#ffffff;}'
            . '.ai-shared-nav.nav-solid .ai-nav-brand{color:#111827;}'
            . '.ai-nav-logo-wrap{display:inline-flex;align-items:center;justify-content:center;text-decoration:none;}'
            . '.ai-nav-logo{max-height:78px;max-width:300px;width:auto;height:auto;display:block;transition:filter .3s ease;}'
            . '.ai-shared-nav.nav-transparent .ai-nav-logo{filter:brightness(10);}'
            . '.ai-shared-nav.nav-solid .ai-nav-logo{filter:none;}'
            . '.ai-nav-links{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}'
            . '.ai-nav-link{font-family:var(--ai-nav-body);font-size:.95rem;font-weight:600;text-decoration:none;padding:8px 11px;border-radius:999px;transition:background-color .2s ease,color .3s ease;}'
            . '.ai-shared-nav.nav-transparent .ai-nav-link{color:rgba(255,255,255,.9);}'
            . '.ai-shared-nav.nav-solid .ai-nav-link{color:#374151;}'
            . '.ai-shared-nav.nav-transparent .ai-nav-link:hover{background:rgba(255,255,255,.15);color:#fff;}'
            . '.ai-shared-nav.nav-solid .ai-nav-link:hover{background:rgba(0,0,0,.06);color:#111827;}'
            . '.ai-shared-nav.nav-transparent .ai-nav-link.is-active{background:rgba(255,255,255,.2);color:#fff;}'
            . '.ai-shared-nav.nav-solid .ai-nav-link.is-active{background:var(--ai-nav-primary);color:#fff;}'
            . '.ai-nav-cta{margin-left:auto;font-family:var(--ai-nav-body);text-decoration:none;font-size:.88rem;font-weight:700;color:#fff;background:var(--ai-nav-primary);padding:9px 14px;border-radius:999px;transition:transform .2s ease,opacity .2s ease;}'
            . '.ai-nav-cta:hover{opacity:.92;transform:translateY(-1px);}'
            . '.ai-nav-inner.variant-centered{justify-content:center;}.ai-nav-center-wrap{display:flex;align-items:center;gap:14px;flex-wrap:wrap;justify-content:center;}'
            . '.ai-nav-inner.variant-split .ai-nav-left{margin-right:auto;}.ai-nav-inner.variant-split .ai-nav-right{margin-left:auto;}'
            . '.ai-nav-inner.variant-minimal .ai-nav-link{padding:6px 8px;border-radius:6px;}'
            . '.ai-nav-inner.variant-overlay-centered{display:block;padding:14px 20px 12px;}'
            . '.ai-nav-top{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:10px;}'
            . '.ai-nav-top-right{justify-self:end;}'
            . '.ai-nav-divider{height:1px;background:rgba(255,255,255,.32);margin:10px 0 8px;}'
            . '.ai-nav-links-center{justify-content:center;}'
            . '.ai-shared-nav.inner-page .ai-nav-inner.variant-overlay-centered{display:flex;align-items:center;gap:14px;padding:10px 20px;}'
            . '.ai-shared-nav.inner-page .ai-nav-top{display:flex;align-items:center;gap:10px;grid-template-columns:none;}'
            . '.ai-shared-nav.inner-page .ai-nav-top-left,.ai-shared-nav.inner-page .ai-nav-top-right,.ai-shared-nav.inner-page .ai-nav-divider{display:none;}'
            . '.ai-shared-nav.inner-page .ai-nav-links-center{margin-left:10px;justify-content:flex-start;}'
            . '.ai-shared-nav.inner-page .ai-nav-logo{max-height:60px;}'
            . '@media (max-width: 900px){.ai-nav-inner{padding:10px 12px;gap:10px;}.ai-nav-brand{font-size:1rem;}.ai-nav-link{font-size:.86rem;padding:6px 8px;}.ai-nav-cta{display:none;}.ai-nav-logo{max-height:60px;}}'
            . '@media (max-width: 640px){.ai-nav-links{width:100%;order:3;gap:4px;}.ai-nav-inner{align-items:flex-start;}}'
            . '</style>';

        $js = '<script>(function(){var nav=document.querySelector(".ai-shared-nav[data-ai-shared-nav=\"1\"]");if(!nav)return;var links=nav.querySelectorAll(".ai-nav-link");var path=(location.pathname||"/").replace(/\/$/,"")||"/";for(var i=0;i<links.length;i++){var href=(links[i].getAttribute("href")||"/").replace(/\/$/,"")||"/";if(href===path){links[i].classList.add("is-active");}}function sync(){var isHome=path==="/";var h=nav.offsetHeight||72;if(!isHome){nav.classList.add("inner-page");nav.classList.add("nav-solid");nav.classList.remove("nav-transparent");var hero=document.querySelector("section:first-of-type");if(hero){hero.style.paddingTop=Math.max(parseInt(getComputedStyle(hero).paddingTop)||0,h)+"px";}}else{nav.classList.remove("inner-page");if(window.scrollY>16){nav.classList.add("nav-solid");nav.classList.remove("nav-transparent");}else{nav.classList.add("nav-transparent");nav.classList.remove("nav-solid");}}}window.addEventListener("scroll",sync,{passive:true});window.addEventListener("resize",sync,{passive:true});sync();})();</script>';

        return $css
            . '<nav class="ai-shared-nav nav-transparent" data-ai-shared-nav="1">'
            . $navInner
            . '</nav>'
            . $js;
    }

    private function safeFontStack(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'system-ui, sans-serif';
        }

        // Allow readable font stacks while stripping obvious dangerous characters.
        $value = preg_replace('/[^a-zA-Z0-9\-\s,\"\'\.]/', '', $value) ?? $value;
        if (trim($value) === '') {
            return 'system-ui, sans-serif';
        }

        return $value;
    }

    /**
     * Ensure each page uses exactly one canonical nav at the top (embed mode).
     * In extract mode use stripNavigationAndFooter() instead.
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

        $css = '<style>' . "\n" . $this->buildRevealCss() . "\n" . '</style>';
        $js  = '<script>' . "\n" . $this->buildRevealJs() . "\n" . '</script>';

        return $canonicalNavHtml . "\n" . $css . "\n" . $js . "\n" . ltrim($body);
    }

    /**
     * Strip nav and footer from a page body without injecting anything.
     * Used in extract_layout_assets mode so those elements go into LayoutBlocks instead.
     */
    private function stripNavigationAndFooter(string $bodyHtml): string
    {
        $body = trim($bodyHtml);

        // Only strip a <nav> element if it does NOT contain <section> elements.
        // If sections are inside the nav, it is likely page content (not just navigation)
        // and stripping it would wipe the entire page body.
        $body = preg_replace_callback('/<nav\b[^>]*>.*?<\/nav>/is', function (array $m): string {
            // Keep the nav if it wraps section content — it's not a standalone nav bar.
            if (stripos($m[0], '<section') !== false) {
                return $m[0];
            }
            return '';
        }, $body, 1) ?? $body;

        $body = preg_replace_callback('/<footer\b[^>]*>.*?<\/footer>/is', function (array $m): string {
            if (stripos($m[0], '<section') !== false) {
                return $m[0];
            }
            return '';
        }, $body, 1) ?? $body;

        return ltrim($body);
    }

    /**
     * Build a canonical footer block for all cloned pages.
     */
    private function buildCanonicalFooterHtml(array $pages, array $designSystem = []): string
    {
        $secondaryColor = $this->safeHexColor((string) ($designSystem['secondary_color'] ?? '#111827'), '#111827');
        $primaryColor   = $this->safeHexColor((string) ($designSystem['primary_color'] ?? '#2563eb'), '#2563eb');
        $mutedColor     = '#9ca3af';
        $bodyFont       = $this->safeFontStack((string) ($designSystem['body_font'] ?? 'system-ui, sans-serif'));
        $brandName      = trim((string) ($designSystem['brand_name'] ?? ''));

        if ($brandName === '') {
            foreach ($pages as $page) {
                if (!is_array($page)) continue;
                if ($page['is_homepage'] ?? false) {
                    $brandName = trim((string) ($page['title'] ?? ''));
                    break;
                }
            }
        }
        if ($brandName === '') $brandName = 'Brand';

        $links = '';
        $seen  = [];
        foreach ($pages as $page) {
            if (!is_array($page)) continue;
            $title = trim((string) ($page['title'] ?? ''));
            if ($title === '') continue;
            $isHomepage = (bool) ($page['is_homepage'] ?? false);
            $slug       = $this->normaliseSlug((string) ($page['slug'] ?? Str::slug($title)));
            $href       = $isHomepage ? '/' : '/' . ltrim($slug, '/');
            if (isset($seen[$href])) continue;
            $seen[$href] = true;
            $links .= '<a href="' . e($href) . '" style="color:' . e($mutedColor) . ';text-decoration:none;font-size:.85rem;white-space:nowrap;transition:color .2s;" onmouseover="this.style.color=\'' . e($primaryColor) . '\'" onmouseout="this.style.color=\'' . e($mutedColor) . '\'">' . e($title) . '</a>';
        }

        $year = date('Y');

        return '<footer style="background:' . e($secondaryColor) . ';padding:48px 24px 32px;font-family:' . e($bodyFont) . ';">'
            . '<div style="max-width:1200px;margin:0 auto;">'
            . '<div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:24px;margin-bottom:32px;">'
            . '<div style="font-size:1.1rem;font-weight:700;color:#fff;">' . e($brandName) . '</div>'
            . '<nav style="display:flex;flex-wrap:wrap;gap:20px;align-items:center;">' . $links . '</nav>'
            . '</div>'
            . '<div style="border-top:1px solid rgba(255,255,255,.12);padding-top:24px;">'
            . '<p style="color:' . e($mutedColor) . ';font-size:.8rem;margin:0;">&copy; ' . $year . ' ' . e($brandName) . '. All rights reserved.</p>'
            . '</div>'
            . '</div>'
            . '</footer>';
    }

    /**
     * Extract all <style> blocks from an HTML string and return them separately.
     * Returns ['html' => $htmlWithoutStyles, 'css' => $extractedCss].
     */
    private function extractAndStripStyleBlocks(string $html): array
    {
        $css = '';
        $cleaned = preg_replace_callback(
            '/<style[^>]*>(.*?)<\/style>/is',
            function (array $m) use (&$css): string {
                $css .= "\n" . trim($m[1]);
                return '';
            },
            $html
        );

        return [
            'html' => $cleaned ?? $html,
            'css'  => trim($css),
        ];
    }

    /**
     * Merge CSS blocks collected from all pages, removing exact duplicate rule blocks.
     * Because the AI tends to generate the same base CSS on every page, simple
     * block-level deduplication eliminates the vast majority of repetition.
     */
    private function deduplicateAndMergeCss(array $blocks): string
    {
        $unique = array_values(array_unique(
            array_filter(array_map('trim', $blocks))
        ));

        return implode("\n\n", $unique);
    }

    /**
     * CSS for scroll-reveal animations — used both in embed mode (via applyCanonicalNavigation)
     * and returned separately in extract_layout_assets mode for a CustomSnippet.
     */
    private function buildRevealCss(): string
    {
        return <<<'CSS'
    /* Scroll-reveal animation system */
    .reveal {
        opacity: 0;
        transform: translateY(30px);
        transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .reveal.visible {
        opacity: 1;
        transform: translateY(0);
    }
    .reveal-delay-1 { transition-delay: 0.1s; }
    .reveal-delay-2 { transition-delay: 0.2s; }
    .reveal-delay-3 { transition-delay: 0.3s; }

    /* Global section styling */
    section {
        scroll-margin-top: 80px;
    }

    /* Container max-width for readability */
    section > div:first-child {
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Card hover effects */
    [style*="box-shadow"] {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    [style*="box-shadow"]:hover {
        box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15) !important;
        transform: translateY(-4px);
    }

    /* Link/button hover effects */
    a[style*="padding"][style*="background"] {
        transition: all 0.3s ease;
    }
    a[style*="padding"][style*="background"]:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }

    /* Image responsiveness */
    img {
        max-width: 100% !important;
        height: auto !important;
        display: block;
    }

    /* Blockquote styling */
    blockquote {
        margin: 32px 0;
        padding: 24px 32px;
        border-left: 4px solid currentColor;
        background: rgba(255, 255, 255, 0.5);
        opacity: 0.85;
        font-style: italic;
        border-radius: 4px;
    }

    /* Responsive design for mobile */
    @media (max-width: 768px) {
        section {
            padding: 40px 16px;
        }
        h1 { font-size: 32px; }
        h2 { font-size: 24px; }
        [style*="grid-template-columns"],
        [style*="grid"] {
            grid-template-columns: 1fr !important;
        }
    }
CSS;
    }

    /**
     * JS for IntersectionObserver scroll-reveal — used both in embed mode and as a CustomSnippet
     * in extract_layout_assets mode.
     */
    private function buildRevealJs(): string
    {
        return <<<'JS'
(function(){
    var obs = new IntersectionObserver(function(entries){
        entries.forEach(function(e){
            if(e.isIntersecting){e.target.classList.add('visible');obs.unobserve(e.target);}
        });
    },{threshold:0.12});
    function init(){document.querySelectorAll('.reveal').forEach(function(el){obs.observe(el);});}
    if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',init);}else{init();}
    // Also observe dynamically added .reveal elements
    new MutationObserver(function(muts){
        muts.forEach(function(m){m.addedNodes.forEach(function(n){
            if(n.nodeType===1){if(n.classList&&n.classList.contains('reveal'))obs.observe(n);n.querySelectorAll&&n.querySelectorAll('.reveal').forEach(function(el){obs.observe(el);});}
        });});
    }).observe(document.body,{childList:true,subtree:true});
})();
JS;
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

    /**
     * Append page-specific media URLs from analysis into the page brief so the LLM
     * uses the correct imported images for each page instead of generic references.
     *
     * @param array<string,array<int,string>> $pageMediaHints Keyed by slug/title → list of local media URLs
     */
    private function injectPageMediaHintsIntoBrief(string $brief, string $title, string $slug, array $pageMediaHints): string
    {
        $keys = [];
        $normSlug = strtolower(trim($slug, '/'));
        $normTitle = strtolower(trim($title));

        if ($normSlug !== '') {
            $keys[] = $normSlug;
            $keys[] = basename($normSlug);
        }
        if ($normTitle !== '') {
            $keys[] = $normTitle;
            // Normalize: "smart lighting" → "smart-lighting" for matching against URL slugs
            $keys[] = str_replace(' ', '-', $normTitle);
        }

        $pool = [];
        foreach ($keys as $key) {
            if ($key === '') {
                continue;
            }
            if (isset($pageMediaHints[$key]) && is_array($pageMediaHints[$key])) {
                $pool = array_values(array_filter($pageMediaHints[$key], fn ($v) => is_string($v) && trim($v) !== ''));
                if (!empty($pool)) {
                    break;
                }
            }
        }

        // Fuzzy match: normalize both sides (hyphens ↔ spaces) before substring comparison
        if (empty($pool)) {
            foreach ($pageMediaHints as $k => $urls) {
                if (!is_string($k) || !is_array($urls)) {
                    continue;
                }

                $kNorm = str_replace('-', ' ', strtolower(trim($k)));
                if ($kNorm === '') {
                    continue;
                }

                $slugNorm = $normSlug !== '' ? str_replace('-', ' ', $normSlug) : '';
                $titleNorm = str_replace('-', ' ', $normTitle);

                if (($slugNorm !== '' && (str_contains($slugNorm, $kNorm) || str_contains($kNorm, $slugNorm)))
                    || ($titleNorm !== '' && (str_contains($titleNorm, $kNorm) || str_contains($kNorm, $titleNorm)))) {
                    $pool = array_values(array_filter($urls, fn ($v) => is_string($v) && trim($v) !== ''));
                    if (!empty($pool)) {
                        break;
                    }
                }
            }
        }

        if (empty($pool)) {
            return $brief;
        }

        $pool = array_slice(array_values(array_unique($pool)), 0, 6);

        $brief .= "\n\nPage-specific media URLs (use these EXACT URLs as src values for this page):\n";
        foreach ($pool as $idx => $url) {
            $brief .= '- Image ' . ($idx + 1) . ': ' . $url . "\n";
        }
        $brief .= "- Use at least two of these URLs in this page's <img> src attributes and background-image URLs.\n";
        $brief .= '- Do NOT invent custom image URLs — only use the URLs listed above.';

        return $brief;
    }

    /**
     * Add robust fallbacks to all image tags so pages never show broken image placeholders.
     *
     * - If src is missing/empty, we set a generated SVG placeholder immediately.
     * - If src later fails to load, onerror swaps to the same placeholder.
     */
    private function applyImageFallbacks(string $html, array $context = []): string
    {
        if (stripos($html, '<img') === false) {
            return $html;
        }

        $fallbackSrc = trim((string) ($context['fallback_image_url'] ?? ''));
        if ($fallbackSrc === '') {
            $fallbackSrc = $this->buildImageFallbackDataUri($context);
        }
        $onErrorJs = "this.onerror=null;this.src='" . $fallbackSrc . "';if(!this.alt){this.alt='Image unavailable';}";

        return preg_replace_callback('/<img\b[^>]*>/i', function (array $matches) use ($fallbackSrc, $onErrorJs) {
            $tag = $matches[0];

            $src = $this->extractAttributeValue($tag, 'src');
            if ($src === null || trim($src) === '') {
                $tag = $this->setOrReplaceAttribute($tag, 'src', $fallbackSrc);
            }

            if ($this->extractAttributeValue($tag, 'alt') === null) {
                $tag = $this->setOrReplaceAttribute($tag, 'alt', 'Image');
            }

            $tag = $this->setOrReplaceAttribute($tag, 'onerror', $onErrorJs);

            return $tag;
        }, $html) ?? $html;
    }

    /**
     * Build a theme-aware SVG placeholder encoded as a data URI.
     */
    private function buildImageFallbackDataUri(array $context = []): string
    {
        $design = (array) ($context['design_system'] ?? []);

        $primary = $this->safeHexColor((string) ($design['primary_color'] ?? '#2563eb'), '#2563eb');
        $secondary = $this->safeHexColor((string) ($design['secondary_color'] ?? '#0f172a'), '#0f172a');

        $brand = $this->brandFromContext($context);
        $title = $brand !== '' ? $brand : 'Website';

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800" role="img" aria-label="Image placeholder">'
            . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
            . '<stop offset="0%" stop-color="' . $primary . '"/>'
            . '<stop offset="100%" stop-color="' . $secondary . '"/>'
            . '</linearGradient></defs>'
            . '<rect width="1200" height="800" fill="url(#g)"/>'
            . '<rect x="140" y="120" width="920" height="560" rx="24" fill="rgba(255,255,255,0.14)"/>'
            . '<circle cx="420" cy="360" r="46" fill="rgba(255,255,255,0.85)"/>'
            . '<path d="M290 540c70-90 130-120 190-90 36 18 66 19 95-6 26-23 56-33 90-31 61 4 111 42 164 127H290z" fill="rgba(255,255,255,0.85)"/>'
            . '<text x="600" y="670" text-anchor="middle" font-family="Arial, sans-serif" font-size="42" fill="#ffffff">'
            . e($title)
            . '</text>'
            . '<text x="600" y="715" text-anchor="middle" font-family="Arial, sans-serif" font-size="24" fill="rgba(255,255,255,0.92)">Image unavailable - generated placeholder</text>'
            . '</svg>';

        return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
    }

    private function extractAttributeValue(string $tag, string $attribute): ?string
    {
        $pattern = "/\\b" . preg_quote($attribute, '/') . "\\s*=\\s*(\"([^\"]*)\"|'([^']*)'|([^\\s>]+))/i";
        if (!preg_match($pattern, $tag, $m)) {
            return null;
        }

        return (string) ($m[2] ?? $m[3] ?? $m[4] ?? '');
    }

    private function setOrReplaceAttribute(string $tag, string $attribute, string $value): string
    {
        $escaped = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $replacement = $attribute . '="' . $escaped . '"';
        $pattern = "/\\b" . preg_quote($attribute, '/') . "\\s*=\\s*(\"[^\"]*\"|'[^']*'|[^\\s>]+)/i";

        if (preg_match($pattern, $tag) === 1) {
            return preg_replace($pattern, $replacement, $tag, 1) ?? $tag;
        }

        return preg_replace('/\/>$/', ' ' . $replacement . ' />', $tag, 1)
            ?? preg_replace('/>$/', ' ' . $replacement . '>', $tag, 1)
            ?? $tag;
    }

    private function safeHexColor(string $value, string $fallback): string
    {
        $value = trim($value);
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) === 1) {
            return strtolower($value);
        }

        return $fallback;
    }

    private function brandFromContext(array $context): string
    {
        $design = (array) ($context['design_system'] ?? []);
        $candidate = trim((string) ($design['brand_name'] ?? ''));
        if ($candidate !== '') {
            return Str::limit($candidate, 40, '');
        }

        $sourceUrl = trim((string) ($context['source_url'] ?? ''));
        if ($sourceUrl !== '') {
            $host = parse_url($sourceUrl, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                $host = preg_replace('/^www\./i', '', $host) ?? $host;
                return Str::limit($host, 40, '');
            }
        }

        return '';
    }
}
