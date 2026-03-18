<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomSnippet;
use App\Models\LayoutBlock;
use App\Models\MediaFile;
use App\Models\Page;
use App\Support\LayoutBlockRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisualEditorController extends Controller
{
    /**
     * Open the visual editor for a page body.
     */
    public function editPage(Page $page): View
    {
        // Strip embedded <style> blocks and any stale <body> wrapper from the page body.
        [$html, $inlineCSS] = $this->extractStyleBlocks($this->stripBodyWrapper((string) ($page->body ?? '')));
        $snippetCSS = $this->resolvePageCss($page);
        $canvasCSS  = trim($inlineCSS . "\n" . $this->sanitiseCanvasCss($snippetCSS));

        return view('admin.visual-editor.editor', [
            'title'        => $page->title,
            'html'         => $html,
            'extractedCSS' => $inlineCSS,
            'saveUrl'      => route('admin.visual-editor.page.save', $page),
            'backUrl'      => route('admin.pages.edit', $page),
            'assetsUrl'    => route('admin.visual-editor.assets'),
            'canvasCSS'    => $canvasCSS,
            'context'      => 'page',
        ]);
    }

    /**
     * Save the page body from the visual editor.
     * If the page had embedded <style> blocks, migrate them to a CustomSnippet.
     */
    public function savePage(Request $request, Page $page): JsonResponse
    {
        $page->body = $this->stripBodyWrapper(trim($request->input('html', '')));
        $page->save();

        // Migrate any CSS that was extracted from the page body into a snippet.
        $extractedCss = trim((string) $request->input('extracted_css', ''));
        if ($extractedCss !== '') {
            $this->migrateCssToSnippet($extractedCss, $page);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Open the visual editor for a LayoutBlock (header or footer).
     */
    public function editBlock(LayoutBlock $layoutBlock): View
    {
        [$html, $inlineCSS] = $this->extractStyleBlocks((string) ($layoutBlock->content ?? ''));

        return view('admin.visual-editor.editor', [
            'title'        => $layoutBlock->name,
            'html'         => $html,
            'extractedCSS' => $inlineCSS,
            'saveUrl'      => route('admin.visual-editor.block.save', $layoutBlock),
            'backUrl'      => route('admin.layout-blocks.edit', $layoutBlock),
            'assetsUrl'    => route('admin.visual-editor.assets'),
            'canvasCSS'    => $this->sanitiseCanvasCss($inlineCSS),
            'context'      => 'block',
        ]);
    }

    /**
     * Save the LayoutBlock content from the visual editor.
     */
    public function saveBlock(Request $request, LayoutBlock $layoutBlock): JsonResponse
    {
        $layoutBlock->content = $request->input('html', '');
        $layoutBlock->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Return media library images for the GrapesJS asset manager.
     */
    public function assets(): JsonResponse
    {
        $files = MediaFile::whereNull('deleted_at')
            ->where('mime_type', 'like', 'image/%')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (MediaFile $f) => [
                'src'    => $f->url,
                'name'   => $f->title ?: $f->original_name ?: $f->filename,
                'width'  => $f->width,
                'height' => $f->height,
            ]);

        return response()->json(['data' => $files]);
    }

    /**
     * Move extracted CSS into a CustomSnippet scoped to the given page.
     * If a snippet already exists for this page (created by a previous save or
     * by the clone pipeline), append any new CSS rather than duplicating.
     */
    private function migrateCssToSnippet(string $css, Page $page): void
    {
        // Look for an existing "Page Styles" snippet already scoped to this page.
        $existing = CustomSnippet::where('type', 'css')
            ->where('target_mode', 'only')
            ->whereHas('pages', fn ($q) => $q->where('pages.id', $page->id))
            ->where(function ($q) {
                $q->where('name', 'like', '%Page Styles%')
                  ->orWhere('name', 'like', '%Visual Editor%');
            })
            ->first();

        if ($existing) {
            // Already has a snippet — nothing to migrate (CSS is already there).
            return;
        }

        $snippet = CustomSnippet::create([
            'type'        => 'css',
            'name'        => $page->title . ' — Page Styles',
            'position'    => 'head',
            'is_enabled'  => true,
            'target_mode' => 'only',
            'content'     => $css,
        ]);
        $snippet->pages()->sync([$page->id]);
    }

    /**
     * Wrap editable page body with read-only nav/footer for visual context.
     * GrapesJS respects data-gjs-* attributes — editable=false prevents
     * the user from accidentally editing the layout blocks.
     */
    private function wrapWithLayout(string $body, string $navHtml, string $footerHtml): string
    {
        $nav    = $navHtml    !== '' ? '<div data-gjs-editable="false" data-gjs-selectable="false" data-gjs-hoverable="false">' . $navHtml    . '</div>' : '';
        $footer = $footerHtml !== '' ? '<div data-gjs-editable="false" data-gjs-selectable="false" data-gjs-hoverable="false">' . $footerHtml . '</div>' : '';

        // Wrap body in comment markers so we can reliably extract it on save,
        // regardless of how deeply nested the nav/footer HTML is.
        return $nav . '<!-- ve-body-start -->' . $body . '<!-- ve-body-end -->' . $footer;
    }

    /**
     * When saving, extract only the content between the body markers.
     * This is reliable even when nav/footer contain many nested div tags.
     */
    private function extractBodyFromLayout(string $html): string
    {
        if (preg_match('/<!--\s*ve-body-start\s*-->(.*?)<!--\s*ve-body-end\s*-->/is', $html, $m)) {
            return $this->stripBodyWrapper(trim($m[1]));
        }

        // No markers found — return as-is (e.g. direct save without layout context).
        return $this->stripBodyWrapper(trim($html));
    }

    /**
     * GrapesJS wraps its HTML output in <body>…</body>.
     * Strip those tags so we only store the inner content.
     */
    private function stripBodyWrapper(string $html): string
    {
        // Globally remove any <html>, <head>, <body> wrapper tags.
        // These should never appear inside a page body fragment and can
        // accumulate if GrapesJS round-trips through the field multiple times.
        return trim(preg_replace('/<\/?(html|head|body)[^>]*>/i', '', $html) ?? $html);
    }

    /**
     * Pull <style> blocks out of HTML, return [cleanHtml, extractedCSS].
     * Styles are injected into the GrapesJS canvas iframe head instead of
     * living inside the component HTML, which makes rendering cleaner.
     *
     * @return array{0: string, 1: string}
     */
    private function extractStyleBlocks(string $html): array
    {
        $css = '';
        $clean = preg_replace_callback(
            '/<style[^>]*>(.*?)<\/style>/is',
            function (array $m) use (&$css): string {
                $css .= "\n" . trim($m[1]);
                return '';
            },
            $html
        );

        return [$clean ?? $html, trim($css)];
    }

    /**
     * Collect all CSS CustomSnippets assigned to a page and combine them.
     */
    private function resolvePageCss(Page $page): string
    {
        $global = CustomSnippet::where('type', 'css')
            ->where('is_enabled', true)
            ->where('target_mode', 'global')
            ->pluck('content')
            ->implode("\n");

        $pageSpecific = CustomSnippet::where('type', 'css')
            ->where('is_enabled', true)
            ->where('target_mode', 'only')
            ->whereHas('pages', fn ($q) => $q->where('pages.id', $page->id))
            ->pluck('content')
            ->implode("\n");

        return trim($global . "\n" . $pageSpecific);
    }

    /**
     * Prepare CSS for the GrapesJS canvas iframe:
     * 1. Move @import rules to the top (they must precede all other rules).
     * 2. Remove scroll-reveal opacity:0 rules — elements must be visible in the
     *    static canvas (no IntersectionObserver fires, so they'd stay hidden).
     */
    private function sanitiseCanvasCss(string $css): string
    {
        if (trim($css) === '') {
            return '';
        }

        // Extract @import lines so we can hoist them to the top.
        $imports = [];
        $css = preg_replace_callback(
            '/@import\s+[^;]+;/i',
            function (array $m) use (&$imports): string {
                $imports[] = $m[0];
                return '';
            },
            $css
        ) ?? $css;

        // Remove scroll-reveal hide rules: any ruleset whose selector contains
        // ".reveal" (but NOT ".reveal.is-visible" or ".reveal.visible") and whose
        // block sets opacity to 0.
        $css = preg_replace_callback(
            '/([^{}]*\.reveal\b[^{}]*)\{([^}]*)\}/s',
            function (array $m): string {
                $selector = $m[1];
                $block    = $m[2];

                // Keep rules for the "visible" state — they should NOT be stripped.
                if (preg_match('/\b(is-visible|visible|active)\b/i', $selector)) {
                    return $m[0];
                }

                // Strip opacity:0 from the block; keep everything else.
                $cleaned = preg_replace('/opacity\s*:\s*0[^;]*;?/i', '', $block) ?? $block;
                $cleaned = preg_replace('/transform\s*:[^;]*translateY[^;]*;?/i', '', $cleaned) ?? $cleaned;
                $cleaned = trim($cleaned);

                if ($cleaned === '') {
                    return ''; // Entire rule was only the hidden state — drop it.
                }

                return $selector . '{' . $cleaned . '}';
            },
            $css
        ) ?? $css;

        // Reassemble: @imports first, then the rest of the CSS.
        $parts = array_filter([
            implode("\n", $imports),
            trim($css),
        ]);

        return implode("\n\n", $parts);
    }
}
