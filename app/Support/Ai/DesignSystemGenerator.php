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
            'Design system must include: primary_color, secondary_color, accent_color, background_color, text_color, heading_font, body_font, layout_pattern, nav_style, cta_style.',
            'Colors must be hex codes (e.g., #3498db).',
            'Fonts must be web-safe or Google Font names.',
            'Layout pattern should be one of: "minimalist", "classic", "modern", "bold".',
            'nav_style should be one of: "top-bar", "sidebar", "centered".',
            'cta_style should describe the button/link style.',
        ]);
    }

    private function buildPrompt(array $siteAnalysis, string $modification): string
    {
        $siteTitle = $siteAnalysis['title'] ?? 'Website';
        $existingColors = implode(', ', $siteAnalysis['colors'] ?? []) ?: 'not detected';
        $existingFonts = implode(', ', $siteAnalysis['fonts'] ?? []) ?: 'not detected';
        $existingNav = implode(', ', array_slice($siteAnalysis['navigation'] ?? [], 0, 5));

        $lines = [
            'Create a unified, professional design system for a website.',
            '',
            'Original site: ' . $siteTitle,
            'Original colors detected: ' . $existingColors,
            'Original fonts detected: ' . $existingFonts,
            'Navigation items: ' . $existingNav,
        ];

        if (trim($modification) !== '') {
            $lines[] = 'Modification request: ' . $modification;
        }

        $lines[] = '';
        $lines[] = 'Requirements:';
        $lines[] = '- Design should feel cohesive and professional';
        $lines[] = '- Colors should work well together (primary, secondary, accent)';
        $lines[] = '- Fonts should be readable and modern';
        $lines[] = '- Layout pattern should support all page types (home, service, contact, etc.)';
        $lines[] = '- Navigation style should be clean and intuitive';
        $lines[] = '- CTA buttons should be prominent and encouraging';

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
        $lines[] = '  "layout_pattern": "minimalist|classic|modern|bold",';
        $lines[] = '  "nav_style": "top-bar|sidebar|centered",';
        $lines[] = '  "cta_style": "description"';
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
        if (!in_array($pattern, ['minimalist', 'classic', 'modern', 'bold'], true)) {
            $pattern = 'modern';
        }
        $design['layout_pattern'] = $pattern;

        // Validate nav style
        $navStyle = (string) ($design['nav_style'] ?? '');
        if (!in_array($navStyle, ['top-bar', 'sidebar', 'centered'], true)) {
            $navStyle = 'top-bar';
        }
        $design['nav_style'] = $navStyle;

        // Validate CTA style
        $ctaStyle = (string) ($design['cta_style'] ?? '');
        if (trim($ctaStyle) === '') {
            $ctaStyle = $defaults['cta_style'];
        }
        $design['cta_style'] = $ctaStyle;

        return $design;
    }

    private function isValidHexColor(string $color): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $color);
    }
}
