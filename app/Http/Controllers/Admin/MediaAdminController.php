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
    /**
     * Media Library (admin)
     */
    public function index(Request $request): View
    {
        $data = $this->buildListing($request, forcedType: null, perPage: 30);

        return view('admin.media.index', $data);
    }

    /**
     * Media Picker (admin, iframe) - WordPress-style modal selection.
     *
     * Query params:
     * - accept: images|fonts|docs|all (default images)
     * - selected: media id (for highlighting)
     */
    public function picker(Request $request): View
    {
        $accept = (string) $request->query('accept', 'images');
        $forcedType = match ($accept) {
            'all' => null,
            'docs' => 'docs',
            'fonts' => 'fonts',
            default => 'images',
        };

        $data = $this->buildListing($request, forcedType: $forcedType, perPage: 24);
        $data['accept'] = $accept;
        $data['selectedId'] = (int) ($request->query('selected') ?? 0);

        return view('admin.media.picker', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            // 10MB each
            'files.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,avif,svg,ico,pdf,woff,woff2,ttf,otf,eot'],
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
        // Safety: refuse delete if it appears in any pages/seo/settings.
        $usage = $this->detectUsage($media);
        $usedInPages = ($usage['pages'] ?? collect())->isNotEmpty();
        $usedInSeo = ($usage['seo_pages'] ?? collect())->isNotEmpty();
        $usedAsLogo = !empty($usage['settings_logo']);

        if ($usedInPages || $usedInSeo || $usedAsLogo) {
            $message = $usedAsLogo
                ? 'This file is currently used as the site logo. Remove it from Settings first, then delete.'
                : 'This file appears to be in use. Remove it from pages first, then delete.';

            return back()->withErrors(['status' => $message]);
        }

        Storage::disk($media->disk ?? 'public')->delete($media->path);
        $media->delete();

        return redirect()->route('admin.media.index')->with('status', 'Media deleted.');
    }

    /**
     * Shared listing logic for both index + picker.
     */
    private function buildListing(Request $request, ?string $forcedType, int $perPage): array
    {
        $folder = (string) $request->query('folder', '');
        $type = (string) $request->query('type', '');
        $q = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'newest');

        if ($forcedType !== null) {
            $type = (string) $forcedType;
        }

        $fontExts = ['woff2', 'woff', 'ttf', 'otf', 'eot'];
        $fontsWhere = function ($query) use ($fontExts) {
            $query->where(function ($qq) use ($fontExts) {
                foreach ($fontExts as $ext) {
                    $qq->orWhere('filename', 'like', '%.' . $ext)
                        ->orWhere('original_name', 'like', '%.' . $ext);
                }
            });
        };

        $base = MediaFile::query();

        if ($folder !== '') {
            $base->where('folder', $folder);
        }

        if ($q !== '') {
            $base->where(function ($query) use ($q) {
                $query->where('title', 'like', '%' . $q . '%')
                    ->orWhere('original_name', 'like', '%' . $q . '%')
                    ->orWhere('filename', 'like', '%' . $q . '%');
            });
        }

        // Counts for WordPress-style tabs (reflect current search + folder filter).
        $countsBase = clone $base;
        $counts = [
            'all' => (clone $countsBase)->count(),
            'images' => (clone $countsBase)->where('mime_type', 'like', 'image/%')->count(),
            'fonts' => (clone $countsBase)->where(function ($q) use ($fontsWhere) { $fontsWhere($q); })->count(),
            'docs' => (clone $countsBase)
                ->where('mime_type', 'not like', 'image/%')
                ->where(function ($q) use ($fontExts) {
                    foreach ($fontExts as $ext) {
                        $q->where('filename', 'not like', '%.' . $ext)
                          ->where('original_name', 'not like', '%.' . $ext);
                    }
                })
                ->count(),
        ];

        // Apply type filter.
        if ($type === 'images') {
            $base->where('mime_type', 'like', 'image/%');
        } elseif ($type === 'fonts') {
            $fontsWhere($base);
        } elseif ($type === 'docs') {
            $base->where('mime_type', 'not like', 'image/%')
                ->where(function ($q) use ($fontExts) {
                    foreach ($fontExts as $ext) {
                        $q->where('filename', 'not like', '%.' . $ext)
                          ->where('original_name', 'not like', '%.' . $ext);
                    }
                });
        } else {
            $type = '';
        }

        // Sorting
        switch ($sort) {
            case 'oldest':
                $base->orderBy('id');
                break;
            case 'title_asc':
                $base->orderBy('title');
                break;
            case 'title_desc':
                $base->orderByDesc('title');
                break;
            case 'largest':
                $base->orderByDesc('size');
                break;
            case 'smallest':
                $base->orderBy('size');
                break;
            case 'newest':
            default:
                $sort = 'newest';
                $base->orderByDesc('id');
                break;
        }

        return [
            'media' => $base->paginate($perPage)->withQueryString(),
            'counts' => $counts,
            'folders' => MediaFile::query()
                ->select('folder')
                ->whereNotNull('folder')
                ->distinct()
                ->orderByDesc('folder')
                ->pluck('folder')
                ->all(),
            'currentFolder' => $folder,
            'currentType' => $type,
            'currentQuery' => $q,
            'currentSort' => $sort,
        ];
    }

    /**
     * Best-effort “Where used” detection for now.
     * - Scans Page.body and SeoMeta OG/Twitter image URLs.
     * - Also checks if used as Settings logo.
     */
    private function detectUsage(MediaFile $media): array
    {
        $relative = '/storage/' . ltrim($media->path, '/');
        $relative2 = 'storage/' . ltrim($media->path, '/');

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

        $logoMediaId = (int) (Setting::get('site_logo_media_id', '0') ?? 0);
        $settingsLogo = ($logoMediaId > 0) && ((int) $media->id === $logoMediaId);

        return [
            'pages' => $pages,
            'seo_pages' => $seoPages,
            'settings_logo' => $settingsLogo,
        ];
    }
}
