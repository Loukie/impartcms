<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $isCreate ? 'New header/footer block' : 'Edit header/footer block' }}
            </h2>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.layout-blocks.index') }}"
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <form method="POST" action="{{ $isCreate ? route('admin.layout-blocks.store') : route('admin.layout-blocks.update', $block) }}" class="p-6 space-y-8">
                    @csrf
                    @if(!$isCreate)
                        @method('PUT')
                    @endif

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Content (HTML)</label>
                                <textarea name="content" rows="16" class="mt-1 w-full rounded-md border-gray-300 font-mono text-sm" placeholder="Paste HTML here… supports shortcodes like [icon …] and [form …]">{{ old('content', $block->content) }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Rendered on the public site. Shortcodes are supported.</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Type</label>
                                        <select name="type" class="mt-1 w-full rounded-md border-gray-300">
                                            <option value="header" {{ old('type', $block->type) === 'header' ? 'selected' : '' }}>Header</option>
                                            <option value="footer" {{ old('type', $block->type) === 'footer' ? 'selected' : '' }}>Footer</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Name</label>
                                        <input type="text" name="name" value="{{ old('name', $block->name) }}" class="mt-1 w-full rounded-md border-gray-300" placeholder="e.g. Main navigation">
                                    </div>

                                    <div>
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                            <input type="checkbox" name="is_enabled" value="1" {{ old('is_enabled', $block->is_enabled) ? 'checked' : '' }} class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                            Enabled
                                        </label>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Priority</label>
                                        <input type="number" name="priority" min="0" max="10000" value="{{ old('priority', $block->priority ?? 100) }}" class="mt-1 w-full rounded-md border-gray-300">
                                        <p class="mt-1 text-xs text-gray-500">Lower wins when multiple blocks match.</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Targeting</label>
                                        <select name="target_mode" class="mt-1 w-full rounded-md border-gray-300">
                                            <option value="global" {{ old('target_mode', $block->target_mode) === 'global' ? 'selected' : '' }}>Global (all pages)</option>
                                            <option value="only" {{ old('target_mode', $block->target_mode) === 'only' ? 'selected' : '' }}>Only selected pages</option>
                                            <option value="except" {{ old('target_mode', $block->target_mode) === 'except' ? 'selected' : '' }}>All except selected pages</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Pages</label>
                                        <select name="page_ids[]" multiple size="10" class="mt-1 w-full rounded-md border-gray-300">
                                            @foreach($pages as $p)
                                                @php
                                                    $sel = collect(old('page_ids', $selectedPageIds ?? []))->map(fn($v) => (int)$v)->contains((int)$p->id);
                                                @endphp
                                                <option value="{{ $p->id }}" {{ $sel ? 'selected' : '' }}>
                                                    {{ $p->title }} (/{{ ltrim($p->slug,'/') }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1 text-xs text-gray-500">Used for “Only” / “Except”.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                    Save
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                @if(!$isCreate)
                    <div class="px-6 pb-6">
                        <form method="POST" action="{{ route('admin.layout-blocks.destroy', $block) }}" onsubmit="return confirm('Move to trash?')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-red-700">
                                Trash
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
