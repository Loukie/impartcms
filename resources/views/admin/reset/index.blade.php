<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Data Reset</h2>
    </x-slot>

    <div class="py-8">
        <div class="sm:px-6 lg:px-8 max-w-3xl">

            @if (session('success'))
                <div class="mb-6 p-4 rounded-lg bg-green-50 text-green-800 border border-green-200 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 p-4 rounded-lg bg-red-50 text-red-800 border border-red-200 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-6 p-4 rounded-lg bg-amber-50 border border-amber-200 text-sm text-amber-800">
                <strong>Warning:</strong> These actions permanently delete all records including trash. This cannot be undone.
            </div>

            <div class="bg-white shadow-sm rounded-lg divide-y divide-gray-100">

                {{-- Pages --}}
                <div class="flex items-center justify-between p-5">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Pages</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $counts['pages'] }} record(s) including trash</p>
                    </div>
                    <form method="POST" action="{{ route('admin.reset.clear') }}"
                          onsubmit="return confirm('Permanently delete ALL pages and their trash? This cannot be undone.')">
                        @csrf
                        <input type="hidden" name="type" value="pages">
                        <button type="submit"
                                class="px-3 py-1.5 text-xs font-medium rounded bg-red-600 text-white hover:bg-red-700 transition"
                                @if($counts['pages'] === 0) disabled @endif>
                            Clear all
                        </button>
                    </form>
                </div>

                {{-- Media --}}
                <div class="flex items-center justify-between p-5">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Media</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $counts['media'] }} record(s) including trash</p>
                    </div>
                    <form method="POST" action="{{ route('admin.reset.clear') }}"
                          onsubmit="return confirm('Permanently delete ALL media files and their trash? This cannot be undone.')">
                        @csrf
                        <input type="hidden" name="type" value="media">
                        <button type="submit"
                                class="px-3 py-1.5 text-xs font-medium rounded bg-red-600 text-white hover:bg-red-700 transition"
                                @if($counts['media'] === 0) disabled @endif>
                            Clear all
                        </button>
                    </form>
                </div>

                {{-- Header & Footer --}}
                <div class="flex items-center justify-between p-5">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Header &amp; Footer</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $counts['layout_blocks'] }} record(s) including trash</p>
                    </div>
                    <form method="POST" action="{{ route('admin.reset.clear') }}"
                          onsubmit="return confirm('Permanently delete ALL header & footer blocks and their trash? This cannot be undone.')">
                        @csrf
                        <input type="hidden" name="type" value="layout_blocks">
                        <button type="submit"
                                class="px-3 py-1.5 text-xs font-medium rounded bg-red-600 text-white hover:bg-red-700 transition"
                                @if($counts['layout_blocks'] === 0) disabled @endif>
                            Clear all
                        </button>
                    </form>
                </div>

                {{-- Custom code --}}
                <div class="flex items-center justify-between p-5">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Custom code</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $counts['snippets'] }} record(s) including trash</p>
                    </div>
                    <form method="POST" action="{{ route('admin.reset.clear') }}"
                          onsubmit="return confirm('Permanently delete ALL custom code snippets and their trash? This cannot be undone.')">
                        @csrf
                        <input type="hidden" name="type" value="snippets">
                        <button type="submit"
                                class="px-3 py-1.5 text-xs font-medium rounded bg-red-600 text-white hover:bg-red-700 transition"
                                @if($counts['snippets'] === 0) disabled @endif>
                            Clear all
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</x-admin-layout>
