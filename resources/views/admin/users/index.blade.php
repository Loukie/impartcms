<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Users</h2>
                <p class="text-sm text-gray-600 mt-1">Manage members and admin access.</p>
            </div>

            <div class="text-xs text-gray-600">
                Admins: <span class="font-semibold text-gray-900">{{ $adminCount }}</span>
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
                    <div class="overflow-x-auto">
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
