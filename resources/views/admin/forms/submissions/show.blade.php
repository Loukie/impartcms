<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Submission</h2>
                <div class="text-sm text-gray-600 mt-1">{{ $form->name }}</div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.forms.submissions.index', $form) }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Date</div>
                            <div class="text-gray-800">{{ $submission->created_at?->format('Y-m-d H:i:s') }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-widest">IP</div>
                            <div class="text-gray-800">{{ $submission->ip }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-widest">User agent</div>
                            <div class="text-gray-800 break-words">{{ $submission->user_agent }}</div>
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-2">Payload</div>
                        <pre class="text-xs bg-gray-50 border rounded p-3 overflow-x-auto">{{ json_encode($submission->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
