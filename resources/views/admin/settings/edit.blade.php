<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Settings</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-3 rounded bg-green-50 text-green-800 border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-3 rounded bg-red-50 text-red-800 border border-red-200">
                    <div class="font-semibold">Please fix the following:</div>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="p-6 space-y-10">
                    @csrf
                    @method('PUT')

                    {{-- GENERAL --}}
                    <section>
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">General</h3>
                            <p class="text-xs text-gray-500">Admin branding + defaults</p>
                        </div>

                        <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-8">
                            {{-- Site name --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Site name</label>
                                <input
                                    type="text"
                                    name="site_name"
                                    value="{{ old('site_name', $siteName) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                                >
                                @error('site_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            {{-- Show name with logo --}}
                            <div class="flex items-end">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="checkbox"
                                        name="admin_show_name_with_logo"
                                        value="1"
                                        {{ old('admin_show_name_with_logo', $showNameWithLogo) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-gray-900 focus:ring-gray-500"
                                    >
                                    Show site name text next to logo (if a logo is set)
                                </label>
                            </div>
                        </div>

                                                {{-- LOGO --}}
                        <div class="mt-8 border-t pt-8">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">Logo</div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        If no logo → show site name text · If logo → logo-only by default (optional logo + text).
                                    </p>
                                </div>
                            </div>

                            @php
                                $hasLogo = !empty($logoMediaUrl) || !empty($logoPath);
                                $logoPreviewUrl = !empty($logoMediaUrl)
                                    ? $logoMediaUrl
                                    : (!empty($logoPath) ? asset('storage/' . $logoPath) : null);
                            @endphp

                            <div class="mt-4">
                                <x-admin.media-icon-picker
                                    label="Choose from Media library"
                                    media-name="site_logo_media_id"
                                    icon-name="site_logo_icon_json"
                                    clear-name="site_logo_clear"
                                    :media-id="$logoMediaId"
                                    :media-url="$logoPreviewUrl"
                                    :icon-json="$logoIconJson"
                                    allow="images,icons"
                                    help="Pick an image OR an icon. Choosing one clears the other."
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Selecting a Media image as your logo will <span class="font-semibold">not</span> delete it when removed from Settings.
                                </p>
                            </div>
                        </div>

                                                {{-- FAVICON --}}
                        <div class="mt-8 border-t pt-8">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">Favicon</div>
                                    <p class="text-xs text-gray-500 mt-1">Shown in browser tabs and bookmarks.</p>
                                </div>
                            </div>

                            @php
                                $hasFavicon = !empty($faviconMediaUrl) || !empty($faviconPath);
                                $faviconPreviewUrl = !empty($faviconMediaUrl)
                                    ? $faviconMediaUrl
                                    : (!empty($faviconPath) ? asset('storage/' . $faviconPath) : null);
                            @endphp

                            <div class="mt-4">
                                <x-admin.media-icon-picker
                                    label="Choose from Media library"
                                    media-name="site_favicon_media_id"
                                    icon-name="site_favicon_icon_json"
                                    clear-name="site_favicon_clear"
                                    :media-id="$faviconMediaId"
                                    :media-url="$faviconPreviewUrl"
                                    :icon-json="$faviconIconJson"
                                    allow="images,icons"
                                    help="Pick an image OR an icon. Icon favicons are served as /favicon.svg."
                                />
                                <p class="mt-1 text-xs text-gray-500">Recommended: ICO or PNG (32×32 / 48×48). Media items are never deleted via Settings.</p>
                            </div>
                        </div>

                        {{-- LOGIN SCREEN --}}
                        <div class="mt-8 border-t pt-8">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">Login screen logo</div>
                                    <p class="text-xs text-gray-500 mt-1">Shown on the login/register pages. If empty, it falls back to the main logo.</p>
                                </div>
                            </div>

                            @php
                                $authPreviewUrl = !empty($authLogoMediaUrl) ? $authLogoMediaUrl : null;
                            @endphp

                            <div class="mt-4">
                                <x-admin.media-icon-picker
                                    label="Choose from Media library"
                                    media-name="auth_logo_media_id"
                                    icon-name="auth_logo_icon_json"
                                    clear-name="auth_logo_clear"
                                    :media-id="$authLogoMediaId"
                                    :media-url="$authPreviewUrl"
                                    :icon-json="$authLogoIconJson"
                                    allow="images,icons"
                                    help="If empty, login falls back to the main logo."
                                />
                            </div>

                            <div class="mt-6 max-w-xs">
                                <label class="block text-sm font-medium text-gray-700">Login logo size (px)</label>
                                <input type="number"
                                       name="auth_logo_size"
                                       min="24"
                                       max="256"
                                       value="{{ (int) old('auth_logo_size', $authLogoSize ?? 80) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                <p class="mt-1 text-xs text-gray-500">Applies to the login/register logo (image or icon).</p>
                                @error('auth_logo_size') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- HOMEPAGE --}}
                    <section class="border-t pt-10">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">Homepage</h3>
                            <p class="text-xs text-gray-500">Select which published page resolves as “/”</p>
                        </div>

                        <div class="mt-5 max-w-xl">
                            <label class="block text-sm font-medium text-gray-700">Homepage page</label>
                            <select name="homepage_page_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                <option value="">— Select a published page —</option>
                                @foreach($homepagePages as $p)
                                    <option value="{{ $p->id }}" {{ (int) old('homepage_page_id', $homepagePageId) === (int) $p->id ? 'selected' : '' }}>
                                        {{ $p->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('homepage_page_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </section>

                    {{-- SITE NOTICE BAR --}}
                    <section class="border-t pt-10" id="notice_settings">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">Site notification bar</h3>
                            <p class="text-xs text-gray-500">Optional banner pinned at the very top of every public page</p>
                        </div>

                        <div class="mt-5 max-w-xl">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="notice_enabled" value="1"
                                       {{ old('notice_enabled', $noticeEnabled ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                Enable notification bar
                            </label>
                        </div>

                        <div class="mt-4 max-w-xl grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bar colour</label>
                                <div class="mt-1 flex items-center gap-3">
                                    <input id="notice_bg_colour_picker" type="color"
                                           value="{{ old('notice_bg_colour', $noticeBgColour ?? '#111827') }}"
                                           class="h-10 w-14 rounded-md border border-gray-300 bg-white p-1" />
                                    <input id="notice_bg_colour" type="text" name="notice_bg_colour"
                                           value="{{ old('notice_bg_colour', $noticeBgColour ?? '#111827') }}"
                                           class="block w-full rounded-md border-gray-300 font-mono text-sm"
                                           placeholder="#111827" />
                                </div>
                                @error('notice_bg_colour') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bar height (px)</label>
                                <input type="number" name="notice_height"
                                       value="{{ (int) old('notice_height', $noticeHeight ?? 44) }}"
                                       min="24" max="200"
                                       class="mt-1 block w-full rounded-md border-gray-300"
                                       placeholder="44" />
                                <p class="mt-1 text-xs text-gray-500">Minimum height. The bar can grow if your message wraps onto multiple lines.</p>
                                @error('notice_height') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Mode</label>
                                <select name="notice_mode" id="notice_mode"
                                        class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                    <option value="text" {{ old('notice_mode', $noticeMode ?? 'text') === 'text' ? 'selected' : '' }}>Plain text</option>
                                    <option value="html" {{ old('notice_mode', $noticeMode ?? 'text') === 'html' ? 'selected' : '' }}>HTML</option>
                                </select>
                                @error('notice_mode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div id="notice_link_fields">
                                <label class="block text-sm font-medium text-gray-700">Optional link (for text mode)</label>
                                <div class="mt-1 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <input type="text" name="notice_link_text" value="{{ old('notice_link_text', $noticeLinkText ?? '') }}"
                                               class="block w-full rounded-md border-gray-300" placeholder="Link text" />
                                    </div>
                                    <div>
                                        <input type="text" name="notice_link_url" value="{{ old('notice_link_url', $noticeLinkUrl ?? '') }}"
                                               class="block w-full rounded-md border-gray-300" placeholder="https://..." />
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">If you choose HTML mode, you can embed links directly in the HTML.</p>
                                @error('notice_link_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="mt-4" id="notice_text_wrap">
                            <label class="block text-sm font-medium text-gray-700">Notification text</label>
                            <textarea name="notice_text" rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300"
                                      placeholder="e.g. Scheduled maintenance: 1–2 March 2026">{{ old('notice_text', $noticeText ?? '') }}</textarea>
                            @error('notice_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="mt-4" id="notice_html_wrap">
                            <label class="block text-sm font-medium text-gray-700">Notification HTML</label>
                            <textarea name="notice_html" rows="6"
                                      class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm"
                                      placeholder="Paste banner HTML here (links, spans, etc)…">{{ old('notice_html', $noticeHtml ?? '') }}</textarea>
                            @error('notice_html') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </section>

                    {{-- MAINTENANCE MODE --}}
                    <section class="border-t pt-10">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">Maintenance mode</h3>
                            <p class="text-xs text-gray-500">When enabled, all public pages redirect to a selected maintenance page</p>
                        </div>

	                        <div class="mt-5 max-w-xl">
	                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
	                                <input
	                                    type="checkbox"
	                                    name="maintenance_enabled"
	                                    value="1"
	                                    {{ old('maintenance_enabled', $maintenanceEnabled ?? false) ? 'checked' : '' }}
	                                    class="rounded border-gray-300 text-gray-900 focus:ring-gray-500"
	                                >
	                                Enable maintenance mode
	                            </label>
	                        </div>

	                        <div class="mt-4 max-w-xl">
	                            <label class="block text-sm font-medium text-gray-700">Maintenance page</label>
	                            <select name="maintenance_page_id"
	                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
	                                <option value="">— Select a published page —</option>
	                                @foreach($maintenancePages as $p)
	                                    <option value="{{ $p->id }}" {{ (int) old('maintenance_page_id', $maintenancePageId) === (int) $p->id ? 'selected' : '' }}>
	                                        {{ $p->title }}
	                                    </option>
	                                @endforeach
	                            </select>
	                            <p class="mt-1 text-xs text-gray-500">Tip: create a page like “Maintenance” and keep it simple (logo, message, contact details).</p>
	                            @error('maintenance_page_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
	                        </div>
                    </section>

                    {{-- SHORTCODES --}}
                    <section class="border-t pt-10">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">Shortcodes</h3>
                            <p class="text-xs text-gray-500">Manual embeds (forms + icons)</p>
                        </div>

                        <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div class="bg-slate-50 border rounded-xl p-4">
                                <div class="text-xs font-semibold text-gray-800 uppercase tracking-wide">Icon shortcode</div>
                                <div class="mt-2 text-sm text-gray-700">
                                    Use this inside any page body.
                                </div>
                                <pre class="mt-3 text-xs bg-white border rounded-lg p-3 overflow-auto"><code>[icon kind="fa" value="fa-solid fa-house" size="24" colour="#111827"]
[icon kind="lucide" value="home" size="24" colour="#111827"]</code></pre>
                                <div class="mt-3 text-xs text-gray-600">
                                    Alternative JSON form (use <span class="font-semibold">single quotes</span> so JSON quotes don’t break the shortcode):
                                </div>
                                <pre class="mt-2 text-xs bg-white border rounded-lg p-3 overflow-auto"><code>[icon data='{"kind":"fa","value":"fa-solid fa-house","size":24,"colour":"#111827"}']</code></pre>
                            </div>

                            <div class="bg-slate-50 border rounded-xl p-4">
                                <div class="text-xs font-semibold text-gray-800 uppercase tracking-wide">Form shortcode</div>
                                <div class="mt-2 text-sm text-gray-700">
                                    Embed an active form by slug.
                                </div>
                                <pre class="mt-3 text-xs bg-white border rounded-lg p-3 overflow-auto"><code>[form slug="contact"]
[form slug="contact" to="hello@example.com"]</code></pre>
                            </div>
                        </div>
                    </section>

                    <div class="pt-6 border-t flex items-center justify-end gap-3">
                        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800">
                            Save settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modeEl = document.getElementById('notice_mode');
            const textWrap = document.getElementById('notice_text_wrap');
            const htmlWrap = document.getElementById('notice_html_wrap');
            const linkFields = document.getElementById('notice_link_fields');

            function refresh() {
                const mode = (modeEl?.value || 'text');
                if (textWrap) textWrap.style.display = (mode === 'text') ? '' : 'none';
                if (htmlWrap) htmlWrap.style.display = (mode === 'html') ? '' : 'none';
                if (linkFields) linkFields.style.opacity = (mode === 'text') ? '1' : '0.5';
            }

            // Notice bar colour picker sync (picker <-> hex input)
            const colourInput = document.getElementById('notice_bg_colour');
            const pickerInput = document.getElementById('notice_bg_colour_picker');

            function normaliseHex(v){
                if(!v) return '';
                v = String(v).trim();
                if(!v.startsWith('#')) v = '#' + v;
                return v;
            }

            function isHex(v){
                return /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(v);
            }

            function syncFromText(){
                if(!colourInput || !pickerInput) return;
                const v = normaliseHex(colourInput.value);
                if(isHex(v)) pickerInput.value = v;
            }

            function syncFromPicker(){
                if(!colourInput || !pickerInput) return;
                const v = normaliseHex(pickerInput.value);
                if(isHex(v)) colourInput.value = v;
            }

            colourInput?.addEventListener('input', syncFromText);
            pickerInput?.addEventListener('input', syncFromPicker);
            syncFromText();

            modeEl?.addEventListener('change', refresh);
            refresh();
        })();
    </script>
</x-admin-layout>
