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
                            <p class="text-xs text-gray-500 mt-2">Minimum 8 characters.</p>
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
</x-admin-layout>
