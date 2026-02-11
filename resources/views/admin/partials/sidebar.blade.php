@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
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

    $isActive = fn(string $pattern) => request()->routeIs($pattern);
@endphp

<div class="rounded-lg border border-gray-200 bg-white p-4">
    <div class="flex items-center gap-3 {{ $showText ? '' : 'justify-center' }}">
        @if($hasLogo)
            <img src="{{ $logoUrl }}" alt="{{ $siteName }} logo" class="h-8 w-auto">
        @endif

        @if($showText)
            <div class="min-w-0">
                <div class="font-semibold text-gray-900 truncate">{{ $siteName }}</div>
                <div class="text-xs text-gray-500">Admin</div>
            </div>
        @else
            <span class="sr-only">{{ $siteName }} Admin</span>
        @endif
    </div>

    <div class="mt-4 border-t pt-4 space-y-1">
        <a href="{{ route('dashboard') }}"
           class="group flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ $isActive('dashboard') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-50' }}">
            <svg class="h-4 w-4 flex-none {{ $isActive('dashboard') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M11.47 2.22a1.5 1.5 0 0 1 1.06 0l7.5 2.75A1.5 1.5 0 0 1 21 6.38v6.82c0 3.6-2.26 6.9-5.64 8.26l-2.35.94a2 2 0 0 1-1.5 0l-2.35-.94C5.76 20.1 3.5 16.8 3.5 13.2V6.38A1.5 1.5 0 0 1 4.47 4.97l7-2.75Z"/>
            </svg>
            <span>Dashboard</span>
        </a>

        <a href="{{ url('/') }}" target="_blank"
           class="group flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
            <svg class="h-4 w-4 flex-none text-gray-400 group-hover:text-gray-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M13.5 4.5a1.5 1.5 0 0 0 0 3h1.88l-6.44 6.44a1.5 1.5 0 1 0 2.12 2.12l6.44-6.44V11.5a1.5 1.5 0 0 0 3 0V6a1.5 1.5 0 0 0-1.5-1.5h-5.5Z"/>
                <path d="M6 6.75A2.25 2.25 0 0 0 3.75 9v9A2.25 2.25 0 0 0 6 20.25h9A2.25 2.25 0 0 0 17.25 18v-4.5a.75.75 0 0 0-1.5 0V18c0 .414-.336.75-.75.75H6A.75.75 0 0 1 5.25 18V9c0-.414.336-.75.75-.75h4.5a.75.75 0 0 0 0-1.5H6Z"/>
            </svg>
            <span>View site</span>
        </a>

        <div class="pt-2 mt-2 border-t border-gray-100"></div>

        <a href="{{ route('admin.pages.index') }}"
           class="group flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ $isActive('admin.pages.*') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-50' }}">
            <svg class="h-4 w-4 flex-none {{ $isActive('admin.pages.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M6 2.25A2.25 2.25 0 0 0 3.75 4.5v15A2.25 2.25 0 0 0 6 21.75h12A2.25 2.25 0 0 0 20.25 19.5v-10.5L14.25 2.25H6Zm7.5 1.56V9h5.19L13.5 3.81Z"/>
                <path d="M7.5 12a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 7.5 12Zm0 3a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 7.5 15Z"/>
            </svg>
            <span>Pages</span>
        </a>

        <a href="{{ route('admin.media.index') }}"
           class="group flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ $isActive('admin.media.*') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-50' }}">
            <svg class="h-4 w-4 flex-none {{ $isActive('admin.media.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M4.5 5.25A2.25 2.25 0 0 1 6.75 3h10.5A2.25 2.25 0 0 1 19.5 5.25v13.5A2.25 2.25 0 0 1 17.25 21H6.75A2.25 2.25 0 0 1 4.5 18.75V5.25Zm12 3a.75.75 0 1 0-1.5 0 .75.75 0 0 0 1.5 0Zm-9.75 9.69 2.22-2.22 1.66 1.66 4.12-4.12a.75.75 0 0 1 1.06 0l2.19 2.19v3.84c0 .414-.336.75-.75.75H6.75a.75.75 0 0 1-.75-.75v-1.35Z"/>
            </svg>
            <span>Media</span>
        </a>

        <a href="{{ route('admin.users.index') }}"
           class="group flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ $isActive('admin.users.*') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-50' }}">
            <svg class="h-4 w-4 flex-none {{ $isActive('admin.users.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M16.5 7.5a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/>
                <path d="M3.75 20.25a7.5 7.5 0 0 1 15 0 .75.75 0 0 1-.75.75H4.5a.75.75 0 0 1-.75-.75Z"/>
            </svg>
            <span>Users</span>
        </a>

        <a href="{{ route('admin.settings.edit') }}"
           class="group flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ $isActive('admin.settings.*') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-50' }}">
            <svg class="h-4 w-4 flex-none {{ $isActive('admin.settings.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M11.983 1.5a1.5 1.5 0 0 1 1.484 1.28l.17 1.14a7.92 7.92 0 0 1 1.82.75l1.05-.5a1.5 1.5 0 0 1 1.86.53l1.5 2.6a1.5 1.5 0 0 1-.38 1.95l-.93.72c.08.6.08 1.22 0 1.82l.93.72a1.5 1.5 0 0 1 .38 1.95l-1.5 2.6a1.5 1.5 0 0 1-1.86.53l-1.05-.5a7.92 7.92 0 0 1-1.82.75l-.17 1.14A1.5 1.5 0 0 1 11.983 22.5h-3a1.5 1.5 0 0 1-1.484-1.28l-.17-1.14a7.92 7.92 0 0 1-1.82-.75l-1.05.5a1.5 1.5 0 0 1-1.86-.53l-1.5-2.6a1.5 1.5 0 0 1 .38-1.95l.93-.72a7.7 7.7 0 0 1 0-1.82l-.93-.72a1.5 1.5 0 0 1-.38-1.95l1.5-2.6a1.5 1.5 0 0 1 1.86-.53l1.05.5c.58-.3 1.18-.56 1.82-.75l.17-1.14A1.5 1.5 0 0 1 8.983 1.5h3Zm-1.5 7.5a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z"/>
            </svg>
            <span>Settings</span>
        </a>
    </div>
</div>
