<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Forms</h2>
                <p class="text-sm text-gray-600 mt-1">Manage forms and submissions.</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.forms.trash') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                    Trash
                </a>

                <a href="{{ route('admin.forms.settings.edit') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                    Settings
                </a>

                <a href="{{ route('admin.forms.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                    New Form
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
                        $isActive = ($currentStatus ?? '') === 'active';
                        $isInactive = ($currentStatus ?? '') === 'inactive';
                    @endphp

                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="text-sm text-gray-600">
                            <a href="{{ route('admin.forms.index', $baseTabQuery) }}"
                               class="{{ $isAll ? 'font-semibold text-gray-900' : 'hover:text-gray-900' }}">
                                All <span class="text-gray-500">({{ $counts['all'] ?? 0 }})</span>
                            </a>
                            <span class="mx-2 text-gray-300">|</span>
                            <a href="{{ route('admin.forms.index', array_merge($baseTabQuery, ['status' => 'active'])) }}"
                               class="{{ $isActive ? 'font-semibold text-gray-900' : 'hover:text-gray-900' }}">
                                Active <span class="text-gray-500">({{ $counts['active'] ?? 0 }})</span>
                            </a>
                            <span class="mx-2 text-gray-300">|</span>
                            <a href="{{ route('admin.forms.index', array_merge($baseTabQuery, ['status' => 'inactive'])) }}"
                               class="{{ $isInactive ? 'font-semibold text-gray-900' : 'hover:text-gray-900' }}">
                                Inactive <span class="text-gray-500">({{ $counts['inactive'] ?? 0 }})</span>
                            </a>
                        </div>

                        <form method="GET" action="{{ route('admin.forms.index') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                            <input type="hidden" name="status" value="{{ $currentStatus }}">

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sort</label>
                                <select name="sort" class="mt-1 rounded-md border-gray-300">
                                    <option value="updated_desc" {{ ($currentSort ?? '') === 'updated_desc' ? 'selected' : '' }}>Recently updated</option>
                                    <option value="updated_asc" {{ ($currentSort ?? '') === 'updated_asc' ? 'selected' : '' }}>Least recently updated</option>
                                    <option value="created_desc" {{ ($currentSort ?? '') === 'created_desc' ? 'selected' : '' }}>Newest</option>
                                    <option value="created_asc" {{ ($currentSort ?? '') === 'created_asc' ? 'selected' : '' }}>Oldest</option>
                                    <option value="name_asc" {{ ($currentSort ?? '') === 'name_asc' ? 'selected' : '' }}>Name A→Z</option>
                                    <option value="name_desc" {{ ($currentSort ?? '') === 'name_desc' ? 'selected' : '' }}>Name Z→A</option>
                                </select>
                            </div>

                            <div class="sm:ml-4">
                                <label class="block text-sm font-medium text-gray-700">Search</label>
                                <div class="mt-1 flex items-center gap-2">
                                    <input type="text" name="q" value="{{ $currentQuery }}"
                                           placeholder="Search name or slug…"
                                           class="w-full sm:w-64 rounded-md border-gray-300" />

                                    <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                        Apply
                                    </button>

                                    <a href="{{ route('admin.forms.index') }}"
                                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submissions</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($forms as $form)
                                    <tr>
                                        <td class="px-3 py-2 whitespace-nowrap font-medium text-gray-900">
                                            {{ $form->name }}
                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700">
                                            {{ $form->slug }}
                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs rounded border {{ $form->is_active ? 'bg-green-50 text-green-800 border-green-200' : 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                                {{ $form->is_active ? 'Yes' : 'No' }}
                                            </span>
                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700 text-sm">
                                            {{ (int) ($form->submissions_count ?? 0) }}
                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700 text-sm">
                                            {{ optional($form->updated_at)->format('Y-m-d H:i') }}
                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-right">
                                            <div class="flex items-center justify-end gap-4">
                                                <a href="{{ route('admin.forms.submissions.index', $form) }}"
                                                   class="underline text-sm text-gray-600 hover:text-gray-900">
                                                    View submissions
                                                </a>

                                                <a href="{{ route('admin.forms.edit', $form) }}"
                                                   class="text-indigo-600 hover:text-indigo-900 font-semibold text-sm">
                                                    Edit
                                                </a>

                                                <form method="POST" action="{{ route('admin.forms.destroy', $form) }}"
                                                      onsubmit="return confirm('Move this form to trash?');"
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
                                            No forms found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($forms, 'links'))
                        <div class="mt-6">
                            {{ $forms->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
