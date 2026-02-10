<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Page
                </h2>

                <span class="px-2 py-1 text-xs rounded border
                    {{ $page->status === 'published' ? 'bg-green-50 text-green-800 border-green-200' : 'bg-yellow-50 text-yellow-800 border-yellow-200' }}">
                    {{ strtoupper($page->status) }}
                </span>
            </div>

            <div class="flex items-center gap-4">
                @if($page->status === 'published')
                    <a href="{{ url('/' . ltrim($page->slug, '/')) }}"
                       target="_blank"
                       class="underline text-sm text-gray-600 hover:text-gray-900">
                        View Live
                    </a>
                @else
                    <a href="{{ route('pages.preview', $page) }}"
                       target="_blank"
                       class="underline text-sm text-gray-600 hover:text-gray-900">
                        Preview Draft
                    </a>
                @endif

                <a href="{{ route('admin.pages.index') }}"
                   class="underline text-sm text-gray-600 hover:text-gray-900">
                    Cancel
                </a>
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        {{-- Core --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" name="title" value="{{ old('title', $page->title) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug', $page->slug) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                            <p class="mt-1 text-xs text-gray-500">Allowed: letters, numbers, dashes, and /</p>
                            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Body</label>
                            <textarea name="body" rows="10"
                                      class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">{{ old('body', $page->body) }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                Tip: embed forms with <code class="px-1 py-0.5 bg-gray-100 rounded">[form slug="contact"]</code>
                            </p>
                            @error('body') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Template</label>
                                <input type="text" name="template" value="{{ old('template', $page->template) }}"
                                       placeholder="optional"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                @error('template') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="sm:col-span-2 flex items-center gap-3 pt-6">
                                <input id="is_homepage" type="checkbox" name="is_homepage" value="1"
                                       class="rounded border-gray-300 text-gray-900 focus:ring-gray-500"
                                       {{ old('is_homepage', $page->is_homepage) ? 'checked' : '' }}>
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
                                    <input type="text" name="meta_title" value="{{ old('meta_title', optional($page->seo)->meta_title) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                    @error('meta_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Canonical URL</label>
                                    <input type="text" name="canonical_url" value="{{ old('canonical_url', optional($page->seo)->canonical_url) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                    @error('canonical_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Meta Description</label>
                                    <textarea name="meta_description" rows="3"
                                              class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">{{ old('meta_description', optional($page->seo)->meta_description) }}</textarea>
                                    @error('meta_description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Robots</label>
                                    <input type="text" name="robots" value="{{ old('robots', optional($page->seo)->robots) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                    @error('robots') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <h4 class="mt-6 font-semibold text-gray-800">Open Graph</h4>
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">OG Title</label>
                                    <input type="text" name="og_title" value="{{ old('og_title', optional($page->seo)->og_title) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">OG Image URL</label>
                                    <input type="text" name="og_image_url" value="{{ old('og_image_url', optional($page->seo)->og_image_url) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">OG Description</label>
                                    <textarea name="og_description" rows="3"
                                              class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">{{ old('og_description', optional($page->seo)->og_description) }}</textarea>
                                </div>
                            </div>

                            <h4 class="mt-6 font-semibold text-gray-800">Twitter</h4>
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Twitter Title</label>
                                    <input type="text" name="twitter_title" value="{{ old('twitter_title', optional($page->seo)->twitter_title) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Twitter Image URL</label>
                                    <input type="text" name="twitter_image_url" value="{{ old('twitter_image_url', optional($page->seo)->twitter_image_url) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Twitter Description</label>
                                    <textarea name="twitter_description" rows="3"
                                              class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">{{ old('twitter_description', optional($page->seo)->twitter_description) }}</textarea>
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

                    {{-- Danger zone --}}
                    <div class="mt-10 pt-6 border-t">
                        <h3 class="text-sm font-semibold text-red-700">Danger zone</h3>
                        <p class="text-sm text-gray-600 mt-1">Delete this page permanently.</p>

                        <form method="POST" action="{{ route('admin.pages.destroy', $page) }}"
                              onsubmit="return confirm('Delete this page permanently?');"
                              class="mt-3">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-red-700">
                                Delete Page
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
