<?php

namespace App\Support\Ai;

use Illuminate\Support\Str;

class AiSiteBlueprintGenerator
{
    public function __construct(
        private readonly LlmClientInterface $llm,
    ) {}

    /**
     * Generate a site blueprint JSON (sitemap + per-page briefs + SEO).
     *
     * @param array{
     *   site_name:string,
     *   industry?:string,
     *   location?:string,
     *   audience?:string,
     *   tone?:string,
     *   primary_cta?:string,
     *   page_preset:'basic'|'business'|'full',
     *   notes?:string
     * } $input
     *
     * @return array{raw_json:string, blueprint:array, model?:string, meta?:array}
     */
    public function generate(array $input): array
    {
        $siteName = trim((string) ($input['site_name'] ?? ''));
        if ($siteName === '') {
            throw new \InvalidArgumentException('Site name is required.');
        }

        $preset = (string) ($input['page_preset'] ?? 'business');
        if (!in_array($preset, ['basic', 'business', 'full'], true)) {
            $preset = 'business';
        }

        $instructions = $this->instructions();
        $prompt = $this->prompt($input);

        $res = $this->llm->generateText($prompt, $instructions);
        $raw = trim((string) ($res['output_text'] ?? ($res['text'] ?? '')));
        if ($raw === '') {
            throw new \RuntimeException('AI returned no output.');
        }

        // The model sometimes wraps JSON in extra text. Extract the first JSON object.
        $json = $this->extractJsonObject($raw);
        $blueprint = $this->decodeJson($json);

        $this->validateBlueprint($blueprint, $preset);

        // Normalise slugs (non-destructive)
        if (isset($blueprint['pages']) && is_array($blueprint['pages'])) {
            $norm = [];
            foreach ($blueprint['pages'] as $p) {
                if (!is_array($p)) continue;
                $title = trim((string) ($p['title'] ?? ''));
                $slug = trim((string) ($p['slug'] ?? ''));
                if ($slug === '' && $title !== '') {
                    $slug = Str::slug($title);
                }
                $p['slug'] = $this->normaliseSlug($slug);
                $norm[] = $p;
            }
            $blueprint['pages'] = $norm;
        }

        return [
            'raw_json' => $json,
            'blueprint' => $blueprint,
            'model' => $res['model'] ?? null,
            'meta' => $res['meta'] ?? null,
        ];
    }

    private function instructions(): string
    {
        return implode("\n", [
            'Return ONLY valid JSON. No markdown. No backticks. No commentary.',
            'The JSON must match the schema exactly.',
            'Do not include any HTML in the JSON, only plain text briefs.',
            'Keep meta_description <= 160 characters.',
            'All slugs must be lowercase and URL-safe (a-z, 0-9, dashes, optional /).',
        ]);
    }

    private function prompt(array $input): string
    {
        $siteName = trim((string) ($input['site_name'] ?? ''));
        $industry = trim((string) ($input['industry'] ?? ''));
        $location = trim((string) ($input['location'] ?? ''));
        $audience = trim((string) ($input['audience'] ?? ''));
        $tone = trim((string) ($input['tone'] ?? 'clear, modern, confident'));
        $cta = trim((string) ($input['primary_cta'] ?? 'Get in touch'));
        $preset = (string) ($input['page_preset'] ?? 'business');
        $notes = trim((string) ($input['notes'] ?? ''));

        $presetDesc = match ($preset) {
            'basic' => 'Create 5-6 pages: Home, About, Services, Contact, Privacy, Terms (optional).',
            'full' => 'Create 10-14 pages: Home, About, Services overview, 3-5 service detail pages, Pricing, FAQ, Contact, Blog index, Privacy, Terms.',
            default => 'Create 7-9 pages: Home, About, Services overview, 2-3 service detail pages, FAQ, Contact, Privacy.',
        };

        $schema = [
            'site' => [
                'name' => 'string',
                'tagline' => 'string',
                'tone' => 'string',
                'primary_cta' => 'string',
                'nav' => ['array of page titles in order'],
            ],
            'pages' => [[
                'title' => 'string',
                'slug' => 'string (no leading slash)',
                'is_homepage' => 'boolean',
                'template' => 'string (use "blank" unless you have a reason)',
                'meta_title' => 'string',
                'meta_description' => 'string (<=160 chars)',
                'brief' => 'string (plain text brief for HTML generation)',
            ]],
        ];

        $lines = [];
        $lines[] = 'Build a CMS site blueprint as JSON.';
        $lines[] = 'Site name: ' . $siteName;
        if ($industry !== '') $lines[] = 'Industry: ' . $industry;
        if ($location !== '') $lines[] = 'Location: ' . $location;
        if ($audience !== '') $lines[] = 'Audience: ' . $audience;
        $lines[] = 'Tone: ' . ($tone !== '' ? $tone : 'clear, modern, confident');
        $lines[] = 'Primary CTA: ' . ($cta !== '' ? $cta : 'Get in touch');
        $lines[] = 'Page preset: ' . $preset;
        $lines[] = 'Preset guidance: ' . $presetDesc;
        if ($notes !== '') {
            $lines[] = 'Extra notes:';
            $lines[] = $notes;
        }
        $lines[] = '';
        $lines[] = 'Schema:';
        $lines[] = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $lines[] = '';
        $lines[] = 'Rules:';
        $lines[] = '⚠️  CRITICAL: EVERY page MUST have is_homepage field (true or false).';
        $lines[] = '- The FIRST page in the "pages" array MUST be the Home / Landing page with is_homepage=true.';
        $lines[] = '- All other pages must have is_homepage=false.';
        $lines[] = '- Provide a concise tagline.';
        $lines[] = '- Each brief must include an outline of sections (Hero, Benefits, Social proof, FAQ if relevant, CTA).';
        $lines[] = '- Service detail page briefs should focus on one service each.';
        $lines[] = '- Keep it realistic and business-ready.';
        $lines[] = '';
        $lines[] = 'Return ONLY valid JSON. No markdown. No backticks. Each page MUST include is_homepage field!';

        return implode("\n", $lines);
    }

    private function extractJsonObject(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') return '';

        // Check if it's HTML (error page or unexpected response)
        if (str_contains($raw, '<!doctype') || str_contains($raw, '<html') || str_contains($raw, '<head')) {
            \Log::error('AiSiteBlueprintGenerator: HTML response from AI', [
                'preview' => substr($raw, 0, 200),
            ]);
            throw new \RuntimeException('AI returned HTML instead of JSON. This usually means an API error occurred.');
        }

        // If it already looks like JSON, accept it.
        if (str_starts_with($raw, '{') && str_ends_with($raw, '}')) {
            return $raw;
        }

        if (preg_match('/\{.*\}/s', $raw, $m)) {
            return trim((string) $m[0]);
        }

        throw new \RuntimeException('Could not extract JSON from AI output. Got: ' . substr($raw, 0, 50));
    }

    private function decodeJson(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Blueprint JSON was invalid: ' . $e->getMessage());
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException('Blueprint JSON did not decode into an object.');
        }

        return $decoded;
    }

    private function validateBlueprint(array &$bp, string $preset): void
    {
        if (!isset($bp['site']) || !is_array($bp['site'])) {
            throw new \RuntimeException('Blueprint is missing "site".');
        }
        if (!isset($bp['pages']) || !is_array($bp['pages'])) {
            throw new \RuntimeException('Blueprint is missing "pages".');
        }

        $pages = $bp['pages'];
        if (count($pages) < 3) {
            throw new \RuntimeException('Blueprint has too few pages.');
        }
        if (count($pages) > 20) {
            throw new \RuntimeException('Blueprint has too many pages (max 20).');
        }

        foreach ($pages as $idx => $p) {
            if (!is_array($p)) {
                throw new \RuntimeException('Blueprint pages must be objects.');
            }
            if (trim((string) ($p['title'] ?? '')) === '') {
                throw new \RuntimeException('A page is missing a title.');
            }
            if (!array_key_exists('brief', $p) || trim((string) ($p['brief'] ?? '')) === '') {
                throw new \RuntimeException('A page is missing a brief.');
            }
            if (!array_key_exists('is_homepage', $p)) {
                $pages[$idx]['is_homepage'] = false;
            }
        }

        // Find which page the AI marked as homepage (first match wins).
        $homepageIdx = null;
        foreach ($pages as $idx => $p) {
            if ((bool) ($p['is_homepage'] ?? false)) {
                $homepageIdx = $idx;
                break;
            }
        }

        // If the homepage is not the first page, move it to position 0.
        // The landing/home page must always be first in the blueprint.
        if ($homepageIdx === null) {
            // No page was marked — default the first page.
            $pages[0]['is_homepage'] = true;
        } elseif ($homepageIdx !== 0) {
            // Homepage is buried — pull it to the front.
            $homePage = $pages[$homepageIdx];
            unset($pages[$homepageIdx]);
            array_unshift($pages, $homePage);
        }

        // Enforce: exactly the first page is the homepage, all others are not.
        foreach ($pages as $idx => $p) {
            $pages[$idx]['is_homepage'] = ($idx === 0);
        }

        $bp['pages'] = array_values($pages);
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

    /**
     * Generate a blueprint from cloned site analysis.
     *
     * @param array{
     *   url: string,
     *   title: string,
     *   pages: array,
     *   navigation: array<string>,
     *   colors: array<string>,
     *   fonts: array<string>,
     * } $siteAnalysis
     *
     * @param array $designSystem Design system from DesignSystemGenerator
     *
     * @param string $modification Requested modification
     *
     * @return array{raw_json:string, blueprint:array, model?:string, meta?:array}
     */
    public function generateForClone(array $siteAnalysis, array $designSystem, string $modification = ''): array
    {
        $siteName = trim((string) ($siteAnalysis['title'] ?? 'Website'));
        if ($siteName === '') {
            $siteName = 'Website';
        }

        $instructions = $this->instructionsForClone();
        $prompt = $this->promptForClone($siteAnalysis, $designSystem, $modification);

        try {
            $res = $this->llm->generateText($prompt, $instructions);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to generate blueprint: ' . $e->getMessage());
        }

        $raw = trim((string) ($res['output_text'] ?? ($res['text'] ?? '')));
        if ($raw === '') {
            throw new \RuntimeException('Blueprint generator returned empty response.');
        }

        // Extract JSON
        try {
            $json = $this->extractJsonObject($raw);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not extract JSON from blueprint response: ' . $e->getMessage());
        }

        try {
            $blueprint = $this->decodeJson($json);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to parse blueprint JSON: ' . $e->getMessage());
        }

        $this->validateBlueprint($blueprint, 'business');

        // Normalise slugs
        if (isset($blueprint['pages']) && is_array($blueprint['pages'])) {
            $norm = [];
            foreach ($blueprint['pages'] as $p) {
                if (!is_array($p)) continue;
                $title = trim((string) ($p['title'] ?? ''));
                $slug = trim((string) ($p['slug'] ?? ''));
                if ($slug === '' && $title !== '') {
                    $slug = Str::slug($title);
                }
                $p['slug'] = $this->normaliseSlug($slug);
                $norm[] = $p;
            }
            $blueprint['pages'] = $norm;
        }

        return [
            'raw_json' => $json,
            'blueprint' => $blueprint,
            'model' => $res['model'] ?? null,
            'meta' => $res['meta'] ?? null,
        ];
    }

    private function instructionsForClone(): string
    {
        return implode("\n", [
            'Return ONLY valid JSON. No markdown. No backticks. No commentary.',
            'The JSON must match the schema exactly.',
            'Do not include any HTML in the JSON, only plain text briefs.',
            'Keep meta_description <= 160 characters.',
            'All slugs must be lowercase and URL-safe (a-z, 0-9, dashes, optional /).',
            'Briefs should reference the design system for styling consistency.',
        ]);
    }

    private function promptForClone(array $siteAnalysis, array $designSystem, string $modification): string
    {
        $siteTitle = $siteAnalysis['title'] ?? 'Website';
        $pageCount = count($siteAnalysis['pages'] ?? []);
        $navItems = implode(', ', array_slice($siteAnalysis['navigation'] ?? [], 0, 8));
        $primaryColor = $designSystem['primary_color'] ?? '#3498db';
        $layout = $designSystem['layout_pattern'] ?? 'modern';
        $navStyle = $designSystem['nav_style'] ?? 'top-bar';

        $pageExamples = '';
        foreach (array_slice($siteAnalysis['pages'] ?? [], 0, 3) as $page) {
            $pageExamples .= "\n- " . ($page['title'] ?? 'Page') . ": " . mb_substr((string) ($page['content_sample'] ?? ''), 0, 100);
        }

        $pageImageContext = $this->buildPerPageImageContext($siteAnalysis);

        $imageInfo = '';
        if (!empty($siteAnalysis['images'])) {
            $imgs = $siteAnalysis['images'];
            if (!empty($imgs['logo'])) {
                $imageInfo .= "\nLogo: " . $imgs['logo'];
            }
            if (!empty($imgs['hero'])) {
                $imageInfo .= "\nHero images: " . implode(', ', array_slice($imgs['hero'], 0, 8));
            }
            if (!empty($imgs['content']) && count($imgs['content']) > 0) {
                $imageInfo .= "\nContent images available: " . count($imgs['content']) . " images";
            }
        }

        $schema = [
            'site' => [
                'name' => 'string',
                'tagline' => 'string',
                'tone' => 'string',
                'primary_cta' => 'string',
                'nav' => ['array of page titles in order'],
            ],
            'pages' => [[
                'title' => 'string',
                'slug' => 'string (no leading slash)',
                'is_homepage' => 'boolean',
                'template' => 'string (use "blank")',
                'meta_title' => 'string',
                'meta_description' => 'string (<=160 chars)',
                'brief' => 'string (MUST include: content, design system, AND specific image URLs to use)',
            ]],
        ];

        $lines = [];
        $lines[] = 'Clone and improve a website with a unified design system.';
        $lines[] = '';
        $lines[] = 'Source site: ' . $siteTitle;
        $lines[] = '🚨 CRITICAL REQUIREMENT: You MUST create EXACTLY ' . $pageCount . ' pages.';
        $lines[] = '🚨 DO NOT consolidate or merge pages. Each URL = One separate page in blueprint.';
        $lines[] = '🚨 Count verification: If you create ' . ($pageCount - 1) . ' or ' . ($pageCount + 1) . ' pages, your response is INVALID.';
        $lines[] = 'Navigation: ' . $navItems;
        $lines[] = '';
        $lines[] = '🎨 UNIFIED Design System (USE ON ALL PAGES):';
        $lines[] = '- Primary Color: ' . $primaryColor . ' ← USE THIS COLOR for ALL hero sections, CTAs, accents';
        $lines[] = '- DO NOT use different gradients or color schemes per page';
        $lines[] = '- ALL pages must have consistent visual identity';
        $lines[] = '- Layout Pattern: ' . $layout;
        $lines[] = '- Navigation Style: ' . $navStyle;
        $lines[] = '- Heading Font: ' . ($designSystem['heading_font'] ?? 'Segoe UI');
        $lines[] = '- Body Font: ' . ($designSystem['body_font'] ?? 'Segoe UI');
        $lines[] = '';
        $lines[] = 'Original Content Sample:' . $pageExamples;

        if ($imageInfo !== '') {
            $lines[] = '';
            $lines[] = '📸 AVAILABLE MEDIA ASSETS:' . $imageInfo;
            $lines[] = '- The shared site navigation is injected by the system. Do NOT place the logo as a large content image in page sections.';
            $lines[] = '- USE hero images for page hero sections (full-width backgrounds)';
            $lines[] = '- Use varied section-appropriate images; do NOT reuse the same hero image across every page unless explicitly a gallery page.';
            $lines[] = '- REFERENCE these image URLs in page briefs so they are included in generated HTML';
            $lines[] = '- Each page brief must indicate where each image is used and why it matches the section context.';
            $lines[] = '- Include hero image URLs in briefs: "Hero section with background image: [URL]"';
            $lines[] = '- The page generator will only use images that are explicitly referenced in the brief';
        }

        if ($pageImageContext !== '') {
            $lines[] = '';
            $lines[] = '📍 PAGE-SPECIFIC IMAGE POOLS (STRICT):';
            $lines[] = $pageImageContext;
            $lines[] = '- For each generated page, pick image URLs from the matching source page pool first.';
            $lines[] = '- Do NOT use unrelated pools (example: do not use blinds imagery on cinema pages).';
            $lines[] = '- Each non-policy page brief must include at least 2 explicit image URLs from its matching pool when available.';
            $lines[] = '- Prefer hero backgrounds from the same source page URL group.';
        }

        $lines[] = '';
        $lines[] = '🎨 ICONS: Use [icon] shortcodes instead of downloading icon images:';
        $lines[] = '- FontAwesome: [icon kind="fa" value="fa-solid fa-house" size="24" colour="' . $primaryColor . '"]';
        $lines[] = '- Lucide: [icon kind="lucide" value="home" size="24" colour="' . $primaryColor . '"]';
        $lines[] = '- Available FA icons: fa-check, fa-users, fa-shield, fa-star, fa-heart, fa-phone, fa-envelope, fa-map-marker, fa-clock, fa-building, fa-cog, fa-chart-line, fa-briefcase, fa-lightbulb, fa-trophy, fa-comments, fa-thumbs-up, fa-rocket';
        $lines[] = '- Use appropriate icons for services, features, benefits sections';
        $lines[] = '- Reference icon shortcodes in briefs: "Services section with 3 cards, each with an icon: [icon kind=\'fa\' value=\'fa-solid fa-shield\']..."';

        if (trim($modification) !== '') {
            $lines[] = '';
            $lines[] = 'Improvement: ' . $modification;
        }

        $lines[] = '';
        $lines[] = 'Schema:';
        $lines[] = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $lines[] = '';
        $lines[] = 'Rules:';
        $lines[] = '⚠️  CRITICAL: EVERY page MUST have is_homepage field (true or false).';
        $lines[] = '- The FIRST page in the "pages" array MUST be the Home / Landing page with is_homepage=true.';
        $lines[] = '- All other pages must have is_homepage=false.';
        $lines[] = '- Maintain the original site structure and navigation.';
        $lines[] = '- Preserve all page titles and sections from the original.';
        $lines[] = '';
        $lines[] = '🚨 BRIEF REQUIREMENTS (CRITICAL - All pages need detailed briefs):';
        $lines[] = '- EVERY page brief must be detailed (target 120-220 words), with specific content details';
        $lines[] = '- NEVER create short/empty briefs like "Contact page" or "Privacy policy page"';
        $lines[] = '- Each brief MUST include: content structure (6-8 sections), design system usage, domain-specific messaging, AND specific image URLs';
        $lines[] = '- For service pages (e.g., cinema rooms), image URLs MUST come from the matching service page pool where available.';
        $lines[] = '- Example GOOD brief: "Contact page with hero section using [hero image URL]. Contact form section with fields for name, email, message. Contact details section with phone [icon kind=\'fa\' value=\'fa-solid fa-phone\'], email [icon], address [icon] in a 3-column grid. Map section if possible. Use primary color ' . $primaryColor . ' for form submit button."';
        $lines[] = '- Example BAD brief: "Contact page" or "Privacy policy page" - TOO VAGUE';
        $lines[] = '- Explicitly avoid generic filler copy; include practical details, process steps, and outcomes relevant to the business domain.';
        $lines[] = '- For Privacy/Terms pages: describe sections like "Data Collection, Cookie Policy, User Rights, Contact Info" with structured content';
        $lines[] = '- Include suggestions for using primary_color, layout, and nav_style in visual hierarchy.';
        $lines[] = '- Briefs should highlight opportunities for visual richness: hero sections with backgrounds, service cards in grids, testimonials with blockquotes, image+text sections.';
        $lines[] = '- Suggest layout patterns: hero at top, then alternating image-left/text-right sections, card grids for services, testimonials with author info.';
        $lines[] = '- Mention color applications: use primary_color for CTAs and important elements, rotate section backgrounds for visual separation.';
        $lines[] = '- Include guidance on using spacing, shadows, and border-radius for professional polish.';
        $lines[] = '- For service/product pages: recommend card layouts with icons/emojis, descriptions, and CTAs.';
        $lines[] = '- For homepages: suggest multi-section layouts with hero, benefits, key services as cards, testimonials, and CTA.';
        $lines[] = '- Keep it professional and consistent.';
        $lines[] = '';
        $lines[] = 'Return ONLY valid JSON. No markdown. No backticks. Each page MUST include is_homepage field!';

        return implode("\n", $lines);
    }

    /**
     * Build a text block mapping each analyzed page to its discovered image URLs.
     * This is injected into the blueprint prompt so the AI assigns correct images per page.
     * Falls back to keyword-matched global assets when a page has no direct images.
     */
    private function buildPerPageImageContext(array $siteAnalysis): string
    {
        $pages = $siteAnalysis['pages'] ?? [];
        if (!is_array($pages) || empty($pages)) {
            return '';
        }

        $globalHero = array_values(array_filter((array) (($siteAnalysis['images']['hero'] ?? [])), fn ($v) => is_string($v) && trim($v) !== ''));
        $globalContent = array_values(array_filter((array) (($siteAnalysis['images']['content'] ?? [])), fn ($v) => is_string($v) && trim($v) !== ''));

        $lines = [];
        foreach (array_slice($pages, 0, 10) as $page) {
            if (!is_array($page)) {
                continue;
            }

            $title = trim((string) ($page['title'] ?? 'Page'));
            $url = trim((string) ($page['url'] ?? ''));
            $pool = array_values(array_filter((array) ($page['images'] ?? []), fn ($v) => is_string($v) && trim($v) !== ''));

            // If page crawler returned no direct images, build a targeted fallback pool from global assets.
            if (empty($pool)) {
                $keywords = $this->derivePageKeywords($title, $url);
                $global = array_slice(array_values(array_unique(array_merge($globalHero, $globalContent))), 0, 120);
                foreach ($global as $img) {
                    $hay = strtolower($img);
                    foreach ($keywords as $kw) {
                        if ($kw !== '' && str_contains($hay, $kw)) {
                            $pool[] = $img;
                            break;
                        }
                    }
                    if (count($pool) >= 6) {
                        break;
                    }
                }
            }

            if (empty($pool)) {
                continue;
            }

            $lines[] = '- ' . ($title !== '' ? $title : 'Page')
                . ($url !== '' ? (' (' . $url . ')') : '')
                . ': ' . implode(', ', array_slice($pool, 0, 6));
        }

        return implode("\n", $lines);
    }

    /**
     * Extract meaningful keywords from a page title and URL for image-pool fallback matching.
     * Filters out common stop words and short tokens.
     *
     * @return array<int,string>
     */
    private function derivePageKeywords(string $title, string $url): array
    {
        $source = strtolower(trim($title . ' ' . $url));
        $tokens = preg_split('/[^a-z0-9]+/', $source) ?: [];

        $ignore = [
            'home', 'page', 'www', 'https', 'http', 'com', 'co', 'za', 'smart', 'architects',
            'the', 'and', 'for', 'with', 'about', 'contact', 'privacy', 'terms', 'service', 'services',
        ];

        $keywords = [];
        foreach ($tokens as $t) {
            $t = trim((string) $t);
            if ($t === '' || strlen($t) < 4 || in_array($t, $ignore, true)) {
                continue;
            }
            if (!in_array($t, $keywords, true)) {
                $keywords[] = $t;
            }
        }

        return array_slice($keywords, 0, 8);
    }
}
