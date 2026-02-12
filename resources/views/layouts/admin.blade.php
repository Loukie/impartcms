<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    /**
     * Admin branding
     * - If no logo: show site name text
     * - If logo: show logo-only by default
     * - Optional setting: allow logo + text
     * - Logo can be either an uploaded settings logo OR a Media library item
     */
    $siteName = \App\Models\Setting::get('site_name', config('app.name', 'ImpartCMS'));

    $logoPath = \App\Models\Setting::get('site_logo_path', null);
    $logoMediaId = (int) (\App\Models\Setting::get('site_logo_media_id', '0') ?? 0);
    $showNameWithLogo = (bool) ((int) \App\Models\Setting::get('admin_show_name_with_logo', '0'));

    $logoUrl = null;
    if ($logoMediaId > 0) {
        $m = \App\Models\MediaFile::query()->whereKey($logoMediaId)->first();
        if ($m && $m->isImage()) {
            $logoUrl = $m->url;
        }
    }

    if (!$logoUrl && !empty($logoPath)) {
        $logoUrl = asset('storage/' . $logoPath);
    }

    $hasLogo = !empty($logoUrl);
    $showText = !$hasLogo || $showNameWithLogo;

// Favicon (admin)
$faviconPath = \App\Models\Setting::get('site_favicon_path', null);
$faviconMediaId = (int) (\App\Models\Setting::get('site_favicon_media_id', '0') ?? 0);
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

    $isActive = fn(string $pattern) => request()->routeIs($pattern);
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $siteName }} Admin</title>

    @if(!empty($faviconUrl))
        <link rel="icon" href="{{ $faviconUrl }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
    /* Admin-only: remove Breeze-style max container widths so admin pages feel fuller */
    .admin-shell .max-w-7xl,
    .admin-shell .max-w-6xl,
    .admin-shell .max-w-5xl,
    .admin-shell .max-w-4xl,
    .admin-shell .max-w-3xl,
    .admin-shell .max-w-2xl,
    .admin-shell .max-w-xl,
    .admin-shell .max-w-lg,
    .admin-shell .max-w-md,
    .admin-shell .max-w-sm,
    .admin-shell .max-w-xs,
    .admin-shell .container {
        max-width: none !important;
    }

    .admin-shell .mx-auto {
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
</style>
</head>
<body class="min-h-screen bg-slate-50 admin-shell">
<div class="min-h-screen flex">
    {{-- Sidebar --}}
    <aside class="w-64 bg-slate-950 text-white flex-shrink-0 border-r border-white/5">
        <div class="px-4 py-4 border-b border-white/10">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 {{ $showText ? '' : 'justify-center' }}">
                @if($hasLogo)
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }} logo" class="h-8 w-auto">
                @endif

                @if($showText)
                    <div class="min-w-0">
                        <div class="text-base font-semibold tracking-tight truncate">{{ $siteName }}</div>
                        <div class="text-xs text-white/60 mt-0.5">Admin</div>
                    </div>
                @else
                    <span class="sr-only">{{ $siteName }} Admin</span>
                @endif
            </a>
        </div>

        @php
            $linkBase = 'group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition';
            $linkInactive = 'text-white/80 hover:text-white hover:bg-white/10';
            $linkActive = 'bg-white/10 text-white';
            $iconInactive = 'text-white/60 group-hover:text-white/80';
            $iconActive = 'text-white';
        @endphp

        <nav class="p-3 space-y-1">
            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}" class="{{ $linkBase }} {{ $isActive('dashboard') ? $linkActive : $linkInactive }}">
                <svg class="h-4 w-4 flex-none {{ $isActive('dashboard') ? $iconActive : $iconInactive }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M11.47 2.22a1.5 1.5 0 0 1 1.06 0l7.5 2.75A1.5 1.5 0 0 1 21 6.38v6.82c0 3.6-2.26 6.9-5.64 8.26l-2.35.94a2 2 0 0 1-1.5 0l-2.35-.94C5.76 20.1 3.5 16.8 3.5 13.2V6.38A1.5 1.5 0 0 1 4.47 4.97l7-2.75Z"/>
                </svg>
                <span>Dashboard</span>
            </a>

            {{-- View site: MUST be in sidebar under Dashboard --}}
            <a href="{{ url('/') }}" target="_blank" rel="noopener" class="{{ $linkBase }} {{ $linkInactive }}">
                <svg class="h-4 w-4 flex-none {{ $iconInactive }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M13.5 4.5a1.5 1.5 0 0 0 0 3h1.88l-6.44 6.44a1.5 1.5 0 1 0 2.12 2.12l6.44-6.44V11.5a1.5 1.5 0 0 0 3 0V6a1.5 1.5 0 0 0-1.5-1.5h-5.5Z"/>
                    <path d="M6 6.75A2.25 2.25 0 0 0 3.75 9v9A2.25 2.25 0 0 0 6 20.25h9A2.25 2.25 0 0 0 17.25 18v-4.5a.75.75 0 0 0-1.5 0V18c0 .414-.336.75-.75.75H6A.75.75 0 0 1 5.25 18V9c0-.414.336-.75.75-.75h4.5a.75.75 0 0 0 0-1.5H6Z"/>
                </svg>
                <span>View site</span>
            </a>

            <div class="my-3 border-t border-white/10"></div>

            {{-- Pages --}}
            <a href="{{ route('admin.pages.index') }}" class="{{ $linkBase }} {{ $isActive('admin.pages.*') ? $linkActive : $linkInactive }}">
                <svg class="h-4 w-4 flex-none {{ $isActive('admin.pages.*') ? $iconActive : $iconInactive }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M6 2.25A2.25 2.25 0 0 0 3.75 4.5v15A2.25 2.25 0 0 0 6 21.75h12A2.25 2.25 0 0 0 20.25 19.5v-10.5L14.25 2.25H6Zm7.5 1.56V9h5.19L13.5 3.81Z"/>
                    <path d="M7.5 12a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 7.5 12Zm0 3a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 7.5 15Z"/>
                </svg>
                <span>Pages</span>
            </a>

            {{-- Media --}}
            @if(\Illuminate\Support\Facades\Route::has('admin.media.index'))
                <a href="{{ route('admin.media.index') }}" class="{{ $linkBase }} {{ $isActive('admin.media.*') ? $linkActive : $linkInactive }}">
                    <svg class="h-4 w-4 flex-none {{ $isActive('admin.media.*') ? $iconActive : $iconInactive }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M4.5 5.25A2.25 2.25 0 0 1 6.75 3h10.5A2.25 2.25 0 0 1 19.5 5.25v13.5A2.25 2.25 0 0 1 17.25 21H6.75A2.25 2.25 0 0 1 4.5 18.75V5.25Zm12 3a.75.75 0 1 0-1.5 0 .75.75 0 0 0 1.5 0Zm-9.75 9.69 2.22-2.22 1.66 1.66 4.12-4.12a.75.75 0 0 1 1.06 0l2.19 2.19v3.84c0 .414-.336.75-.75.75H6.75a.75.75 0 0 1-.75-.75v-1.35Z"/>
                    </svg>
                    <span>Media</span>
                </a>
            @endif

            {{-- Users --}}
            @if(\Illuminate\Support\Facades\Route::has('admin.users.index'))
                <a href="{{ route('admin.users.index') }}" class="{{ $linkBase }} {{ $isActive('admin.users.*') ? $linkActive : $linkInactive }}">
                    <svg class="h-4 w-4 flex-none {{ $isActive('admin.users.*') ? $iconActive : $iconInactive }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M16.5 7.5a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/>
                        <path d="M3.75 20.25a7.5 7.5 0 0 1 15 0 .75.75 0 0 1-.75.75H4.5a.75.75 0 0 1-.75-.75Z"/>
                    </svg>
                    <span>Users</span>
                </a>
            @endif

            {{-- Settings --}}
            @if(\Illuminate\Support\Facades\Route::has('admin.settings.edit'))
                <a href="{{ route('admin.settings.edit') }}" class="{{ $linkBase }} {{ $isActive('admin.settings.*') ? $linkActive : $linkInactive }}">
                    <svg class="h-4 w-4 flex-none {{ $isActive('admin.settings.*') ? $iconActive : $iconInactive }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M11.983 1.5a1.5 1.5 0 0 1 1.484 1.28l.17 1.14a7.92 7.92 0 0 1 1.82.75l1.05-.5a1.5 1.5 0 0 1 1.86.53l1.5 2.6a1.5 1.5 0 0 1-.38 1.95l-.93.72c.08.6.08 1.22 0 1.82l.93.72a1.5 1.5 0 0 1 .38 1.95l-1.5 2.6a1.5 1.5 0 0 1-1.86.53l-1.05-.5a7.92 7.92 0 0 1-1.82.75l-.17 1.14A1.5 1.5 0 0 1 11.983 22.5h-3a1.5 1.5 0 0 1-1.484-1.28l-.17-1.14a7.92 7.92 0 0 1-1.82-.75l-1.05.5a1.5 1.5 0 0 1-1.86-.53l-1.5-2.6a1.5 1.5 0 0 1 .38-1.95l.93-.72a7.7 7.7 0 0 1 0-1.82l-.93-.72a1.5 1.5 0 0 1-.38-1.95l1.5-2.6a1.5 1.5 0 0 1 1.86-.53l1.05.5c.58-.3 1.18-.56 1.82-.75l.17-1.14A1.5 1.5 0 0 1 8.983 1.5h3Zm-1.5 7.5a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z"/>
                    </svg>
                    <span>Settings</span>
                </a>
            @endif
        </nav>
    </aside>

    {{-- Main --}}
    <div class="flex-1 min-w-0">
        {{-- Top bar --}}
        <header class="bg-white/80 backdrop-blur border-b border-slate-200">
            <div class="px-6 py-4 flex items-center justify-between">
                <div class="text-sm text-slate-600">
                    Logged in as <span class="font-semibold text-slate-900">{{ Auth::user()->name }}</span>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-slate-700 hover:text-slate-900">
                        Log out
                    </button>
                </form>
            </div>

            @isset($header)
                <div class="px-6 pb-4">
                    {{ $header }}
                </div>
            @endisset
        </header>

        <main class="px-6 py-6">
            {{ $slot }}
        </main>
    </div>
</div>

{{-- Global Media Picker Modal (WordPress-style) --}}
<div id="impart-media-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60" data-impart-media-close></div>

    <div class="relative mx-auto mt-4 w-[95vw] max-w-[1440px] h-[92vh] bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200">
            <div class="text-sm font-semibold text-slate-900">Select media</div>
            <button type="button" class="text-sm font-semibold text-slate-700 hover:text-slate-900" data-impart-media-close>
                Cancel
            </button>
        </div>
        <iframe id="impart-media-iframe" class="flex-1 w-full" src="about:blank"></iframe>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('impart-media-modal');
    const iframe = document.getElementById('impart-media-iframe');

    let onSelect = null;

    function closeModal() {
        modal.classList.add('hidden');
        iframe.src = 'about:blank';
        onSelect = null;
    }

    function openModal(opts) {
        const url = (opts && opts.url) ? opts.url : "{{ route('admin.media.index') }}?picker=1";
        onSelect = (opts && typeof opts.onSelect === 'function') ? opts.onSelect : null;

        iframe.src = url;
        modal.classList.remove('hidden');
    }

    window.ImpartMediaPicker = {
        open: openModal,
        close: closeModal,
    };

    modal.addEventListener('click', (e) => {
        if (e.target && e.target.hasAttribute('data-impart-media-close')) closeModal();
    });

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });

    window.addEventListener('message', (event) => {
        try {
            if (event.origin !== window.location.origin) return;
            const data = event.data || {};
            if (data.type === 'impartcms-media-cancel') {
                closeModal();
                return;
            }
            if (data.type !== 'impartcms-media-selected') return;
            if (onSelect) onSelect(data);
            closeModal();
        } catch (e) {
            // ignore
        }
    });
})();
</script>
</body>
</html>
