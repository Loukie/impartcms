<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">AI Visual Audit</h2>
            <a href="{{ route('admin.pages.index') }}" class="underline text-sm text-gray-600 hover:text-gray-900">Back to Pages</a>
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

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="prose max-w-none">
                        <p>
                            This tool captures a screenshot of one of your pages and a screenshot of a reference site,
                            then asks the AI to redesign your page to match the reference's hierarchy and vibe.
                        </p>
                        <ul>
                            <li>✅ Saves as <strong>draft</strong> (safe)</li>
                            <li>✅ Keeps your existing page system intact</li>
                            <li>⚠️ Requires Chrome/Edge installed (no Node)</li>
                        </ul>
                    </div>

                    <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="text-sm font-semibold text-gray-900">Setup (one-time)</div>
                        <div class="mt-2 text-xs text-gray-700">
                            Ensure <strong>Google Chrome</strong> or <strong>Microsoft Edge</strong> is installed. No Node required ✅<br>
                            Optional: if your browser is in a custom path, add this to your <code>.env</code>:
                            <div class="mt-2 font-mono text-[11px] bg-white border rounded p-3 overflow-auto">
                                AI_SCREENSHOT_BIN=C:\Path\To\chrome.exe
                            </div>
                            Quick check in CMD:
                            <div class="mt-2 font-mono text-[11px] bg-white border rounded p-3 overflow-auto">
                                where chrome<br>
                                where msedge
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.ai.visual-audit.redesign') }}" class="mt-6 space-y-4">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Select a page</label>
                            <select name="page_id" class="mt-1 w-full rounded-md border-gray-300">
                                <option value="">— choose —</option>
                                @foreach($pages as $p)
                                    <option value="{{ $p->id }}">{{ $p->title }} ({{ $p->slug }}) · {{ strtoupper($p->status) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Reference site URL</label>
                            <input type="url" name="reference_url" class="mt-1 w-full rounded-md border-gray-300" placeholder="https://example.com">
                            <p class="mt-1 text-xs text-gray-500">Tip: use the specific page URL that best matches your target look.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Extra instruction (optional)</label>
                            <textarea name="instruction" rows="3" class="mt-1 w-full rounded-md border-gray-300" placeholder="e.g. Use a bold hero, lots of whitespace, and a clean card layout."></textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs hover:bg-gray-800">
                                Capture &amp; Redesign (Draft)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
