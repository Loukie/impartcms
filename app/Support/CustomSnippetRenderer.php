<?php

namespace App\Support;

use App\Models\CustomSnippet;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

class CustomSnippetRenderer
{
    /**
     * Render all enabled CSS snippets for a given page.
     * CSS is always injected late in <head> so it can override theme styles.
     */
    public static function renderCss(?Page $page = null): HtmlString
    {
        $snippets = self::snippets();
        $pageId = $page?->id;

        $out = '';
        foreach ($snippets as $s) {
            if (($s['type'] ?? '') !== 'css') continue;
            if (!self::appliesToPage($s, $pageId)) continue;

            $content = (string) ($s['content'] ?? '');
            if (trim($content) === '') continue;

            // If they pasted a <style> block already, output as-is.
            if (stripos($content, '<style') !== false) {
                $out .= "\n<!-- Custom CSS: {$s['name']} (#{$s['id']}) -->\n" . $content . "\n";
            } else {
                $out .= "\n<!-- Custom CSS: {$s['name']} (#{$s['id']}) -->\n";
                $out .= '<style data-custom-snippet-id="' . (int) $s['id'] . '">' . "\n";
                $out .= $content . "\n";
                $out .= "</style>\n";
            }
        }

        return new HtmlString($out);
    }

    /**
     * Render all enabled script snippets for a given page and position.
     * Positions: head | body | footer
     */
    public static function renderScripts(string $position, ?Page $page = null): HtmlString
    {
        $position = strtolower(trim($position));
        if (!in_array($position, ['head', 'body', 'footer'], true)) {
            $position = 'head';
        }

        $snippets = self::snippets();
        $pageId = $page?->id;

        $out = '';
        foreach ($snippets as $s) {
            if (($s['type'] ?? '') !== 'script') continue;
            if (($s['position'] ?? 'head') !== $position) continue;
            if (!self::appliesToPage($s, $pageId)) continue;

            $content = (string) ($s['content'] ?? '');
            if (trim($content) === '') continue;

            // If they pasted a <script> or <noscript> block already, output as-is.
            if (stripos($content, '<script') !== false || stripos($content, '<noscript') !== false) {
                $out .= "\n<!-- Custom Script: {$s['name']} (#{$s['id']}) -->\n" . $content . "\n";
            } else {
                $out .= "\n<!-- Custom Script: {$s['name']} (#{$s['id']}) -->\n";
                $out .= '<script data-custom-snippet-id="' . (int) $s['id'] . '">' . "\n";
                $out .= $content . "\n";
                $out .= "</script>\n";
            }
        }

        return new HtmlString($out);
    }

    /**
     * Cached snapshot of enabled snippets + their targeted page IDs.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function snippets(): array
    {
        return Cache::remember('custom_snippets:enabled:v1', 3600, function () {
            return CustomSnippet::query()
                ->where('is_enabled', true)
                ->orderBy('type')
                ->orderBy('position')
                ->orderBy('id')
                ->with('pages:id')
                ->get()
                ->map(function (CustomSnippet $s) {
                    return [
                        'id' => $s->id,
                        'type' => (string) $s->type,
                        'name' => (string) $s->name,
                        'position' => (string) ($s->position ?? 'head'),
                        'target_mode' => (string) ($s->target_mode ?? 'global'),
                        'content' => (string) ($s->content ?? ''),
                        'page_ids' => $s->pages->pluck('id')->map(fn ($v) => (int) $v)->values()->all(),
                    ];
                })
                ->all();
        });
    }

    private static function appliesToPage(array $snippet, ?int $pageId): bool
    {
        $mode = strtolower((string) ($snippet['target_mode'] ?? 'global'));
        $ids = array_map('intval', (array) ($snippet['page_ids'] ?? []));

        // If we don't have a page ID (shouldn't happen for normal CMS pages), only global snippets apply.
        if (!$pageId) {
            return $mode === 'global';
        }

        if ($mode === 'global') return true;
        if ($mode === 'only') return in_array((int) $pageId, $ids, true);
        if ($mode === 'except') return !in_array((int) $pageId, $ids, true);

        return true;
    }
}
