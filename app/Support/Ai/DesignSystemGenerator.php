<?php

namespace App\Support\Ai;

class DesignSystemGenerator
{
    public function __construct(
        private readonly LlmClientInterface $llm,
    ) {}

    /**
     * Generate a unified design system from site analysis.
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
     * @param string $modification E.g., "Make it more modern and professional"
     *
     * @return array{
     *   primary_color: string,
     *   secondary_color: string,
     *   accent_color: string,
     *   background_color: string,
     *   text_color: string,
     *   heading_font: string,
     *   body_font: string,
     *   layout_pattern: string,
     *   nav_style: string,
     *   cta_style: string,
     * }
     */
    public function generate(array $siteAnalysis, string $modification = ''): array
    {
        $input = $this->buildPrompt($siteAnalysis, $modification);
        $instructions = $this->buildInstructions();

        try {
            $res = $this->llm->generateText($input, $instructions);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to generate design system: ' . $e->getMessage());
        }

        $raw = (string) ($res['output_text'] ?? '');
        if (trim($raw) === '') {
            throw new \RuntimeException('Design system generator returned empty response.');
        }

        // Extract JSON from response
        try {
            $json = $this->extractJson($raw);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not extract design system JSON: ' . $e->getMessage());
        }

        try {
            $design = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Failed to parse design system JSON: ' . $e->getMessage());
        }

        if (!is_array($design)) {
            throw new \RuntimeException('Design system JSON did not decode into an object.');
        }

        // Validate and apply defaults
        return $this->normalizeDesignSystem($design);
    }

    private function buildInstructions(): string
    {
        return implode("\n", [
            'Return ONLY valid JSON. No markdown. No backticks. No commentary.',
            'Design system must capture the full visual language of the reference site — not just colors and fonts.',
            'Colors must be hex codes (e.g., #3498db).',
            'Fonts must be web-safe or Google Font names.',
            'Layout pattern should be one of: "minimalist", "classic", "modern", "bold", "editorial", "luxury".',
            'nav_style should be one of: "top-bar", "sidebar", "centered", "overlay", "split".',
            'Visual character fields must describe the reference site\'s actual design approach, not generic defaults.',
        ]);
    }

    private function buildPrompt(array $siteAnalysis, string $modification): string
    {
        $siteTitle = $siteAnalysis['title'] ?? 'Website';
        $existingColors = implode(', ', $siteAnalysis['colors'] ?? []) ?: 'not detected';
        $existingFonts = implode(', ', $siteAnalysis['fonts'] ?? []) ?: 'not detected';
        $existingNav = implode(', ', array_slice($siteAnalysis['navigation'] ?? [], 0, 5));

        // Build content context from analyzed pages
        $pageContext = '';
        foreach (array_slice($siteAnalysis['pages'] ?? [], 0, 4) as $page) {
            if (!is_array($page)) continue;
            $pTitle = trim((string) ($page['title'] ?? ''));
            $headings = implode(' | ', array_slice((array) ($page['headings'] ?? []), 0, 3));
            $desc = trim((string) ($page['description'] ?? ''));
            if ($pTitle !== '') {
                $pageContext .= "\n  - " . $pTitle;
                if ($headings !== '') $pageContext .= ' (sections: ' . $headings . ')';
                if ($desc !== '') $pageContext .= ' — ' . mb_substr($desc, 0, 80);
            }
        }

        $lines = [
            'Analyze this reference website and extract its FULL visual design language as a design system.',
            '',
            'Reference site: ' . $siteTitle,
            'Colors detected on site: ' . $existingColors,
            'Fonts detected on site: ' . $existingFonts,
            'Navigation items: ' . $existingNav,
        ];

        if ($pageContext !== '') {
            $lines[] = 'Page structure:' . $pageContext;
        }

        if (trim($modification) !== '') {
            $lines[] = '';
            $lines[] = 'Modification request: ' . $modification;
        }

        $lines[] = '';
        $lines[] = 'Requirements:';
        $lines[] = '- Capture the ACTUAL visual character of this reference site, not generic defaults.';
        $lines[] = '- Describe how the site uses space, contrast, and density — is it airy with lots of whitespace, or dense with content-heavy sections?';
        $lines[] = '- Identify the hero treatment: full-bleed image, video, gradient overlay, split layout, or text-only?';
        $lines[] = '- Describe the section rhythm: do sections alternate backgrounds? Use image-text pairs? Feature card grids?';
        $lines[] = '- Capture the mood/tone: luxury, corporate, playful, technical, editorial, warm, clinical?';
        $lines[] = '- Describe the typography scale: large dramatic headings or understated? How much contrast between h1/h2/body?';
        $lines[] = '- Describe the spacing approach: generous padding between sections or compact information-dense layout?';
        $lines[] = '- Capture the contrast philosophy: high-contrast dark/light sections or low-contrast subtle tones?';
        $lines[] = '- Note the dominant colors from the detected palette and which role they play (primary accent, backgrounds, text).';
        $lines[] = '- Identify the CTA treatment: rounded/square buttons, ghost/filled, color scheme, positioning.';

        if (trim($modification) !== '') {
            $lines[] = '';
            $lines[] = 'Apply this modification throughout: ' . $modification;
        }

        $lines[] = '';
        $lines[] = 'Return JSON with this exact structure:';
        $lines[] = '{';
        $lines[] = '  "primary_color": "#hex",';
        $lines[] = '  "secondary_color": "#hex",';
        $lines[] = '  "accent_color": "#hex",';
        $lines[] = '  "background_color": "#hex",';
        $lines[] = '  "text_color": "#hex",';
        $lines[] = '  "heading_font": "font name",';
        $lines[] = '  "body_font": "font name",';
        $lines[] = '  "layout_pattern": "minimalist|classic|modern|bold|editorial|luxury",';
        $lines[] = '  "nav_style": "top-bar|sidebar|centered|overlay|split",';
        $lines[] = '  "cta_style": "describe button treatment: shape, fill, color, hover behavior",';
        $lines[] = '  "hero_treatment": "describe the hero section approach: full-bleed image with overlay, split image-text, gradient background, video background, text-only minimal, etc.",';
        $lines[] = '  "section_rhythm": "describe how sections flow: alternating light/dark backgrounds, image-text pairs, card grids, full-width breaks, etc.",';
        $lines[] = '  "spacing_density": "airy|balanced|dense — how much whitespace between and within sections",';
        $lines[] = '  "visual_mood": "1-3 word mood: e.g. luxury-warm, corporate-clean, bold-technical, editorial-minimal",';
        $lines[] = '  "contrast_approach": "describe the contrast strategy: high-contrast dark/light alternation, low-contrast subtle tones, dramatic hero with muted body, etc.",';
        $lines[] = '  "typography_scale": "describe heading sizes and hierarchy: dramatic large h1 (60px+), moderate (40px), understated (32px), and body text relationship",';
        $lines[] = '  "section_backgrounds": "describe the background pattern: solid white, alternating white/cream, dark sections, gradient accents, image backgrounds, etc.",';
        $lines[] = '  "border_radius": "none|subtle (4-6px)|rounded (8-12px)|pill (50px) — what radius do cards, buttons, images use?",';
        $lines[] = '  "shadow_depth": "none|subtle|medium|dramatic — how much shadow/elevation is used on cards and elements?",';
        $lines[] = '  "brand_name": "the business/brand name from the site title"';
        $lines[] = '}';

        return implode("\n", $lines);
    }

    private function extractJson(string $raw): string
    {
        $raw = trim($raw);

        // Check if it's HTML (error page or unexpected response)
        if (str_contains($raw, '<!doctype') || str_contains($raw, '<html') || str_contains($raw, '<head')) {
            \Log::error('DesignSystemGenerator: HTML response from AI', [
                'preview' => substr($raw, 0, 200),
            ]);
            throw new \RuntimeException('AI returned HTML instead of JSON. This usually means an API error occurred.');
        }

        // If it already looks like JSON, accept it
        if (str_starts_with($raw, '{') && str_ends_with($raw, '}')) {
            return $raw;
        }

        // Try to extract JSON object
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            return trim((string) $m[0]);
        }

        throw new \RuntimeException('Could not extract JSON from design system response. Got: ' . substr($raw, 0, 50));
    }

    private function normalizeDesignSystem(array $design): array
    {
        $defaults = [
            'primary_color' => '#3498db',
            'secondary_color' => '#2c3e50',
            'accent_color' => '#e74c3c',
            'background_color' => '#ffffff',
            'text_color' => '#333333',
            'heading_font' => 'Segoe UI, Helvetica, Arial, sans-serif',
            'body_font' => 'Segoe UI, Helvetica, Arial, sans-serif',
            'layout_pattern' => 'modern',
            'nav_style' => 'top-bar',
            'cta_style' => 'Prominent rounded button with primary color',
            'hero_treatment' => 'Full-bleed image background with dark overlay and centered white text',
            'section_rhythm' => 'Alternating white and light-gray section backgrounds with image-text pairs',
            'spacing_density' => 'balanced',
            'visual_mood' => 'modern-professional',
            'contrast_approach' => 'Medium contrast with alternating light/dark sections',
            'typography_scale' => 'Moderate heading scale with 40-48px h1, 28-32px h2, 16px body',
            'section_backgrounds' => 'Alternating white and light gray',
            'border_radius' => 'rounded (8-12px)',
            'shadow_depth' => 'subtle',
            'brand_name' => '',
        ];

        // Validate colors
        foreach (['primary_color', 'secondary_color', 'accent_color', 'background_color', 'text_color'] as $key) {
            $value = (string) ($design[$key] ?? $defaults[$key]);
            if (!$this->isValidHexColor($value)) {
                $value = $defaults[$key];
            }
            $design[$key] = $value;
        }

        // Validate fonts
        foreach (['heading_font', 'body_font'] as $key) {
            $value = (string) ($design[$key] ?? $defaults[$key]);
            if (trim($value) === '') {
                $value = $defaults[$key];
            }
            $design[$key] = $value;
        }

        // Validate patterns
        $pattern = (string) ($design['layout_pattern'] ?? '');
        if (!in_array($pattern, ['minimalist', 'classic', 'modern', 'bold', 'editorial', 'luxury'], true)) {
            $pattern = 'modern';
        }
        $design['layout_pattern'] = $pattern;

        // Validate nav style
        $navStyle = (string) ($design['nav_style'] ?? '');
        if (!in_array($navStyle, ['top-bar', 'sidebar', 'centered', 'overlay', 'split'], true)) {
            $navStyle = 'top-bar';
        }
        $design['nav_style'] = $navStyle;

        // Validate CTA style
        $ctaStyle = (string) ($design['cta_style'] ?? '');
        if (trim($ctaStyle) === '') {
            $ctaStyle = $defaults['cta_style'];
        }
        $design['cta_style'] = $ctaStyle;

        // Validate visual character fields (text descriptions, use defaults if empty)
        foreach (['hero_treatment', 'section_rhythm', 'visual_mood', 'contrast_approach', 'typography_scale', 'section_backgrounds', 'brand_name'] as $key) {
            $value = trim((string) ($design[$key] ?? ''));
            if ($value === '') {
                $value = (string) ($defaults[$key] ?? '');
            }
            $design[$key] = $value;
        }

        // Validate spacing density
        $density = strtolower(trim((string) ($design['spacing_density'] ?? '')));
        if (!in_array($density, ['airy', 'balanced', 'dense'], true)) {
            $density = 'balanced';
        }
        $design['spacing_density'] = $density;

        // Validate border radius
        $radius = strtolower(trim((string) ($design['border_radius'] ?? '')));
        if ($radius === '') {
            $radius = $defaults['border_radius'];
        }
        $design['border_radius'] = $radius;

        // Validate shadow depth
        $shadow = strtolower(trim((string) ($design['shadow_depth'] ?? '')));
        if ($shadow === '') {
            $shadow = $defaults['shadow_depth'];
        }
        $design['shadow_depth'] = $shadow;

        return $design;
    }

    private function isValidHexColor(string $color): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $color);
    }
}
