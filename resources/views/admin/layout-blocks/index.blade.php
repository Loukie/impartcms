<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Header &amp; Footer</h2>
                <p class="mt-1 text-sm text-gray-500">Create multiple headers/footers, target pages, and override per-page.</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.layout-blocks.trash') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                    Trash
                </a>

                <a href="{{ route('admin.layout-blocks.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                    New block
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-green-900 border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Options --}}
            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    <h3 class="text-sm font-semibold text-gray-900">Site-wide options</h3>
                    <p class="mt-1 text-xs text-gray-500">Disable header/footer injection globally (useful during testing).</p>

                    <form method="POST" action="{{ route('admin.layout-blocks.options') }}" class="mt-4 flex flex-wrap items-center gap-6">
                        @csrf
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="layout_header_enabled" value="1" {{ $headerEnabled ? 'checked' : '' }} class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                            Enable header blocks
                        </label>

                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="layout_footer_enabled" value="1" {{ $footerEnabled ? 'checked' : '' }} class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                            Enable footer blocks
                        </label>

                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                            Save
                        </button>
                    </form>
                </div>
            </div>

            {{-- Filters --}}
            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    <form method="GET" class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Search</label>
                            <input type="text" name="q" value="{{ $currentQuery }}" placeholder="Search by name…" class="mt-1 w-full rounded-md border-gray-300">
                        </div>

                        <div class="grid grid-cols-2 gap-4 md:flex md:items-end md:gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Type</label>
                                <select name="type" class="mt-1 w-full rounded-md border-gray-300">
                                    <option value="">All</option>
                                    <option value="header" {{ $currentType === 'header' ? 'selected' : '' }}>Header</option>
                                    <option value="footer" {{ $currentType === 'footer' ? 'selected' : '' }}>Footer</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <select name="status" class="mt-1 w-full rounded-md border-gray-300">
                                    <option value="">All</option>
                                    <option value="enabled" {{ $currentStatus === 'enabled' ? 'selected' : '' }}>Enabled</option>
                                    <option value="disabled" {{ $currentStatus === 'disabled' ? 'selected' : '' }}>Disabled</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                Apply
                            </button>

                            <a href="{{ route('admin.layout-blocks.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Reset</a>
                        </div>
                    </form>

                    <div class="mt-6 flex flex-wrap items-center gap-2">
                        <span class="text-xs text-gray-500">Counts:</span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-gray-100 text-gray-700">All {{ $counts['all'] }}</span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-green-100 text-green-700">Enabled {{ $counts['enabled'] }}</span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">Disabled {{ $counts['disabled'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Targeting</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($blocks as $b)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $b->name }}</div>
                                <div class="text-xs text-gray-500">Updated {{ $b->updated_at?->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ ucfirst($b->type) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $b->target_mode === 'only' ? 'Only selected pages' : ($b->target_mode === 'except' ? 'All except selected pages' : 'Global') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $b->priority }}
                            </td>
                            <td class="px-6 py-4">
                                @if($b->is_enabled)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-green-100 text-green-700">Enabled</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">Disabled</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('admin.layout-blocks.edit', $b) }}" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-900 uppercase tracking-widest hover:bg-gray-50">Edit</a>

                                    <form method="POST" action="{{ route('admin.layout-blocks.destroy', $b) }}" onsubmit="return confirm('Move to trash?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white rounded-md text-xs font-semibold uppercase tracking-widest hover:bg-red-700">Trash</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-6 py-8 text-sm text-gray-500" colspan="6">No blocks yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

                <div class="p-4">
                    {{ $blocks->links() }}
                </div>
            </div>

        </div>
    </div>
</x-admin-layout>
