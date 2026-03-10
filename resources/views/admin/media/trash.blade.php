<x-admin-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Media Trash</h2>
            <p class="text-sm text-gray-600 mt-1">Restore or permanently delete trashed media files.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-3 rounded bg-green-50 text-green-800 border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold">Trashed Media ({{ $trashed->total() }})</h3>
                        <a href="{{ route('admin.media.index') }}" class="px-4 py-2 rounded-md border text-sm font-semibold">
                            ← Back to Media
                        </a>
                    </div>

                    @if($trashed->isEmpty())
                        <div class="text-center py-12 text-gray-500">
                            <p class="text-lg">No trashed media files.</p>
                            <p class="text-sm mt-2">Deleted files will appear here and can be restored or permanently removed.</p>
                        </div>
                    @else
                        <form method="POST" action="{{ route('admin.media.trash.bulk') }}" id="bulkMediaTrashForm" onsubmit="return confirm('Permanently delete selected media? This cannot be undone.');">
                            @csrf
                            <div class="mb-4 flex items-center gap-2">
                                <button type="button" id="bulk-media-select-all" class="px-3 py-2 rounded-md border text-xs font-semibold">Select all on this page</button>
                                <button type="button" id="bulk-media-clear" class="px-3 py-2 rounded-md border text-xs font-semibold">Clear</button>
                                <button type="submit" id="bulkMediaTrashBtn" disabled class="px-3 py-2 rounded-md bg-red-600 text-white text-xs font-semibold hover:bg-red-700 disabled:opacity-50">
                                    Delete Selected
                                </button>
                            </div>
                        <div class="space-y-3">
                            @foreach($trashed as $media)
                                <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                                    <div class="flex items-center gap-4">
                                        <input type="checkbox" name="ids[]" value="{{ $media->id }}" class="bulk-media-checkbox w-4 h-4 text-red-600">
                                        @if($media->is_image)
                                            <img src="{{ $media->url }}" alt="" class="w-16 h-16 object-cover rounded">
                                        @else
                                            <div class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center text-gray-500 text-xs font-bold">
                                                {{ strtoupper($media->extension) }}
                                            </div>
                                        @endif
                                        
                                        <div>
                                            <p class="font-semibold">{{ $media->title ?: $media->original_name }}</p>
                                            <p class="text-sm text-gray-500">{{ $media->original_name }} • {{ number_format($media->size / 1024, 1) }} KB</p>
                                            <p class="text-xs text-gray-400">Deleted {{ $media->deleted_at->diffForHumans() }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="{{ route('admin.media.restore', $media->id) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-2 rounded-md bg-green-600 text-white text-xs font-semibold hover:bg-green-700">
                                                Restore
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.media.forceDelete', $media->id) }}"
                                              onsubmit="return confirm('Permanently delete this file? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-2 rounded-md bg-red-600 text-white text-xs font-semibold hover:bg-red-700">
                                                Delete Forever
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $trashed->links() }}
                        </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        (function(){
            const form = document.getElementById('bulkMediaTrashForm');
            if (!form) return;

            const boxes = Array.from(form.querySelectorAll('.bulk-media-checkbox'));
            const btn = document.getElementById('bulkMediaTrashBtn');
            const selectAllBtn = document.getElementById('bulk-media-select-all');
            const clearBtn = document.getElementById('bulk-media-clear');

            function sync() {
                btn.disabled = !boxes.some(cb => cb.checked);
            }

            selectAllBtn?.addEventListener('click', function() {
                boxes.forEach(cb => cb.checked = true);
                sync();
            });

            clearBtn?.addEventListener('click', function() {
                boxes.forEach(cb => cb.checked = false);
                sync();
            });

            boxes.forEach(cb => cb.addEventListener('change', sync));
            sync();
        })();
    </script>
</x-admin-layout>
