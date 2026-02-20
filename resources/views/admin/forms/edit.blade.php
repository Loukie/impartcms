<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $isNew ? 'New form' : 'Edit form' }}
                </h2>
                <div class="text-xs text-gray-500 mt-1">Shortcode: <code class="px-1 py-0.5 bg-gray-100 rounded">[form slug=&quot;{{ $form->slug ?: 'your-slug' }}&quot;]</code></div>
            </div>

            <div class="flex items-center gap-2">
                @if(!$isNew)
                    <a href="{{ route('admin.forms.submissions.index', $form) }}"
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                        Submissions
                    </a>
                @endif

                <a href="{{ route('admin.forms.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                    Back
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

            @if ($errors->any())
                <div class="mb-4 p-3 rounded bg-red-50 text-red-800 border border-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ $isNew ? route('admin.forms.store') : route('admin.forms.update', $form) }}">
                @csrf
                @if(!$isNew)
                    @method('PUT')
                @endif

                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Name</label>
                                    <input type="text" name="name" value="{{ old('name', $form->name) }}" class="mt-1 w-full rounded-md border-gray-300" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Slug</label>
                                    <input type="text" name="slug" value="{{ old('slug', $form->slug) }}" class="mt-1 w-full rounded-md border-gray-300" required>
                                </div>
                            </div>

                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300" {{ old('is_active', $form->is_active) ? 'checked' : '' }}>
                                <span class="text-sm font-semibold text-gray-800">Active</span>
                            </label>

                            {{-- Builder root --}}
                            @php
                                $initial = [
                                    'fields' => $form->fields ?? [],
                                    'settings' => $form->settings ?? [],
                                ];
                            @endphp

                            <div id="impart-forms-builder" data-initial='@json($initial)' class="border border-slate-200 rounded-xl overflow-hidden">
                                <div class="bg-slate-950 text-white px-4 py-3 flex items-center justify-between">
                                    <div class="text-sm font-semibold">Form builder</div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" data-fb-add-row class="px-3 py-1.5 rounded-md bg-white/10 hover:bg-white/20 text-xs font-semibold">+ Row</button>
                                        <button type="button" data-fb-add-section class="px-3 py-1.5 rounded-md bg-white/10 hover:bg-white/20 text-xs font-semibold">+ Section</button>
                                        <button type="button" data-fb-add-pagebreak class="px-3 py-1.5 rounded-md bg-white/10 hover:bg-white/20 text-xs font-semibold">+ Page break</button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-12">
                                    {{-- Palette --}}
                                    <div class="lg:col-span-3 border-r border-slate-200 bg-white p-4">
                                        <div class="text-xs font-semibold uppercase tracking-widest text-slate-500 mb-3">Fields</div>

                                        <div data-fb-palette class="space-y-2">
                                            <div class="rounded-lg border border-slate-200 bg-white p-3 cursor-grab active:cursor-grabbing" data-fb-palette-item="1" data-type="text">
                                                <div class="text-sm font-semibold text-slate-900">Text</div>
                                                <div class="text-xs text-slate-500">Single line</div>
                                            </div>
                                            <div class="rounded-lg border border-slate-200 bg-white p-3 cursor-grab active:cursor-grabbing" data-fb-palette-item="1" data-type="email">
                                                <div class="text-sm font-semibold text-slate-900">Email</div>
                                                <div class="text-xs text-slate-500">Email address</div>
                                            </div>
                                            <div class="rounded-lg border border-slate-200 bg-white p-3 cursor-grab active:cursor-grabbing" data-fb-palette-item="1" data-type="phone">
                                                <div class="text-sm font-semibold text-slate-900">Phone</div>
                                                <div class="text-xs text-slate-500">Country + number</div>
                                            </div>
                                            <div class="rounded-lg border border-slate-200 bg-white p-3 cursor-grab active:cursor-grabbing" data-fb-palette-item="1" data-type="textarea">
                                                <div class="text-sm font-semibold text-slate-900">Textarea</div>
                                                <div class="text-xs text-slate-500">Long text</div>
                                            </div>
                                            <div class="rounded-lg border border-slate-200 bg-white p-3 cursor-grab active:cursor-grabbing" data-fb-palette-item="1" data-type="select">
                                                <div class="text-sm font-semibold text-slate-900">Select</div>
                                                <div class="text-xs text-slate-500">Dropdown</div>
                                            </div>
                                            <div class="rounded-lg border border-slate-200 bg-white p-3 cursor-grab active:cursor-grabbing" data-fb-palette-item="1" data-type="cards">
                                                <div class="text-sm font-semibold text-slate-900">Cards + Select</div>
                                                <div class="text-xs text-slate-500">Single choice cards</div>
                                            </div>
                                            <div class="rounded-lg border border-slate-200 bg-white p-3 cursor-grab active:cursor-grabbing" data-fb-palette-item="1" data-type="cards_multi">
                                                <div class="text-sm font-semibold text-slate-900">Cards + Multi</div>
                                                <div class="text-xs text-slate-500">Multiple choice cards</div>
                                            </div>
                                        </div>

                                        <div class="mt-6 text-xs text-slate-500 leading-relaxed">
                                            Drag a field into the canvas. ✅<br>
                                            Click a field to edit settings.
                                        </div>
                                    </div>

                                    {{-- Canvas --}}
                                    <div class="lg:col-span-6 bg-slate-50 p-4" data-fb-canvas>
                                        {{-- Rendered by JS --}}
                                    </div>

                                    {{-- Inspector --}}
                                    <div class="lg:col-span-3 border-l border-slate-200 bg-white p-4" data-fb-inspector>
                                        <div class="text-xs font-semibold uppercase tracking-widest text-slate-500 mb-3">Field settings</div>

                                        <div data-fb-inspector-empty class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                                            Select a field to edit.
                                        </div>

                                        <div data-fb-inspector-form class="hidden space-y-4">
                                            <div class="text-sm font-semibold text-slate-900">Type: <span data-fb-i-type class="text-slate-600"></span></div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Label</label>
                                                <input type="text" class="mt-1 w-full rounded-md border-gray-300" data-fb-i-label>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Key (name)</label>
                                                <input type="text" class="mt-1 w-full rounded-md border-gray-300" data-fb-i-name>
                                                <div class="text-xs text-gray-500 mt-1">Used as the stored field name.</div>
                                            </div>

                                            <div data-fb-i-placeholder-wrap>
                                                <label class="block text-sm font-medium text-gray-700">Placeholder</label>
                                                <input type="text" class="mt-1 w-full rounded-md border-gray-300" data-fb-i-placeholder>
                                            </div>

                                            <label class="inline-flex items-center gap-2">
                                                <input type="checkbox" class="rounded border-gray-300" data-fb-i-required>
                                                <span class="text-sm font-semibold text-gray-800">Required</span>
                                            </label>

                                            <div data-fb-i-options-wrap class="hidden">
                                                <div class="flex items-center justify-between">
                                                    <div class="text-sm font-semibold text-slate-900">Options</div>
                                                    <button type="button" class="px-3 py-1.5 rounded-md bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800" data-fb-i-options-add>
                                                        + Option
                                                    </button>
                                                </div>
                                                <div class="mt-3 space-y-2" data-fb-i-options></div>
                                                <div class="text-xs text-slate-500 mt-2">
                                                    Images use your Media library. Icons use Font Awesome/Lucide picker. ✅
                                                </div>
                                            </div>

                                            <div class="pt-2 border-t">
                                                <button type="button" class="w-full px-3 py-2 rounded-md border bg-white hover:bg-gray-50 text-sm font-semibold text-red-700" data-fb-i-delete>
                                                    Delete field
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Form-level settings (always visible) --}}
                                        <div class="mt-6 pt-4 border-t border-slate-200" data-fb-form-settings>
                                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500 mb-3">Form settings</div>

                                            {{-- Pricing --}}
                                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3" data-fb-pricing>
                                                <label class="inline-flex items-center gap-2">
                                                    <input type="checkbox" class="rounded border-gray-300" data-fb-pricing-enabled>
                                                    <span class="text-sm font-semibold text-gray-800">Enable pricing</span>
                                                </label>

                                                <div class="mt-3 space-y-3" data-fb-pricing-body>
                                                    <div class="text-xs text-slate-600">Create one or more pricing options, then add rules to decide which price applies.</div>

                                                    <div>
                                                        <div class="flex items-center justify-between">
                                                            <div class="text-sm font-semibold text-slate-900">Pricing options (ZAR)</div>
                                                            <button type="button" class="px-3 py-1.5 rounded-md bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800" data-fb-pricing-add>
                                                                + Price
                                                            </button>
                                                        </div>
                                                        <div class="mt-2 space-y-2" data-fb-pricing-options></div>
                                                    </div>

                                                    <div class="pt-3 border-t border-slate-200">
                                                        <div class="flex items-center justify-between">
                                                            <div class="text-sm font-semibold text-slate-900">Pricing rules</div>
                                                            <button type="button" class="px-3 py-1.5 rounded-md bg-white border text-xs font-semibold hover:bg-gray-50" data-fb-rule-add>
                                                                + Rule
                                                            </button>
                                                        </div>
                                                        <div class="mt-2 space-y-2" data-fb-pricing-rules></div>

                                                        <div class="mt-2">
                                                            <label class="block text-sm font-medium text-gray-700">Default price</label>
                                                            <select class="mt-1 w-full rounded-md border-gray-300" data-fb-pricing-default></select>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Note (optional)</label>
                                                        <input type="text" class="mt-1 w-full rounded-md border-gray-300" placeholder="e.g. VAT excluded" data-fb-pricing-note>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Hidden JSON inputs --}}
                                <input type="hidden" name="fields_json" value='@json($form->fields ?? [])'>
                                <input type="hidden" name="settings_json" value='@json($form->settings ?? [])'>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="rounded-lg border border-gray-200 bg-white p-4">
                                <div class="text-sm font-semibold text-gray-900">Email routing</div>
                                <div class="text-xs text-gray-500 mt-1">If shortcode has <code>to=</code> it overrides. Otherwise, uses Forms Settings default.</div>
                                <div class="mt-4 space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Default recipients (CSV)</label>
                                        <input type="text" name="_dummy" disabled value="Set in Forms → Settings" class="mt-1 w-full rounded-md border-gray-200 bg-gray-50 text-gray-500">
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-lg border border-gray-200 bg-white p-4">
                                <div class="text-sm font-semibold text-gray-900">Shortcode</div>
                                <div class="mt-2 text-xs text-gray-700">
                                    <div class="mb-2">Basic:</div>
                                    <code class="block p-2 bg-gray-50 rounded border">[form slug=&quot;{{ $form->slug ?: 'your-slug' }}&quot;]</code>
                                    <div class="mt-3 mb-2">Override recipients:</div>
                                    <code class="block p-2 bg-gray-50 rounded border">[form slug=&quot;{{ $form->slug ?: 'your-slug' }}&quot; to=&quot;you@email.com&quot;]</code>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                    Save
                                </button>

                                @if(!$isNew)
                                    <button type="button"
                                            onclick="if(confirm('Move this form to trash?')) document.getElementById('delete-form').submit();"
                                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-red-700">
                                        Move to Trash
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            @if(!$isNew)
                <form id="delete-form" method="POST" action="{{ route('admin.forms.destroy', $form) }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </div>
    </div>
</x-admin-layout>
