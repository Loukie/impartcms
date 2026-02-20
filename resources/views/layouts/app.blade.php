<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ \App\Models\Setting::get('site_name', config('app.name', 'Laravel')) }}</title>

        @php
            // Favicon (frontend)
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
        @endphp

        @if(!empty($faviconUrl))
            <link rel="icon" href="{{ $faviconUrl }}">
        @elseif(!empty($faviconIconUrl))
            <link rel="icon" type="image/svg+xml" href="{{ $faviconIconUrl }}">
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans antialiased">
        @php
            $adminPrefix = trim(config('cms.admin_path', 'admin'), '/');
            $isAdminPath = request()->is($adminPrefix . '*');

            $showAdminSidebar = $isAdminPath && \Illuminate\Support\Facades\Gate::allows('access-admin');
        @endphp

        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            @if($showAdminSidebar)
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    {{-- IMPORTANT: switch to columns at md (>=768px), not lg --}}
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                        <aside class="md:col-span-3">
                            <div class="md:sticky md:top-6">
                                @include('admin.partials.sidebar')
                            </div>
                        </aside>

                        <div class="md:col-span-9">
                            @isset($header)
                                <header class="bg-white shadow sm:rounded-lg">
                                    <div class="py-6 px-4 sm:px-6 lg:px-8">
                                        {{ $header }}
                                    </div>
                                </header>
                            @endisset

                            <main class="mt-6">
                                {{ $slot }}
                            </main>
                        </div>
                    </div>
                </div>
            @else
                @isset($header)
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main>
                    {{ $slot }}
                </main>
            @endif
        </div>
    </body>
</html>
