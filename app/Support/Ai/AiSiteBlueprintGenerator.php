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
        $lines[] = '- Exactly one page must have is_homepage=true.';
        $lines[] = '- Provide a concise tagline.';
        $lines[] = '- Each brief must include an outline of sections (Hero, Benefits, Social proof, FAQ if relevant, CTA).';
        $lines[] = '- Service detail page briefs should focus on one service each.';
        $lines[] = '- Keep it realistic and business-ready.';
        $lines[] = '';
        $lines[] = 'Return ONLY JSON.';

        return implode("\n", $lines);
    }

    private function extractJsonObject(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') return '';

        // If it already looks like JSON, accept it.
        if (str_starts_with($raw, '{') && str_ends_with($raw, '}')) {
            return $raw;
        }

        if (preg_match('/\{.*\}/s', $raw, $m)) {
            return trim((string) $m[0]);
        }

        throw new \RuntimeException('Could not extract JSON from AI output.');
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
        foreach ($pages as $p) {
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
                throw new \RuntimeException('Each page must include is_homepage.');
            }
            if ((bool) $p['is_homepage']) {
                $homepageCount++;
            }
        }
        if ($homepageCount !== 1) {
            throw new \RuntimeException('Blueprint must mark exactly one page as the homepage.');
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
}
