<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                New Page
            </h2>

            <a href="{{ route('admin.pages.index') }}"
               class="underline text-sm text-gray-600 hover:text-gray-900">
                Cancel
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-3 rounded bg-green-50 text-green-800 border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.pages.store') }}" class="space-y-6">
                        @csrf

                        {{-- Core --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" name="title" value="{{ old('title') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug') }}"
                                   placeholder="e.g. about or info/team"
                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                            <p class="mt-1 text-xs text-gray-500">Allowed: letters, numbers, dashes, and /</p>
                            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Body</label>
                            <textarea name="body" rows="10"
                                      class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                                      placeholder="Write content here...">{{ old('body') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                Tip: embed forms with <code class="px-1 py-0.5 bg-gray-100 rounded">[form slug="contact"]</code>
                            </p>
                            @error('body') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Template</label>
                                <input type="text" name="template" value="{{ old('template') }}"
                                       placeholder="optional"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                @error('template') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="sm:col-span-2 flex items-center gap-3 pt-6">
                                <input id="is_homepage" type="checkbox" name="is_homepage" value="1"
                                       class="rounded border-gray-300 text-gray-900 focus:ring-gray-500"
                                       {{ old('is_homepage') ? 'checked' : '' }}>
                                <label for="is_homepage" class="text-sm text-gray-700">
                                    Set as homepage
                                </label>
                            </div>
                        </div>

                        {{-- SEO --}}
                        <div class="pt-4 border-t">
                            <h3 class="text-lg font-semibold text-gray-800">SEO</h3>

                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Meta Title</label>
                                    <input type="text" name="meta_title" value="{{ old('meta_title') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                    @error('meta_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Canonical URL</label>
                                    <input type="text" name="canonical_url" value="{{ old('canonical_url') }}"
                                           placeholder="https://example.com/about"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                    @error('canonical_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Meta Description</label>
                                    <textarea name="meta_description" rows="3"
                                              class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">{{ old('meta_description') }}</textarea>
                                    @error('meta_description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Robots</label>
                                    <input type="text" name="robots" value="{{ old('robots') }}"
                                           placeholder="index,follow"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                    @error('robots') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <h4 class="mt-6 font-semibold text-gray-800">Open Graph</h4>
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">OG Title</label>
                                    <input type="text" name="og_title" value="{{ old('og_title') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">OG Image URL</label>
                                    <input type="text" name="og_image_url" value="{{ old('og_image_url') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">OG Description</label>
                                    <textarea name="og_description" rows="3"
                                              class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">{{ old('og_description') }}</textarea>
                                </div>
                            </div>

                            <h4 class="mt-6 font-semibold text-gray-800">Twitter</h4>
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Twitter Title</label>
                                    <input type="text" name="twitter_title" value="{{ old('twitter_title') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Twitter Image URL</label>
                                    <input type="text" name="twitter_image_url" value="{{ old('twitter_image_url') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Twitter Description</label>
                                    <textarea name="twitter_description" rows="3"
                                              class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">{{ old('twitter_description') }}</textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="pt-4 border-t flex items-center justify-between">
                            <a href="{{ route('admin.pages.index') }}"
                               class="underline text-sm text-gray-600 hover:text-gray-900">
                                Cancel
                            </a>

                            <div class="flex gap-3">
                                <button type="submit" name="action" value="draft"
                                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                    Save Draft
                                </button>

                                <button type="submit" name="action" value="publish"
                                    class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                    Publish
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
