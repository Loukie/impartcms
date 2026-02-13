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
                                <x-admin.media-picker
                                    label="Choose from Media library"
                                    name="site_logo_media_id"
                                    :value="old('site_logo_media_id', $logoMediaId)"
                                    :preview-url="$logoPreviewUrl"
                                    type="images"
                                    choose-text="Choose from Media Library"
                                    upload-text="Upload"
                                    clear-text="Clear"
                                    clear-name="site_logo_clear"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Selecting a Media image as your logo will <span class="font-semibold">not</span> delete it when removed from Settings.
                                </p>
                                @error('site_logo_media_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                                <x-admin.media-picker
                                    label="Choose from Media library"
                                    name="site_favicon_media_id"
                                    :value="old('site_favicon_media_id', $faviconMediaId)"
                                    :preview-url="$faviconPreviewUrl"
                                    type="images"
                                    choose-text="Choose from Media Library"
                                    upload-text="Upload"
                                    clear-text="Clear"
                                    clear-name="site_favicon_clear"
                                />
                                <p class="mt-1 text-xs text-gray-500">Recommended: ICO or PNG (32×32 / 48×48). Media items are never deleted via Settings.</p>
                                @error('site_favicon_media_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                                        {{ $p->title }} (/{{ $p->slug }})
                                    </option>
                                @endforeach
                            </select>
                            @error('homepage_page_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
</x-admin-layout>
