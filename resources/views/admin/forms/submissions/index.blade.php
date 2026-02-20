<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Submissions</h2>
                <div class="text-sm text-gray-600 mt-1">{{ $form->name }} (<code class="px-1 py-0.5 bg-gray-100 rounded">{{ $form->slug }}</code>)</div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.forms.edit', $form) }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                    Back to builder
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b">
                                    <th class="py-2 pr-4">Date</th>
                                    <th class="py-2 pr-4">IP</th>
                                    <th class="py-2 pr-4">Summary</th>
                                    <th class="py-2">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($submissions as $s)
                                    <tr class="border-b last:border-0">
                                        <td class="py-3 pr-4 text-gray-700">{{ $s->created_at?->format('Y-m-d H:i') }}</td>
                                        <td class="py-3 pr-4 text-gray-500">{{ $s->ip }}</td>
                                        <td class="py-3 pr-4 text-gray-700">
                                            @php
                                                $payload = is_array($s->payload) ? $s->payload : [];
                                                $preview = collect($payload)->take(3)->map(fn($v,$k) => $k . ': ' . (is_scalar($v) ? $v : json_encode($v)))->implode(' • ');
                                            @endphp
                                            <span class="text-gray-700">{{ $preview }}</span>
                                        </td>
                                        <td class="py-3">
                                            <a href="{{ route('admin.forms.submissions.show', [$form, $s]) }}"
                                               class="inline-flex items-center px-3 py-1.5 rounded-md bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 text-gray-600">No submissions yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $submissions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
