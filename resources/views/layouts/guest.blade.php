<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $siteName = \App\Models\Setting::get('site_name', config('app.name', 'Laravel'));

            // Favicon (guest)
            $faviconPath = \App\Models\Setting::get('site_favicon_path', null);
            $faviconMediaId = (int) (\App\Models\Setting::get('site_favicon_media_id', '0') ?? 0);
            $faviconIconJson = \App\Models\Setting::get('site_favicon_icon_json', null);
            $faviconUrl = null;
            if ($faviconMediaId > 0) {
                $f = \App\Models\MediaFile::query()->whereKey($faviconMediaId)->first();
                if ($f && ($f->isImage() || (is_string($f->mime_type ?? null) && str_starts_with($f->mime_type, 'image/')))) {
                    $faviconUrl = $f->url;
                }
            }
            if (!$faviconUrl && !empty($faviconPath)) {
                $faviconUrl = asset('storage/' . $faviconPath);
            }
            $faviconIconUrl = (empty($faviconUrl) && !empty($faviconIconJson)) ? route('favicon.svg') : null;

            // Auth/login logo (prefer auth settings; fallback to site logo)
            $authLogoMediaId = (int) (\App\Models\Setting::get('auth_logo_media_id', '0') ?? 0);
            $authLogoIconJson = \App\Models\Setting::get('auth_logo_icon_json', null);
            $authLogoSize = (int) (\App\Models\Setting::get('auth_logo_size', '80') ?? 80);
            if ($authLogoSize < 24) $authLogoSize = 24;
            if ($authLogoSize > 256) $authLogoSize = 256;

            $siteLogoMediaId = (int) (\App\Models\Setting::get('site_logo_media_id', '0') ?? 0);
            $siteLogoPath = \App\Models\Setting::get('site_logo_path', null);
            $siteLogoIconJson = \App\Models\Setting::get('site_logo_icon_json', null);

            $logoUrl = null;
            $pickMediaId = $authLogoMediaId > 0 ? $authLogoMediaId : $siteLogoMediaId;
            if ($pickMediaId > 0) {
                $m = \App\Models\MediaFile::query()->whereKey($pickMediaId)->first();
                if ($m && $m->isImage()) {
                    $logoUrl = $m->url;
                }
            }
            if (!$logoUrl && !empty($siteLogoPath)) {
                $logoUrl = asset('storage/' . $siteLogoPath);
            }

            $logoIconHtml = '';
            $pickIconJson = !empty($authLogoIconJson) ? $authLogoIconJson : $siteLogoIconJson;
            if (empty($logoUrl) && !empty($pickIconJson)) {
                $logoIconHtml = \App\Support\IconRenderer::renderHtml($pickIconJson, $authLogoSize, '#6b7280');
            }
        @endphp

        <title>{{ $siteName }}</title>

        @if(!empty($faviconUrl))
            <link rel="icon" href="{{ $faviconUrl }}">
        @elseif(!empty($faviconIconUrl))
            <link rel="icon" type="image/svg+xml" href="{{ $faviconIconUrl }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    @if(!empty($logoUrl))
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }} logo" style="width: {{ $authLogoSize }}px; height: {{ $authLogoSize }}px;" class="object-contain" />
                    @elseif(!empty($logoIconHtml))
                        <span class="inline-flex items-center justify-center" style="width: {{ $authLogoSize }}px; height: {{ $authLogoSize }}px;">{!! $logoIconHtml !!}</span>
                    @else
                        <x-application-logo style="width: {{ $authLogoSize }}px; height: {{ $authLogoSize }}px;" class="fill-current text-gray-500" />
                    @endif
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
