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
        $lines[] = '- Exactly one page must have is_homepage=true.';
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

    private function validateBlueprint(array $bp, string $preset): void
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

        $homepageCount = 0;
        $homepagePageIndex = 0;
        
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
            
            // Auto-fix missing is_homepage field
            if (!array_key_exists('is_homepage', $p)) {
                $pages[$idx]['is_homepage'] = false;  // Default to false, will set first to true below
            }
            
            if ((bool) $pages[$idx]['is_homepage']) {
                $homepageCount++;
                $homepagePageIndex = $idx;
            }
        }
        
        // If no homepage was marked, mark the first page as homepage
        if ($homepageCount === 0) {
            $pages[0]['is_homepage'] = true;
        } elseif ($homepageCount > 1) {
            // If multiple marked as homepage, only keep first one
            $found = false;
            foreach ($pages as $idx => $p) {
                if ((bool) $p['is_homepage']) {
                    if (!$found) {
                        $found = true;
                    } else {
                        $pages[$idx]['is_homepage'] = false;
                    }
                }
            }
        }
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
                'brief' => 'string (plain text brief including design system reference)',
            ]],
        ];

        $lines = [];
        $lines[] = 'Clone and improve a website with a unified design system.';
        $lines[] = '';
        $lines[] = 'Source site: ' . $siteTitle;
        $lines[] = 'Page count: ' . $pageCount;
        $lines[] = 'Navigation: ' . $navItems;
        $lines[] = '';
        $lines[] = 'Unified Design System:';
        $lines[] = '- Primary Color: ' . $primaryColor;
        $lines[] = '- Layout Pattern: ' . $layout;
        $lines[] = '- Navigation Style: ' . $navStyle;
        $lines[] = '- Heading Font: ' . ($designSystem['heading_font'] ?? 'Segoe UI');
        $lines[] = '- Body Font: ' . ($designSystem['body_font'] ?? 'Segoe UI');
        $lines[] = '';
        $lines[] = 'Original Content Sample:' . $pageExamples;

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
        $lines[] = '- Exactly one page must have is_homepage=true (usually the Home page).';
        $lines[] = '- All other pages must have is_homepage=false.';
        $lines[] = '- Maintain the original site structure and navigation.';
        $lines[] = '- Preserve all page titles and sections from the original.';
        $lines[] = '- Each brief must describe content in context of the design system.';
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
}
