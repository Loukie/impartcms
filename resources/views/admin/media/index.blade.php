<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Media</h2>
                <p class="text-sm text-gray-600 mt-1">Upload and manage files. Public pages stay fast — the builder never runs on the front-end.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
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
                <div class="p-6">

                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <form method="GET" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Type</label>
                                <select name="type" class="mt-1 rounded-md border-gray-300">
                                    <option value="" {{ $currentType === '' ? 'selected' : '' }}>All</option>
                                    <option value="images" {{ $currentType === 'images' ? 'selected' : '' }}>Images</option>
                                    <option value="docs" {{ $currentType === 'docs' ? 'selected' : '' }}>Docs</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Folder</label>
                                <select name="folder" class="mt-1 rounded-md border-gray-300">
                                    <option value="" {{ $currentFolder === '' ? 'selected' : '' }}>All</option>
                                    @foreach($folders as $f)
                                        <option value="{{ $f }}" {{ $currentFolder === $f ? 'selected' : '' }}>{{ $f }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                    Filter
                                </button>
                                <a href="{{ route('admin.media.index') }}"
                                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                    Reset
                                </a>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="w-full lg:w-auto">
                            @csrf
                            <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Upload</label>
                                    <input name="files[]" type="file" multiple
                                           class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-gray-900 file:text-white hover:file:bg-gray-800"
                                           accept="image/*,.pdf">
                                    <div class="text-xs text-gray-500 mt-1">Images + PDFs (max 10MB each). Auto-organised into YYYY/MM.</div>
                                </div>
                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                    Upload
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        @foreach($media as $item)
                            <a href="{{ route('admin.media.show', $item) }}"
                               class="group border rounded-lg overflow-hidden hover:shadow-sm">
                                <div class="bg-gray-50 aspect-square flex items-center justify-center">
                                    @if($item->isImage())
                                        <img src="{{ $item->url }}" alt="{{ $item->alt_text ?? $item->original_name }}"
                                             class="h-full w-full object-cover group-hover:opacity-95">
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
                            </a>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $media->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
