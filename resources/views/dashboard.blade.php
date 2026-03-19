<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        <p class="text-sm text-gray-500 mt-0.5">Welcome back, {{ Auth::user()->name }}</p>
    </x-slot>

    @php
        $pageCount   = \App\Models\Page::count();
        $mediaCount  = \App\Models\MediaFile::count();
        $formCount   = \Illuminate\Support\Facades\Route::has('admin.forms.index')
                        ? \App\Models\Form::count() : null;
        $userCount   = \App\Models\User::count();
        $siteName    = \App\Models\Setting::get('site_name', config('app.name', 'ImpartCMS'));
    @endphp

    {{-- Stats row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach([
            ['label' => 'Pages',  'value' => $pageCount,  'href' => route('admin.pages.index'),  'icon' => 'M6 2.25A2.25 2.25 0 0 0 3.75 4.5v15A2.25 2.25 0 0 0 6 21.75h12A2.25 2.25 0 0 0 20.25 19.5v-10.5L14.25 2.25H6Zm7.5 1.56V9h5.19L13.5 3.81Z'],
            ['label' => 'Media',  'value' => $mediaCount, 'href' => route('admin.media.index'),  'icon' => 'M4.5 5.25A2.25 2.25 0 0 1 6.75 3h10.5A2.25 2.25 0 0 1 19.5 5.25v13.5A2.25 2.25 0 0 1 17.25 21H6.75A2.25 2.25 0 0 1 4.5 18.75V5.25Zm12 3a.75.75 0 1 0-1.5 0 .75.75 0 0 0 1.5 0Zm-9.75 9.69 2.22-2.22 1.66 1.66 4.12-4.12a.75.75 0 0 1 1.06 0l2.19 2.19v3.84c0 .414-.336.75-.75.75H6.75a.75.75 0 0 1-.75-.75v-1.35Z'],
            ['label' => 'Users',  'value' => $userCount,  'href' => route('admin.users.index'),  'icon' => 'M16.5 7.5a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM3.75 20.25a7.5 7.5 0 0 1 15 0 .75.75 0 0 1-.75.75H4.5a.75.75 0 0 1-.75-.75Z'],
            ['label' => 'Forms',  'value' => $formCount,  'href' => \Illuminate\Support\Facades\Route::has('admin.forms.index') ? route('admin.forms.index') : '#', 'icon' => 'M6 2.25A2.25 2.25 0 0 0 3.75 4.5v15A2.25 2.25 0 0 0 6 21.75h12A2.25 2.25 0 0 0 20.25 19.5v-15A2.25 2.25 0 0 0 18 2.25H6Zm2.25 6a.75.75 0 0 1 .75-.75h6a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75Zm0 3a.75.75 0 0 1 .75-.75h6a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75Zm0 3a.75.75 0 0 1 .75-.75h3.5a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75Z'],
        ] as $stat)
            <a href="{{ $stat['href'] }}" class="bg-white border border-zinc-200 rounded-xl p-5 hover:border-violet-300 hover:shadow-sm transition-all group">
                <div class="flex items-center justify-between">
                    <div class="text-2xl font-bold text-zinc-900">{{ $stat['value'] ?? '—' }}</div>
                    <div class="w-9 h-9 rounded-lg bg-zinc-50 group-hover:bg-violet-50 flex items-center justify-center transition-colors">
                        <svg class="h-4 w-4 text-zinc-400 group-hover:text-violet-500 transition-colors" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="{{ $stat['icon'] }}"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-1 text-sm text-zinc-500">{{ $stat['label'] }}</div>
            </a>
        @endforeach
    </div>

    {{-- Quick actions --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white border border-zinc-200 rounded-xl p-5">
            <div class="text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-3">Quick Actions</div>
            <div class="space-y-1">
                <a href="{{ route('admin.pages.create') }}" class="flex items-center gap-2 text-sm text-zinc-700 hover:text-violet-600 py-1.5 transition-colors">
                    <svg class="h-3.5 w-3.5 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    New page
                </a>
                <a href="{{ route('admin.media.index') }}" class="flex items-center gap-2 text-sm text-zinc-700 hover:text-violet-600 py-1.5 transition-colors">
                    <svg class="h-3.5 w-3.5 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    Upload media
                </a>
                @if(\Illuminate\Support\Facades\Route::has('admin.site-clone.create'))
                    <a href="{{ route('admin.site-clone.create') }}" class="flex items-center gap-2 text-sm text-zinc-700 hover:text-violet-600 py-1.5 transition-colors">
                        <svg class="h-3.5 w-3.5 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                        Clone a website
                    </a>
                @endif
                <a href="{{ route('admin.settings.edit') }}" class="flex items-center gap-2 text-sm text-zinc-700 hover:text-violet-600 py-1.5 transition-colors">
                    <svg class="h-3.5 w-3.5 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    Site settings
                </a>
                <a href="{{ url('/') }}" target="_blank" rel="noopener" class="flex items-center gap-2 text-sm text-zinc-700 hover:text-violet-600 py-1.5 transition-colors">
                    <svg class="h-3.5 w-3.5 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    View live site
                </a>
            </div>
        </div>

        <div class="bg-white border border-zinc-200 rounded-xl p-5">
            <div class="text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-3">Site</div>
            <div class="text-2xl font-bold text-zinc-900 truncate">{{ $siteName }}</div>
            <div class="mt-1 text-sm text-zinc-400">{{ config('app.url') }}</div>
            <div class="mt-4 flex items-center gap-2">
                @php $maintenanceEnabled = \App\Models\Setting::get('maintenance_enabled', '0'); @endphp
                @if($maintenanceEnabled)
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        Maintenance mode
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Live
                    </span>
                @endif
            </div>
        </div>

        <div class="bg-white border border-zinc-200 rounded-xl p-5">
            <div class="text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-3">AI Tools</div>
            <div class="space-y-1">
                @if(\Illuminate\Support\Facades\Route::has('admin.pages.ai.create'))
                    <a href="{{ route('admin.pages.ai.create') }}" class="flex items-center gap-2 text-sm text-zinc-700 hover:text-violet-600 py-1.5 transition-colors">
                        <svg class="h-3.5 w-3.5 text-violet-400" viewBox="0 0 24 24" fill="currentColor"><path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                        Generate a page
                    </a>
                @endif
                @if(\Illuminate\Support\Facades\Route::has('admin.site-builder.create'))
                    <a href="{{ route('admin.site-builder.create') }}" class="flex items-center gap-2 text-sm text-zinc-700 hover:text-violet-600 py-1.5 transition-colors">
                        <svg class="h-3.5 w-3.5 text-violet-400" viewBox="0 0 24 24" fill="currentColor"><path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                        Build a full site
                    </a>
                @endif
                @if(\Illuminate\Support\Facades\Route::has('admin.site-clone.create'))
                    <a href="{{ route('admin.site-clone.create') }}" class="flex items-center gap-2 text-sm text-zinc-700 hover:text-violet-600 py-1.5 transition-colors">
                        <svg class="h-3.5 w-3.5 text-violet-400" viewBox="0 0 24 24" fill="currentColor"><path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                        Clone a website
                    </a>
                @endif
                @if(\Illuminate\Support\Facades\Route::has('admin.ai.visual-audit'))
                    <a href="{{ route('admin.ai.visual-audit') }}" class="flex items-center gap-2 text-sm text-zinc-700 hover:text-violet-600 py-1.5 transition-colors">
                        <svg class="h-3.5 w-3.5 text-violet-400" viewBox="0 0 24 24" fill="currentColor"><path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                        Visual audit
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
