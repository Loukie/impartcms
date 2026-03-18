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
        $canvasCSS = $this->resolvePageCss($page);

        return view('admin.visual-editor.editor', [
            'title'      => $page->title,
            'html'       => $page->body ?? '',
            'saveUrl'    => route('admin.visual-editor.page.save', $page),
            'backUrl'    => route('admin.pages.edit', $page),
            'assetsUrl'  => route('admin.visual-editor.assets'),
            'canvasCSS'  => $canvasCSS,
            'context'    => 'page',
        ]);
    }

    /**
     * Save the page body from the visual editor.
     */
    public function savePage(Request $request, Page $page): JsonResponse
    {
        $page->body = $request->input('html', '');
        $page->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Open the visual editor for a LayoutBlock (header or footer).
     */
    public function editBlock(LayoutBlock $layoutBlock): View
    {
        return view('admin.visual-editor.editor', [
            'title'     => $layoutBlock->name,
            'html'      => $layoutBlock->content ?? '',
            'saveUrl'   => route('admin.visual-editor.block.save', $layoutBlock),
            'backUrl'   => route('admin.layout-blocks.edit', $layoutBlock),
            'assetsUrl' => route('admin.visual-editor.assets'),
            'canvasCSS' => '',
            'context'   => 'block',
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
