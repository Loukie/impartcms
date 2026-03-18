<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomSnippet;
use App\Models\LayoutBlock;
use App\Models\MediaFile;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ResetController extends Controller
{
    public function index(): View
    {
        return view('admin.reset.index', [
            'counts' => [
                'pages'         => Page::withTrashed()->count(),
                'media'         => MediaFile::withTrashed()->count(),
                'layout_blocks' => LayoutBlock::withTrashed()->count(),
                'snippets'      => CustomSnippet::withTrashed()->count(),
            ],
        ]);
    }

    public function clear(Request $request): RedirectResponse
    {
        $type = $request->input('type');

        $cleared = match ($type) {
            'pages'         => $this->clearPages(),
            'media'         => $this->clearMedia(),
            'layout_blocks' => $this->clearLayoutBlocks(),
            'snippets'      => $this->clearSnippets(),
            default         => null,
        };

        if ($cleared === null) {
            return redirect()->route('admin.reset')->with('error', 'Unknown type.');
        }

        return redirect()->route('admin.reset')->with('success', "Cleared {$cleared} record(s).");
    }

    private function clearPages(): int
    {
        $count = Page::withTrashed()->count();
        Page::withTrashed()->forceDelete();
        return $count;
    }

    private function clearMedia(): int
    {
        $count = 0;
        MediaFile::withTrashed()->each(function (MediaFile $file) use (&$count) {
            if ($file->path) {
                $disk = $file->disk ?: 'public';
                if (Storage::disk($disk)->exists($file->path)) {
                    Storage::disk($disk)->delete($file->path);
                }
            }
            $file->forceDelete();
            $count++;
        });
        return $count;
    }

    private function clearLayoutBlocks(): int
    {
        $count = LayoutBlock::withTrashed()->count();
        LayoutBlock::withTrashed()->forceDelete();
        return $count;
    }

    private function clearSnippets(): int
    {
        $count = CustomSnippet::withTrashed()->count();
        CustomSnippet::withTrashed()->each(fn (CustomSnippet $s) => $s->pages()->detach());
        CustomSnippet::withTrashed()->forceDelete();
        return $count;
    }
}
