<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.forms.index') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">← Forms</a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Submissions</h2>
                    <div class="text-xs text-gray-500">{{ $form->name }} ({{ $form->slug }})</div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.forms.edit', $form) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">Edit form</a>
                <a href="{{ route('admin.forms.submissions.export', $form, ['status' => $status]) }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">Export CSV</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6">
                    <div class="flex flex-wrap items-center gap-2">
                        @php
                            $pill = 'inline-flex items-center px-3 py-1.5 rounded-full border text-xs font-semibold';
                            $active = 'bg-gray-900 border-gray-900 text-white';
                            $inactive = 'bg-white border-gray-300 text-gray-800 hover:bg-gray-50';
                            $makeUrl = fn($s) => route('admin.forms.submissions.index', [$form, 'status' => $s]);
                        @endphp

                        <a href="{{ $makeUrl('') }}" class="{{ $pill }} {{ $status==='' ? $active : $inactive }}">All ({{ array_sum($stats) }})</a>
                        <a href="{{ $makeUrl('sent') }}" class="{{ $pill }} {{ $status==='sent' ? $active : $inactive }}">Sent ({{ $stats['sent'] ?? 0 }})</a>
                        <a href="{{ $makeUrl('failed') }}" class="{{ $pill }} {{ $status==='failed' ? $active : $inactive }}">Failed ({{ $stats['failed'] ?? 0 }})</a>
                        <a href="{{ $makeUrl('skipped') }}" class="{{ $pill }} {{ $status==='skipped' ? $active : $inactive }}">Skipped ({{ $stats['skipped'] ?? 0 }})</a>
                        <a href="{{ $makeUrl('pending') }}" class="{{ $pill }} {{ $status==='pending' ? $active : $inactive }}">Pending ({{ $stats['pending'] ?? 0 }})</a>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-xs uppercase text-gray-500 border-b">
                                <tr>
                                    <th class="py-3 text-left">Date</th>
                                    <th class="py-3 text-left">Status</th>
                                    <th class="py-3 text-left">To</th>
                                    <th class="py-3 text-left">IP</th>
                                    <th class="py-3 text-right">View</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse($submissions as $s)
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 text-gray-800">{{ optional($s->created_at)->format('Y-m-d H:i') }}</td>
                                        <td class="py-3">
                                            @php
                                                $map = [
                                                    'sent' => 'bg-green-50 text-green-800 border-green-200',
                                                    'failed' => 'bg-rose-50 text-rose-800 border-rose-200',
                                                    'skipped' => 'bg-gray-50 text-gray-700 border-gray-200',
                                                    'pending' => 'bg-amber-50 text-amber-800 border-amber-200',
                                                ];
                                                $cls = $map[$s->mail_status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-1 rounded border text-xs font-semibold {{ $cls }}">
                                                {{ strtoupper($s->mail_status) }}
                                            </span>
                                            @if($s->spam_reason)
                                                <div class="mt-1 text-xs text-gray-500">{{ $s->spam_reason }}</div>
                                            @endif
                                        </td>
                                        <td class="py-3 text-gray-700">{{ $s->to_email ?: '—' }}</td>
                                        <td class="py-3 text-gray-700">{{ $s->ip ?: '—' }}</td>
                                        <td class="py-3 text-right">
                                            <a href="{{ route('admin.forms.submissions.show', [$form, $s]) }}"
                                               class="px-3 py-1.5 rounded border border-gray-300 text-xs font-semibold hover:bg-gray-50">Open</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="py-6 text-gray-600" colspan="5">No submissions yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $submissions->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
