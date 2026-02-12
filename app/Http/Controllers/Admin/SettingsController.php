<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function edit()
    {
        $fallbackHomepageId = (int) (Page::query()
            ->where('is_homepage', true)
            ->where('status', 'published')
            ->value('id') ?? 0);

        // Logo (uploaded OR Media library)
        $logoMediaId = (int) (Setting::get('site_logo_media_id', '0') ?? 0);
        $logoMedia = $logoMediaId > 0
            ? MediaFile::query()->whereKey($logoMediaId)->first()
            : null;

        // Favicon (uploaded OR Media library)
        $faviconMediaId = (int) (Setting::get('site_favicon_media_id', '0') ?? 0);
        $faviconMedia = $faviconMediaId > 0
            ? MediaFile::query()->whereKey($faviconMediaId)->first()
            : null;

        return view('admin.settings.edit', [
            'siteName' => Setting::get('site_name', config('app.name')),
            'showNameWithLogo' => (bool) ((int) Setting::get('admin_show_name_with_logo', '0')),

            'logoPath' => Setting::get('site_logo_path', null),
            'logoMediaId' => $logoMedia?->id,
            'logoMediaUrl' => ($logoMedia && (is_string($logoMedia->mime_type ?? null) && str_starts_with($logoMedia->mime_type, 'image/')))
                ? $logoMedia->url
                : null,

            'faviconPath' => Setting::get('site_favicon_path', null),
            'faviconMediaId' => $faviconMedia?->id,
            'faviconMediaUrl' => ($faviconMedia && (is_string($faviconMedia->mime_type ?? null) && str_starts_with($faviconMedia->mime_type, 'image/')))
                ? $faviconMedia->url
                : null,

            'homepagePageId' => (int) (Setting::get('homepage_page_id', $fallbackHomepageId) ?? 0),
            'homepagePages' => Page::query()
                ->where('status', 'published')
                ->orderBy('title')
                ->get(['id', 'title', 'slug']),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],

            // Uploaded logo (optional)
            'site_logo' => ['nullable', 'file', 'max:2048', 'mimes:jpg,jpeg,png,gif,webp,avif,svg'],
            'site_logo_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'remove_logo' => ['nullable', 'boolean'],

            // Uploaded favicon (optional)
            'site_favicon' => ['nullable', 'file', 'max:1024', 'mimes:ico,png,svg,jpg,jpeg,webp'],
            'site_favicon_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'remove_favicon' => ['nullable', 'boolean'],

            'admin_show_name_with_logo' => ['nullable', 'boolean'],
            'homepage_page_id' => ['nullable', 'integer', 'exists:pages,id'],
        ]);

        Setting::set('site_name', $validated['site_name']);

        $showNameWithLogo = (bool)($validated['admin_show_name_with_logo'] ?? false);
        Setting::set('admin_show_name_with_logo', $showNameWithLogo ? '1' : '0');

        // Homepage selection (published pages only)
        $homepageId = (int) ($validated['homepage_page_id'] ?? 0);
        if ($homepageId > 0) {
            $page = Page::query()->whereKey($homepageId)->first();
            if (!$page || $page->status !== 'published' || $page->trashed()) {
                return back()->withErrors([
                    'homepage_page_id' => 'Homepage must be a published (non-trashed) page.',
                ])->withInput();
            }

            // Clear existing homepage flags everywhere (including trashed) to keep it single.
            Page::withTrashed()->where('id', '!=', $page->id)->update(['is_homepage' => false]);
            $page->is_homepage = true;
            $page->save();

            Setting::set('homepage_page_id', (string) $page->id);
        }

        // Remove logo (only clears setting; never deletes a Media library item)
        if ((bool)($validated['remove_logo'] ?? false)) {
            $existing = Setting::get('site_logo_path', null);
            if ($existing && str_starts_with((string) $existing, 'settings/')) {
                Storage::disk('public')->delete($existing);
            }
            Setting::set('site_logo_path', null);
            Setting::set('site_logo_media_id', '0');
        }

        // Remove favicon (only clears setting; never deletes a Media library item)
        if ((bool)($validated['remove_favicon'] ?? false)) {
            $existing = Setting::get('site_favicon_path', null);
            if ($existing && str_starts_with((string) $existing, 'settings/')) {
                Storage::disk('public')->delete($existing);
            }
            Setting::set('site_favicon_path', null);
            Setting::set('site_favicon_media_id', '0');
        }

        // Logo selected from Media
        $selectedLogoMediaId = (int) ($validated['site_logo_media_id'] ?? 0);
        if ($selectedLogoMediaId > 0) {
            $media = MediaFile::query()->whereKey($selectedLogoMediaId)->first();
            $isImage = $media && (is_string($media->mime_type ?? null) && str_starts_with($media->mime_type, 'image/'));
            if (!$isImage) {
                return back()->withErrors([
                    'site_logo_media_id' => 'Please select a valid image from Media.',
                ])->withInput();
            }

            Setting::set('site_logo_path', null);
            Setting::set('site_logo_media_id', (string) $media->id);
        }

        // Favicon selected from Media
        $selectedFaviconMediaId = (int) ($validated['site_favicon_media_id'] ?? 0);
        if ($selectedFaviconMediaId > 0) {
            $media = MediaFile::query()->whereKey($selectedFaviconMediaId)->first();
            $isImage = $media && (is_string($media->mime_type ?? null) && str_starts_with($media->mime_type, 'image/'));
            if (!$isImage) {
                return back()->withErrors([
                    'site_favicon_media_id' => 'Please select a valid favicon image (ICO/PNG/SVG) from Media.',
                ])->withInput();
            }

            Setting::set('site_favicon_path', null);
            Setting::set('site_favicon_media_id', (string) $media->id);
        }

        // Uploaded logo (overrides Media-selected logo)
        if ($request->hasFile('site_logo')) {
            $existing = Setting::get('site_logo_path', null);
            if ($existing && str_starts_with((string) $existing, 'settings/')) {
                Storage::disk('public')->delete($existing);
            }

            $path = $request->file('site_logo')->store('settings', 'public');
            Setting::set('site_logo_path', $path);
            Setting::set('site_logo_media_id', '0');
        }

        // Uploaded favicon (overrides Media-selected favicon)
        if ($request->hasFile('site_favicon')) {
            $existing = Setting::get('site_favicon_path', null);
            if ($existing && str_starts_with((string) $existing, 'settings/')) {
                Storage::disk('public')->delete($existing);
            }

            $path = $request->file('site_favicon')->store('settings', 'public');
            Setting::set('site_favicon_path', $path);
            Setting::set('site_favicon_media_id', '0');
        }

        return back()->with('status', 'Settings updated.');
    }
}
