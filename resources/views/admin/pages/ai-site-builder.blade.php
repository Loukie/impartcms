<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                AI Site Builder
            </h2>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.pages.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 hover:bg-gray-50">
                    Back to Pages
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-3 rounded bg-green-50 text-green-800 border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-3 rounded bg-red-50 text-red-800 border border-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-6">

                    @if(($step ?? 'input') === 'input')
                        <div>
                            <div class="text-sm text-gray-600">
                                Generate a blueprint (sitemap + per-page briefs), review it, then build pages. Everything defaults to <span class="font-semibold">draft</span> so you can review safely ✅
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.site-builder.blueprint') }}" id="site-builder-input-form" class="space-y-5">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Site name *</label>
                                    <input name="site_name" value="{{ old('site_name', $input['site_name'] ?? '') }}" class="mt-1 w-full rounded-md border-gray-300" placeholder="e.g. Acme Consulting" required>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Industry</label>
                                    <input name="industry" value="{{ old('industry', $input['industry'] ?? '') }}" class="mt-1 w-full rounded-md border-gray-300" placeholder="e.g. Accounting, Construction, SaaS">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Location</label>
                                    <input name="location" value="{{ old('location', $input['location'] ?? '') }}" class="mt-1 w-full rounded-md border-gray-300" placeholder="e.g. George, South Africa">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Audience</label>
                                    <input name="audience" value="{{ old('audience', $input['audience'] ?? '') }}" class="mt-1 w-full rounded-md border-gray-300" placeholder="e.g. SMEs, homeowners, HR teams">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tone</label>
                                    <input name="tone" value="{{ old('tone', $input['tone'] ?? 'clear, modern, confident') }}" class="mt-1 w-full rounded-md border-gray-300">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Primary CTA</label>
                                    <input name="primary_cta" value="{{ old('primary_cta', $input['primary_cta'] ?? 'Get in touch') }}" class="mt-1 w-full rounded-md border-gray-300" placeholder="e.g. Book a call">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Page preset</label>
                                    <select name="page_preset" class="mt-1 w-full rounded-md border-gray-300">
                                        @php $preset = old('page_preset', $input['page_preset'] ?? 'business'); @endphp
                                        <option value="basic" {{ $preset === 'basic' ? 'selected' : '' }}>Basic (5–6 pages)</option>
                                        <option value="business" {{ $preset === 'business' ? 'selected' : '' }}>Business (7–9 pages)</option>
                                        <option value="full" {{ $preset === 'full' ? 'selected' : '' }}>Full (10–14 pages)</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Extra notes (optional)</label>
                                <textarea id="site-builder-notes" name="notes" rows="5" class="mt-1 w-full rounded-md border-gray-300" placeholder="Any must-have pages, services, offers, brand personality, keywords, etc.">{{ old('notes', $input['notes'] ?? '') }}</textarea>
                                <div id="notes-counter" class="text-xs text-gray-500 mt-1"></div>
                            </div>

                            <div class="flex items-center gap-3">
                                <button id="blueprint-btn" type="submit" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs hover:bg-gray-800">
                                    Generate Blueprint
                                </button>
                                <div class="text-xs text-gray-500">Rate limit: 3 per minute</div>
                            </div>
                        </form>

                    @elseif(($step ?? '') === 'blueprint')
                        <div class="space-y-2">
                            <div class="text-sm text-gray-600">
                                Blueprint generated ✅ Review/edit the JSON if needed, then build the site.
                            </div>
                        </div>

                        @php
                            $pages = is_array($blueprint['pages'] ?? null) ? $blueprint['pages'] : [];
                        @endphp

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <div class="text-sm font-semibold text-gray-900 mb-2">Blueprint JSON</div>
                                <form method="POST" action="{{ route('admin.site-builder.build') }}" class="space-y-4">
                                    @csrf
                                    <textarea name="blueprint_json" rows="18" class="w-full font-mono text-xs rounded-md border-gray-300">{{ old('blueprint_json', $blueprintJson ?? '') }}</textarea>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Style mode</label>
                                            <select name="style_mode" class="mt-1 w-full rounded-md border-gray-300">
                                                @php $sm = old('style_mode', 'inline'); @endphp
                                                <option value="inline" {{ $sm === 'inline' ? 'selected' : '' }}>Inline styles (recommended)</option>
                                                <option value="classes" {{ $sm === 'classes' ? 'selected' : '' }}>Classes</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Default template</label>
                                            <select name="template" class="mt-1 w-full rounded-md border-gray-300">
                                                @php $tpl = old('template', 'blank'); @endphp
                                                <option value="blank" {{ $tpl === 'blank' ? 'selected' : '' }}>blank</option>
                                                <option value="" {{ $tpl === '' ? 'selected' : '' }}>(theme default)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Build mode</label>
                                            <select name="action" class="mt-1 w-full rounded-md border-gray-300">
                                                @php $act = old('action', 'draft'); @endphp
                                                <option value="draft" {{ $act === 'draft' ? 'selected' : '' }}>Create drafts</option>
                                                <option value="publish" {{ $act === 'publish' ? 'selected' : '' }}>Publish all pages</option>
                                            </select>
                                        </div>

                                        <div class="flex items-end">
                                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                <input type="checkbox" name="publish_homepage" value="1" class="rounded border-gray-300" {{ old('publish_homepage') ? 'checked' : '' }}>
                                                Publish homepage (even if drafts)
                                            </label>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                            <input type="checkbox" name="set_homepage" value="1" class="rounded border-gray-300" {{ old('set_homepage', '1') ? 'checked' : '' }}>
                                            Set homepage as active (updates Settings)
                                        </label>
                                        <div class="text-xs text-gray-500 mt-1">Requires the homepage to be published.</div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <button id="build-btn" type="submit" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs hover:bg-gray-800">
                                            Build Site
                                        </button>
                                        <a href="{{ route('admin.site-builder.create') }}" class="text-sm text-gray-600 hover:text-gray-900">Start over</a>
                                    </div>
                                </form>
                            </div>

                            <div>
                                <div class="text-sm font-semibold text-gray-900 mb-2">Pages preview</div>
                                <div class="rounded border border-gray-200 overflow-hidden">
                                    <table class="w-full text-sm">
                                        <thead class="bg-gray-50 text-gray-700">
                                        <tr>
                                            <th class="text-left px-3 py-2">Title</th>
                                            <th class="text-left px-3 py-2">Slug</th>
                                            <th class="text-left px-3 py-2">Home</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($pages as $p)
                                            @php
                                                $t = is_array($p) ? (string)($p['title'] ?? '') : '';
                                                $s = is_array($p) ? (string)($p['slug'] ?? '') : '';
                                                $h = is_array($p) ? (bool)($p['is_homepage'] ?? false) : false;
                                            @endphp
                                            <tr class="border-t">
                                                <td class="px-3 py-2 font-medium text-gray-900">{{ $t }}</td>
                                                <td class="px-3 py-2 text-gray-700">{{ $s }}</td>
                                                <td class="px-3 py-2">{!! $h ? '<span class="inline-flex items-center px-2 py-0.5 rounded bg-green-50 text-green-800 border border-green-200 text-xs">Yes</span>' : '<span class="text-xs text-gray-500">—</span>' !!}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="px-3 py-4 text-gray-500">No pages found in blueprint.</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="text-xs text-gray-500 mt-3">
                                    Tip: keep service detail pages focused on one service each. You can always delete drafts later.
                                </div>
                            </div>
                        </div>

                    @elseif(($step ?? '') === 'report')
                        @php
                            $r = is_array($report ?? null) ? $report : null;
                            $rows = is_array($r['pages'] ?? null) ? $r['pages'] : [];
                            $warnings = is_array($r['warnings'] ?? null) ? $r['warnings'] : [];
                            $homepageId = isset($r['homepage_id']) ? (int)$r['homepage_id'] : 0;
                        @endphp

                        <div class="space-y-3">
                            <div class="text-sm text-gray-700">
                                Build complete ✅ Created <span class="font-semibold">{{ count($rows) }}</span> pages.
                            </div>

                            @if($homepageId > 0)
                                <div class="text-sm">
                                    Homepage published: <a class="text-blue-700 hover:underline" href="{{ route('admin.pages.edit', $homepageId) }}">Edit homepage</a>
                                </div>
                            @endif

                            @if(count($warnings) > 0)
                                <div class="p-3 rounded bg-yellow-50 border border-yellow-200 text-yellow-900 text-sm">
                                    <div class="font-semibold mb-1">Warnings</div>
                                    <ul class="list-disc ml-5 space-y-1">
                                        @foreach($warnings as $w)
                                            <li>{{ $w }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <div class="rounded border border-gray-200 overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-gray-700">
                                <tr>
                                    <th class="text-left px-3 py-2">Page</th>
                                    <th class="text-left px-3 py-2">Slug</th>
                                    <th class="text-left px-3 py-2">Status</th>
                                    <th class="text-left px-3 py-2">Result</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($rows as $row)
                                    @php
                                        $id = (int)($row['id'] ?? 0);
                                        $title = (string)($row['title'] ?? '');
                                        $slug = (string)($row['slug'] ?? '');
                                        $status = (string)($row['status'] ?? 'draft');
                                        $err = $row['error'] ?? null;
                                    @endphp
                                    <tr class="border-t">
                                        <td class="px-3 py-2 font-medium text-gray-900">
                                            @if($id > 0)
                                                <a class="text-blue-700 hover:underline" href="{{ route('admin.pages.edit', $id) }}">{{ $title }}</a>
                                            @else
                                                {{ $title }}
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-gray-700">{{ $slug }}</td>
                                        <td class="px-3 py-2">
                                            @if($status === 'published')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-green-50 text-green-800 border border-green-200 text-xs">published</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-50 text-gray-800 border border-gray-200 text-xs">draft</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            @if($err)
                                                <span class="text-red-700">{{ $err }}</span>
                                            @else
                                                <span class="text-green-700">OK</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.pages.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs hover:bg-gray-800">
                                Go to Pages
                            </a>

                            <a href="{{ route('admin.site-builder.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 hover:bg-gray-50">
                                Build another site
                            </a>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function(){
            // disable buttons during submit
            const inputForm = document.getElementById('site-builder-input-form');
            if(inputForm){
                const btn = document.getElementById('blueprint-btn');
                inputForm.addEventListener('submit', function(){
                    if(btn){
                        btn.disabled = true;
                        btn.dataset.orig = btn.innerHTML;
                        btn.innerHTML = 'Working…';
                    }
                });
            }

            const buildForm = document.querySelector('form[action$="site-builder.build"]');
            if(buildForm){
                const btn = document.getElementById('build-btn');
                buildForm.addEventListener('submit', function(){
                    if(btn){
                        btn.disabled = true;
                        btn.dataset.orig = btn.innerHTML;
                        btn.innerHTML = 'Working…';
                    }
                });
            }

            // notes counter
            const notes = document.getElementById('site-builder-notes');
            const counter = document.getElementById('notes-counter');
            if(notes && counter){
                function update(){ counter.textContent = notes.value.length + ' characters'; }
                notes.addEventListener('input', update);
                update();
            }
        });
    </script>
</x-admin-layout>
