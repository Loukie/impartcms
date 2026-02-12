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

        $logoMediaId = (int) (Setting::get('site_logo_media_id', '0') ?? 0);
        $logoMedia = $logoMediaId > 0
            ? MediaFile::query()->whereKey($logoMediaId)->where('mime_type', 'like', 'image/%')->first()
            : null;

        return view('admin.settings.edit', [
            'siteName' => Setting::get('site_name', config('app.name')),
            'logoPath' => Setting::get('site_logo_path', null),
            'logoMediaId' => $logoMedia?->id,
            'logoMediaUrl' => $logoMedia?->url,
            'showNameWithLogo' => (bool) ((int) Setting::get('admin_show_name_with_logo', '0')),
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
            'site_logo' => ['nullable', 'image', 'max:2048'], // 2MB
            'site_logo_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'remove_logo' => ['nullable', 'boolean'],
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

        // Remove logo is explicit: clears both uploaded-logo + media-logo references.
        $removeLogo = (bool)($validated['remove_logo'] ?? false);
        if ($removeLogo) {
            $existing = Setting::get('site_logo_path', null);
            if ($existing && str_starts_with((string) $existing, 'settings/')) {
                Storage::disk('public')->delete($existing);
            }

            Setting::set('site_logo_path', null);
            Setting::set('site_logo_media_id', '0');

            return back()->with('status', 'Settings updated.');
        }

        // If a Media Library image is selected, use it as the logo.
        // This does NOT delete any Media file when removed from Settings.
        $selectedMediaId = (int) ($validated['site_logo_media_id'] ?? 0);
        if ($selectedMediaId > 0) {
            $media = MediaFile::query()->whereKey($selectedMediaId)->first();
            if (!$media || !$media->isImage()) {
                return back()->withErrors([
                    'site_logo_media_id' => 'Please select a valid image from Media.',
                ])->withInput();
            }

            // Clear uploaded logo path so we have a single source of truth.
            Setting::set('site_logo_path', null);
            Setting::set('site_logo_media_id', (string) $media->id);
        }

        // Uploaded logo overrides any Media-selected logo.
        if ($request->hasFile('site_logo')) {
            $existing = Setting::get('site_logo_path', null);
            if ($existing && str_starts_with((string) $existing, 'settings/')) {
                Storage::disk('public')->delete($existing);
            }

            $path = $request->file('site_logo')->store('settings', 'public');
            Setting::set('site_logo_path', $path);
            Setting::set('site_logo_media_id', '0');
        }

        return back()->with('status', 'Settings updated.');
    }
}
