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
    $logoIconJson = \App\Models\Setting::get('site_logo_icon_json', null);
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

    // Optional icon logo (fallback when no image)
    $logoIconHtml = '';
    if (empty($logoUrl) && !empty($logoIconJson)) {
        $j = $logoIconJson;
        try {
            $arr = json_decode((string) $logoIconJson, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($arr)) {
                // If user kept default dark colour, make it visible on dark admin sidebar.
                if (($arr['colour'] ?? '') === '#111827') {
                    $arr['colour'] = '#ffffff';
                }
                $j = json_encode($arr, JSON_UNESCAPED_SLASHES);
            }
        } catch (\Throwable $e) {
            $j = $logoIconJson;
        }

        $logoIconHtml = \App\Support\IconRenderer::renderHtml($j, 28, '#ffffff');
    }

    $hasLogo = !empty($logoUrl) || !empty($logoIconHtml);
    $showText = !$hasLogo || $showNameWithLogo;

// Favicon (admin)
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

$faviconIconUrl = null;
if (empty($faviconUrl) && !empty($faviconIconJson)) {
    $faviconIconUrl = route('favicon.svg');
}

    $isActive = fn(string $pattern) => request()->routeIs($pattern);
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $siteName }} Admin</title>

    @if(!empty($faviconUrl))
        <link rel="icon" href="{{ $faviconUrl }}">
    @elseif(!empty($faviconIconUrl))
        <link rel="icon" type="image/svg+xml" href="{{ $faviconIconUrl }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
    /* ═══════════════════════════════════════════════════════
       ImpartCMS Admin – Modern Refresh
       All rules scoped to .admin-shell
    ═══════════════════════════════════════════════════════ */

    /* ── Base ── */
    body.admin-shell {
        background-color: #f4f4f5;
        -webkit-font-smoothing: antialiased;
    }

    /* ── Remove Breeze container constraints ── */
    .admin-shell .max-w-7xl,.admin-shell .max-w-6xl,.admin-shell .max-w-5xl,
    .admin-shell .max-w-4xl,.admin-shell .max-w-3xl,.admin-shell .max-w-2xl,
    .admin-shell .max-w-xl,.admin-shell .max-w-lg,.admin-shell .max-w-md,
    .admin-shell .max-w-sm,.admin-shell .max-w-xs,.admin-shell .container {
        max-width: none !important;
    }
    .admin-shell .mx-auto { margin-left: 0 !important; margin-right: 0 !important; }

    /* ── Sidebar section labels ── */
    .admin-sidebar-section {
        padding: 16px 12px 5px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: rgba(255,255,255,0.22);
        user-select: none;
    }

    /* ── Cards ── */
    .admin-shell .bg-white.overflow-hidden.shadow-sm,
    .admin-shell .bg-white.shadow-sm {
        border: 1px solid #e4e4e7 !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03) !important;
        border-radius: 12px !important;
        overflow: hidden !important;
    }
    .admin-shell .bg-white.border.border-gray-200.rounded-lg,
    .admin-shell .bg-white.border.border-gray-200.rounded-xl {
        border-color: #e4e4e7 !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04) !important;
        border-radius: 12px !important;
    }

    /* ── Tables ── */
    .admin-shell table.min-w-full { border-collapse: collapse; }
    .admin-shell table.min-w-full thead th {
        background: #fafafa !important;
        border-bottom: 1px solid #e4e4e7 !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.07em !important;
        color: #a1a1aa !important;
        padding-top: 11px !important;
        padding-bottom: 11px !important;
    }
    .admin-shell table.min-w-full tbody tr { transition: background-color 0.1s ease; }
    .admin-shell table.min-w-full tbody tr:hover td { background-color: #fafafa !important; }
    .admin-shell table.min-w-full.divide-y tbody tr + tr td { border-top: 1px solid #f4f4f5 !important; }
    .admin-shell table.min-w-full td {
        padding-top: 13px !important;
        padding-bottom: 13px !important;
        font-size: 13.5px !important;
        color: #3f3f46 !important;
        border-bottom: none !important;
    }
    .admin-shell table.min-w-full td.font-medium { color: #18181b !important; }

    /* ── Buttons — primary (dark) ── */
    .admin-shell a.bg-gray-900,
    .admin-shell button.bg-gray-900 {
        background-color: #18181b !important;
        border-radius: 8px !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        letter-spacing: 0 !important;
        text-transform: none !important;
        padding: 7px 14px !important;
        transition: background-color 0.15s ease !important;
    }
    .admin-shell a.bg-gray-900:hover,
    .admin-shell button.bg-gray-900:hover { background-color: #27272a !important; }

    /* ── Buttons — secondary (white/border) ── */
    .admin-shell a.bg-white.border.border-gray-300,
    .admin-shell button.bg-white.border.border-gray-300 {
        border-color: #e4e4e7 !important;
        border-radius: 8px !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        letter-spacing: 0 !important;
        text-transform: none !important;
        padding: 7px 14px !important;
        color: #52525b !important;
        transition: background-color 0.15s ease, border-color 0.15s ease !important;
    }
    .admin-shell a.bg-white.border.border-gray-300:hover,
    .admin-shell button.bg-white.border.border-gray-300:hover {
        background-color: #fafafa !important;
        border-color: #d4d4d8 !important;
    }

    /* ── Buttons — danger ── */
    .admin-shell button.bg-red-600 {
        border-radius: 8px !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        letter-spacing: 0 !important;
        text-transform: none !important;
        padding: 7px 14px !important;
    }

    /* ── Form inputs ── */
    .admin-shell input[type="text"],
    .admin-shell input[type="email"],
    .admin-shell input[type="number"],
    .admin-shell input[type="password"],
    .admin-shell input[type="url"],
    .admin-shell input[type="search"] {
        border-color: #e4e4e7 !important;
        border-radius: 8px !important;
        font-size: 14px !important;
        color: #18181b !important;
        transition: border-color 0.15s, box-shadow 0.15s !important;
    }
    .admin-shell select {
        border-color: #e4e4e7 !important;
        border-radius: 8px !important;
        font-size: 14px !important;
        color: #18181b !important;
    }
    .admin-shell textarea {
        border-color: #e4e4e7 !important;
        border-radius: 8px !important;
        font-size: 14px !important;
        color: #18181b !important;
    }
    .admin-shell input[type="text"]:focus,
    .admin-shell input[type="email"]:focus,
    .admin-shell input[type="number"]:focus,
    .admin-shell input[type="password"]:focus,
    .admin-shell input[type="url"]:focus,
    .admin-shell select:focus,
    .admin-shell textarea:focus {
        border-color: #7c3aed !important;
        box-shadow: 0 0 0 3px rgba(124,58,237,0.12) !important;
        --tw-ring-shadow: none !important;
        outline: none !important;
    }

    /* ── Labels ── */
    .admin-shell label.block.text-sm.font-medium.text-gray-700 {
        font-size: 13px !important;
        font-weight: 500 !important;
        color: #3f3f46 !important;
    }

    /* ── Badges ── */
    .admin-shell span.text-xs.px-2.py-1.rounded.border,
    .admin-shell span.text-xs.px-2.py-0\.5.rounded.border {
        border-radius: 6px !important;
        font-size: 11px !important;
        font-weight: 500 !important;
    }

    /* ── Flash alerts ── */
    .admin-shell div.mb-4.p-3.rounded.bg-green-50,
    .admin-shell div.mb-4.p-3.rounded.bg-red-50 {
        border-radius: 10px !important;
        padding: 13px 16px !important;
        font-size: 14px !important;
    }

    /* ── Section headings inside cards ── */
    .admin-shell h3.text-sm.font-semibold.text-gray-900 {
        font-size: 14px !important;
        font-weight: 600 !important;
        color: #18181b !important;
    }

    /* ── Page header title ── */
    .admin-shell h2.font-semibold.text-xl.text-gray-800 {
        font-size: 18px !important;
        font-weight: 700 !important;
        color: #18181b !important;
        letter-spacing: -0.01em !important;
    }

    /* ── Topbar ── */
    .admin-topbar-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 24px;
        height: 56px;
        border-bottom: 1px solid #e4e4e7;
    }

    /* ── User avatar initials ── */
    .admin-user-avatar {
        width: 32px; height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #7c3aed, #4f46e5);
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        letter-spacing: 0.02em;
        flex-shrink: 0;
    }
</style>
</head>
<body class="min-h-screen admin-shell">
<div class="min-h-screen flex">
    {{-- Sidebar --}}
    <aside class="w-64 bg-slate-950 text-white flex-shrink-0 border-r border-white/5">
        <div class="px-4 py-4 border-b border-white/10">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 {{ $showText ? '' : 'justify-center' }}">
                @if(!empty($logoUrl))
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }} logo" class="h-8 w-auto">
                @elseif(!empty($logoIconHtml))
                    <span class="inline-flex items-center justify-center h-8 w-8">{!! $logoIconHtml !!}</span>
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
            $linkBase = 'group flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors';
            $linkInactive = 'text-white/70 hover:text-white hover:bg-white/[0.07]';
            $linkActive = 'bg-violet-500/[0.18] text-white';
            $iconInactive = 'text-white/40 group-hover:text-white/70 transition-colors';
            $iconActive = 'text-violet-300';
        @endphp

        <nav class="p-3 space-y-0.5">

            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}" class="{{ $linkBase }} {{ $isActive('dashboard') ? $linkActive : $linkInactive }}">
                <svg class="h-4 w-4 flex-none {{ $isActive('dashboard') ? $iconActive : $iconInactive }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M11.47 2.22a1.5 1.5 0 0 1 1.06 0l7.5 2.75A1.5 1.5 0 0 1 21 6.38v6.82c0 3.6-2.26 6.9-5.64 8.26l-2.35.94a2 2 0 0 1-1.5 0l-2.35-.94C5.76 20.1 3.5 16.8 3.5 13.2V6.38A1.5 1.5 0 0 1 4.47 4.97l7-2.75Z"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="{{ url('/') }}" target="_blank" rel="noopener" class="{{ $linkBase }} {{ $linkInactive }}">
                <svg class="h-4 w-4 flex-none {{ $iconInactive }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M13.5 4.5a1.5 1.5 0 0 0 0 3h1.88l-6.44 6.44a1.5 1.5 0 1 0 2.12 2.12l6.44-6.44V11.5a1.5 1.5 0 0 0 3 0V6a1.5 1.5 0 0 0-1.5-1.5h-5.5Z"/>
                    <path d="M6 6.75A2.25 2.25 0 0 0 3.75 9v9A2.25 2.25 0 0 0 6 20.25h9A2.25 2.25 0 0 0 17.25 18v-4.5a.75.75 0 0 0-1.5 0V18c0 .414-.336.75-.75.75H6A.75.75 0 0 1 5.25 18V9c0-.414.336-.75.75-.75h4.5a.75.75 0 0 0 0-1.5H6Z"/>
                </svg>
                <span>View site</span>
            </a>

            {{-- CONTENT ── ── ── ── ── ── ── ── ── ── ─── --}}
            <div class="admin-sidebar-section">Content</div>

            <a href="{{ route('admin.pages.index') }}" class="{{ $linkBase }} {{ $isActive('admin.pages.*') ? $linkActive : $linkInactive }}">
                <svg class="h-4 w-4 flex-none {{ $isActive('admin.pages.*') ? $iconActive : $iconInactive }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M6 2.25A2.25 2.25 0 0 0 3.75 4.5v15A2.25 2.25 0 0 0 6 21.75h12A2.25 2.25 0 0 0 20.25 19.5v-10.5L14.25 2.25H6Zm7.5 1.56V9h5.19L13.5 3.81Z"/>
                    <path d="M7.5 12a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 7.5 12Zm0 3a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 7.5 15Z"/>
                </svg>
                <span>Pages</span>
            </a>

            @if(\Illuminate\Support\Facades\Route::has('admin.media.index'))
                <a href="{{ route('admin.media.index') }}" class="{{ $linkBase }} {{ $isActive('admin.media.*') ? $linkActive : $linkInactive }}">
                    <svg class="h-4 w-4 flex-none {{ $isActive('admin.media.*') ? $iconActive : $iconInactive }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M4.5 5.25A2.25 2.25 0 0 1 6.75 3h10.5A2.25 2.25 0 0 1 19.5 5.25v13.5A2.25 2.25 0 0 1 17.25 21H6.75A2.25 2.25 0 0 1 4.5 18.75V5.25Zm12 3a.75.75 0 1 0-1.5 0 .75.75 0 0 0 1.5 0Zm-9.75 9.69 2.22-2.22 1.66 1.66 4.12-4.12a.75.75 0 0 1 1.06 0l2.19 2.19v3.84c0 .414-.336.75-.75.75H6.75a.75.75 0 0 1-.75-.75v-1.35Z"/>
                    </svg>
                    <span>Media</span>
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.forms.index'))
                <a href="{{ route('admin.forms.index') }}" class="{{ $linkBase }} {{ $isActive('admin.forms.*') ? $linkActive : $linkInactive }}">
                    <svg class="h-4 w-4 flex-none {{ $isActive('admin.forms.*') ? $iconActive : $iconInactive }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M6 2.25A2.25 2.25 0 0 0 3.75 4.5v15A2.25 2.25 0 0 0 6 21.75h12A2.25 2.25 0 0 0 20.25 19.5v-15A2.25 2.25 0 0 0 18 2.25H6Zm2.25 6a.75.75 0 0 1 .75-.75h6a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75Zm0 3a.75.75 0 0 1 .75-.75h6a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75Zm0 3a.75.75 0 0 1 .75-.75h3.5a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75Z"/>
                    </svg>
                    <span>Forms</span>
                </a>
            @endif

            {{-- PEOPLE ── ── ── ── ── ── ── ── ── ── ─── --}}
            @if(\Illuminate\Support\Facades\Route::has('admin.users.index'))
                <div class="admin-sidebar-section">People</div>
                <a href="{{ route('admin.users.index') }}" class="{{ $linkBase }} {{ $isActive('admin.users.*') ? $linkActive : $linkInactive }}">
                    <svg class="h-4 w-4 flex-none {{ $isActive('admin.users.*') ? $iconActive : $iconInactive }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M16.5 7.5a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/>
                        <path d="M3.75 20.25a7.5 7.5 0 0 1 15 0 .75.75 0 0 1-.75.75H4.5a.75.75 0 0 1-.75-.75Z"/>
                    </svg>
                    <span>Users</span>
                </a>
            @endif

            {{-- TOOLS ── ── ── ── ── ── ── ── ── ── ─── --}}
            <div class="admin-sidebar-section">Tools</div>

            @php
                $assistGroupActive = $isActive('admin.pages.ai.*') || $isActive('admin.site-builder.*') || $isActive('admin.site-clone.*') || $isActive('admin.ai.visual-audit*');
            @endphp
            <div x-data="{ open: {{ $assistGroupActive ? 'true' : 'false' }} }">
                <button @click="open = !open" class="{{ $linkBase }} w-full {{ $assistGroupActive ? $linkActive : $linkInactive }}">
                    <svg class="h-4 w-4 flex-none {{ $assistGroupActive ? $iconActive : $iconInactive }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423L16.5 15.75l.394 1.183a2.25 2.25 0 0 0 1.423 1.423L19.5 18.75l-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/>
                    </svg>
                    <span class="flex-1 text-left">Assist Tools</span>
                    <svg class="h-3 w-3 flex-none transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
                </button>
                <div x-show="open" class="mt-0.5 ml-7 space-y-0.5">
                    <a href="{{ route('admin.pages.ai.create') }}" class="{{ $linkBase }} text-xs {{ $isActive('admin.pages.ai.*') ? $linkActive : $linkInactive }}">AI Page</a>
                    @if(\Illuminate\Support\Facades\Route::has('admin.site-builder.create'))
                        <a href="{{ route('admin.site-builder.create') }}" class="{{ $linkBase }} text-xs {{ $isActive('admin.site-builder.*') ? $linkActive : $linkInactive }}">AI Site Builder</a>
                    @endif
                    @if(\Illuminate\Support\Facades\Route::has('admin.site-clone.create'))
                        <a href="{{ route('admin.site-clone.create') }}" class="{{ $linkBase }} text-xs {{ $isActive('admin.site-clone.*') ? $linkActive : $linkInactive }}">Clone Website</a>
                    @endif
                    @if(\Illuminate\Support\Facades\Route::has('admin.ai.visual-audit'))
                        <a href="{{ route('admin.ai.visual-audit') }}" class="{{ $linkBase }} text-xs {{ $isActive('admin.ai.visual-audit*') ? $linkActive : $linkInactive }}">AI Visual Audit</a>
                    @endif
                </div>
            </div>

            {{-- SYSTEM ── ── ── ── ── ── ── ── ── ── ─── --}}
            <div class="admin-sidebar-section">System</div>

            @if(\Illuminate\Support\Facades\Route::has('admin.settings.edit'))
                @php $settingsGroupActive = $isActive('admin.settings.*') || $isActive('admin.reset*') || $isActive('admin.ai-agent.*'); @endphp
                <div x-data="{ open: {{ $settingsGroupActive ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="{{ $linkBase }} w-full {{ $settingsGroupActive ? $linkActive : $linkInactive }}">
                        <svg class="h-4 w-4 flex-none {{ $settingsGroupActive ? $iconActive : $iconInactive }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M11.983 1.5a1.5 1.5 0 0 1 1.484 1.28l.17 1.14a7.92 7.92 0 0 1 1.82.75l1.05-.5a1.5 1.5 0 0 1 1.86.53l1.5 2.6a1.5 1.5 0 0 1-.38 1.95l-.93.72c.08.6.08 1.22 0 1.82l.93.72a1.5 1.5 0 0 1 .38 1.95l-1.5 2.6a1.5 1.5 0 0 1-1.86.53l-1.05-.5a7.92 7.92 0 0 1-1.82.75l-.17 1.14A1.5 1.5 0 0 1 11.983 22.5h-3a1.5 1.5 0 0 1-1.484-1.28l-.17-1.14a7.92 7.92 0 0 1-1.82-.75l-1.05.5a1.5 1.5 0 0 1-1.86-.53l-1.5-2.6a1.5 1.5 0 0 1 .38-1.95l.93-.72a7.7 7.7 0 0 1 0-1.82l-.93-.72a1.5 1.5 0 0 1-.38-1.95l1.5-2.6a1.5 1.5 0 0 1 1.86-.53l1.05.5c.58-.3 1.18-.56 1.82-.75l.17-1.14A1.5 1.5 0 0 1 8.983 1.5h3Zm-1.5 7.5a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z"/>
                        </svg>
                        <span class="flex-1 text-left">Settings</span>
                        <svg class="h-3 w-3 flex-none transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 9l6 6 6-6"/>
                        </svg>
                    </button>
                    <div x-show="open" class="mt-0.5 ml-7 space-y-0.5">
                        <a href="{{ route('admin.settings.edit') }}" class="{{ $linkBase }} text-xs {{ $isActive('admin.settings.*') ? $linkActive : $linkInactive }}">Settings</a>
                        @if(\Illuminate\Support\Facades\Route::has('admin.ai-agent.edit'))
                            <a href="{{ route('admin.ai-agent.edit') }}" class="{{ $linkBase }} text-xs {{ $isActive('admin.ai-agent.*') ? $linkActive : $linkInactive }}">AI Agent</a>
                        @endif
                        <a href="{{ route('admin.reset') }}" class="{{ $linkBase }} text-xs {{ $isActive('admin.reset*') ? $linkActive : $linkInactive }}">Data Reset</a>
                    </div>
                </div>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.layout-blocks.index'))
                <a href="{{ route('admin.layout-blocks.index') }}" class="{{ $linkBase }} {{ $isActive('admin.layout-blocks.*') ? $linkActive : $linkInactive }}">
                    <svg class="h-4 w-4 flex-none {{ $isActive('admin.layout-blocks.*') ? $iconActive : $iconInactive }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M4.5 6.75A2.25 2.25 0 0 1 6.75 4.5h10.5A2.25 2.25 0 0 1 19.5 6.75v10.5A2.25 2.25 0 0 1 17.25 19.5H6.75A2.25 2.25 0 0 1 4.5 17.25V6.75Zm2.25-.75a.75.75 0 0 0-.75.75v.75h12v-.75a.75.75 0 0 0-.75-.75H6.75Zm11.25 12H6v.75c0 .414.336.75.75.75h10.5c.414 0 .75-.336.75-.75V18Zm-12 0h12V9H6v9Z"/>
                    </svg>
                    <span>Header &amp; Footer</span>
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.snippets.index'))
                <a href="{{ route('admin.snippets.index') }}" class="{{ $linkBase }} {{ $isActive('admin.snippets.*') ? $linkActive : $linkInactive }}">
                    <svg class="h-4 w-4 flex-none {{ $isActive('admin.snippets.*') ? $iconActive : $iconInactive }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M8.7 7.2a.75.75 0 0 1 0 1.06L5.96 11l2.74 2.74a.75.75 0 1 1-1.06 1.06L4.38 11.53a.75.75 0 0 1 0-1.06l3.26-3.27a.75.75 0 0 1 1.06 0Zm6.6 0a.75.75 0 0 1 1.06 0l3.26 3.27a.75.75 0 0 1 0 1.06l-3.26 3.27a.75.75 0 1 1-1.06-1.06L18.04 11l-2.74-2.74a.75.75 0 0 1 0-1.06Z"/>
                        <path d="M13.65 5.1a.75.75 0 0 1 .53.92l-2.5 13a.75.75 0 1 1-1.47-.28l2.5-13a.75.75 0 0 1 .94-.64Z"/>
                    </svg>
                    <span>Custom code</span>
                </a>
            @endif

        </nav>
    </aside>

    {{-- Main --}}
    <div class="flex-1 min-w-0">
        {{-- Top bar --}}
        <header class="bg-white border-b border-zinc-200/80">
            <div class="px-6 flex items-center justify-between" style="height:56px">
                @php
                    $initials = collect(explode(' ', Auth::user()->name))
                        ->map(fn($w) => strtoupper(substr($w,0,1)))
                        ->take(2)->implode('');
                @endphp
                <div class="flex items-center gap-3">
                    <div class="admin-user-avatar">{{ $initials }}</div>
                    <div>
                        <div class="text-sm font-semibold text-zinc-900 leading-tight">{{ Auth::user()->name }}</div>
                        <div class="text-xs text-zinc-400 leading-tight">{{ Auth::user()->email }}</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-zinc-400 hover:text-zinc-700 transition-colors">
                        Sign out
                    </button>
                </form>
            </div>

            @if (isset($header))
                <div class="px-6 pb-4">
                    {{ $header }}
                </div>
            @elseif (View::hasSection('header'))
                <div class="px-6 pb-4">
                    @yield('header')
                </div>
            @endif
        </header>

        <main class="px-6 py-6">
            @if (isset($slot))
                {{ $slot }}
            @else
                @yield('content')
            @endif
        </main>
    </div>
</div>

{{-- Global Media Picker Modal (WordPress-style) --}}
<div id="impart-media-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60" data-impart-media-close></div>

    {{-- Centered modal container --}}
    <div class="relative min-h-screen w-full flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-[1440px] h-[92vh] bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200">
            <div class="text-sm font-semibold text-slate-900">Select media</div>
            <button type="button" class="text-sm font-semibold text-slate-700 hover:text-slate-900" data-impart-media-close>
                Cancel
            </button>
        </div>
        <iframe id="impart-media-iframe" class="flex-1 w-full" src="about:blank"></iframe>
        </div>
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
        document.documentElement.style.overflow = '';
    }

    function openModal(opts) {
        const url = (opts && opts.url) ? opts.url : "{{ route('admin.media.picker') }}";
        onSelect = (opts && typeof opts.onSelect === 'function') ? opts.onSelect : null;

        iframe.src = url;
        modal.classList.remove('hidden');
        document.documentElement.style.overflow = 'hidden';
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
            if (data.type === 'impartcms-media-cancel' || data.type === 'impart-media-cancel') {
                closeModal();
                return;
            }
            if (data.type !== 'impartcms-media-selected' && data.type !== 'impart-media-selected') return;

            // Normalise payload shape (old patches may post payload directly)
            const payload = (data && typeof data === 'object' && 'payload' in data) ? data.payload : data;
            if (onSelect) onSelect(payload);
            closeModal();
        } catch (e) {
            // ignore
        }
    });
})();
</script>

{{-- Global admin AI helper (page assist popup) --}}
@includeIf('admin.partials.ai-popup')
</body>
</html>
