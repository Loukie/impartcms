<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $isNew ? 'New snippet' : 'Edit snippet' }}
            </h2>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.snippets.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                    Back
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
                    <div class="font-semibold">Please fix the following:</div>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST"
                      action="{{ $isNew ? route('admin.snippets.store') : route('admin.snippets.update', $snippet) }}"
                      class="p-6 space-y-8">
                    @csrf
                    @if(!$isNew)
                        @method('PUT')
                    @endif

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type</label>
                            <select name="type" id="snippet_type" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="script" {{ old('type', $snippet->type) === 'script' ? 'selected' : '' }}>Script</option>
                                <option value="css" {{ old('type', $snippet->type) === 'css' ? 'selected' : '' }}>CSS</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Scripts can be placed in Head / Body / Footer. CSS always loads at the end of &lt;head&gt; so it overrides theme styles.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="name" value="{{ old('name', $snippet->name) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300" placeholder="e.g. Google Analytics" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="is_enabled" value="1"
                                       {{ old('is_enabled', $snippet->is_enabled) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                Enabled
                            </label>
                        </div>

                        <div id="position_wrap">
                            <label class="block text-sm font-medium text-gray-700">Script placement</label>
                            <select name="position" id="snippet_position" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="head" {{ old('position', $snippet->position) === 'head' ? 'selected' : '' }}>Head</option>
                                <option value="body" {{ old('position', $snippet->position) === 'body' ? 'selected' : '' }}>Body (top)</option>
                                <option value="footer" {{ old('position', $snippet->position) === 'footer' ? 'selected' : '' }}>Footer</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Targeting</label>
                            <select name="target_mode" id="snippet_target_mode" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="global" {{ old('target_mode', $snippet->target_mode) === 'global' ? 'selected' : '' }}>Global (all pages)</option>
                                <option value="only" {{ old('target_mode', $snippet->target_mode) === 'only' ? 'selected' : '' }}>Only selected pages</option>
                                <option value="except" {{ old('target_mode', $snippet->target_mode) === 'except' ? 'selected' : '' }}>All except selected pages</option>
                            </select>
                        </div>
                    </div>

                    <div id="pages_wrap">
                        <label class="block text-sm font-medium text-gray-700">Target pages</label>
                        <select name="page_ids[]" multiple class="mt-1 block w-full rounded-md border-gray-300 min-h-[160px]">
                            @foreach($pages as $p)
                                @php
                                    $selected = in_array((int) $p->id, (array) old('page_ids', $selectedPageIds ?? []), true);
                                @endphp
                                <option value="{{ $p->id }}" {{ $selected ? 'selected' : '' }}>{{ $p->title }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Used when Targeting is “Only selected pages” or “All except selected pages”.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Code</label>
                        <textarea name="content" rows="16"
                                  class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm"
                                  placeholder="Paste your code here… (You can paste full <script> tags or raw JS)">{{ old('content', $snippet->content) }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">This applies to the public site (front end). Admin pages are not affected.</p>
                    </div>

                    <div class="pt-6 border-t flex items-center justify-end gap-3">
                        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const typeEl = document.getElementById('snippet_type');
            const positionWrap = document.getElementById('position_wrap');
            const pagesWrap = document.getElementById('pages_wrap');
            const targetModeEl = document.getElementById('snippet_target_mode');

            function refresh() {
                const type = (typeEl?.value || 'script');
                if (positionWrap) {
                    positionWrap.style.display = (type === 'script') ? '' : 'none';
                }

                const mode = (targetModeEl?.value || 'global');
                if (pagesWrap) {
                    pagesWrap.style.display = (mode === 'global') ? 'none' : '';
                }
            }

            typeEl?.addEventListener('change', refresh);
            targetModeEl?.addEventListener('change', refresh);
            refresh();
        })();
    </script>
</x-admin-layout>
