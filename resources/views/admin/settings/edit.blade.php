<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Settings</h2>
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
                    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-8">
                        @csrf
                        @method('PUT')

                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">General</h3>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700">Site name</label>
                                <input type="text" name="site_name" value="{{ old('site_name', $siteName) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                <p class="mt-1 text-xs text-gray-500">Used across the CMS admin and default fallbacks.</p>
                                @error('site_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700">Homepage / Landing page</label>
                                <select name="homepage_page_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                    <option value="">— Select a published page —</option>
                                    @foreach($homepagePages as $p)
                                        <option value="{{ $p->id }}" {{ (int) old('homepage_page_id', $homepagePageId) === (int) $p->id ? 'selected' : '' }}>
                                            {{ $p->title }} (/{{ ltrim($p->slug, '/') }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">This page will load at <span class="font-mono">/</span>. Only published pages are listed.</p>
                                @error('homepage_page_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-sm font-semibold text-gray-900">Branding</h3>
                            <p class="mt-1 text-xs text-gray-500">Admin sidebar branding rules: if no logo → text. If logo → logo-only by default (optional logo + text).</p>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700">Logo</label>

                                @if($logoPath)
                                    <div class="mt-3 flex items-center gap-4">
                                        <img src="{{ asset('storage/' . $logoPath) }}" alt="Site logo"
                                             class="h-10 w-auto rounded bg-white border border-gray-200 p-1">
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                            <input type="checkbox" name="remove_logo" value="1"
                                                   class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                            Remove logo
                                        </label>
                                    </div>
                                @endif

                                <input type="file" name="site_logo" accept="image/*"
                                       class="mt-3 block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gray-900 file:text-white hover:file:bg-gray-800">
                                @error('site_logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                                <p class="mt-2 text-xs text-gray-500">PNG/JPG. Max 2MB. Recommended height ~40px.</p>
                            </div>

                            <div class="mt-5">
                                <label class="inline-flex items-start gap-3 text-sm text-gray-700">
                                    <input type="checkbox" name="admin_show_name_with_logo" value="1"
                                           {{ old('admin_show_name_with_logo', $showNameWithLogo) ? 'checked' : '' }}
                                           {{ $logoPath ? '' : 'disabled' }}
                                           class="mt-0.5 rounded border-gray-300 text-gray-900 focus:ring-gray-500 disabled:opacity-50">
                                    <span>
                                        <span class="font-medium">Show site name next to logo in admin sidebar</span>
                                        <span class="block text-xs text-gray-500 mt-1">
                                            {{ $logoPath ? 'Enabled = logo + text. Disabled = logo-only.' : 'Upload a logo to enable this option.' }}
                                        </span>
                                    </span>
                                </label>
                                @error('admin_show_name_with_logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
