<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Models\Page;
use App\Models\SeoMeta;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MediaAdminController extends Controller
{
    public function index(Request $request): View
    {
        $folder = (string) $request->query('folder', '');
        $type = (string) $request->query('type', '');

        $query = MediaFile::query()->latest();

        if ($folder !== '') {
            $query->where('folder', $folder);
        }

        if ($type === 'images') {
            $query->where('mime_type', 'like', 'image/%');
        } elseif ($type === 'docs') {
            $query->where('mime_type', 'not like', 'image/%');
        }

        return view('admin.media.index', [
            'media' => $query->paginate(30)->withQueryString(),
            'folders' => MediaFile::query()
                ->select('folder')
                ->whereNotNull('folder')
                ->distinct()
                ->orderByDesc('folder')
                ->pluck('folder')
                ->all(),
            'currentFolder' => $folder,
            'currentType' => $type,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,avif,pdf'], // 10MB
        ]);

        $folder = now()->format('Y/m');
        $disk = 'public';

        foreach ($validated['files'] as $file) {
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
            $filename = (string) Str::uuid() . '.' . $ext;
            $path = $file->storeAs('media/' . $folder, $filename, $disk);

            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $size = (int) $file->getSize();
            $width = null;
            $height = null;

            if (is_string($mime) && str_starts_with($mime, 'image/')) {
                try {
                    $full = Storage::disk($disk)->path($path);
                    $info = @getimagesize($full);
                    if (is_array($info)) {
                        $width = (int) ($info[0] ?? 0) ?: null;
                        $height = (int) ($info[1] ?? 0) ?: null;
                    }
                } catch (\Throwable $e) {
                    // Ignore; still save file record.
                }
            }

            MediaFile::query()->create([
                'disk' => $disk,
                'path' => $path,
                'folder' => $folder,
                'original_name' => $file->getClientOriginalName(),
                'filename' => $filename,
                'mime_type' => $mime,
                'size' => $size,
                'width' => $width,
                'height' => $height,
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'created_by' => $request->user()?->id,
            ]);
        }

        return back()->with('status', 'Media uploaded.');
    }

    public function show(MediaFile $media): View
    {
        $usage = $this->detectUsage($media);

        return view('admin.media.show', [
            'media' => $media,
            'usage' => $usage,
        ]);
    }

    public function update(Request $request, MediaFile $media): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:180'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:2000'],
        ]);

        $media->title = $validated['title'] ?? null;
        $media->alt_text = $validated['alt_text'] ?? null;
        $media->caption = $validated['caption'] ?? null;
        $media->save();

        return back()->with('status', 'Media updated.');
    }

    public function destroy(MediaFile $media): RedirectResponse
    {
        // Safety: refuse delete if it appears in any pages/seo.
        $usage = $this->detectUsage($media);
        $inUse = (
            (isset($usage['pages']) && method_exists($usage['pages'], 'isNotEmpty') && $usage['pages']->isNotEmpty())
            || (isset($usage['seo_pages']) && method_exists($usage['seo_pages'], 'isNotEmpty') && $usage['seo_pages']->isNotEmpty())
            || ((bool) ($usage['settings_logo'] ?? false))
        );

        if ($inUse) {
            return back()->withErrors([
                'status' => 'This file appears to be in use. Remove it from pages first, then delete.'
            ]);
        }

        Storage::disk($media->disk ?? 'public')->delete($media->path);
        $media->delete();

        return redirect()->route('admin.media.index')->with('status', 'Media deleted.');
    }

    /**
     * Best-effort “Where used” detection for now.
     * - Scans Page.body and SeoMeta OG/Twitter image URLs.
     * - This avoids needing a builder integration immediately.
     */
    private function detectUsage(MediaFile $media): array
    {
        $relative = '/storage/' . ltrim($media->path, '/');
        $relative2 = 'storage/' . ltrim($media->path, '/');

        $logoMediaId = (int) (Setting::get('site_logo_media_id', '0') ?? 0);
        $usedAsLogo = $logoMediaId > 0 && $logoMediaId === (int) $media->id;

        $pages = Page::query()
            ->where(function ($q) use ($relative, $relative2) {
                $q->where('body', 'like', '%' . $relative . '%')
                    ->orWhere('body', 'like', '%' . $relative2 . '%');
            })
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'status', 'deleted_at']);

        $seoPageIds = SeoMeta::query()
            ->where(function ($q) use ($relative, $relative2) {
                $q->where('og_image_url', 'like', '%' . $relative . '%')
                    ->orWhere('og_image_url', 'like', '%' . $relative2 . '%')
                    ->orWhere('twitter_image_url', 'like', '%' . $relative . '%')
                    ->orWhere('twitter_image_url', 'like', '%' . $relative2 . '%');
            })
            ->pluck('page_id')
            ->unique()
            ->values()
            ->all();

        $seoPages = empty($seoPageIds)
            ? collect()
            : Page::query()->whereIn('id', $seoPageIds)->orderBy('title')->get(['id', 'title', 'slug', 'status', 'deleted_at']);

        return [
            'pages' => $pages,
            'seo_pages' => $seoPages,
            'settings_logo' => $usedAsLogo,
        ];
    }
}
