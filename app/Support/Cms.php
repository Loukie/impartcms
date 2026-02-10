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
     * Supports: [form slug="contact" to="a@x.com,b@y.com"]
     *
     * Security: normal text is escaped; only known shortcodes render HTML.
     */
    public function renderContent(string $content, ?Page $page = null): HtmlString
    {
        // Split content into text + shortcode tokens while preserving the tokens
        $parts = preg_split(
            '/(\[form\s+[^\]]+\])/i',
            $content,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        $out = '';

        foreach ($parts as $part) {
            // If this chunk is a [form ...] shortcode, render HTML
            if (preg_match('/^\[form\s+([^\]]+)\]$/i', $part, $m)) {
                $out .= $this->renderSingleFormShortcode($m[1] ?? '', $page);
                continue;
            }

            // Otherwise, treat as plain text and escape
            $out .= nl2br(e($part));
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

        return view('cms.forms.embed', [
            'form' => $form,
            'page' => $page,
            'overrideTo' => $overrideTo,
        ])->render();
    }

    /**
     * Parse shortcode attributes like: slug="contact" to="a@x.com,b@y.com"
     *
     * @return array<string, string>
     */
    private function parseShortcodeAttributes(string $raw): array
    {
        $attrs = [];
        preg_match_all('/(\w+)\s*=\s*"([^"]*)"/', $raw, $m, PREG_SET_ORDER);

        foreach ($m as $pair) {
            $attrs[strtolower($pair[1])] = (string) $pair[2];
        }

        return $attrs;
    }
}
