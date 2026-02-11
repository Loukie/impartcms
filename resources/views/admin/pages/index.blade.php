<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Pages
            </h2>

            <div class="flex items-center gap-3">
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
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
                                        <td class="px-3 py-2 whitespace-nowrap font-medium text-gray-900">
                                            {{ $page->title }}
                                            @if($page->is_homepage)
                                                <span class="ml-2 text-xs px-2 py-0.5 rounded border border-gray-200 bg-gray-50 text-gray-700">
                                                    Home
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
                                            <div class="flex items-center justify-end gap-4">
                                                @if($page->status === 'published')
                                                    <a href="{{ url('/' . ltrim($page->slug, '/')) }}"
                                                       target="_blank"
                                                       class="underline text-sm text-gray-600 hover:text-gray-900">
                                                        View Live
                                                    </a>
                                                @else
                                                    <a href="{{ route('pages.preview', $page) }}"
                                                       target="_blank"
                                                       class="underline text-sm text-gray-600 hover:text-gray-900">
                                                        Preview Draft
                                                    </a>
                                                @endif

                                                <a href="{{ route('admin.pages.edit', $page) }}"
                                                   class="text-indigo-600 hover:text-indigo-900 font-semibold text-sm">
                                                    Edit
                                                </a>

                                                @if($page->status === 'published' && !$page->is_homepage)
                                                    <form method="POST" action="{{ route('admin.pages.setHomepage', $page) }}" class="inline"
                                                          onsubmit="return confirm('Set this page as the homepage (/)?');">
                                                        @csrf
                                                        <button type="submit"
                                                                class="text-gray-700 hover:text-gray-900 font-semibold text-sm">
                                                            Set Home
                                                        </button>
                                                    </form>
                                                @endif

                                                <form method="POST" action="{{ route('admin.pages.destroy', $page) }}"
                                                      onsubmit="return confirm('Move this page to trash?');"
                                                      class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="text-red-600 hover:text-red-800 font-semibold text-sm">
                                                        Trash
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-gray-500">
                                            No pages yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($pages, 'links'))
                        <div class="mt-6">
                            {{ $pages->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>