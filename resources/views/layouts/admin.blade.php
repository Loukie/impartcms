<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    /**
     * Admin branding
     * - If no logo: show site name text
     * - If logo: show logo-only by default
     * - Optional setting: allow logo + text
     */
    $siteName = \App\Models\Setting::get('site_name', config('app.name', 'ImpartCMS'));
    $logoPath = \App\Models\Setting::get('site_logo_path', null);
    $showNameWithLogo = (bool) ((int) \App\Models\Setting::get('admin_show_name_with_logo', '0'));

    $hasLogo = !empty($logoPath);
    $showText = !$hasLogo || $showNameWithLogo;
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $siteName }} Admin</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50">
<div class="min-h-screen flex">
    {{-- Sidebar --}}
    <aside class="w-64 bg-slate-950 text-white flex-shrink-0 border-r border-white/5">
        <div class="px-4 py-4 border-b border-white/10">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 {{ $showText ? '' : 'justify-center' }}">
                @if($hasLogo)
                    <img src="{{ asset('storage/' . $logoPath) }}"
                         alt="{{ $siteName }} logo"
                         class="h-8 w-auto">
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
            $linkBase = 'group flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition';
            $linkInactive = 'text-white/80 hover:text-white hover:bg-white/10';
            $linkActive = 'bg-white/10 text-white';
        @endphp

        <nav class="p-3 space-y-1">
            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}"
               class="{{ $linkBase }} {{ request()->routeIs('dashboard') ? $linkActive : $linkInactive }}">
                <span>Dashboard</span>
            </a>

            {{-- View site: MUST be in sidebar under Dashboard --}}
            <a href="{{ url('/') }}" target="_blank" rel="noopener"
               class="{{ $linkBase }} {{ $linkInactive }}">
                <span>View site</span>
            </a>

            <div class="my-3 border-t border-white/10"></div>

            {{-- Pages --}}
            <a href="{{ route('admin.pages.index') }}"
               class="{{ $linkBase }} {{ request()->routeIs('admin.pages.*') ? $linkActive : $linkInactive }}">
                <span>Pages</span>
            </a>

            {{-- Users --}}
            <a href="{{ route('admin.users.index') }}"
               class="{{ $linkBase }} {{ request()->routeIs('admin.users.*') ? $linkActive : $linkInactive }}">
                <span>Users</span>
            </a>

            {{-- Settings (show only if route exists) --}}
            @if(\Illuminate\Support\Facades\Route::has('admin.settings.edit'))
                <a href="{{ route('admin.settings.edit') }}"
                   class="{{ $linkBase }} {{ request()->routeIs('admin.settings.*') ? $linkActive : $linkInactive }}">
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
</body>
</html>
