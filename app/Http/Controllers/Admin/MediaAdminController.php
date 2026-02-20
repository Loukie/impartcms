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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MediaAdminController extends Controller
{
    private array $iconExts = ['svg', 'ico'];
    private array $fontExts = ['woff2', 'woff', 'ttf', 'otf', 'eot'];

    public function index(Request $request): View
    {
        $folder = (string) $request->query('folder', '');
        $type = (string) $request->query('type', '');
        $q = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'newest');

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

        // WordPress-style counts (reflect current search + folder filter)
        $countsBase = clone $base;

        $counts = [
            'all' => (clone $countsBase)->count(),
            'images' => $this->countImages(clone $countsBase),
            'icons' => $this->countIcons(clone $countsBase),
            'fonts' => $this->countFonts(clone $countsBase),
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
     * Route should point to this: GET /admin/media/picker  -> name admin.media.picker
     */
    public function picker(Request $request): View
    {
        $folder = (string) $request->query('folder', '');
        $type = (string) $request->query('type', '');
        $q = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'newest');
        $tab = (string) $request->query('tab', 'library');

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

        $countsBase = clone $base;
        $counts = [
            'all' => (clone $countsBase)->count(),
            'images' => $this->countImages(clone $countsBase),
            'icons' => $this->countIcons(clone $countsBase),
            'fonts' => $this->countFonts(clone $countsBase),
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
            $width = null;
            $height = null;

            // Best-effort dimensions for raster images.
            if (is_string($mime) && str_starts_with($mime, 'image/')) {
                try {
                    $full = Storage::disk($disk)->path($path);
                    $info = @getimagesize($full);
                    if (is_array($info)) {
                        $width = (int) ($info[0] ?? 0) ?: null;
                        $height = (int) ($info[1] ?? 0) ?: null;
                    }
                } catch (\Throwable $e) {
                    // Ignore; still save record.
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

        $pagesUsed = ($usage['pages'] ?? collect())->isNotEmpty();
        $seoUsed = ($usage['seo_pages'] ?? collect())->isNotEmpty();
        $settingsUsed = !empty($usage['settings'] ?? []);

        if ($pagesUsed || $seoUsed || $settingsUsed) {
            return back()->withErrors([
                'status' => 'This file appears to be in use. Remove it from pages/settings first, then delete.',
            ]);
        }

        Storage::disk($media->disk ?? 'public')->delete($media->path);
        $media->delete();

        return redirect()->route('admin.media.index')->with('status', 'Media deleted.');
    }

    /**
     * Best-effort “Where used” detection for now.
     * - Scans Page.body and SeoMeta OG/Twitter image URLs.
     * - Also checks Settings logo/favicon/login-logo media ids.
     */
        private function detectUsage(MediaFile $media): array
    {
        $empty = [
            'pages' => collect(),
            'seo_pages' => collect(),
            'settings' => [],
        ];

        try {
            if (!Schema::hasTable('pages')) {
                return $empty;
            }

            $columns = Schema::getColumnListing('pages');

            // Try to find a sensible "body/content" column to scan.
            $bodyCol = null;
            foreach (['body', 'content', 'content_html', 'html'] as $c) {
                if (in_array($c, $columns, true)) {
                    $bodyCol = $c;
                    break;
                }
            }

            $selectCols = [];
            foreach (['id', 'title', 'slug', 'status', 'updated_at'] as $c) {
                if (in_array($c, $columns, true)) {
                    $selectCols[] = $c;
                }
            }
            if (in_array('deleted_at', $columns, true)) {
                $selectCols[] = 'deleted_at';
            }
            if (empty($selectCols)) {
                $selectCols = ['id'];
            }

            $needle = $media->url;
            $like = '%' . $needle . '%';

            // Pages: find usage in body/content column (if present)
            $pages = collect();
            if ($bodyCol) {
                try {
                    $q = Page::query()->select($selectCols)
                        ->where($bodyCol, 'like', $like);

                    if (in_array('title', $columns, true)) {
                        $q->orderBy('title');
                    } else {
                        $q->orderBy($selectCols[0]);
                    }

                    $pages = $q->limit(200)->get();
                } catch (\Throwable $e) {
                    $pages = collect();
                }
            }

            // SEO usage: only if likely columns exist
            $seoPages = collect();
            $seoCols = [];
            foreach (['og_image', 'twitter_image', 'featured_image', 'meta_image', 'seo', 'meta', 'schema_json'] as $c) {
                if (in_array($c, $columns, true)) {
                    $seoCols[] = $c;
                }
            }
            if (!empty($seoCols)) {
                try {
                    $q = Page::query()->select($selectCols);
                    $q->where(function ($qq) use ($seoCols, $like) {
                        foreach ($seoCols as $c) {
                            $qq->orWhere($c, 'like', $like);
                        }
                    });

                    if (in_array('title', $columns, true)) {
                        $q->orderBy('title');
                    } else {
                        $q->orderBy($selectCols[0]);
                    }

                    $seoPages = $q->limit(200)->get();
                } catch (\Throwable $e) {
                    $seoPages = collect();
                }
            }

            // Settings usage
            $settings = [];
            try {
                // 1) URL/string references
                $settings = Setting::query()
                    ->where('value', 'like', $like)
                    ->pluck('key')
                    ->all();

                // 2) Known media-id settings
                $idKeys = ['site_logo_media_id', 'site_favicon_media_id', 'auth_logo_media_id'];
                $idHits = Setting::query()
                    ->whereIn('key', $idKeys)
                    ->where('value', (string) $media->id)
                    ->pluck('key')
                    ->all();

                $settings = array_values(array_unique(array_merge($settings, $idHits)));
            } catch (\Throwable $e) {
                $settings = $settings ?? [];
            }

            return [
                'pages' => $pages,
                'seo_pages' => $seoPages,
                'settings' => $settings,
            ];
        } catch (\Throwable $e) {
            return $empty;
        }
    }


    private function applyTypeFilter($query, string $type): string
    {
        if ($type === 'images') {
            $query->where('mime_type', 'like', 'image/%')
                ->where(function ($qq) {
                    foreach ($this->iconExts as $ext) {
                        $qq->where('filename', 'not like', '%.' . $ext);
                    }
                });
            return 'images';
        }

        if ($type === 'icons') {
            $query->where(function ($qq) {
                foreach ($this->iconExts as $ext) {
                    $qq->orWhere('filename', 'like', '%.' . $ext);
                }
            });
            return 'icons';
        }

        if ($type === 'fonts') {
            $query->where(function ($qq) {
                foreach ($this->fontExts as $ext) {
                    $qq->orWhere('filename', 'like', '%.' . $ext);
                }
            });
            return 'fonts';
        }

        if ($type === 'docs') {
            $query->where('mime_type', 'not like', 'image/%')
                ->where(function ($qq) {
                    foreach ($this->fontExts as $ext) {
                        $qq->where('filename', 'not like', '%.' . $ext);
                    }
                });
            return 'docs';
        }

        return '';
    }

    private function countIcons($query): int
    {
        return $query->where(function ($qq) {
            foreach ($this->iconExts as $ext) {
                $qq->orWhere('filename', 'like', '%.' . $ext);
            }
        })->count();
    }

    private function countFonts($query): int
    {
        return $query->where(function ($qq) {
            foreach ($this->fontExts as $ext) {
                $qq->orWhere('filename', 'like', '%.' . $ext);
            }
        })->count();
    }

    private function countImages($query): int
    {
        return $query->where('mime_type', 'like', 'image/%')
            ->where(function ($qq) {
                foreach ($this->iconExts as $ext) {
                    $qq->where('filename', 'not like', '%.' . $ext);
                }
            })
            ->count();
    }

    private function countDocs($query): int
    {
        return $query->where('mime_type', 'not like', 'image/%')
            ->where(function ($qq) {
                foreach ($this->fontExts as $ext) {
                    $qq->where('filename', 'not like', '%.' . $ext);
                }
            })
            ->count();
    }
}
