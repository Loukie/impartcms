<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Edit Page
                    </h2>

                    <span class="px-2 py-1 text-xs rounded border
                        {{ $page->status === 'published' ? 'bg-green-50 text-green-800 border-green-200' : 'bg-yellow-50 text-yellow-800 border-yellow-200' }}">
                        {{ strtoupper($page->status) }}
                    </span>

                    <span class="text-xs text-gray-500">
                        Created {{ $page->created_at?->format('Y-m-d H:i') }} · Updated {{ $page->updated_at?->format('Y-m-d H:i') }}
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
                        Back to Pages
                    </a>
                </div>
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


            @if ($errors->any())
                <div class=\"mb-4 p-3 rounded bg-red-50 text-red-800 border border-red-200\">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        {{-- Main column --}}
                        <div class="lg:col-span-8">
                            <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="space-y-6" id="page-form">
                                @csrf
                                @method('PUT')

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Title</label>
                                    <input type="text" name="title" value="{{ old('title', $page->title) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                                           id="field-title">
                                    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Slug</label>
                                    <input type="text" name="slug" value="{{ old('slug', $page->slug) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                                           id="field-slug">
                                    <p class="mt-1 text-xs text-gray-500">Allowed: letters, numbers, dashes, and /</p>
                                    @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Body</label>
                                    <textarea name="body" rows="14"
                                              class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">{{ old('body', $page->body) }}</textarea>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Tip: embed forms with <code class="px-1 py-0.5 bg-gray-100 rounded">[form slug="contact"]</code>
                                    </p>
                                    @error('body') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                {{-- Keep sidebar actions inside the SAME form (no nested forms) --}}
                            </form>
                        </div>

                        {{-- Sidebar --}}
                        <aside class="lg:col-span-4">
                            <div class="space-y-6">
                                {{-- Actions + SEO are part of the update form (submitted via #page-form) --}}
                                <div class="hidden lg:block">
                                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                                        <h3 class="text-sm font-semibold text-gray-900">Actions</h3>

                                        <p class="mt-1 text-xs text-gray-600">
                                            @if($page->status === 'published')
                                                Live at <span class="font-mono">{{ url('/' . ltrim($page->slug, '/')) }}</span>
                                            @else
                                                Drafts are admin-preview only until published.
                                            @endif
                                        </p>

                                        <div class="mt-4 flex flex-col gap-2">
                                            @if($page->status === 'published')
                                                <a href="{{ url('/' . ltrim($page->slug, '/')) }}"
                                                   target="_blank"
                                                   class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                                    View Live
                                                </a>
                                            @else
                                                <a href="{{ route('pages.preview', $page) }}"
                                                   target="_blank"
                                                   class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                                    Preview Draft
                                                </a>
                                            @endif

                                            @if($page->status === 'published')
                                                @if($page->is_homepage)
                                                    <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-700">
                                                        This page is currently set as the <strong>Homepage</strong>.
                                                    </div>
                                                @else
                                                    <form method="POST" action="{{ route('admin.pages.setHomepage', $page) }}"
                                                          onsubmit="return confirm('Set this page as the homepage (/)?');">
                                                        @csrf
                                                        <button type="submit"
                                                                class="inline-flex w-full items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                                            Set as Homepage
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-700">
                                                    Publish this page to set it as the homepage.
                                                </div>
                                            @endif

                                            {{-- Buttons submit the MAIN form via form="page-form" --}}
                                            @if($page->status === 'published')
                                                <button type="submit" form="page-form" name="action" value="publish"
                                                        class="inline-flex items-center justify-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                                    Update
                                                </button>

                                                <button type="submit" form="page-form" name="action" value="draft"
                                                        class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                                    Move to Draft
                                                </button>
                                            @else
                                                <button type="submit" form="page-form" name="action" value="draft"
                                                        class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                                    Save Draft
                                                </button>

                                                <button type="submit" form="page-form" name="action" value="publish"
                                                        class="inline-flex items-center justify-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                                    Go Live
                                                </button>
                                            @endif

                                            <a href="{{ route('admin.pages.index') }}"
                                               class="text-center underline text-sm text-gray-600 hover:text-gray-900">
                                                Cancel
                                            </a>
                                        </div>
                                    </div>

                                    <div class="rounded-lg border border-gray-200 bg-white p-4" id="seo-panel">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-sm font-semibold text-gray-900">SEO</h3>
                                            <span class="text-xs text-gray-500" id="seo-score">Score: —</span>
                                        </div>

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

                                        <div class="mt-4 rounded-md border border-gray-200 bg-gray-50 p-3">
                                            <div class="text-xs text-gray-500" id="seo-preview-url">—</div>
                                            <div class="mt-1 text-sm font-semibold text-blue-800" id="seo-preview-title">—</div>
                                            <div class="mt-1 text-xs text-gray-700" id="seo-preview-desc">—</div>
                                        </div>

                                        <ul class="mt-3 space-y-1 text-xs" id="seo-checklist">
                                            <li class="text-gray-500" data-check="title">• Title set</li>
                                            <li class="text-gray-500" data-check="desc">• Description set</li>
                                            <li class="text-gray-500" data-check="titlelen">• Title length ok</li>
                                            <li class="text-gray-500" data-check="desclen">• Description length ok</li>
                                        </ul>

                                        <div class="mt-4 space-y-4">
                                            <div class="seo-tab" data-seo-tab-panel="general">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Meta Title</label>
                                                    <input type="text" name="meta_title" form="page-form"
                                                           value="{{ old('meta_title', optional($page->seo)->meta_title) }}"
                                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                                                           id="field-meta-title">
                                                    @error('meta_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Meta Description</label>
                                                    <textarea name="meta_description" rows="3" form="page-form"
                                                              class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                                                              id="field-meta-description">{{ old('meta_description', optional($page->seo)->meta_description) }}</textarea>
                                                    @error('meta_description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                                </div>
                                            </div>

                                            <div class="seo-tab hidden" data-seo-tab-panel="social">
                                                <div class="grid grid-cols-1 gap-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">OG Title</label>
                                                        <input type="text" name="og_title" form="page-form"
                                                               value="{{ old('og_title', optional($page->seo)->og_title) }}"
                                                               class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">OG Description</label>
                                                        <textarea name="og_description" rows="3" form="page-form"
                                                                  class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">{{ old('og_description', optional($page->seo)->og_description) }}</textarea>
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">OG Image URL</label>
                                                        <input type="text" name="og_image_url" form="page-form"
                                                               value="{{ old('og_image_url', optional($page->seo)->og_image_url) }}"
                                                               class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                                    </div>
                                                </div>

                                                <div class="mt-4 border-t pt-4">
                                                    <h4 class="text-xs font-semibold uppercase tracking-widest text-gray-700">Twitter</h4>

                                                    <div class="mt-3 grid grid-cols-1 gap-4">
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700">Twitter Title</label>
                                                            <input type="text" name="twitter_title" form="page-form"
                                                                   value="{{ old('twitter_title', optional($page->seo)->twitter_title) }}"
                                                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                                        </div>

                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700">Twitter Description</label>
                                                            <textarea name="twitter_description" rows="3" form="page-form"
                                                                      class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">{{ old('twitter_description', optional($page->seo)->twitter_description) }}</textarea>
                                                        </div>

                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700">Twitter Image URL</label>
                                                            <input type="text" name="twitter_image_url" form="page-form"
                                                                   value="{{ old('twitter_image_url', optional($page->seo)->twitter_image_url) }}"
                                                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="seo-tab hidden" data-seo-tab-panel="advanced">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Canonical URL</label>
                                                    <input type="text" name="canonical_url" form="page-form"
                                                           value="{{ old('canonical_url', optional($page->seo)->canonical_url) }}"
                                                           placeholder="Auto uses current URL if empty"
                                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                                                           id="field-canonical">
                                                    @error('canonical_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Robots</label>
                                                    <input type="text" name="robots" form="page-form"
                                                           value="{{ old('robots', optional($page->seo)->robots) }}"
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
                                </div>

                                {{-- Danger zone (SEPARATE form, NOT nested) --}}
                                <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                                    <h3 class="text-sm font-semibold text-red-700">Danger zone</h3>

                                    @if($page->is_homepage)
                                        <p class="text-xs text-red-700/80 mt-1">
                                            This page is set as the <strong>Homepage</strong>. Choose a different homepage first before moving it to Trash.
                                        </p>

                                        <button type="button"
                                                class="mt-3 w-full inline-flex items-center justify-center px-4 py-2 bg-red-300 text-white rounded-md font-semibold text-xs uppercase tracking-widest cursor-not-allowed"
                                                disabled>
                                            Move to Trash
                                        </button>
                                    @else
                                        <p class="text-xs text-red-700/80 mt-1">Move this page to Trash (you can restore it later).</p>

                                        <form method="POST" action="{{ route('admin.pages.destroy', $page) }}"
                                              onsubmit="return confirm('Move this page to Trash?');"
                                              class="mt-3">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="w-full inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-red-700">
                                                Move to Trash
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </aside>
                    </div>
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
                const slug = (elSlug?.value || '').replace(/^\/+/, '').trim() || '{{ ltrim($page->slug, '/') }}';
                const canonical = (elCanonical?.value || '').trim();

                const url = canonical !== '' ? canonical : `{{ url('/') }}/${slug}`;

                prevUrl.textContent = url;
                prevTitle.textContent = title !== '' ? title : '—';
                prevDesc.textContent = desc !== '' ? desc : '—';

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
</x-admin-layout>
