<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomSnippet;
use App\Models\LayoutBlock;
use App\Models\MediaFile;
use App\Models\Page;
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
        // Strip any embedded <style> blocks from the HTML and move them into
        // canvasCSS so GrapesJS injects them cleanly into the iframe <head>.
        // This handles both old pages (styles still in body) and new ones
        // (styles already extracted to a CustomSnippet).
        [$html, $inlineCSS] = $this->extractStyleBlocks((string) ($page->body ?? ''));
        $snippetCSS = $this->resolvePageCss($page);
        $canvasCSS  = trim($inlineCSS . "\n" . $snippetCSS);

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
        $page->body = $request->input('html', '');
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
