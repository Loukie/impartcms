<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">AI Agent</h2>
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
                <form method="POST" action="{{ route('admin.ai-agent.update') }}" class="p-6 space-y-10">
                    @csrf
                    @method('PUT')

                    <section>
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Provider</h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    Select which AI provider to use for page generation. API keys are stored encrypted in the database.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 max-w-xl">
                            <label class="block text-sm font-medium text-gray-700">Active provider</label>
                            <select id="ai_provider" name="provider" class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                <option value="openai" {{ old('provider', $provider) === 'openai' ? 'selected' : '' }}>OpenAI</option>
                                <option value="gemini" {{ old('provider', $provider) === 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                                <option value="anthropic" {{ old('provider', $provider) === 'anthropic' ? 'selected' : '' }}>Anthropic (Claude) — coming soon</option>
                                <option value="disabled" {{ old('provider', $provider) === 'disabled' ? 'selected' : '' }}>Disabled</option>
                            </select>
                            @error('provider') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </section>

                    {{-- OPENAI --}}
                    <section id="provider_openai" class="border-t pt-10">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">OpenAI</h3>
                                <p class="text-xs text-gray-500 mt-1">Uses the OpenAI Responses API.</p>
                            </div>
                            <div class="text-xs {{ $openAiHasKey ? 'text-green-700' : 'text-red-700' }}">
                                {{ $openAiHasKey ? 'Key detected ✅' : 'No key set ❌' }}
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">API key</label>
                                <input type="password"
                                       name="openai_api_key"
                                       value=""
                                       autocomplete="off"
                                       placeholder="Paste your OpenAI API key (leave blank to keep current)"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                <p class="mt-1 text-xs text-gray-500">
                                    Leave blank to keep the existing key. Stored encrypted in <code>settings</code>.
                                    (You can also use <code>OPENAI_API_KEY</code> in <code>.env</code> as a fallback.)
                                </p>
                                @error('openai_api_key') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 mt-3">
                                    <input type="checkbox" name="openai_api_key_clear" value="1" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                    Clear stored key
                                </label>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Model</label>

                                {{-- Hidden canonical field that the controller saves --}}
                                <input type="hidden" name="openai_model" id="openai_model" value="{{ old('openai_model', $openAiModel) }}">

                                <select id="openai_model_select" class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                    @foreach ($openAiModelOptions as $opt)
                                        <option value="{{ $opt['id'] }}" {{ old('openAiModelSelect', $openAiModelSelect) === $opt['id'] ? 'selected' : '' }}>
                                            {{ $opt['label'] }}
                                        </option>
                                    @endforeach
                                    <option value="custom" {{ old('openAiModelSelect', $openAiModelSelect) === 'custom' ? 'selected' : '' }}>Custom…</option>
                                </select>

                                <div id="openai_model_custom_wrap" class="mt-3" style="display:none;">
                                    <label class="block text-xs font-medium text-gray-600">Custom model</label>
                                    <input type="text"
                                           id="openai_model_custom"
                                           value="{{ old('openAiModelCustom', $openAiModelCustom) }}"
                                           placeholder="e.g. gpt-5.2"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                    <p class="mt-1 text-xs text-gray-500">Only use this if your model isn’t in the list.</p>
                                </div>

                                @error('openai_model') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                                <div class="mt-6">
                                    <label class="block text-sm font-medium text-gray-700">Timeout (seconds)</label>
                                    <input type="number"
                                           name="openai_timeout"
                                           min="5" max="120"
                                           value="{{ (int) old('openai_timeout', $openAiTimeout) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                    @error('openai_timeout') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- GEMINI --}}
                    <section id="provider_gemini" class="border-t pt-10">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Google Gemini</h3>
                                <p class="text-xs text-gray-500 mt-1">Uses the Gemini Developer API (generateContent).</p>
                            </div>
                            <div class="text-xs {{ $geminiHasKey ? 'text-green-700' : 'text-red-700' }}">
                                {{ $geminiHasKey ? 'Key stored ✅' : 'No key stored ❌' }}
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">API key</label>
                                <input type="password"
                                       name="gemini_api_key"
                                       value=""
                                       autocomplete="off"
                                       placeholder="Paste your Gemini API key (leave blank to keep current)"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                @error('gemini_api_key') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 mt-3">
                                    <input type="checkbox" name="gemini_api_key_clear" value="1" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                    Clear stored key
                                </label>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Model</label>

                                <input type="hidden" name="gemini_model" id="gemini_model" value="{{ old('gemini_model', $geminiModel) }}">

                                <select id="gemini_model_select" class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                    @foreach ($geminiModelOptions as $opt)
                                        <option value="{{ $opt['id'] }}" {{ old('geminiModelSelect', $geminiModelSelect) === $opt['id'] ? 'selected' : '' }}>
                                            {{ $opt['label'] }}
                                        </option>
                                    @endforeach
                                    <option value="custom" {{ old('geminiModelSelect', $geminiModelSelect) === 'custom' ? 'selected' : '' }}>Custom…</option>
                                </select>

                                <div id="gemini_model_custom_wrap" class="mt-3" style="display:none;">
                                    <label class="block text-xs font-medium text-gray-600">Custom model</label>
                                    <input type="text"
                                           id="gemini_model_custom"
                                           value="{{ old('geminiModelCustom', $geminiModelCustom) }}"
                                           placeholder="e.g. gemini-2.5-flash"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                </div>

                                @error('gemini_model') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                                <div class="mt-6">
                                    <label class="block text-sm font-medium text-gray-700">Timeout (seconds)</label>
                                    <input type="number"
                                           name="gemini_timeout"
                                           min="5" max="120"
                                           value="{{ (int) old('gemini_timeout', $geminiTimeout) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                    @error('gemini_timeout') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- ANTHROPIC (placeholder) --}}
                    <section id="provider_anthropic" class="border-t pt-10">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Anthropic (Claude)</h3>
                                <p class="text-xs text-gray-500 mt-1">Saved here for convenience — API wiring is not enabled yet.</p>
                            </div>
                            <div class="text-xs {{ $anthropicHasKey ? 'text-green-700' : 'text-gray-500' }}">
                                {{ $anthropicHasKey ? 'Key stored ✅' : 'No key stored' }}
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">API key</label>
                                <input type="password"
                                       name="anthropic_api_key"
                                       value=""
                                       autocomplete="off"
                                       placeholder="Paste your Anthropic API key (optional)"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 mt-3">
                                    <input type="checkbox" name="anthropic_api_key_clear" value="1" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                    Clear stored key
                                </label>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Model</label>
                                <input type="hidden" name="anthropic_model" id="anthropic_model" value="{{ old('anthropic_model', $anthropicModel) }}">

                                <select id="anthropic_model_select" class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                    @foreach ($anthropicModelOptions as $opt)
                                        <option value="{{ $opt['id'] }}" {{ old('anthropicModelSelect', $anthropicModelSelect) === $opt['id'] ? 'selected' : '' }}>
                                            {{ $opt['label'] }}
                                        </option>
                                    @endforeach
                                    <option value="custom" {{ old('anthropicModelSelect', $anthropicModelSelect) === 'custom' ? 'selected' : '' }}>Custom…</option>
                                </select>

                                <div id="anthropic_model_custom_wrap" class="mt-3" style="display:none;">
                                    <label class="block text-xs font-medium text-gray-600">Custom model</label>
                                    <input type="text"
                                           id="anthropic_model_custom"
                                           value="{{ old('anthropicModelCustom', $anthropicModelCustom) }}"
                                           placeholder="e.g. claude-3-5-sonnet-latest"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="pt-6 border-t flex items-center justify-end gap-3">
                        <a href="{{ route('admin.pages.ai.create') }}" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200">
                            Go to AI Page
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-gray-900 hover:bg-gray-800">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const providerSelect = document.getElementById('ai_provider');
            const sections = {
                openai: document.getElementById('provider_openai'),
                gemini: document.getElementById('provider_gemini'),
                anthropic: document.getElementById('provider_anthropic'),
            };

            function syncProvider() {
                const v = (providerSelect?.value || 'openai').toLowerCase();
                Object.entries(sections).forEach(([key, el]) => {
                    if (!el) return;
                    if (v === 'disabled') {
                        el.style.display = 'none';
                        return;
                    }
                    el.style.display = (key === v) ? 'block' : 'none';
                });
            }

            function wireModel(selectId, hiddenId, customWrapId, customInputId) {
                const sel = document.getElementById(selectId);
                const hidden = document.getElementById(hiddenId);
                const wrap = document.getElementById(customWrapId);
                const custom = document.getElementById(customInputId);

                function sync() {
                    if (!sel || !hidden) return;
                    const val = sel.value;
                    const isCustom = val === 'custom';
                    if (wrap) wrap.style.display = isCustom ? 'block' : 'none';

                    if (isCustom) {
                        const v = (custom?.value || '').trim();
                        if (v !== '') hidden.value = v;
                    } else {
                        hidden.value = val;
                    }
                }

                sel?.addEventListener('change', sync);
                custom?.addEventListener('input', sync);
                sync();
            }

            providerSelect?.addEventListener('change', syncProvider);
            syncProvider();

            wireModel('openai_model_select', 'openai_model', 'openai_model_custom_wrap', 'openai_model_custom');
            wireModel('gemini_model_select', 'gemini_model', 'gemini_model_custom_wrap', 'gemini_model_custom');
            wireModel('anthropic_model_select', 'anthropic_model', 'anthropic_model_custom_wrap', 'anthropic_model_custom');
        })();
    </script>
</x-admin-layout>
