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

        // Logo (legacy uploaded path OR Media library)
        $logoMediaId = (int) (Setting::get('site_logo_media_id', '0') ?? 0);
        $logoMedia = $logoMediaId > 0
            ? MediaFile::query()->whereKey($logoMediaId)->first()
            : null;

        // Logo (icon JSON from icon picker)
        $logoIconJson = Setting::get('site_logo_icon_json', null);

        // Favicon (legacy uploaded path OR Media library)
        $faviconMediaId = (int) (Setting::get('site_favicon_media_id', '0') ?? 0);
        $faviconMedia = $faviconMediaId > 0
            ? MediaFile::query()->whereKey($faviconMediaId)->first()
            : null;

        // Favicon (icon JSON from icon picker)
        $faviconIconJson = Setting::get('site_favicon_icon_json', null);

        // Login screen logo (media or icon). If not set, UI will fallback to site logo.
        $authLogoMediaId = (int) (Setting::get('auth_logo_media_id', '0') ?? 0);
        $authLogoMedia = $authLogoMediaId > 0
            ? MediaFile::query()->whereKey($authLogoMediaId)->first()
            : null;
        $authLogoIconJson = Setting::get('auth_logo_icon_json', null);

        $authLogoSize = (int) (Setting::get('auth_logo_size', '80') ?? 80);
        if ($authLogoSize < 24) $authLogoSize = 24;
                // Site notice bar styling
        $noticeHeight = (int) (Setting::get('notice_height', '44') ?? 44);
        if ($noticeHeight < 24) $noticeHeight = 24;
        if ($noticeHeight > 200) $noticeHeight = 200;
        $noticeBgColour = (string) (Setting::get('notice_bg_colour', '#111827') ?? '#111827');
        // Normalise basic hex formats (fallback to default if invalid)
        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $noticeBgColour)) {
            $noticeBgColour = '#111827';
        }

if ($authLogoSize > 256) $authLogoSize = 256;

        return view('admin.settings.edit', [
            'siteName' => Setting::get('site_name', config('app.name')),
            'showNameWithLogo' => (bool) ((int) Setting::get('admin_show_name_with_logo', '0')),

            // Legacy values still supported for backwards compatibility (read-only unless cleared)
            'logoPath' => Setting::get('site_logo_path', null),
            'logoMediaId' => $logoMedia?->id,
            'logoMediaUrl' => ($logoMedia && (is_string($logoMedia->mime_type ?? null) && str_starts_with($logoMedia->mime_type, 'image/')))
                ? $logoMedia->url
                : null,
            'logoIconJson' => $logoIconJson,

            'faviconPath' => Setting::get('site_favicon_path', null),
            'faviconMediaId' => $faviconMedia?->id,
            'faviconMediaUrl' => ($faviconMedia && (is_string($faviconMedia->mime_type ?? null) && str_starts_with($faviconMedia->mime_type, 'image/')))
                ? $faviconMedia->url
                : null,
            'faviconIconJson' => $faviconIconJson,

            'authLogoMediaId' => $authLogoMedia?->id,
            'authLogoMediaUrl' => ($authLogoMedia && (is_string($authLogoMedia->mime_type ?? null) && str_starts_with($authLogoMedia->mime_type, 'image/')))
                ? $authLogoMedia->url
                : null,
            'authLogoIconJson' => $authLogoIconJson,
            'authLogoSize' => $authLogoSize,

            'homepagePageId' => (int) (Setting::get('homepage_page_id', $fallbackHomepageId) ?? 0),
            'homepagePages' => Page::query()
                ->where('status', 'published')
                ->orderBy('title')
                ->get(['id', 'title', 'slug']),

            'maintenanceEnabled' => (bool) ((int) (Setting::get('maintenance_enabled', '0') ?? 0)),
            'maintenancePageId' => (int) (Setting::get('maintenance_page_id', 0) ?? 0),
            'maintenancePages' => Page::query()
                ->where('status', 'published')
                ->orderBy('title')
                ->get(['id', 'title', 'slug']),

            // Site-wide notification bar
            'noticeEnabled' => (bool) ((int) (Setting::get('notice_enabled', '0') ?? 0)),
            'noticeMode' => (string) (Setting::get('notice_mode', 'text') ?? 'text'),
            'noticeText' => (string) (Setting::get('notice_text', '') ?? ''),
            'noticeHtml' => (string) (Setting::get('notice_html', '') ?? ''),
            'noticeLinkText' => (string) (Setting::get('notice_link_text', '') ?? ''),
            'noticeLinkUrl' => (string) (Setting::get('notice_link_url', '') ?? ''),
            'noticeBgColour' => $noticeBgColour,
            'noticeHeight' => $noticeHeight,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],

            // Media library selections
            'site_logo_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'site_logo_icon_json' => ['nullable', 'string', 'max:20000'],
            'site_logo_clear' => ['nullable', 'boolean'],

            'site_favicon_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'site_favicon_icon_json' => ['nullable', 'string', 'max:20000'],
            'site_favicon_clear' => ['nullable', 'boolean'],

            'auth_logo_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'auth_logo_icon_json' => ['nullable', 'string', 'max:20000'],
            'auth_logo_clear' => ['nullable', 'boolean'],

            'auth_logo_size' => ['nullable', 'integer', 'min:24', 'max:256'],

            'admin_show_name_with_logo' => ['nullable', 'boolean'],
            'homepage_page_id' => ['nullable', 'integer', 'exists:pages,id'],

            'maintenance_enabled' => ['nullable', 'boolean'],
            'maintenance_page_id' => ['nullable', 'integer', 'exists:pages,id'],

            // Site notice
            'notice_enabled' => ['nullable', 'boolean'],
            'notice_mode' => ['nullable', 'string', 'in:text,html'],
            'notice_text' => ['nullable', 'string', 'max:5000'],
            'notice_html' => ['nullable', 'string'],
            'notice_link_text' => ['nullable', 'string', 'max:120'],
            'notice_link_url' => ['nullable', 'string', 'max:2000'],
            'notice_bg_colour' => ['nullable', 'string', 'max:20', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'notice_height' => ['nullable', 'integer', 'min:24', 'max:200'],
        ]);

        Setting::set('site_name', $validated['site_name']);

        $showNameWithLogo = (bool)($validated['admin_show_name_with_logo'] ?? false);
        Setting::set('admin_show_name_with_logo', $showNameWithLogo ? '1' : '0');

        $authLogoSize = (int) ($validated['auth_logo_size'] ?? (int) (Setting::get('auth_logo_size', '80') ?? 80));
        if ($authLogoSize < 24) $authLogoSize = 24;
        if ($authLogoSize > 256) $authLogoSize = 256;
        Setting::set('auth_logo_size', (string) $authLogoSize);

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

        // Maintenance mode (published page required when enabled)
        $wasMaintenanceEnabled = ((string) (Setting::get('maintenance_enabled', '0') ?? '0')) === '1';
        $maintenanceEnabled = (bool) ($validated['maintenance_enabled'] ?? false);
        $maintenancePageId = (int) ($validated['maintenance_page_id'] ?? 0);

        if ($maintenanceEnabled && $maintenancePageId <= 0) {
            return back()->withErrors([
                'maintenance_page_id' => 'Please select a published page to use for maintenance mode.',
            ])->withInput();
        }

        if ($maintenancePageId > 0) {
            $mPage = Page::query()->whereKey($maintenancePageId)->first();
            if (!$mPage || $mPage->status !== 'published' || $mPage->trashed()) {
                return back()->withErrors([
                    'maintenance_page_id' => 'Maintenance page must be a published (non-trashed) page.',
                ])->withInput();
            }
        }

        // If maintenance is enabled, keep the URL clean by serving the maintenance page at '/'
        // (i.e. temporarily set it as the homepage). When disabled, restore the previous homepage.
        $backupHomepageId = (int) (Setting::get('maintenance_homepage_backup_id', '0') ?? 0);
        $currentHomepageId = (int) (Setting::get('homepage_page_id', '0') ?? 0);
        if ($currentHomepageId <= 0) {
            $currentHomepageId = (int) (Page::withTrashed()->where('is_homepage', true)->value('id') ?? 0);
        }

        // Transition: OFF -> ON (capture backup once)
        if ($maintenanceEnabled && !$wasMaintenanceEnabled) {
            if ($backupHomepageId <= 0 && $currentHomepageId > 0 && $currentHomepageId !== $maintenancePageId) {
                Setting::set('maintenance_homepage_backup_id', (string) $currentHomepageId);
            }
        }

        // While enabled, ensure the selected maintenance page is the homepage
        if ($maintenanceEnabled && $maintenancePageId > 0) {
            $mPage = Page::query()->whereKey($maintenancePageId)->first();
            if ($mPage && $mPage->status === 'published' && !$mPage->trashed()) {
                Page::withTrashed()->where('id', '!=', $mPage->id)->update(['is_homepage' => false]);
                $mPage->is_homepage = true;
                $mPage->save();
                Setting::set('homepage_page_id', (string) $mPage->id);
            }
        }

        // Transition: ON -> OFF (restore)
        if (!$maintenanceEnabled && $wasMaintenanceEnabled) {
            $restoreId = (int) (Setting::get('maintenance_homepage_backup_id', '0') ?? 0);
            if ($restoreId > 0) {
                $restore = Page::query()->whereKey($restoreId)->first();
                if ($restore && $restore->status === 'published' && !$restore->trashed()) {
                    Page::withTrashed()->where('id', '!=', $restore->id)->update(['is_homepage' => false]);
                    $restore->is_homepage = true;
                    $restore->save();
                    Setting::set('homepage_page_id', (string) $restore->id);
                }
            }
            Setting::set('maintenance_homepage_backup_id', '0');
        }

        Setting::set('maintenance_page_id', (string) $maintenancePageId);
        Setting::set('maintenance_enabled', $maintenanceEnabled ? '1' : '0');

        // Site-wide notification bar
        $noticeEnabled = (bool) ($validated['notice_enabled'] ?? false);
        $noticeMode = (string) ($validated['notice_mode'] ?? 'text');
        if (!in_array($noticeMode, ['text', 'html'], true)) $noticeMode = 'text';

        Setting::set('notice_enabled', $noticeEnabled ? '1' : '0');
        Setting::set('notice_mode', $noticeMode);
        Setting::set('notice_text', (string) ($validated['notice_text'] ?? ''));
        Setting::set('notice_html', (string) ($validated['notice_html'] ?? ''));
        Setting::set('notice_link_text', (string) ($validated['notice_link_text'] ?? ''));
        Setting::set('notice_link_url', (string) ($validated['notice_link_url'] ?? ''));
        $noticeHeight = (int) ($validated['notice_height'] ?? (int) (Setting::get('notice_height', '44') ?? 44));
        if ($noticeHeight < 24) $noticeHeight = 24;
        if ($noticeHeight > 200) $noticeHeight = 200;
        $noticeBgColour = (string) ($validated['notice_bg_colour'] ?? (string) (Setting::get('notice_bg_colour', '#111827') ?? '#111827'));
        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $noticeBgColour)) {
            $noticeBgColour = '#111827';
        }
        Setting::set('notice_height', (string) $noticeHeight);
        Setting::set('notice_bg_colour', $noticeBgColour);

        // Explicit clears (settings only — never deletes a Media library item)
        if ((bool)($validated['site_logo_clear'] ?? false)) {
            $existing = Setting::get('site_logo_path', null);
            if ($existing && str_starts_with((string) $existing, 'settings/')) {
                Storage::disk('public')->delete($existing);
            }
            Setting::set('site_logo_path', null);
            Setting::set('site_logo_media_id', '0');
            Setting::set('site_logo_icon_json', null);
        }

        if ((bool)($validated['site_favicon_clear'] ?? false)) {
            $existing = Setting::get('site_favicon_path', null);
            if ($existing && str_starts_with((string) $existing, 'settings/')) {
                Storage::disk('public')->delete($existing);
            }
            Setting::set('site_favicon_path', null);
            Setting::set('site_favicon_media_id', '0');
            Setting::set('site_favicon_icon_json', null);
        }

        if ((bool)($validated['auth_logo_clear'] ?? false)) {
            Setting::set('auth_logo_media_id', '0');
            Setting::set('auth_logo_icon_json', null);
        }

        // Media selections (override legacy uploaded paths)
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
            Setting::set('site_logo_icon_json', null);
        }

        // Icon selection (overrides media)
        $logoIconJson = trim((string) ($validated['site_logo_icon_json'] ?? ''));
        if ($logoIconJson !== '') {
            $sanitised = $this->sanitiseIconJson($logoIconJson);
            if ($sanitised === null) {
                return back()->withErrors([
                    'site_logo_icon_json' => 'Please select a valid icon.',
                ])->withInput();
            }
            Setting::set('site_logo_icon_json', $sanitised);
            Setting::set('site_logo_path', null);
            Setting::set('site_logo_media_id', '0');
        }

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
            Setting::set('site_favicon_icon_json', null);
        }

        $faviconIconJson = trim((string) ($validated['site_favicon_icon_json'] ?? ''));
        if ($faviconIconJson !== '') {
            $sanitised = $this->sanitiseIconJson($faviconIconJson);
            if ($sanitised === null) {
                return back()->withErrors([
                    'site_favicon_icon_json' => 'Please select a valid icon.',
                ])->withInput();
            }
            Setting::set('site_favicon_icon_json', $sanitised);
            Setting::set('site_favicon_path', null);
            Setting::set('site_favicon_media_id', '0');
        }

        // Login screen logo
        $authLogoMediaId = (int) ($validated['auth_logo_media_id'] ?? 0);
        if ($authLogoMediaId > 0) {
            $media = MediaFile::query()->whereKey($authLogoMediaId)->first();
            $isImage = $media && (is_string($media->mime_type ?? null) && str_starts_with($media->mime_type, 'image/'));
            if (!$isImage) {
                return back()->withErrors([
                    'auth_logo_media_id' => 'Please select a valid image from Media.',
                ])->withInput();
            }

            Setting::set('auth_logo_media_id', (string) $media->id);
            Setting::set('auth_logo_icon_json', null);
        }

        $authLogoIconJson = trim((string) ($validated['auth_logo_icon_json'] ?? ''));
        if ($authLogoIconJson !== '') {
            $sanitised = $this->sanitiseIconJson($authLogoIconJson);
            if ($sanitised === null) {
                return back()->withErrors([
                    'auth_logo_icon_json' => 'Please select a valid icon.',
                ])->withInput();
            }
            Setting::set('auth_logo_icon_json', $sanitised);
            Setting::set('auth_logo_media_id', '0');
        }

        return back()->with('status', 'Settings updated.');
    }

    /**
     * Accepts the icon picker JSON string, validates a safe subset and returns a normalised JSON string.
     */
    private function sanitiseIconJson(string $json): ?string
    {
        $raw = trim($json);
        if ($raw === '') return null;

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded)) return null;

            $kind = strtolower((string) ($decoded['kind'] ?? ''));
            if (!in_array($kind, ['fa', 'lucide'], true)) return null;

            $value = (string) ($decoded['value'] ?? '');
            if ($value === '') return null;

            // Validate value format
            if ($kind === 'fa') {
                if (!preg_match('/^[a-z0-9\s\-]+$/i', $value)) return null;
            } else {
                if (!preg_match('/^[a-z0-9\-]+$/', $value)) return null;
            }

            $size = isset($decoded['size']) ? (int) $decoded['size'] : 24;
            if ($size < 8) $size = 8;
            if ($size > 256) $size = 256;

            $colour = (string) ($decoded['colour'] ?? $decoded['color'] ?? '#111827');
            if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', trim($colour))) {
                $colour = '#111827';
            }

            $out = [
                'kind' => $kind,
                'value' => $value,
                'size' => $size,
                'colour' => $colour,
            ];

            // Preserve svg if provided (portable FA)
            if ($kind === 'fa' && isset($decoded['svg']) && is_string($decoded['svg'])) {
                $svg = trim($decoded['svg']);
                if ($svg !== '' && str_starts_with($svg, '<svg')) {
                    // store as-is; renderer sanitises
                    $out['svg'] = $svg;
                }
            }

            // Optional metadata
            foreach (['name', 'style', 'shortcode'] as $k) {
                if (isset($decoded[$k]) && is_string($decoded[$k])) {
                    $out[$k] = $decoded[$k];
                }
            }

            return json_encode($out, JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
