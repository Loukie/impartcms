<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    New Page
                </h2>

                <a href="{{ route('admin.pages.index') }}"
                   class="underline text-sm text-gray-600 hover:text-gray-900">
                    Cancel
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.pages.store') }}" class="space-y-6" id="page-form">
                        @csrf

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                            {{-- Main --}}
                            <div class="lg:col-span-8 space-y-6">
                                {{-- Core --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Title</label>
                                    <input type="text" name="title" value="{{ old('title') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                                           id="field-title">
                                    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Slug</label>
                                    <input type="text" name="slug" value="{{ old('slug') }}"
                                           placeholder="e.g. about or info/team"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                                           id="field-slug">
                                    <p class="mt-1 text-xs text-gray-500">Allowed: letters, numbers, dashes, and /</p>
                                    @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Body</label>
                                    <textarea name="body" rows="14"
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
                            </div>

                            {{-- Sidebar --}}
                            <aside class="lg:col-span-4">
                                <div class="lg:sticky lg:top-6 space-y-4">
                                    {{-- Status / Actions --}}
                                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-sm font-semibold text-gray-900">Status</h3>
                                            <span class="text-xs font-semibold uppercase tracking-widest px-2 py-1 rounded border bg-yellow-50 text-yellow-800 border-yellow-200">
                                                DRAFT
                                            </span>
                                        </div>

                                        <p class="mt-2 text-xs text-gray-500">
                                            Drafts are admin-preview only until published.
                                        </p>

                                        <div class="mt-4 flex flex-col gap-2">
                                            <button type="submit" name="action" value="draft"
                                                    class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                                Save Draft
                                            </button>

                                            <button type="submit" name="action" value="publish"
                                                    class="inline-flex items-center justify-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                                Publish
                                            </button>

                                            <a href="{{ route('admin.pages.index') }}"
                                               class="text-center underline text-sm text-gray-600 hover:text-gray-900">
                                                Cancel
                                            </a>
                                        </div>
                                    </div>

                                    {{-- SEO (Rank Math-style) --}}
                                    <div class="rounded-lg border border-gray-200 bg-white p-4" id="seo-panel">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-sm font-semibold text-gray-900">SEO</h3>
                                            <span class="text-xs text-gray-500" id="seo-score">Score: —</span>
                                        </div>

                                        {{-- Tabs --}}
                                        <div class="mt-3 flex flex-wrap gap-2" role="tablist" aria-label="SEO tabs">
                                            <button type="button" data-seo-tab="general"
                                                    class="seo-tab-btn inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold uppercase tracking-widest border bg-gray-900 text-white border-gray-900">
                                                General
                                            </button>
                                            <button type="button" data-seo-tab="social"
                                                    class="seo-tab-btn inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold uppercase tracking-widest border bg-white text-gray-700 border-gray-300 hover:bg-gray-50">
                                                Social
                                            </button>
                                            <button type="button" data-seo-tab="advanced"
                                                    class="seo-tab-btn inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold uppercase tracking-widest border bg-white text-gray-700 border-gray-300 hover:bg-gray-50">
                                                Advanced
                                            </button>
                                        </div>

                                        {{-- Snippet preview --}}
                                        <div class="mt-4 rounded-md border border-gray-200 bg-gray-50 p-3">
                                            <div class="text-xs text-gray-500" id="seo-preview-url">{{ url('/') }}/<span class="text-gray-700">your-slug</span></div>
                                            <div class="mt-1 text-sm font-semibold text-blue-800" id="seo-preview-title">Your title</div>
                                            <div class="mt-1 text-xs text-gray-700" id="seo-preview-desc">Your meta description will show here.</div>
                                        </div>

                                        {{-- Checklist --}}
                                        <ul class="mt-3 space-y-1 text-xs" id="seo-checklist">
                                            <li class="text-gray-500" data-check="title">• Title set</li>
                                            <li class="text-gray-500" data-check="desc">• Description set</li>
                                            <li class="text-gray-500" data-check="titlelen">• Title length ok</li>
                                            <li class="text-gray-500" data-check="desclen">• Description length ok</li>
                                        </ul>

                                        {{-- Tab content --}}
                                        <div class="mt-4 space-y-4">
                                            {{-- General --}}
                                            <div class="seo-tab" data-seo-tab-panel="general">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Meta Title</label>
                                                    <input type="text" name="meta_title" value="{{ old('meta_title') }}"
                                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                                                           id="field-meta-title">
                                                    @error('meta_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Meta Description</label>
                                                    <textarea name="meta_description" rows="3"
                                                              class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                                                              id="field-meta-description">{{ old('meta_description') }}</textarea>
                                                    @error('meta_description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                                </div>
                                            </div>

                                            {{-- Social --}}
                                            <div class="seo-tab hidden" data-seo-tab-panel="social">
                                                <div class="grid grid-cols-1 gap-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">OG Title</label>
                                                        <input type="text" name="og_title" value="{{ old('og_title') }}"
                                                               class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">OG Description</label>
                                                        <textarea name="og_description" rows="3"
                                                                  class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">{{ old('og_description') }}</textarea>
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">OG Image URL</label>
                                                        <input type="text" name="og_image_url" value="{{ old('og_image_url') }}"
                                                               class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                                    </div>
                                                </div>

                                                <div class="mt-4 border-t pt-4">
                                                    <h4 class="text-xs font-semibold uppercase tracking-widest text-gray-700">Twitter</h4>

                                                    <div class="mt-3 grid grid-cols-1 gap-4">
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700">Twitter Title</label>
                                                            <input type="text" name="twitter_title" value="{{ old('twitter_title') }}"
                                                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                                        </div>

                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700">Twitter Description</label>
                                                            <textarea name="twitter_description" rows="3"
                                                                      class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">{{ old('twitter_description') }}</textarea>
                                                        </div>

                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700">Twitter Image URL</label>
                                                            <input type="text" name="twitter_image_url" value="{{ old('twitter_image_url') }}"
                                                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Advanced --}}
                                            <div class="seo-tab hidden" data-seo-tab-panel="advanced">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Canonical URL</label>
                                                    <input type="text" name="canonical_url" value="{{ old('canonical_url') }}"
                                                           placeholder="Auto uses current URL if empty"
                                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                                                           id="field-canonical">
                                                    @error('canonical_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Robots</label>
                                                    <input type="text" name="robots" value="{{ old('robots') }}"
                                                           placeholder="index,follow"
                                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                                    @error('robots') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                                </div>

                                                <p class="text-xs text-gray-500">
                                                    Tip: leave canonical blank to use the current page URL.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Help --}}
                                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                                        <h3 class="text-sm font-semibold text-gray-900">Shortcodes</h3>
                                        <p class="mt-2 text-xs text-gray-500">
                                            Embed forms anywhere in the body:
                                            <code class="px-1 py-0.5 bg-gray-100 rounded">[form slug="contact"]</code>
                                        </p>
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const root = document.getElementById('seo-panel');
            if (!root) return;

            const tabButtons = Array.from(root.querySelectorAll('.seo-tab-btn'));
            const tabPanels = Array.from(root.querySelectorAll('.seo-tab'));

            function setActiveTab(key) {
                tabButtons.forEach(btn => {
                    const isActive = btn.getAttribute('data-seo-tab') === key;
                    btn.classList.toggle('bg-gray-900', isActive);
                    btn.classList.toggle('text-white', isActive);
                    btn.classList.toggle('border-gray-900', isActive);

                    btn.classList.toggle('bg-white', !isActive);
                    btn.classList.toggle('text-gray-700', !isActive);
                    btn.classList.toggle('border-gray-300', !isActive);
                });

                tabPanels.forEach(panel => {
                    const isActive = panel.getAttribute('data-seo-tab-panel') === key;
                    panel.classList.toggle('hidden', !isActive);
                });
            }

            tabButtons.forEach(btn => {
                btn.addEventListener('click', () => setActiveTab(btn.getAttribute('data-seo-tab')));
            });

            // Live preview + simple score
            const elTitle = document.getElementById('field-title');
            const elSlug = document.getElementById('field-slug');
            const elMetaTitle = document.getElementById('field-meta-title');
            const elMetaDesc = document.getElementById('field-meta-description');
            const elCanonical = document.getElementById('field-canonical');

            const prevUrl = document.getElementById('seo-preview-url');
            const prevTitle = document.getElementById('seo-preview-title');
            const prevDesc = document.getElementById('seo-preview-desc');
            const scoreEl = document.getElementById('seo-score');

            function mark(checkKey, ok) {
                const li = root.querySelector(`[data-check="${checkKey}"]`);
                if (!li) return;
                li.classList.toggle('text-green-700', ok);
                li.classList.toggle('text-gray-500', !ok);
            }

            function updatePreview() {
                const title = (elMetaTitle?.value || elTitle?.value || '').trim();
                const desc = (elMetaDesc?.value || '').trim();
                const slug = (elSlug?.value || 'your-slug').replace(/^\/+/, '').trim();
                const canonical = (elCanonical?.value || '').trim();

                const url = canonical !== '' ? canonical : `{{ url('/') }}/${slug}`;

                prevUrl.innerHTML = url.replace(/&/g, '&amp;').replace(/</g, '&lt;');
                prevTitle.textContent = title !== '' ? title : 'Your title';
                prevDesc.textContent = desc !== '' ? desc : 'Your meta description will show here.';

                const titleLen = title.length;
                const descLen = desc.length;

                const hasTitle = titleLen > 0;
                const hasDesc = descLen > 0;
                const okTitleLen = titleLen >= 20 && titleLen <= 60;
                const okDescLen = descLen >= 50 && descLen <= 160;

                mark('title', hasTitle);
                mark('desc', hasDesc);
                mark('titlelen', hasTitle && okTitleLen);
                mark('desclen', hasDesc && okDescLen);

                let score = 0;
                if (hasTitle) score += 25;
                if (hasDesc) score += 25;
                if (hasTitle && okTitleLen) score += 25;
                if (hasDesc && okDescLen) score += 25;

                scoreEl.textContent = `Score: ${score}/100`;
            }

            [elTitle, elSlug, elMetaTitle, elMetaDesc, elCanonical].forEach(el => {
                if (!el) return;
                el.addEventListener('input', updatePreview);
            });

            setActiveTab('general');
            updatePreview();
        })();
    </script>
</x-app-layout>
