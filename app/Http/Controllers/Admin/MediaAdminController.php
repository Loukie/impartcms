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
        $type = (string) $request->query('type', 'images');
        $q = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'newest');

        // Icons tab is a Font Awesome browser (no DB query)
        if ($type === 'icons') {
            return view('admin.media.index', [
                'media' => MediaFile::query()->whereRaw('1=0')->paginate(1),
                'counts' => [
                    'images' => $this->countImages(clone MediaFile::query()->when($folder !== '', fn($qq) => $qq->where('folder', $folder))->when($q !== '', fn($qq) => $this->applySearch($qq, $q))),
                    'docs' => $this->countDocs(clone MediaFile::query()->when($folder !== '', fn($qq) => $qq->where('folder', $folder))->when($q !== '', fn($qq) => $this->applySearch($qq, $q))),
                ],
                'folders' => MediaFile::query()
                    ->select('folder')
                    ->whereNotNull('folder')
                    ->distinct()
                    ->orderByDesc('folder')
                    ->pluck('folder')
                    ->all(),
                'currentFolder' => $folder,
                'currentType' => 'icons',
                'currentQuery' => $q,
                'currentSort' => $sort,
            ]);
        }

        $base = MediaFile::query();

        if ($folder !== '') {
            $base->where('folder', $folder);
        }

        if ($q !== '') {
            $this->applySearch($base, $q);
        }

        $countsBase = clone $base;
        $counts = [
            'images' => $this->countImages(clone $countsBase),
            'docs' => $this->countDocs(clone $countsBase),
        ];

        // Apply type filter
        $type = $this->applyTypeFilter($base, $type);

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

        return view('admin.media.index', [
            'media' => $base->paginate(30)->withQueryString(),
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
        ]);
    }

    /**
     * Minimal picker view (used inside the modal iframe).
     */
    public function picker(Request $request): View
    {
        $folder = (string) $request->query('folder', '');
        $type = (string) $request->query('type', 'images');
        $q = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'newest');
        $tab = (string) $request->query('tab', 'library');

        // Icons tab is a Font Awesome browser (no DB query)
        if ($type === 'icons') {
            return view('admin.media.picker', [
                'media' => MediaFile::query()->whereRaw('1=0')->paginate(1),
                'counts' => [
                    'images' => $this->countImages(clone MediaFile::query()->when($folder !== '', fn($qq) => $qq->where('folder', $folder))->when($q !== '', fn($qq) => $this->applySearch($qq, $q))),
                    'docs' => $this->countDocs(clone MediaFile::query()->when($folder !== '', fn($qq) => $qq->where('folder', $folder))->when($q !== '', fn($qq) => $this->applySearch($qq, $q))),
                ],
                'folders' => MediaFile::query()
                    ->select('folder')
                    ->whereNotNull('folder')
                    ->distinct()
                    ->orderByDesc('folder')
                    ->pluck('folder')
                    ->all(),
                'currentFolder' => $folder,
                'currentType' => 'icons',
                'currentQuery' => $q,
                'currentSort' => $sort,
                'tab' => in_array($tab, ['library', 'upload'], true) ? $tab : 'library',
            ]);
        }

        $base = MediaFile::query();

        if ($folder !== '') {
            $base->where('folder', $folder);
        }

        if ($q !== '') {
            $this->applySearch($base, $q);
        }

        $countsBase = clone $base;
        $counts = [
            'images' => $this->countImages(clone $countsBase),
            'docs' => $this->countDocs(clone $countsBase),
        ];

        $type = $this->applyTypeFilter($base, $type);

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

        return view('admin.media.picker', [
            'media' => $base->paginate(30)->withQueryString(),
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
            'tab' => in_array($tab, ['library', 'upload'], true) ? $tab : 'library',
        ]);
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

            MediaFile::create([
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'original_name' => $file->getClientOriginalName(),
                'filename' => $filename,
                'path' => $path,
                'mime_type' => $mime,
                'size' => $size,
                'folder' => $folder,
            ]);
        }

        return back()->with('status', 'Uploaded.');
    }

    public function show(MediaFile $media): View
    {
        $usage = $this->findUsage($media);

        return view('admin.media.show', [
            'media' => $media,
            'usage' => $usage,
        ]);
    }

    public function update(Request $request, MediaFile $media): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'alt_text' => ['nullable', 'string', 'max:160'],
        ]);

        $media->title = $validated['title'] ?? $media->title;
        $media->alt_text = $validated['alt_text'] ?? $media->alt_text;
        $media->save();

        return back()->with('status', 'Saved.');
    }

    public function destroy(MediaFile $media): RedirectResponse
    {
        // Protect deletion if referenced in Pages/SEO/Settings
        $usage = $this->findUsage($media);

        if (!empty($usage['settings'])) {
            return back()->withErrors([
                'delete' => 'This file is currently used in Settings (' . implode(', ', $usage['settings']) . '). Remove it there first.',
            ]);
        }

        if ($usage['pages']->count() || $usage['seo_pages']->count()) {
            return back()->withErrors([
                'delete' => 'This file is referenced in content/SEO. Remove references first.',
            ]);
        }

        if ($media->path && Storage::disk('public')->exists($media->path)) {
            Storage::disk('public')->delete($media->path);
        }

        $media->delete();

        return redirect()->route('admin.media.index')->with('status', 'Deleted.');
    }

    private function applySearch($query, string $q): void
    {
        $query->where(function ($qq) use ($q) {
            $qq->where('title', 'like', '%' . $q . '%')
                ->orWhere('original_name', 'like', '%' . $q . '%')
                ->orWhere('filename', 'like', '%' . $q . '%');
        });
    }

    private function applyTypeFilter($query, string $type): string
    {
        if ($type === 'docs') {
            $query->where('mime_type', 'not like', 'image/%');
            return 'docs';
        }

        // Default: images
        $query->where('mime_type', 'like', 'image/%');
        return 'images';
    }

    private function countImages($query): int
    {
        return $query->where('mime_type', 'like', 'image/%')->count();
    }

    private function countDocs($query): int
    {
        return $query->where('mime_type', 'not like', 'image/%')->count();
    }

    private function findUsage(MediaFile $media): array
    {
        $relative = ltrim((string) $media->path, '/');
        $relative2 = ltrim((string) ('storage/' . $relative), '/');

        $pages = Page::withTrashed()
            ->where(function ($q) use ($relative, $relative2) {
                $q->where('featured_image_url', 'like', '%' . $relative . '%')
                    ->orWhere('featured_image_url', 'like', '%' . $relative2 . '%')
                    ->orWhere('body', 'like', '%' . $relative . '%')
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
            : Page::query()
                ->whereIn('id', $seoPageIds)
                ->orderBy('title')
                ->get(['id', 'title', 'slug', 'status', 'deleted_at']);

        $settingsHits = [];

        $logoId = (int) (Setting::get('site_logo_media_id', '0') ?? 0);
        if ($logoId > 0 && $logoId === (int) $media->id) {
            $settingsHits[] = 'site_logo_media_id';
        }

        $faviconId = (int) (Setting::get('site_favicon_media_id', '0') ?? 0);
        if ($faviconId > 0 && $faviconId === (int) $media->id) {
            $settingsHits[] = 'site_favicon_media_id';
        }

        return [
            'pages' => $pages,
            'seo_pages' => $seoPages,
            'settings' => $settingsHits,
        ];
    }
}
