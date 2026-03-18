<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomSnippet;
use App\Models\LayoutBlock;
use App\Models\MediaFile;
use App\Models\Page;
use App\Models\Setting;
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

        // Wrap the editable body with read-only nav/footer for visual context.
        // Extract any <style> blocks from nav/footer HTML so GrapesJS doesn't strip them —
        // they are injected via canvasCSS instead (which goes into the canvas iframe <head>).
        [$navHtml, $navCss]       = $this->extractStyleBlocks(LayoutBlockRenderer::headerRaw($page));
        [$footerHtml, $footerCss] = $this->extractStyleBlocks(LayoutBlockRenderer::footerRaw($page));
        $wrappedHtml = $this->wrapWithLayout($html, $navHtml, $footerHtml);

        $canvasCSS = trim($navCss . "\n" . $footerCss . "\n" . $canvasCSS);

        return view('admin.visual-editor.editor', [
            'title'          => $page->title,
            'html'           => $wrappedHtml,
            'extractedCSS'   => $inlineCSS,
            'saveUrl'        => route('admin.visual-editor.page.save', $page),
            'backUrl'        => route('admin.pages.edit', $page),
            'assetsUrl'      => route('admin.visual-editor.assets'),
            'typographyUrl'  => route('admin.visual-editor.typography', $page),
            'canvasCSS'      => $canvasCSS,
            'context'        => 'page',
        ]);
    }

    /**
     * Save the page body from the visual editor.
     * If the page had embedded <style> blocks, migrate them to a CustomSnippet.
     */
    public function savePage(Request $request, Page $page): JsonResponse
    {
        // extractBodyFromLayout strips the nav/footer markers added by wrapWithLayout,
        // saving only the editable page body (handles both wrapped and bare HTML).
        $page->body = $this->extractBodyFromLayout(trim($request->input('html', '')));
        $page->save();

        // Migrate any CSS that was extracted from the page body into a snippet.
        $extractedCss = trim((string) $request->input('extracted_css', ''));
        if ($extractedCss !== '') {
            $this->migrateCssToSnippet($extractedCss, $page);
        }

        // full_css = @imports + full CSS manager contents (original page CSS + Style Manager edits).
        // Replace the snippet content so the saved CSS reflects exactly what the editor shows.
        $fullCss = trim((string) $request->input('full_css', ''));
        if ($fullCss !== '') {
            $this->replacePageCssSnippet($fullCss, $page);
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
     * Handle custom font upload.
     * Stores the font file under public/fonts/ and returns the font name + URL.
     */
    public function uploadFont(Request $request): JsonResponse
    {
        $request->validate([
            'font' => ['required', 'file', 'mimes:ttf,otf,woff,woff2', 'max:5120'],
        ]);

        $file     = $request->file('font');
        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Derive a clean font name from the filename (e.g. "my-font" → "My Font").
        $fontName = ucwords(str_replace(['-', '_'], ' ', $original));

        // Keep the original extension for correct browser interpretation.
        $ext      = strtolower($file->getClientOriginalExtension());
        $filename = $original . '.' . $ext;

        $dest = public_path('fonts');
        if (! is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $file->move($dest, $filename);

        $url = asset('fonts/' . $filename);

        return response()->json([
            'ok'         => true,
            'name'       => $fontName,
            'url'        => $url,
            'ext'        => $ext,
            'fontFamily' => "'{$fontName}', sans-serif",
        ]);
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
     * Replace the page's CSS snippet content with the full CSS from the editor.
     * The CSS includes @imports (for fonts) + all CSS manager rules (original
     * page styles + any Style Manager edits), so the saved snippet is authoritative.
     */
    private function replacePageCssSnippet(string $css, Page $page): void
    {
        $snippet = CustomSnippet::where('type', 'css')
            ->where('is_enabled', true)
            ->where('target_mode', 'only')
            ->whereHas('pages', fn ($q) => $q->where('pages.id', $page->id))
            ->first();

        if ($snippet) {
            $snippet->content = $css;
            $snippet->save();
        } else {
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
     * Return current global + page typography settings as JSON.
     */
    public function getTypography(Page $page): JsonResponse
    {
        $global = json_decode((string) (Setting::get('typography.global', '{}') ?? '{}'), true) ?: [];
        $pageData = json_decode((string) (Setting::get('typography.page.' . $page->id, '{}') ?? '{}'), true) ?: [];

        return response()->json(['global' => $global, 'page' => $pageData]);
    }

    /**
     * Save global and per-page typography settings, then regenerate CSS snippets.
     */
    public function saveTypography(Request $request, Page $page): JsonResponse
    {
        $tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'];

        $globalData = is_array($request->input('global')) ? $request->input('global') : [];
        Setting::set('typography.global', json_encode($globalData));
        $this->saveGlobalTypographySnippet($this->buildTypographyCss($globalData, $tags, false));

        $pageData = is_array($request->input('page')) ? $request->input('page') : [];
        Setting::set('typography.page.' . $page->id, json_encode($pageData));
        $overrideTags = array_filter($pageData, fn ($v) => is_array($v) && !empty($v['override']));
        $this->savePageTypographySnippet($this->buildTypographyCss($overrideTags, array_keys($overrideTags), true), $page);

        return response()->json(['ok' => true]);
    }

    /**
     * Generate CSS from typography tag data.
     * $highSpecificity = true uses "body h1" selectors so page rules beat global rules.
     */
    private function buildTypographyCss(array $data, array $tags, bool $highSpecificity): string
    {
        $propMap = [
            'font_family'     => 'font-family',
            'font_size'       => 'font-size',
            'font_weight'     => 'font-weight',
            'font_style'      => 'font-style',
            'line_height'     => 'line-height',
            'letter_spacing'  => 'letter-spacing',
            'color'           => 'color',
            'text_decoration' => 'text-decoration',
            'text_transform'  => 'text-transform',
        ];

        $css = '';
        foreach ($tags as $tag) {
            $values = $data[$tag] ?? [];
            if (!is_array($values)) continue;

            $decls = [];
            foreach ($propMap as $key => $cssProp) {
                $val = trim((string) ($values[$key] ?? ''));
                if ($val !== '' && $val !== 'inherit' && $val !== 'initial') {
                    $decls[] = "  {$cssProp}: {$val}";
                }
            }

            if ($decls) {
                $selector = $highSpecificity ? "body {$tag}" : $tag;
                $css .= "{$selector} {\n" . implode(";\n", $decls) . ";\n}\n\n";
            }
        }

        return trim($css);
    }

    private function saveGlobalTypographySnippet(string $css): void
    {
        if ($css === '') {
            CustomSnippet::where('name', 'Global Typography')->where('type', 'css')->delete();
            return;
        }

        CustomSnippet::updateOrCreate(
            ['name' => 'Global Typography', 'type' => 'css'],
            ['position' => 'head', 'is_enabled' => true, 'target_mode' => 'global', 'content' => $css]
        );
    }

    private function savePageTypographySnippet(string $css, Page $page): void
    {
        $name = 'Page Typography: ' . $page->title;

        if ($css === '') {
            $snippet = CustomSnippet::where('name', $name)->where('type', 'css')->first();
            if ($snippet) {
                $snippet->pages()->detach();
                $snippet->forceDelete();
            }
            return;
        }

        $snippet = CustomSnippet::updateOrCreate(
            ['name' => $name, 'type' => 'css'],
            ['position' => 'head', 'is_enabled' => true, 'target_mode' => 'only', 'content' => $css]
        );
        $snippet->pages()->sync([$page->id]);
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
        // The regex must match the full @import statement without stopping early at
        // semicolons that appear inside quoted URL strings (e.g. Google Fonts URLs
        // like "wght@400;500;700"). We handle three common forms:
        //   @import url('...');   @import url("...");   @import '...';   @import "...";
        $imports = [];
        $css = preg_replace_callback(
            '/@import\s+(?:url\([\'"][^\'"]*[\'"]\)|[\'"][^\'"]*[\'"]);/i',
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
