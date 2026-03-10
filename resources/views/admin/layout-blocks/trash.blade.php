<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Header &amp; Footer Trash</h2>

            <a href="{{ route('admin.layout-blocks.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-green-900 border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 overflow-hidden">
                <form method="POST" action="{{ route('admin.layout-blocks.trash.bulk') }}" id="bulkLayoutTrashForm" onsubmit="return confirm('Permanently delete selected blocks? This cannot be undone.');">
                    @csrf
                    <div class="p-4 border-b border-gray-200">
                        <button type="submit" id="bulkLayoutTrashBtn" disabled class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white rounded-md text-xs font-semibold uppercase tracking-widest hover:bg-red-700 disabled:opacity-50">
                            Delete Selected
                        </button>
                    </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3">
                            <input type="checkbox" id="bulk-layout-select-all" />
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($blocks as $b)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" name="ids[]" value="{{ $b->id }}" class="bulk-layout-checkbox" />
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $b->name }}</div>
                                <div class="text-xs text-gray-500">Deleted {{ $b->deleted_at?->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ ucfirst($b->type) }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <form method="POST" action="{{ route('admin.layout-blocks.restore', $b) }}">
                                        @csrf
                                        <button class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-900 uppercase tracking-widest hover:bg-gray-50" type="submit">Restore</button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.layout-blocks.force-destroy', $b) }}" onsubmit="return confirm('Delete permanently?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white rounded-md text-xs font-semibold uppercase tracking-widest hover:bg-red-700" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-6 py-8 text-sm text-gray-500" colspan="4">Trash is empty.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

                <div class="p-4">
                    {{ $blocks->links() }}
                </div>
                </form>
            </div>

        </div>
    </div>

    <script>
        (function(){
            const form = document.getElementById('bulkLayoutTrashForm');
            if (!form) return;
            const selectAll = document.getElementById('bulk-layout-select-all');
            const boxes = Array.from(form.querySelectorAll('.bulk-layout-checkbox'));
            const btn = document.getElementById('bulkLayoutTrashBtn');

            function sync(){
                btn.disabled = !boxes.some(cb => cb.checked);
            }

            selectAll?.addEventListener('change', function(){
                boxes.forEach(cb => cb.checked = selectAll.checked);
                sync();
            });

            boxes.forEach(cb => cb.addEventListener('change', sync));
            sync();
        })();
    </script>
</x-admin-layout>
