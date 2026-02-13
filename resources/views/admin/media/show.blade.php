<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Media</h2>
                <p class="text-sm text-gray-600 mt-1">Edit file details and manage usage.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.media.index') }}"
                   class="text-sm text-gray-600 hover:text-gray-900">
                    Back to Media
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto sm:px-6 lg:px-8">
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Left: Preview + edit form --}}
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="border rounded-lg bg-slate-50 overflow-hidden">
                                <div class="aspect-video flex items-center justify-center">
                                    @if($media->isImage())
                                        <img src="{{ $media->url }}"
                                             alt="{{ $media->alt_text ?? $media->title ?? $media->original_name }}"
                                             class="max-h-[420px] w-auto object-contain" />
                                    @else
                                        <div class="text-sm text-slate-700">
                                            {{ strtoupper(pathinfo($media->original_name, PATHINFO_EXTENSION) ?: 'FILE') }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700">Public URL</label>
                                <div class="mt-1 flex items-center gap-2">
                                    <input id="mediaPublicUrl" type="text" readonly
                                           class="w-full rounded-md border-gray-300 text-sm"
                                           value="{{ $media->url }}" />
                                    <button type="button"
                                            onclick="navigator.clipboard.writeText(document.getElementById('mediaPublicUrl').value)"
                                            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                        Copy
                                    </button>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">Tip: paste this URL into page body or SEO image fields.</div>
                            </div>

                            <form method="POST" action="{{ route('admin.media.update', $media) }}" class="mt-6">
                                @csrf
                                @method('PUT')

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Title</label>
                                        <input type="text" name="title" value="{{ old('title', $media->title) }}"
                                               class="mt-1 w-full rounded-md border-gray-300" />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Alt text</label>
                                        <input type="text" name="alt_text" value="{{ old('alt_text', $media->alt_text) }}"
                                               class="mt-1 w-full rounded-md border-gray-300" />
                                        <div class="text-xs text-gray-500 mt-1">Used for accessibility + SEO (images only).</div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700">Caption</label>
                                    <textarea name="caption" rows="3" class="mt-1 w-full rounded-md border-gray-300">{{ old('caption', $media->caption) }}</textarea>
                                </div>

                                <div class="mt-6 flex items-center justify-between">
                                    <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                        Save
                                    </button>

                                    {{-- Delete lives OUTSIDE the update form (no nested forms) --}}
                                    <a href="#media-delete" class="sr-only">Delete</a>
                                </div>
                            </form>

                            <form id="media-delete" method="POST" action="{{ route('admin.media.destroy', $media) }}" class="mt-3 flex justify-end" onsubmit="return confirm('Delete this file?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        @if(($usage['is_used'] ?? false)) disabled @endif
                                        class="inline-flex items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest
                                               {{ ($usage['is_used'] ?? false) ? 'bg-red-200 text-red-700 cursor-not-allowed' : 'bg-red-600 text-white hover:bg-red-500' }}">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Right: File info + usage --}}
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-sm font-semibold text-gray-900">File info</h3>

                            <dl class="mt-3 space-y-2 text-sm">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">Name</dt>
                                    <dd class="text-gray-900 text-right break-all">{{ $media->original_name }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">Folder</dt>
                                    <dd class="text-gray-900 text-right">{{ $media->folder }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">Type</dt>
                                    <dd class="text-gray-900 text-right">{{ $media->mime_type }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">Size</dt>
                                    <dd class="text-gray-900 text-right">{{ number_format(($media->size_bytes ?? 0) / 1024, 1) }} KB</dd>
                                </div>
                                @if(!empty($media->width) && !empty($media->height))
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-gray-500">Dimensions</dt>
                                        <dd class="text-gray-900 text-right">{{ $media->width }} × {{ $media->height }}</dd>
                                    </div>
                                @endif
                            </dl>

                            <hr class="my-5">

                            <h3 class="text-sm font-semibold text-gray-900">Where used</h3>
                            <div class="mt-2 text-sm text-gray-700">
                                @if(($usage['is_used'] ?? false) && !empty($usage['where_used'] ?? []))
                                    <ul class="list-disc pl-5 space-y-1">
                                        @foreach($usage['where_used'] as $u)
                                            <li>{{ $u }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="text-gray-500">No usage detected yet.</div>
                                    <div class="text-xs text-gray-500 mt-2">
                                        (We scan Page body + SEO OG/Twitter image URLs. Builder integration will make this perfect later.)
                                    </div>
                                @endif
                            </div>

                            @if(($usage['is_used'] ?? false))
                                <div class="mt-4 p-3 rounded bg-amber-50 border border-amber-200 text-amber-900 text-sm">
                                    This file appears to be in use. Remove it from pages first, then delete.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
