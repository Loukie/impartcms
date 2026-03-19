<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Media</h2>
                <p class="text-sm text-gray-500 mt-0.5">Upload and manage files.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.media.trash') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                    Trash
                </a>
                <button type="button"
                        x-data
                        @click="$dispatch('media-toggle-upload')"
                        class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                    Upload
                </button>
            </div>
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
                        <a href="{{ route('admin.media.trash') }}" class="text-sm font-semibold underline whitespace-nowrap">View Trash</a>
                    @endif
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-3 rounded bg-red-50 text-red-800 border border-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                 x-data="{ showUpload: {{ $errors->any() ? 'true' : 'false' }} }"
                 @media-toggle-upload.window="showUpload = !showUpload">

                <div class="p-6">
                    {{-- Upload panel --}}
                    @if (!$isIcons)
                        <div class="mb-5" x-show="showUpload" x-cloak>
                            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-5">
                                <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row sm:items-center gap-3">
                                    @csrf
                                    <input type="file" name="files[]" multiple class="block w-full text-sm text-zinc-600" />
                                    <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800 whitespace-nowrap">
                                        Upload files
                                    </button>
                                </form>
                                <p class="mt-2 text-xs text-zinc-400">Images and documents (max 10 MB each). Auto-organised into YYYY/MM folders.</p>
                            </div>
                        </div>
                    @endif

                    {{-- Tabs + Filters --}}
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        {{-- Type tabs --}}
                        <div class="flex items-center gap-1 text-sm">
                            <a href="{{ route('admin.media.index', array_merge($baseTabQuery, ['type' => 'images'])) }}"
                               class="px-3 py-1.5 rounded-lg font-medium transition-colors {{ $isImages ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-500 hover:text-zinc-900 hover:bg-zinc-50' }}">
                                Images <span class="text-zinc-400 text-xs">({{ $counts['images'] ?? 0 }})</span>
                            </a>
                            <a href="{{ route('admin.media.index', array_merge($baseTabQuery, ['type' => 'icons'])) }}"
                               class="px-3 py-1.5 rounded-lg font-medium transition-colors {{ $isIcons ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-500 hover:text-zinc-900 hover:bg-zinc-50' }}">
                                Icons
                            </a>
                            <a href="{{ route('admin.media.index', array_merge($baseTabQuery, ['type' => 'docs'])) }}"
                               class="px-3 py-1.5 rounded-lg font-medium transition-colors {{ $isDocs ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-500 hover:text-zinc-900 hover:bg-zinc-50' }}">
                                Docs <span class="text-zinc-400 text-xs">({{ $counts['docs'] ?? 0 }})</span>
                            </a>
                        </div>

                        {{-- Filters --}}
                        @if (!$isIcons)
                            <form method="GET" action="{{ route('admin.media.index') }}" class="flex items-center gap-2 flex-wrap">
                                <input type="hidden" name="type" value="{{ $currentType }}">

                                <select name="folder" class="border border-gray-300 rounded-md text-sm px-3 py-2">
                                    <option value="">All folders</option>
                                    @foreach(($folders ?? []) as $folder)
                                        <option value="{{ $folder }}" @selected(($currentFolder ?? '') === $folder)>{{ $folder }}</option>
                                    @endforeach
                                </select>

                                <select name="sort" class="border border-gray-300 rounded-md text-sm px-3 py-2">
                                    <option value="newest" @selected(($currentSort ?? 'newest') === 'newest')>Newest</option>
                                    <option value="oldest" @selected(($currentSort ?? '') === 'oldest')>Oldest</option>
                                    <option value="title_asc" @selected(($currentSort ?? '') === 'title_asc')>A→Z</option>
                                    <option value="title_desc" @selected(($currentSort ?? '') === 'title_desc')>Z→A</option>
                                </select>

                                <select name="per_page" class="border border-gray-300 rounded-md text-sm px-3 py-2">
                                    <option value="30" @selected((int)($currentPerPage ?? 30) === 30)>30</option>
                                    <option value="50" @selected((int)($currentPerPage ?? 30) === 50)>50</option>
                                    <option value="100" @selected((int)($currentPerPage ?? 30) === 100)>100</option>
                                </select>

                                <input type="text" name="q" value="{{ $currentQuery ?? '' }}"
                                       class="border border-gray-300 rounded-md text-sm px-3 py-2 w-48"
                                       placeholder="Search…" />

                                <button class="inline-flex items-center px-3 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">Apply</button>
                                <a href="{{ route('admin.media.index', ['type' => $currentType]) }}"
                                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">Reset</a>
                            </form>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="mt-5">
                        @if ($isIcons)
                            @include('admin.media.partials.fa-icons', ['mode' => 'copy'])
                        @else
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                @forelse ($media as $item)
                                    <a href="{{ route('admin.media.show', $item) }}"
                                       class="block border border-zinc-200 rounded-xl overflow-hidden bg-white hover:border-violet-300 hover:shadow-sm transition-all">
                                        <div class="aspect-square bg-zinc-50 flex items-center justify-center overflow-hidden">
                                            @if ($item->is_image)
                                                <img src="{{ $item->url }}"
                                                     alt="{{ $item->title ?? $item->original_name ?? '' }}"
                                                     class="w-full h-full object-contain p-2" />
                                            @else
                                                <div class="text-xs font-bold text-zinc-400 tracking-wider">
                                                    {{ strtoupper($item->extension ?? 'FILE') }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="px-2.5 py-2 border-t border-zinc-100">
                                            <div class="text-xs font-medium text-zinc-800 truncate">
                                                {{ $item->title ?: ($item->original_name ?? 'Untitled') }}
                                            </div>
                                            @if($item->folder)
                                                <div class="text-[10px] text-zinc-400 truncate mt-0.5">{{ $item->folder }}</div>
                                            @endif
                                        </div>
                                    </a>
                                @empty
                                    <div class="col-span-full py-12 text-center text-zinc-400 text-sm">No media found.</div>
                                @endforelse
                            </div>

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
