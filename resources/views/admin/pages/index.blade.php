<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Pages
            </h2>

            <div class="flex items-center gap-3">

                <form method="POST" action="{{ route('admin.pages.clearHomepage') }}" class="inline"
                      onsubmit="return confirm('Clear homepage selection? This will unmark the homepage and let you trash/delete pages freely.');">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                        Clear Home
                    </button>
                </form>

                <a href="{{ route('admin.pages.trash') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                    Trash
                </a>

                <a href="{{ route('admin.pages.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                    New Page
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
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @php
                        $baseTabQuery = request()->except('page', 'status');
                        $isAll = ($currentStatus ?? '') === '';
                        $isPublished = ($currentStatus ?? '') === 'published';
                        $isDraft = ($currentStatus ?? '') === 'draft';
                    @endphp

                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="flex items-center gap-1 text-sm">
                            <a href="{{ route('admin.pages.index', $baseTabQuery) }}"
                               class="px-3 py-1.5 rounded-lg font-medium transition-colors {{ $isAll ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-500 hover:text-zinc-900 hover:bg-zinc-50' }}">
                                All <span class="text-zinc-400 text-xs">({{ $counts['all'] ?? 0 }})</span>
                            </a>
                            <a href="{{ route('admin.pages.index', array_merge($baseTabQuery, ['status' => 'published'])) }}"
                               class="px-3 py-1.5 rounded-lg font-medium transition-colors {{ $isPublished ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-500 hover:text-zinc-900 hover:bg-zinc-50' }}">
                                Published <span class="text-zinc-400 text-xs">({{ $counts['published'] ?? 0 }})</span>
                            </a>
                            <a href="{{ route('admin.pages.index', array_merge($baseTabQuery, ['status' => 'draft'])) }}"
                               class="px-3 py-1.5 rounded-lg font-medium transition-colors {{ $isDraft ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-500 hover:text-zinc-900 hover:bg-zinc-50' }}">
                                Drafts <span class="text-zinc-400 text-xs">({{ $counts['draft'] ?? 0 }})</span>
                            </a>
                        </div>

                        <form method="GET" action="{{ route('admin.pages.index') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                            <input type="hidden" name="status" value="{{ $currentStatus }}">

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Show</label>
                                <select name="homepage" class="mt-1 rounded-md border-gray-300">
                                    <option value="" {{ ($currentHomepage ?? '') === '' ? 'selected' : '' }}>All pages</option>
                                    <option value="1" {{ ($currentHomepage ?? '') === '1' ? 'selected' : '' }}>Homepage only</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sort</label>
                                <select name="sort" class="mt-1 rounded-md border-gray-300">
                                    <option value="updated_desc" {{ ($currentSort ?? '') === 'updated_desc' ? 'selected' : '' }}>Recently updated</option>
                                    <option value="updated_asc" {{ ($currentSort ?? '') === 'updated_asc' ? 'selected' : '' }}>Least recently updated</option>
                                    <option value="created_desc" {{ ($currentSort ?? '') === 'created_desc' ? 'selected' : '' }}>Newest</option>
                                    <option value="created_asc" {{ ($currentSort ?? '') === 'created_asc' ? 'selected' : '' }}>Oldest</option>
                                    <option value="title_asc" {{ ($currentSort ?? '') === 'title_asc' ? 'selected' : '' }}>Title A→Z</option>
                                    <option value="title_desc" {{ ($currentSort ?? '') === 'title_desc' ? 'selected' : '' }}>Title Z→A</option>
                                </select>
                            </div>

                            <div class="sm:ml-4">
                                <label class="block text-sm font-medium text-gray-700">Search</label>
                                <div class="mt-1 flex items-center gap-2">
                                    <input type="text" name="q" value="{{ $currentQuery }}"
                                           placeholder="Search title or slug…"
                                           class="w-full sm:w-64 rounded-md border-gray-300" />

                                    <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                        Apply
                                    </button>

                                    <a href="{{ route('admin.pages.index') }}"
                                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <form method="POST" action="{{ route('admin.pages.bulk') }}" id="bulkForm" onsubmit="return confirm('Move selected pages to trash?');">
                        @csrf
                        <div class="mb-3">
                            <button type="submit" id="bulkTrashBtn" disabled
                                    class="px-4 py-2 bg-red-600 text-white rounded-md text-xs uppercase font-semibold hover:bg-red-700">
                                Trash Selected
                            </button>
                        </div>

                        <div class="mt-6 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 w-8">
                                            <input type="checkbox" id="bulk-select-all" class="bulk-checkbox-header" />
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>

                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($pages as $page)
                                    <tr>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <input type="checkbox" name="ids[]" value="{{ $page->id }}" class="bulk-checkbox" />
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap font-medium text-gray-900">
                                            {{ $page->title }}
                                            @if($page->is_homepage)
                                                <span class="ml-2 text-xs px-2 py-0.5 rounded border
                                                    {{ $page->status === 'published'
                                                        ? 'border-gray-200 bg-gray-50 text-gray-700'
                                                        : 'border-yellow-200 bg-yellow-50 text-yellow-800' }}">
                                                    {{ $page->status === 'published' ? 'Home' : 'Home (draft)' }}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700">
                                            {{ $page->slug }}
                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs rounded border
                                                {{ $page->status === 'published'
                                                    ? 'bg-green-50 text-green-800 border-green-200'
                                                    : 'bg-yellow-50 text-yellow-800 border-yellow-200' }}">
                                                {{ strtoupper($page->status) }}
                                            </span>
                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700 text-sm">
                                            {{ optional($page->created_at)->format('Y-m-d H:i') }}
                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700 text-sm">
                                            {{ optional($page->updated_at)->format('Y-m-d H:i') }}
                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @if($page->status === ‘published’)
                                                    <a href="{{ url(‘/’ . ltrim($page->slug, ‘/’)) }}" target="_blank"
                                                       class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                                        View Live
                                                    </a>
                                                @else
                                                    <a href="{{ route(‘pages.preview’, $page) }}" target="_blank"
                                                       class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                                        Preview
                                                    </a>
                                                @endif

                                                <a href="{{ route(‘admin.pages.edit’, $page) }}"
                                                   class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                                    Edit
                                                </a>

                                                @if($page->is_homepage)
                                                    <form method="POST" action="{{ route(‘admin.pages.unsetHomepage’, $page) }}" class="inline"
                                                          onsubmit="return confirm(‘Unset this page as the homepage?’);">
                                                        @csrf
                                                        <button type="submit"
                                                                class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                                            Unset Home
                                                        </button>
                                                    </form>
                                                @elseif($page->status === ‘published’)
                                                    <form method="POST" action="{{ route(‘admin.pages.setHomepage’, $page) }}" class="inline"
                                                          onsubmit="return confirm(‘Set this page as the homepage (/)?’);">
                                                        @csrf
                                                        <button type="submit"
                                                                class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                                            Set Home
                                                        </button>
                                                    </form>
                                                @endif

                                                @if(!$page->is_homepage)
                                                    <form method="POST" action="{{ route(‘admin.pages.destroy’, $page) }}"
                                                          onsubmit="return confirm(‘Move this page to trash?’);" class="inline">
                                                        @csrf
                                                        @method(‘DELETE’)
                                                        <button type="submit"
                                                                class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white rounded-md text-xs font-semibold uppercase tracking-widest hover:bg-red-700">
                                                            Trash
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="inline-flex items-center px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-md text-xs font-semibold text-gray-400 uppercase tracking-widest cursor-not-allowed"
                                                          title="Unset as homepage first.">
                                                        Trash
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 py-6 text-center text-gray-500">
                                            No pages found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $pages->links() }}
                    </div>
                    </form>

                    <script>
                        (function(){
                            const form = document.getElementById('bulkForm');
                            if (!form) return;
                            const selectAll = form.querySelector('#bulk-select-all');
                            const checkboxes = Array.from(form.querySelectorAll('.bulk-checkbox'));
                            const submitBtn = form.querySelector('#bulkTrashBtn');
                            function update(){
                                const any = checkboxes.some(cb=>cb.checked);
                                submitBtn.disabled = !any;
                            }
                            if(selectAll){
                                selectAll.addEventListener('change', ()=>{
                                    checkboxes.forEach(cb=>cb.checked = selectAll.checked);
                                    update();
                                });
                            }
                            checkboxes.forEach(cb=>cb.addEventListener('change', update));
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
