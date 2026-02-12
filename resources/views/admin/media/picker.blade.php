<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Media Picker</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white">
@php
    $tab = request()->query('tab', 'library');
    $baseQuery = request()->except('page', 'tab');
    $qForLinks = fn($extra) => array_merge($baseQuery, $extra);

    $isAll = ($currentType ?? '') === '';
    $isImages = ($currentType ?? '') === 'images';
    $isDocs = ($currentType ?? '') === 'docs';
    $isFonts = ($currentType ?? '') === 'fonts';
@endphp

<div class="h-[min(80vh,760px)] flex flex-col">
    <div class="px-4 py-3 border-b border-gray-200">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-gray-900">Media library</div>
                    <div class="text-xs text-gray-500">Select a file to use it.</div>
                </div>

                <button type="button" class="md:hidden inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold uppercase tracking-widest" id="picker-cancel">
                    Cancel
                </button>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.media.picker', $qForLinks(['tab' => 'library'])) }}"
                   class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold uppercase tracking-widest {{ $tab === 'library' ? 'bg-gray-900 text-white' : 'bg-white border border-gray-300 text-gray-900 hover:bg-gray-50' }}">
                    Library
                </a>
                <a href="{{ route('admin.media.picker', $qForLinks(['tab' => 'upload'])) }}"
                   class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold uppercase tracking-widest {{ $tab === 'upload' ? 'bg-gray-900 text-white' : 'bg-white border border-gray-300 text-gray-900 hover:bg-gray-50' }}">
                    Upload
                </a>

                <button type="button" class="hidden md:inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold uppercase tracking-widest hover:bg-gray-50" id="picker-cancel-desktop">
                    Cancel
                </button>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-3 p-2 rounded bg-green-50 text-green-800 border border-green-200 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-3 p-2 rounded bg-red-50 text-red-800 border border-red-200 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        @if($tab === 'library')
            <div class="mt-4 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div class="text-sm text-gray-600">
                    @if(($accept ?? 'images') === 'all')
                    <a href="{{ route('admin.media.picker', $qForLinks(['type' => ''])) }}"
                       class="{{ $isAll ? 'font-semibold text-gray-900' : 'hover:text-gray-900' }}">
                        All <span class="text-gray-500">({{ $counts['all'] ?? 0 }})</span>
                    </a>
                    <span class="mx-2 text-gray-300">|</span>
                    <a href="{{ route('admin.media.picker', $qForLinks(['type' => 'images'])) }}"
                       class="{{ $isImages ? 'font-semibold text-gray-900' : 'hover:text-gray-900' }}">
                        Images <span class="text-gray-500">({{ $counts['images'] ?? 0 }})</span>
                    </a>
                    <span class="mx-2 text-gray-300">|</span>
                    <a href="{{ route('admin.media.picker', $qForLinks(['type' => 'fonts'])) }}"
                       class="{{ $isFonts ? 'font-semibold text-gray-900' : 'hover:text-gray-900' }}">
                        Fonts <span class="text-gray-500">({{ $counts['fonts'] ?? 0 }})</span>
                    </a>
                    <span class="mx-2 text-gray-300">|</span>
                    <a href="{{ route('admin.media.picker', $qForLinks(['type' => 'docs'])) }}"
                       class="{{ $isDocs ? 'font-semibold text-gray-900' : 'hover:text-gray-900' }}">
                        Docs <span class="text-gray-500">({{ $counts['docs'] ?? 0 }})</span>
                    </a>
                    @else
                        <span class="font-semibold text-gray-900">{{ ucfirst($currentType ?: 'media') }}</span>
                        <span class="text-gray-500">({{ $counts[$currentType ?: 'all'] ?? $counts['all'] ?? 0 }})</span>
                    @endif
                </div>

                <form method="GET" action="{{ route('admin.media.picker') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                    <input type="hidden" name="accept" value="{{ $accept }}">
                    <input type="hidden" name="tab" value="library">
                    <input type="hidden" name="type" value="{{ $currentType }}">
                    <input type="hidden" name="selected" value="{{ $selectedId }}">

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-widest">Folder</label>
                        <select name="folder" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="" {{ ($currentFolder ?? '') === '' ? 'selected' : '' }}>All folders</option>
                            @foreach($folders as $f)
                                <option value="{{ $f }}" {{ ($currentFolder ?? '') === $f ? 'selected' : '' }}>{{ $f }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-widest">Search</label>
                        <div class="mt-1 flex items-center gap-2">
                            <input type="text" name="q" value="{{ $currentQuery }}" placeholder="Search…" class="w-full sm:w-56 rounded-md border-gray-300 text-sm" />
                            <button type="submit" class="inline-flex items-center px-3 py-2 bg-gray-900 text-white rounded-md text-xs font-semibold uppercase tracking-widest hover:bg-gray-800">Apply</button>
                            <a href="{{ route('admin.media.picker', ['accept' => $accept, 'tab' => 'library', 'type' => $currentType]) }}" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold uppercase tracking-widest hover:bg-gray-50">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    </div>

    <div class="flex-1 overflow-auto">
        <div class="p-4">
            @if($tab === 'upload')
                <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Upload</label>
                        <input name="files[]" type="file" multiple
                               class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-gray-900 file:text-white hover:file:bg-gray-800"
                               accept="image/*,.svg,.ico,.pdf,.woff,.woff2,.ttf,.otf,.eot">
                        <div class="text-xs text-gray-500 mt-1">Images, PDFs, and font files. Max 10MB each. Auto-organised into YYYY/MM.</div>
                    </div>

                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                        Upload
                    </button>

                    <div class="text-xs text-gray-500">
                        After upload, switch back to <span class="font-semibold">Library</span> to select.
                    </div>
                </form>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    @forelse($media as $item)
                        @php $selected = ((int) $item->id === (int) $selectedId); @endphp
                        <button type="button"
                                class="group text-left border rounded-lg overflow-hidden hover:shadow-sm {{ $selected ? 'ring-2 ring-gray-900' : '' }}"
                                data-media-select
                                data-id="{{ $item->id }}"
                                data-url="{{ $item->url }}"
                                data-title="{{ e($item->title ?? '') }}"
                                data-original="{{ e($item->original_name ?? '') }}">
                            <div class="bg-gray-50 aspect-square flex items-center justify-center">
                                @if($item->isImage())
                                    <img src="{{ $item->url }}" alt="{{ $item->alt_text ?? $item->original_name }}" class="h-full w-full object-cover group-hover:opacity-95">
                                @else
                                    <div class="text-xs text-gray-600 px-2 text-center">
                                        {{ strtoupper(pathinfo($item->original_name, PATHINFO_EXTENSION) ?: 'FILE') }}
                                    </div>
                                @endif
                            </div>
                            <div class="p-2">
                                <div class="text-xs font-medium text-gray-900 truncate">{{ $item->title ?: $item->original_name }}</div>
                                <div class="text-[11px] text-gray-500 truncate">{{ $item->folder }}</div>
                            </div>
                        </button>
                    @empty
                        <div class="col-span-6 text-sm text-gray-500">No media found.</div>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $media->links() }}
                </div>

                <div class="mt-4 flex items-center justify-end gap-2">
                    <button type="button" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold uppercase tracking-widest hover:bg-gray-50" id="picker-cancel-footer">
                        Cancel
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    (function () {
        function cancel() {
            window.parent?.postMessage({ type: 'impartcms-media-cancel' }, window.location.origin);
        }

        document.getElementById('picker-cancel')?.addEventListener('click', cancel);
        document.getElementById('picker-cancel-desktop')?.addEventListener('click', cancel);
        document.getElementById('picker-cancel-footer')?.addEventListener('click', cancel);

        document.querySelectorAll('[data-media-select]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const payload = {
                    id: btn.getAttribute('data-id'),
                    url: btn.getAttribute('data-url'),
                    title: btn.getAttribute('data-title'),
                    original_name: btn.getAttribute('data-original'),
                };

                window.parent?.postMessage({ type: 'impartcms-media-selected', payload }, window.location.origin);
            });
        });
    })();
</script>
</body>
</html>
