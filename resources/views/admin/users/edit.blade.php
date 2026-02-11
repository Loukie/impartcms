<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit User</h2>
                <p class="text-sm text-gray-600 mt-1">Update profile details and access level.</p>
            </div>

            <a href="{{ route('admin.users.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                Back to Users
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
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
                    <form id="user-update-form" method="POST" action="{{ route('admin.users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name</label>
                                <input name="name" type="text" value="{{ old('name', $user->name) }}"
                                       class="mt-1 w-full rounded-md border-gray-300" required>
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input name="email" type="email" value="{{ old('email', $user->email) }}"
                                       class="mt-1 w-full rounded-md border-gray-300" required>
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-6 border-t pt-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="text-sm font-medium text-gray-900">Password reset</div>
                                <button type="button" id="gen-password"
                                        class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                    Generate random password
                                </button>
                            </div>

                            <div id="generated-wrap" class="mt-3 hidden">
                                <div class="text-xs text-gray-600">Generated (copy this now — it won’t be shown again):</div>
                                <div class="mt-2 flex items-center gap-2">
                                    <code id="generated-password" class="px-2 py-1 rounded bg-gray-100 text-gray-900 text-sm"></code>
                                    <button type="button" id="copy-password"
                                            class="inline-flex items-center px-3 py-1.5 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                        Copy
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">New password (optional)</label>
                                    <input name="password" type="password" autocomplete="new-password"
                                           class="mt-1 w-full rounded-md border-gray-300" placeholder="Leave blank to keep current">
                                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Confirm new password</label>
                                    <input name="password_confirmation" type="password" autocomplete="new-password"
                                           class="mt-1 w-full rounded-md border-gray-300" placeholder="Repeat new password">
                                </div>
                            </div>

                            @if(auth()->id() !== $user->id)
                                <div class="mt-4">
                                    <form method="POST" action="{{ route('admin.users.sendResetLink', $user) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                            Send reset link
                                        </button>
                                    </form>
                                    <span class="ml-2 text-xs text-gray-500">Requires working mail settings.</span>
                                </div>
                            @endif
                        </div>

                        <div class="mt-6 border-t pt-6">
                            <div class="flex items-start gap-3">
                                <input id="is_admin" name="is_admin" type="checkbox" value="1"
                                       class="mt-1 rounded border-gray-300"
                                       {{ old('is_admin', $user->is_admin ? 1 : 0) ? 'checked' : '' }}>
                                <div class="min-w-0">
                                    <label for="is_admin" class="text-sm font-medium text-gray-900">Admin (co-admin)</label>
                                    <p class="text-xs text-gray-600 mt-1">
                                        Admins can access the CMS backend (pages, settings, users). Keep at least one admin.
                                    </p>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('is_admin')" class="mt-2" />
                        </div>

                    </form>

                    <div class="mt-8 flex items-center justify-between">
                        <button type="submit" form="user-update-form"
                                class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                            Save
                        </button>

                        @if(auth()->id() !== $user->id)
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                  onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-red-700">
                                    Delete User
                                </button>
                            </form>
                        @else
                            <span class="text-sm text-gray-500">You can’t delete yourself here.</span>
                        @endif
                    </div>

                    @if($user->is_admin && $adminCount <= 1)
                        <div class="mt-6 p-3 rounded bg-yellow-50 text-yellow-800 border border-yellow-200">
                            This is the last admin account. You won’t be able to remove admin access or delete this user until another admin exists.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const genBtn = document.getElementById('gen-password');
            const copyBtn = document.getElementById('copy-password');
            const wrap = document.getElementById('generated-wrap');
            const out = document.getElementById('generated-password');
            const pw = document.querySelector('input[name="password"]');
            const pw2 = document.querySelector('input[name="password_confirmation"]');

            function generatePassword(length = 16) {
                const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*_-';
                const bytes = new Uint32Array(length);
                window.crypto.getRandomValues(bytes);
                let result = '';
                for (let i = 0; i < length; i++) {
                    result += chars[bytes[i] % chars.length];
                }
                return result;
            }

            if (genBtn) {
                genBtn.addEventListener('click', () => {
                    const p = generatePassword(18);
                    pw.value = p;
                    pw2.value = p;
                    out.textContent = p;
                    wrap.classList.remove('hidden');
                });
            }

            if (copyBtn) {
                copyBtn.addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(out.textContent || '');
                        copyBtn.textContent = 'Copied';
                        setTimeout(() => (copyBtn.textContent = 'Copy'), 1200);
                    } catch (e) {
                        const sel = document.createRange();
                        sel.selectNode(out);
                        const s = window.getSelection();
                        s.removeAllRanges();
                        s.addRange(sel);
                        document.execCommand('copy');
                        s.removeAllRanges();
                    }
                });
            }
        })();
    </script>
</x-admin-layout>
