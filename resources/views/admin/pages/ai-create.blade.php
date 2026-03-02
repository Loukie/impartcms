<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    New Page (AI)
                </h2>

                <a href="{{ route('admin.pages.index') }}"
                   class="underline text-sm text-gray-600 hover:text-gray-900">
                    Back to Pages
                </a>
            </div>

            <div class="text-xs text-gray-500">
                Requires <code class="px-1 py-0.5 bg-gray-100 rounded">OPENAI_API_KEY</code> in <code class="px-1 py-0.5 bg-gray-100 rounded">.env</code>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
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
                    <form method="POST" action="{{ route('admin.pages.ai.store') }}" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title</label>
                                <input type="text" name="title" value="{{ old('title') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                                       placeholder="e.g. About us" required>
                                @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Slug (optional)</label>
                                <input type="text" name="slug" value="{{ old('slug') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                                       placeholder="e.g. about or services/web">
                                <p class="mt-1 text-xs text-gray-500">Allowed: letters, numbers, dashes, and /</p>
                                @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Template</label>
                                <input type="text" name="template" value="{{ old('template', 'blank') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                                       placeholder="blank">
                                <p class="mt-1 text-xs text-gray-500">Default: <span class="font-semibold">blank</span></p>
                                @error('template') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Styling mode</label>
                                <select name="style_mode" class="mt-1 block w-full rounded-md border-gray-300">
                                    @php($mode = old('style_mode', 'inline'))
                                    <option value="inline" @selected($mode === 'inline')>Inline styles (recommended)</option>
                                    <option value="classes" @selected($mode === 'classes')>Classes (advanced)</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Your front-end theme doesn’t always load app CSS, so inline is safest.</p>
                                @error('style_mode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-center gap-3 pt-7">
                                <input id="full_document" type="checkbox" name="full_document" value="1"
                                       class="rounded border-gray-300 text-gray-900 focus:ring-gray-500"
                                       {{ old('full_document') ? 'checked' : '' }}>
                                <label for="full_document" class="text-sm text-gray-700">
                                    Full HTML document
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Brief (what should this page say/do?)</label>
                            <textarea name="brief" rows="10"
                                      class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                                      placeholder="Write the page goal, target audience, key sections, offers, CTAs, any links you want included…" required>{{ old('brief') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                The AI will generate HTML and it will be saved into the page <span class="font-semibold">Body</span> field.
                                Scripts/iframes are blocked for safety.
                            </p>
                            @error('brief') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between pt-2">
                            <div class="text-xs text-gray-500">
                                Tip: you can always tweak the HTML in the normal editor after generation.
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.pages.index') }}"
                                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                    Cancel
                                </a>

                                <button type="submit" name="action" value="draft"
                                        class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                    Generate Draft
                                </button>

                                <button type="submit" name="action" value="publish"
                                        class="inline-flex items-center px-4 py-2 bg-emerald-700 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-emerald-800">
                                    Generate &amp; Publish
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
