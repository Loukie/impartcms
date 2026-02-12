<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Select media</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50">
<div class="p-4">

    {{-- Top toolbar: left actions + right filters (same row) --}}
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.media.picker', array_merge(request()->query(), ['tab' => 'library'])) }}"
               class="px-3 py-2 rounded-md text-sm font-semibold border {{ ($tab ?? 'library') === 'library' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-900' }}">
                Library
            </a>

            <a href="{{ route('admin.media.picker', array_merge(request()->query(), ['tab' => 'upload'])) }}"
               class="px-3 py-2 rounded-md text-sm font-semibold border {{ ($tab ?? 'library') === 'upload' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-900' }}">
                Upload
            </a>

            <button type="button"
                    class="px-3 py-2 rounded-md text-sm font-semibold border bg-white text-gray-900 hover:bg-gray-50"
                    onclick="window.parent?.ImpartMediaPicker?.close?.()">
                Cancel
            </button>
        </div>

        <form method="GET" class="flex items-center gap-2 flex-wrap">
            <input type="hidden" name="tab" value="{{ $tab ?? 'library' }}">
            <input type="hidden" name="type" value="{{ $currentType ?? '' }}">
            <input type="hidden" name="sort" value="{{ $currentSort ?? 'newest' }}">

            <select name="folder" class="rounded-md border-gray-300 text-sm">
                <option value="">All folders</option>
                @foreach(($folders ?? []) as $f)
                    <option value="{{ $f }}" @selected(($currentFolder ?? '') === $f)>{{ $f }}</option>
                @endforeach
            </select>

            <input name="q" value="{{ $currentQuery ?? '' }}" placeholder="Search…"
                   class="rounded-md border-gray-300 text-sm w-64 max-w-full"/>

            <button class="px-3 py-2 rounded-md bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800">
                Apply
            </button>

            <a href="{{ route('admin.media.picker') }}"
               class="px-3 py-2 rounded-md border bg-white text-sm font-semibold hover:bg-gray-50">
                Reset
            </a>
        </form>
    </div>

    {{-- Tabs --}}
    <div class="mt-4 border-b border-slate-200 flex items-center gap-4 text-sm font-semibold">
        @php
            $tabs = [
                '' => ['All', $counts['all'] ?? 0],
                'images' => ['Images', $counts['images'] ?? 0],
                'icons' => ['Icons', $counts['icons'] ?? 0],
                'fonts' => ['Fonts', $counts['fonts'] ?? 0],
                'docs' => ['Docs', $counts['docs'] ?? 0],
            ];
            $active = $currentType ?? '';
        @endphp

        @foreach($tabs as $key => [$label, $count])
            <a href="{{ route('admin.media.picker', array_merge(request()->query(), ['type' => $key])) }}"
               class="px-2 py-2 -mb-px border-b-2 {{ $active === $key ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                {{ $label }} <span class="text-gray-400">({{ $count }})</span>
            </a>
        @endforeach
    </div>

    {{-- Upload tab --}}
    @if(($tab ?? 'library') === 'upload')
        <div class="mt-4 bg-white border rounded-xl p-4">
            <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="flex items-center gap-3 flex-wrap">
                @csrf
                <input type="file" name="files[]" multiple class="text-sm">
                <button class="px-3 py-2 rounded-md bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800">
                    Upload
                </button>
                <div class="text-xs text-gray-500">
                    Images + Icons + Fonts + PDFs (max 10MB each)
                </div>
            </form>

            @if($errors->any())
                <div class="mt-3 text-sm text-red-600">
                    {{ $errors->first() }}
                </div>
            @endif
        </div>
    @endif

    {{-- Library grid --}}
    <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        @foreach($media as $m)
            @php
                $url = $m->url ?? asset('storage/' . ltrim($m->path, '/'));
                $isImage = method_exists($m, 'isImage') ? $m->isImage() : (str_starts_with((string) $m->mime_type, 'image/'));
            @endphp

            <div class="bg-white border rounded-xl overflow-hidden hover:shadow-sm transition">
                <div class="aspect-square bg-slate-100 flex items-center justify-center overflow-hidden">
                    @if($isImage)
                        <img src="{{ $url }}" alt="" class="w-full h-full object-contain">
                    @else
                        <div class="text-xs text-slate-500 p-3 text-center break-all">
                            {{ $m->original_name ?? $m->filename }}
                        </div>
                    @endif
                </div>

                <div class="p-2 flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <div class="text-xs font-semibold text-slate-900 truncate">{{ $m->title ?? 'Untitled' }}</div>
                        <div class="text-[11px] text-slate-500 truncate">{{ $m->folder ?? '' }}</div>
                    </div>

                    <button type="button"
                        class="px-2 py-1 rounded-md bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800"
                        onclick="selectMedia(@js([
                            'id' => $m->id,
                            'url' => $url,
                            'title' => $m->title,
                            'mime_type' => $m->mime_type,
                        ]))">
                        Select
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $media->links() }}
    </div>

</div>

<script>
function selectMedia(payload) {
    window.parent.postMessage(
        { type: 'impart-media-selected', payload },
        window.location.origin
    );
}
</script>
</body>
</html>
