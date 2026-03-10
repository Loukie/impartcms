<x-admin-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Media</h2>
            <p class="text-sm text-gray-600 mt-1">Upload and manage files.</p>
        </div>
    </x-slot>

    @php
        $currentType = $currentType ?? 'images';
        $isImages = $currentType === 'images';
        $isIcons = $currentType === 'icons';
        $isDocs = $currentType === 'docs';

        $baseTabQuery = [
            'folder' => $currentFolder ?? '',
            'q' => $currentQuery ?? '',
            'sort' => $currentSort ?? 'newest',
            'per_page' => $currentPerPage ?? 30,
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-3 rounded bg-green-50 text-green-800 border border-green-200 flex items-center justify-between gap-3">
                    <span>{{ session('status') }}</span>
                    @if (session('show_trash_link'))
                        <a href="{{ route('admin.media.trash') }}" class="text-sm font-semibold underline whitespace-nowrap">
                            View Trash
                        </a>
                    @endif
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-3 rounded bg-red-50 text-red-800 border border-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6" x-data="{ showUpload: {{ $errors->any() ? 'true' : 'false' }}, bulkMode: false, selected: [], pageIds: @js($media->pluck('id')->values()->all()), selectAllPage() { this.selected = this.pageIds.map((id) => String(id)); }, clearSelected() { this.selected = []; } }">
                    {{-- Tabs + Filters --}}
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="flex items-center gap-2 text-sm font-semibold">
                            <a href="{{ route('admin.media.index', array_merge($baseTabQuery, ['type' => 'images'])) }}"
                               class="{{ $isImages ? 'text-gray-900' : 'text-gray-500 hover:text-gray-900' }}">
                                Images <span class="text-gray-400">({{ $counts['images'] ?? 0 }})</span>
                            </a>
                            <span class="text-gray-300">|</span>
                            <a href="{{ route('admin.media.index', array_merge($baseTabQuery, ['type' => 'icons'])) }}"
                               class="{{ $isIcons ? 'text-gray-900' : 'text-gray-500 hover:text-gray-900' }}">
                                Icons
                            </a>
                            <span class="text-gray-300">|</span>
                            <a href="{{ route('admin.media.index', array_merge($baseTabQuery, ['type' => 'docs'])) }}"
                               class="{{ $isDocs ? 'text-gray-900' : 'text-gray-500 hover:text-gray-900' }}">
                                Docs <span class="text-gray-400">({{ $counts['docs'] ?? 0 }})</span>
                            </a>
                            <span class="text-gray-300">|</span>
                            <a href="{{ route('admin.media.trash') }}"
                               class="text-red-600 hover:text-red-700">
                                Trash
                            </a>
                        </div>

                        {{-- Right-side controls --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            @if (!$isIcons)
                                <form method="GET" action="{{ route('admin.media.index') }}" class="flex items-center gap-2 flex-wrap">
                                    <input type="hidden" name="type" value="{{ $currentType }}">

                                    <div class="flex items-center gap-2">
                                        <label class="text-xs font-semibold text-gray-600">Folder</label>
                                        <select name="folder" class="border rounded-md text-sm px-3 py-2">
                                            <option value="">All folders</option>
                                            @foreach(($folders ?? []) as $folder)
                                                <option value="{{ $folder }}" @selected(($currentFolder ?? '') === $folder)>{{ $folder }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <label class="text-xs font-semibold text-gray-600">Sort</label>
                                        <select name="sort" class="border rounded-md text-sm px-3 py-2">
                                            <option value="newest" @selected(($currentSort ?? 'newest') === 'newest')>Newest</option>
                                            <option value="oldest" @selected(($currentSort ?? '') === 'oldest')>Oldest</option>
                                            <option value="title_asc" @selected(($currentSort ?? '') === 'title_asc')>Title (A→Z)</option>
                                            <option value="title_desc" @selected(($currentSort ?? '') === 'title_desc')>Title (Z→A)</option>
                                        </select>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <label class="text-xs font-semibold text-gray-600">Per page</label>
                                        <select name="per_page" class="border rounded-md text-sm px-3 py-2">
                                            <option value="30" @selected((int)($currentPerPage ?? 30) === 30)>30</option>
                                            <option value="50" @selected((int)($currentPerPage ?? 30) === 50)>50</option>
                                            <option value="100" @selected((int)($currentPerPage ?? 30) === 100)>100</option>
                                        </select>
                                    </div>

                                    <input
                                        type="text"
                                        name="q"
                                        value="{{ $currentQuery ?? '' }}"
                                        class="border rounded-md text-sm px-3 py-2"
                                        placeholder="Search filename or title..."
                                    />

                                    <button class="px-3 py-2 rounded-md bg-gray-900 text-white text-xs font-semibold">Apply</button>
                                    <a href="{{ route('admin.media.index', ['type' => $currentType]) }}" class="px-3 py-2 rounded-md border text-xs font-semibold">Reset</a>
                                </form>

                                <button type="button"
                                        class="ml-auto px-3 py-2 rounded-md bg-gray-900 text-white text-xs font-semibold"
                                        @click="showUpload = !showUpload" x-show="!bulkMode">
                                    Upload
                                </button>
                                <button type="button"
                                        class="ml-2 px-3 py-2 rounded-md bg-yellow-600 text-white text-xs font-semibold"
                                        @click="bulkMode = !bulkMode; if(!bulkMode) selected=[]"
                                        x-text="bulkMode ? 'Cancel' : 'Bulk delete'">
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Upload (collapsed by default; not shown on Icons tab) --}}
                    @if (!$isIcons)
                        <div class="mt-4" x-show="showUpload" x-cloak>
                            <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row sm:items-center gap-3">
                                @csrf
                                <input type="file" name="files[]" multiple class="block w-full text-sm" />
                                <button type="submit" class="px-4 py-2 rounded-md bg-gray-900 text-white text-xs font-semibold">Upload</button>
                            </form>
                            <div class="mt-2 text-xs text-gray-500">
                                Images and documents (max 10MB each). Auto-organised into YYYY/MM.
                            </div>
                        </div>
                    @endif

                    {{-- Content --}}
                    <div class="mt-6">
                        @if ($isIcons)
                            {{-- Font Awesome browser (same UI as the Media Picker popup) --}}
                            @include('admin.media.partials.fa-icons', ['mode' => 'copy'])
                        @else
                            {{-- Media grid --}}
                            <form method="POST" action="{{ route('admin.media.bulk') }}">
                                @csrf
                                <div class="mb-3" x-show="bulkMode">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <button type="button" class="px-3 py-2 rounded-md border text-xs font-semibold" @click="selectAllPage()">Select all on this page</button>
                                        <button type="button" class="px-3 py-2 rounded-md border text-xs font-semibold" @click="clearSelected()">Clear</button>
                                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md text-xs uppercase font-semibold hover:bg-red-700" :disabled="selected.length === 0">
                                        Trash selected (<span x-text="selected.length"></span>)
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                                    @forelse ($media as $item)
                                        <div class="relative">
                                            <template x-if="bulkMode">
                                                <input type="checkbox" name="ids[]" value="{{ $item->id }}" x-model="selected" class="absolute top-2 left-2 z-10 w-4 h-4" />
                                            </template>
                                            <a href="{{ route('admin.media.show', $item) }}" class="group block border rounded-lg overflow-hidden bg-white hover:shadow">
                                                <div class="aspect-square bg-gray-50 flex items-center justify-center overflow-hidden">
                                                    @if ($item->is_image)
                                                        <img src="{{ $item->url }}" alt="{{ $item->title ?? $item->original_name ?? '' }}" class="w-full h-full object-contain p-2" />
                                                    @else
                                                        <div class="text-xs font-semibold text-gray-600">{{ strtoupper($item->extension ?? 'FILE') }}</div>
                                                    @endif
                                                </div>
                                                <div class="p-2">
                                                    <div class="text-sm font-semibold text-gray-900 truncate group-hover:text-gray-900">
                                                        {{ $item->title ?: ($item->original_name ?? 'Untitled') }}
                                                    </div>
                                                    <div class="text-[11px] text-gray-500 truncate">
                                                        {{ $item->folder ?? '' }}
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    @empty
                                        <div class="text-sm text-gray-500 col-span-full">No media found.</div>
                                    @endforelse
                                </div>
                            </form>

                            <div class="mt-6">
                                {{ $media->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
