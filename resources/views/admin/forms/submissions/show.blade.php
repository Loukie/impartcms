<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.forms.submissions.index', $form) }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">← Submissions</a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Submission #{{ $submission->id }}</h2>
                    <div class="text-xs text-gray-500">{{ $form->name }} ({{ $form->slug }})</div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase">Created</div>
                            <div class="mt-1 text-gray-900">{{ optional($submission->created_at)->toDayDateTimeString() }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase">Status</div>
                            <div class="mt-1 text-gray-900">{{ strtoupper($submission->mail_status) }}</div>
                            @if($submission->mail_sent_at)
                                <div class="mt-1 text-sm text-gray-600">Sent: {{ $submission->mail_sent_at->toDayDateTimeString() }}</div>
                            @endif
                            @if($submission->spam_reason)
                                <div class="mt-1 text-sm text-gray-600">Reason: {{ $submission->spam_reason }}</div>
                            @endif
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase">To</div>
                            <div class="mt-1 text-gray-900">{{ $submission->to_email ?: '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase">IP / UA</div>
                            <div class="mt-1 text-gray-900">{{ $submission->ip ?: '—' }}</div>
                            <div class="mt-1 text-xs text-gray-600 break-all">{{ $submission->user_agent ?: '—' }}</div>
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Payload</div>
                        <pre class="mt-2 rounded-lg bg-gray-900 text-gray-100 p-4 text-xs overflow-x-auto">{{ json_encode($submission->payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>

                    @if($submission->mail_error)
                        <div>
                            <div class="text-xs font-semibold text-rose-700 uppercase">Mail error</div>
                            <pre class="mt-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-900 p-4 text-xs overflow-x-auto">{{ $submission->mail_error }}</pre>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
