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
        // Strip embedded <style> blocks from body — inject as canvas CSS instead.
        [$html, $inlineCSS] = $this->extractStyleBlocks((string) ($page->body ?? ''));
        $snippetCSS = $this->resolvePageCss($page);

        // Resolve nav/footer LayoutBlocks for this page and strip their styles too.
        $rawNav    = LayoutBlockRenderer::headerHtml($page);
        $rawFooter = LayoutBlockRenderer::footerHtml($page);
        [$navHtml,    $navCss]    = $this->extractStyleBlocks($rawNav);
        [$footerHtml, $footerCss] = $this->extractStyleBlocks($rawFooter);

        $canvasCSS = trim($inlineCSS . "\n" . $snippetCSS . "\n" . $navCss . "\n" . $footerCss);

        // Wrap page body with non-editable nav/footer so the user sees full context.
        $fullHtml = $this->wrapWithLayout($html, $navHtml, $footerHtml);

        return view('admin.visual-editor.editor', [
            'title'        => $page->title,
            'html'         => $fullHtml,
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
        // Strip the non-editable nav/footer wrappers — save only the body portion.
        $page->body = $this->extractBodyFromLayout($request->input('html', ''));
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
            'canvasCSS'    => $inlineCSS,
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
        $nav    = $navHtml    !== '' ? '<div data-gjs-editable="false" data-gjs-selectable="false" data-gjs-hoverable="false" data-ve-role="nav">'    . $navHtml    . '</div>' : '';
        $footer = $footerHtml !== '' ? '<div data-gjs-editable="false" data-gjs-selectable="false" data-gjs-hoverable="false" data-ve-role="footer">' . $footerHtml . '</div>' : '';

        return $nav . $body . $footer;
    }

    /**
     * When saving, strip the nav/footer wrapper divs and return only the
     * editable page body that was originally between them.
     */
    private function extractBodyFromLayout(string $html): string
    {
        // Remove non-editable nav/footer wrapper divs added by wrapWithLayout().
        $html = preg_replace('/<div[^>]+data-ve-role="nav"[^>]*>.*?<\/div>/is', '', $html);
        $html = preg_replace('/<div[^>]+data-ve-role="footer"[^>]*>.*?<\/div>/is', '', $html);

        return trim($html ?? '');
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
}
