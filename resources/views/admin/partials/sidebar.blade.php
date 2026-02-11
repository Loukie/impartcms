@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $logoPath = \App\Models\Setting::get('site_logo_path', null);

    $isActive = fn(string $pattern) => request()->routeIs($pattern);
@endphp

<div class="rounded-lg border border-gray-200 bg-white p-4">
    <div class="flex items-center gap-3">
        @if($logoPath)
            <img src="{{ asset('storage/' . $logoPath) }}" alt="Logo" class="h-8 w-auto">
        @endif
        <div class="min-w-0">
            <div class="font-semibold text-gray-900 truncate">{{ $siteName }}</div>
            <div class="text-xs text-gray-500">Admin</div>
        </div>
    </div>

    <div class="mt-4 border-t pt-4 space-y-1">
        <a href="{{ route('dashboard') }}"
           class="block px-3 py-2 rounded-md text-sm font-medium {{ $isActive('dashboard') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-50' }}">
            Dashboard
        </a>

        <a href="{{ url('/') }}" target="_blank"
           class="block px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
            View site
        </a>

        <div class="pt-2 mt-2 border-t border-gray-100"></div>

        <a href="{{ route('admin.pages.index') }}"
           class="block px-3 py-2 rounded-md text-sm font-medium {{ $isActive('admin.pages.*') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-50' }}">
            Pages
        </a>

        <a href="{{ route('admin.settings.edit') }}"
           class="block px-3 py-2 rounded-md text-sm font-medium {{ $isActive('admin.settings.*') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-50' }}">
            Settings
        </a>
    </div>
</div>
