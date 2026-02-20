<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Forms settings</h2>

            <a href="{{ route('admin.forms.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
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
                    <form method="POST" action="{{ route('admin.forms.settings.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Default recipients (CSV)</label>
                            <input type="text" name="default_to" value="{{ old('default_to', $defaults['default_to'] ?? '') }}" placeholder="you@example.com, team@example.com"
                                   class="mt-1 w-full rounded-md border-gray-300">
                            <div class="text-xs text-gray-500 mt-1">Used when shortcode does not specify <code>to=</code>.</div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Default CC (CSV, optional)</label>
                                <input type="text" name="default_cc" value="{{ old('default_cc', $defaults['default_cc'] ?? '') }}" placeholder="cc1@example.com, cc2@example.com"
                                       class="mt-1 w-full rounded-md border-gray-300">
                                <div class="text-xs text-gray-500 mt-1">Used when shortcode does not specify <code>cc=</code>.</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Default BCC (CSV, optional)</label>
                                <input type="text" name="default_bcc" value="{{ old('default_bcc', $defaults['default_bcc'] ?? '') }}" placeholder="audit@example.com"
                                       class="mt-1 w-full rounded-md border-gray-300">
                                <div class="text-xs text-gray-500 mt-1">Used when shortcode does not specify <code>bcc=</code>.</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">From name (optional)</label>
                                <input type="text" name="from_name" value="{{ old('from_name', $defaults['from_name'] ?? '') }}"
                                       class="mt-1 w-full rounded-md border-gray-300">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">From email (optional)</label>
                                <input type="text" name="from_email" value="{{ old('from_email', $defaults['from_email'] ?? '') }}"
                                       class="mt-1 w-full rounded-md border-gray-300">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Reply-to email (optional)</label>
                            <input type="text" name="reply_to" value="{{ old('reply_to', $defaults['reply_to'] ?? '') }}"
                                   class="mt-1 w-full rounded-md border-gray-300">
                        </div>

                        <div class="border-t pt-6">
                            <h3 class="text-sm font-semibold text-gray-900">Email delivery</h3>
                            <p class="text-xs text-gray-500 mt-1">
                                Choose how form emails are sent. If you leave this on <strong>Use system mail config</strong>, Laravel will use your <code>.env</code> mail settings.
                            </p>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700">Provider</label>
                                <select name="mail_provider" class="mt-1 w-full rounded-md border-gray-300">
                                    @php $prov = old('mail_provider', $defaults['mail_provider'] ?? 'env'); @endphp
                                    <option value="env" {{ $prov === 'env' ? 'selected' : '' }}>Use system mail config (.env)</option>
                                    <option value="smtp" {{ $prov === 'smtp' ? 'selected' : '' }}>Custom SMTP (override)</option>
                                    <option value="brevo" {{ $prov === 'brevo' ? 'selected' : '' }}>Brevo API (Transactional Email)</option>
                                </select>
                            </div>

                            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <div class="text-xs font-semibold text-gray-700">SMTP override (optional)</div>
                                    <div class="text-xs text-gray-500">Only used when Provider = Custom SMTP. Leave blank to keep existing secret values.</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">SMTP host</label>
                                    <input type="text" name="smtp_host" value="{{ old('smtp_host', $defaults['smtp_host'] ?? '') }}" placeholder="smtp.example.com"
                                           class="mt-1 w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">SMTP port</label>
                                    <input type="text" name="smtp_port" value="{{ old('smtp_port', $defaults['smtp_port'] ?? '587') }}" placeholder="587"
                                           class="mt-1 w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">SMTP username</label>
                                    <input type="text" name="smtp_username" value="{{ old('smtp_username', $defaults['smtp_username'] ?? '') }}"
                                           class="mt-1 w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">SMTP password</label>
                                    <input type="password" name="smtp_password" value="" placeholder="Leave blank to keep existing"
                                           class="mt-1 w-full rounded-md border-gray-300">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Encryption</label>
                                    @php $enc = old('smtp_encryption', $defaults['smtp_encryption'] ?? 'tls'); @endphp
                                    <select name="smtp_encryption" class="mt-1 w-full rounded-md border-gray-300">
                                        <option value="tls" {{ $enc === 'tls' ? 'selected' : '' }}>TLS (recommended)</option>
                                        <option value="ssl" {{ $enc === 'ssl' ? 'selected' : '' }}>SSL</option>
                                        <option value="starttls" {{ $enc === 'starttls' ? 'selected' : '' }}>STARTTLS</option>
                                        <option value="none" {{ $enc === '' || $enc === 'none' ? 'selected' : '' }}>None</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-6">
                                <div class="text-xs font-semibold text-gray-700">Brevo API (optional)</div>
                                <div class="text-xs text-gray-500">Only used when Provider = Brevo API. We store this encrypted.</div>
                                <label class="block text-sm font-medium text-gray-700 mt-2">Brevo API key</label>
                                <input type="password" name="brevo_api_key" value="" placeholder="Leave blank to keep existing"
                                       class="mt-1 w-full rounded-md border-gray-300">
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                Save settings
                            </button>
                        </div>

                        <div class="text-xs text-gray-500 border-t pt-4 leading-relaxed">
                            Tip: Your form shortcode works like this:
                            <pre class="mt-2 text-xs bg-white border rounded-lg p-3 overflow-auto"><code>[form slug="contact"]
[form slug="contact" to="hello@example.com"]
[form slug="contact" to="hello@example.com" cc="sales@example.com" bcc="audit@example.com"]</code></pre>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
