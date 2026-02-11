<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add User</h2>
                <p class="text-sm text-gray-600 mt-1">Create a new member or co-admin.</p>
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
                    <form id="user-create-form" method="POST" action="{{ route('admin.users.store') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name</label>
                                <input name="name" type="text" value="{{ old('name') }}"
                                       class="mt-1 w-full rounded-md border-gray-300" required>
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input name="email" type="email" value="{{ old('email') }}"
                                       class="mt-1 w-full rounded-md border-gray-300" required>
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-6 border-t pt-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Password</label>
                                    <input name="password" type="password" autocomplete="new-password"
                                           class="mt-1 w-full rounded-md border-gray-300" required>
                                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Confirm password</label>
                                    <input name="password_confirmation" type="password" autocomplete="new-password"
                                           class="mt-1 w-full rounded-md border-gray-300" required>
                                </div>
                            </div>
                            
                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                <button type="button" id="btn-generate-password"
                                        class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                    Generate random password
                                </button>

                                <div id="generated-password-wrap" class="hidden items-center gap-2 text-xs">
                                    <span class="text-gray-600">Generated:</span>
                                    <code id="generated-password" class="px-2 py-1 rounded bg-gray-100 text-gray-900 border border-gray-200"></code>
                                    <button type="button" id="btn-copy-password"
                                            class="inline-flex items-center px-2 py-1 bg-gray-900 text-white rounded-md font-semibold text-[10px] uppercase tracking-widest hover:bg-gray-800">
                                        Copy
                                    </button>
                                    <span id="copy-status" class="text-gray-500"></span>
                                </div>
                            </div>

                            <p class="text-xs text-gray-500 mt-2">
                                Minimum 8 characters. Passwords are stored hashed — you can’t view them later, only reset them.
                            </p>

                        </div>

                        <div class="mt-6 border-t pt-6">
                            <div class="flex items-start gap-3">
                                <input id="is_admin" name="is_admin" type="checkbox" value="1"
                                       class="mt-1 rounded border-gray-300"
                                       {{ old('is_admin') ? 'checked' : '' }}>
                                <div class="min-w-0">
                                    <label for="is_admin" class="text-sm font-medium text-gray-900">Admin (co-admin)</label>
                                    <p class="text-xs text-gray-600 mt-1">
                                        Admins can access the CMS backend (pages, settings, users).
                                    </p>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('is_admin')" class="mt-2" />
                        </div>

                    </form>

                    <div class="mt-8 flex items-center justify-between">
                        <button type="submit" form="user-create-form"
                                class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                            Create User
                        </button>

                        <div class="text-xs text-gray-600">
                            Admins currently: <span class="font-semibold text-gray-900">{{ $adminCount ?? '' }}</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

<script>
(function () {
    function generatePassword(length) {
        length = length || 16;
        const chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*()-_=+[]{};:,.?";
        const bytes = new Uint32Array(length);
        window.crypto.getRandomValues(bytes);
        let out = "";
        for (let i = 0; i < bytes.length; i++) {
            out += chars[bytes[i] % chars.length];
        }
        return out;
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            try {
                const input = document.createElement('input');
                input.value = text;
                input.setAttribute('readonly', '');
                input.style.position = 'absolute';
                input.style.left = '-9999px';
                document.body.appendChild(input);
                input.select();
                const ok = document.execCommand('copy');
                document.body.removeChild(input);
                ok ? resolve() : reject();
            } catch (e) {
                reject(e);
            }
        });
    }

    const btnGen = document.getElementById('btn-generate-password');
    const wrap = document.getElementById('generated-password-wrap');
    const code = document.getElementById('generated-password');
    const btnCopy = document.getElementById('btn-copy-password');
    const status = document.getElementById('copy-status');

    if (!btnGen) return;

    btnGen.addEventListener('click', function () {
        const pwd = generatePassword(16);
        const p1 = document.querySelector('input[name="password"]');
        const p2 = document.querySelector('input[name="password_confirmation"]');
        if (p1) p1.value = pwd;
        if (p2) p2.value = pwd;

        if (code) code.textContent = pwd;
        if (wrap) wrap.classList.remove('hidden');
        if (status) status.textContent = '— copy and send to the user';
    });

    if (btnCopy) {
        btnCopy.addEventListener('click', function () {
            const pwd = (code && code.textContent) ? code.textContent : '';
            if (!pwd) return;

            copyToClipboard(pwd).then(function () {
                if (status) status.textContent = 'Copied ✅';
                setTimeout(function () { if (status) status.textContent = ''; }, 1500);
            }).catch(function () {
                if (status) status.textContent = 'Copy failed — select and copy manually';
            });
        });
    }
})();
</script>

</x-admin-layout>
