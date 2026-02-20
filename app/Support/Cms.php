<?php

namespace App\Support;

use App\Models\Form;
use App\Models\Page;
use Illuminate\Support\HtmlString;

class Cms
{
    public function __construct(
        private readonly ModuleManager $modules
    ) {}

    /**
     * Render content with shortcodes.
     * Supports:
     * - [form slug="contact" to="a@x.com,b@y.com" cc="cc@x.com" bcc="bcc@x.com"]
     * - [icon kind="fa" value="fa-solid fa-house" size="24" colour="#111827"]
     * - [icon kind="lucide" value="home" size="24" colour="#111827"]
     * - [icon data='{"kind":"fa","value":"fa-solid fa-house","svg":"<svg...>","size":24,"colour":"#111827"}']
     *
     * Notes:
     * - If cms.allow_raw_html is enabled, non-shortcode content is output as-is (HTML supported).
     * - If disabled, non-shortcode content is escaped (safer for untrusted inputs).
     */
    public function renderContent(string $content, ?Page $page = null, ?bool $forceRawHtml = null): HtmlString
    {
        // Split content into text + shortcode tokens while preserving the tokens
        $parts = preg_split(
            '/(\[(?:form|icon)\s+[^\]]+\])/i',
            $content,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        $out = '';
        $allowRawHtml = $forceRawHtml ?? (bool) config('cms.allow_raw_html', true);

        foreach ($parts as $part) {
            // If this chunk is a [form ...] shortcode, render HTML
            if (preg_match('/^\[form\s+([^\]]+)\]$/i', $part, $m)) {
                $out .= $this->renderSingleFormShortcode($m[1] ?? '', $page);
                continue;
            }

            // If this chunk is an [icon ...] shortcode, render HTML
            if (preg_match('/^\[icon\s+([^\]]+)\]$/i', $part, $m)) {
                $out .= $this->renderSingleIconShortcode($m[1] ?? '');
                continue;
            }

            // Otherwise, treat as plain content
            if ($allowRawHtml) {
                // HTML + plain text are output as-is.
                $out .= $part;
            } else {
                // Safer mode: escape everything except known shortcodes.
                $out .= nl2br(e($part));
            }
        }

        return new HtmlString($out);
    }

    private function renderSingleFormShortcode(string $rawAttrs, ?Page $page = null): string
    {
        $attrs = $this->parseShortcodeAttributes($rawAttrs);
        $slug = $attrs['slug'] ?? null;

        if (!$slug) {
            return '';
        }

        $form = Form::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$form) {
            return '';
        }

        $overrideTo = isset($attrs['to']) ? trim((string) $attrs['to']) : null;
        $overrideCc = isset($attrs['cc']) ? trim((string) $attrs['cc']) : null;
        $overrideBcc = isset($attrs['bcc']) ? trim((string) $attrs['bcc']) : null;

        return view('cms.forms.embed', [
            'form' => $form,
            'page' => $page,
            'overrideTo' => $overrideTo,
            'overrideCc' => $overrideCc,
            'overrideBcc' => $overrideBcc,
        ])->render();
    }

    /**
     * Render an icon shortcode.
     *
     * Examples:
     *  - [icon kind="fa" value="fa-solid fa-house" size="24" colour="#111827"]
     *  - [icon kind="lucide" value="home" size="24" colour="#111827"]
     *  - [icon data='{"kind":"fa","value":"fa-solid fa-house","svg":"<svg...>","size":24,"colour":"#111827"}']
     */
    private function renderSingleIconShortcode(string $rawAttrs): string
    {
        $attrs = $this->parseShortcodeAttributes($rawAttrs);

        // Option A: data JSON blob
        $dataJson = $attrs['data'] ?? null;
        if (is_string($dataJson) && $dataJson !== '') {
            try {
                $decoded = json_decode($dataJson, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $attrs = array_merge($attrs, $decoded);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $kind = strtolower((string) ($attrs['kind'] ?? ''));
        $value = (string) ($attrs['value'] ?? $attrs['icon'] ?? $attrs['name'] ?? '');

        if (!in_array($kind, ['fa', 'lucide'], true) || $value === '') {
            return '';
        }

        $size = (int) ($attrs['size'] ?? 24);
        if ($size < 8) $size = 8;
        if ($size > 256) $size = 256;

        $colour = (string) ($attrs['colour'] ?? $attrs['color'] ?? '#111827');
        if (!$this->isSafeHexColour($colour)) {
            $colour = '#111827';
        }

        // Portable mode: if svg is provided, we render it directly (no font/CSS dependency)
        $svg = isset($attrs['svg']) && is_string($attrs['svg']) ? trim($attrs['svg']) : '';

        if ($kind === 'fa') {
            if ($svg !== '' && str_starts_with($svg, '<svg')) {
                return $this->renderInlineSvg($svg, $size, $colour);
            }

            // Only allow safe FA classes (letters/numbers/spaces/dashes)
            if (!preg_match('/^[a-z0-9\s\-]+$/i', $value)) {
                return '';
            }

            $class = trim($value);

            return '<i class="' . e($class) . '" style="font-size:' . $size . 'px;color:' . e($colour) . ';line-height:1;vertical-align:-0.125em"></i>';
        }

        // lucide: value is icon name (kebab-case)
        if (!preg_match('/^[a-z0-9\-]+$/', $value)) {
            return '';
        }

        return '<i data-lucide="' . e($value) . '" style="width:' . $size . 'px;height:' . $size . 'px;color:' . e($colour) . ';display:inline-block;vertical-align:-0.125em"></i>';
    }

    private function renderInlineSvg(string $svg, int $size, string $colour): string
    {
        $s = trim($svg);

        // Defensive clean-up
        $s = preg_replace('/<!--([\s\S]*?)-->/', '', $s) ?? $s;
        $s = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $s) ?? $s;

        // Remove width/height so our wrapper sizing wins
        $s = preg_replace('/\s(width|height)="[^"]*"/i', '', $s) ?? $s;

        // Ensure we only return the SVG element itself
        if (!preg_match('/<svg\b[\s\S]*?<\/svg>/i', $s, $m)) {
            return '';
        }

        $svgOnly = $m[0];

        // Wrap with inline-block + colour. Font Awesome SVGs use currentColor.
        return '<span style="display:inline-block;width:' . $size . 'px;height:' . $size . 'px;color:' . e($colour) . ';line-height:1;vertical-align:-0.125em">'
            . $svgOnly
            . '</span>';
    }

    /**
     * Parse shortcode attributes like: slug="contact" to="a@x.com,b@y.com"
     *
     * @return array<string, string>
     */
    private function parseShortcodeAttributes(string $raw): array
    {
        $attrs = [];

        // Supports both double and single quotes:
        //  - key="value"
        //  - key='value'
        preg_match_all('/(\w+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/', $raw, $m, PREG_SET_ORDER);

        foreach ($m as $pair) {
            $val = $pair[2] ?? '';
            if ($val === '' && isset($pair[3])) {
                $val = (string) $pair[3];
            }

            $attrs[strtolower($pair[1])] = (string) $val;
        }

        return $attrs;
    }

    private function isSafeHexColour(string $value): bool
    {
        $v = trim($value);
        return (bool) preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $v);
    }
}
