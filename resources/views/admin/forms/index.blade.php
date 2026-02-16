<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Forms</h2>

            <div class="flex items-center gap-2">
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

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-xs uppercase text-gray-500 border-b">
                                <tr>
                                    <th class="py-3 text-left">Name</th>
                                    <th class="py-3 text-left">Slug</th>
                                    <th class="py-3 text-left">Active</th>
                                    <th class="py-3 text-left">Shortcode</th>
                                    <th class="py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse($forms as $form)
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 font-semibold text-gray-900">{{ $form->name }}</td>
                                        <td class="py-3 text-gray-700">{{ $form->slug }}</td>
                                        <td class="py-3">
                                            @if($form->is_active)
                                                <span class="inline-flex items-center px-2 py-1 rounded bg-green-50 text-green-800 border border-green-200 text-xs font-semibold">Yes</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-1 rounded bg-gray-50 text-gray-700 border border-gray-200 text-xs font-semibold">No</span>
                                            @endif
                                        </td>
                                        <td class="py-3 text-gray-700">
                                            <code class="px-2 py-1 rounded bg-gray-100">[form slug="{{ $form->slug }}"]</code>
                                        </td>
                                        <td class="py-3 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <a href="{{ route('admin.forms.submissions.index', $form) }}"
                                                   class="px-3 py-1.5 rounded border border-gray-300 text-xs font-semibold hover:bg-gray-50">Submissions</a>
                                                <a href="{{ route('admin.forms.edit', $form) }}"
                                                   class="px-3 py-1.5 rounded bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800">Edit</a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="py-6 text-gray-600" colspan="5">No forms yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $forms->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
