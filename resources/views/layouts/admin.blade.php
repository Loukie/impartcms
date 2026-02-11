<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'ImpartCMS') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100">
<div class="min-h-screen flex">
    {{-- Sidebar --}}
    <aside class="w-64 bg-gray-900 text-white flex-shrink-0">
        <div class="px-4 py-4 border-b border-white/10">
            <div class="text-lg font-semibold">{{ config('app.name', 'ImpartCMS') }}</div>
            <div class="text-xs text-white/70 mt-1">Admin</div>
        </div>

        <nav class="p-3 space-y-1">
            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}"
               class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-white/10 {{ request()->routeIs('dashboard') ? 'bg-white/10' : '' }}">
                Dashboard
            </a>

            {{-- View site: MUST be in sidebar under Dashboard --}}
            <a href="{{ url('/') }}" target="_blank"
               class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-white/10">
                View site
            </a>

            <div class="my-3 border-t border-white/10"></div>

            {{-- Pages --}}
            <a href="{{ route('admin.pages.index') }}"
               class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-white/10 {{ request()->routeIs('admin.pages.*') ? 'bg-white/10' : '' }}">
                Pages
            </a>

            {{-- Settings (show only if route exists) --}}
            @if(\Illuminate\Support\Facades\Route::has('admin.settings.edit'))
                <a href="{{ route('admin.settings.edit') }}"
                   class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-white/10 {{ request()->routeIs('admin.settings.*') ? 'bg-white/10' : '' }}">
                    Settings
                </a>
            @endif
        </nav>
    </aside>

    {{-- Main --}}
    <div class="flex-1 min-w-0">
        {{-- Top bar --}}
        <header class="bg-white border-b border-gray-200">
            <div class="px-6 py-4 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Logged in as <span class="font-semibold text-gray-900">{{ Auth::user()->name }}</span>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-gray-700 hover:text-gray-900">
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
