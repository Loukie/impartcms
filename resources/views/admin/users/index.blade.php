<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Users</h2>
                <p class="text-sm text-gray-600 mt-1">Manage members and admin access.</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.users.trash') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                    Trash
                </a>

                <a href="{{ route('admin.users.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                    New User
                </a>

                <div class="text-xs text-gray-600">
                    Admins: <span class="font-semibold text-gray-900">{{ $adminCount }}</span>
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
                <div class="mb-4 p-3 rounded bg-red-50 text-red-800 border border-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @php
                        $baseTabQuery = request()->except('page', 'role');
                        $isAll = ($currentRole ?? '') === '';
                        $isAdmins = ($currentRole ?? '') === 'admin';
                        $isMembers = ($currentRole ?? '') === 'member';
                    @endphp

                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="text-sm text-gray-600">
                            <a href="{{ route('admin.users.index', $baseTabQuery) }}"
                               class="{{ $isAll ? 'font-semibold text-gray-900' : 'hover:text-gray-900' }}">
                                All <span class="text-gray-500">({{ $counts['all'] ?? 0 }})</span>
                            </a>
                            <span class="mx-2 text-gray-300">|</span>
                            <a href="{{ route('admin.users.index', array_merge($baseTabQuery, ['role' => 'admin'])) }}"
                               class="{{ $isAdmins ? 'font-semibold text-gray-900' : 'hover:text-gray-900' }}">
                                Admins <span class="text-gray-500">({{ $counts['admins'] ?? 0 }})</span>
                            </a>
                            <span class="mx-2 text-gray-300">|</span>
                            <a href="{{ route('admin.users.index', array_merge($baseTabQuery, ['role' => 'member'])) }}"
                               class="{{ $isMembers ? 'font-semibold text-gray-900' : 'hover:text-gray-900' }}">
                                Members <span class="text-gray-500">({{ $counts['members'] ?? 0 }})</span>
                            </a>
                        </div>

                        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                            <input type="hidden" name="role" value="{{ $currentRole }}">

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sort</label>
                                <select name="sort" class="mt-1 rounded-md border-gray-300">
                                    <option value="name_asc" {{ ($currentSort ?? '') === 'name_asc' ? 'selected' : '' }}>Name A→Z</option>
                                    <option value="name_desc" {{ ($currentSort ?? '') === 'name_desc' ? 'selected' : '' }}>Name Z→A</option>
                                    <option value="email_asc" {{ ($currentSort ?? '') === 'email_asc' ? 'selected' : '' }}>Email A→Z</option>
                                    <option value="email_desc" {{ ($currentSort ?? '') === 'email_desc' ? 'selected' : '' }}>Email Z→A</option>
                                    <option value="created_desc" {{ ($currentSort ?? '') === 'created_desc' ? 'selected' : '' }}>Newest</option>
                                    <option value="created_asc" {{ ($currentSort ?? '') === 'created_asc' ? 'selected' : '' }}>Oldest</option>
                                </select>
                            </div>

                            <div class="sm:ml-4">
                                <label class="block text-sm font-medium text-gray-700">Search</label>
                                <div class="mt-1 flex items-center gap-2">
                                    <input type="text" name="q" value="{{ $currentQuery }}"
                                           placeholder="Search name or email…"
                                           class="w-full sm:w-64 rounded-md border-gray-300" />

                                    <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                        Apply
                                    </button>

                                    <a href="{{ route('admin.users.index') }}"
                                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($users as $user)
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap font-medium text-gray-900">
                                        {{ $user->name }}
                                        @if(auth()->id() === $user->id)
                                            <span class="ml-2 text-xs px-2 py-0.5 rounded border border-gray-200 bg-gray-50 text-gray-700">You</span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-2 whitespace-nowrap text-gray-700">
                                        {{ $user->email }}
                                    </td>

                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @if($user->is_admin)
                                            <span class="px-2 py-1 text-xs rounded border bg-indigo-50 text-indigo-800 border-indigo-200">Admin</span>
                                        @else
                                            <span class="px-2 py-1 text-xs rounded border bg-gray-50 text-gray-700 border-gray-200">Member</span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-2 whitespace-nowrap text-gray-700 text-sm">
                                        {{ optional($user->created_at)->format('Y-m-d H:i') }}
                                    </td>

                                    <td class="px-3 py-2 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-4">
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                               class="text-indigo-600 hover:text-indigo-900 font-semibold text-sm">
                                                Edit
                                            </a>

                                            @if(auth()->id() !== $user->id)
                                                <form method="POST" action="{{ route('admin.users.toggleAdmin', $user) }}" class="inline"
                                                      onsubmit="return confirm('{{ $user->is_admin ? 'Remove admin access for this user?' : 'Promote this user to admin?' }}');">
                                                    @csrf
                                                    <button type="submit"
                                                            class="text-gray-700 hover:text-gray-900 font-semibold text-sm">
                                                        {{ $user->is_admin ? 'Remove Admin' : 'Make Admin' }}
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-gray-400 font-semibold text-sm cursor-not-allowed"
                                                      title="You can’t change your own role here.">
                                                    {{ $user->is_admin ? 'Admin' : 'Member' }}
                                                </span>
                                            @endif

                                            @php
                                                $canTrash = auth()->id() !== $user->id && !($user->is_admin && ($adminCount ?? 0) <= 1);
                                            @endphp

                                            @if($canTrash)
                                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                                      onsubmit="return confirm('Move this user to trash?');"
                                                      class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="text-red-600 hover:text-red-800 font-semibold text-sm">
                                                        Trash
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-gray-400 font-semibold text-sm cursor-not-allowed"
                                                      title="{{ auth()->id() === $user->id ? 'You can’t trash yourself.' : 'You can’t trash the last admin.' }}">
                                                    Trash
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-gray-500">
                                        No users found.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($users, 'links'))
                        <div class="mt-6">
                            {{ $users->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
