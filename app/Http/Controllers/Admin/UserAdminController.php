<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'adminCount' => $this->adminCount(),
            'users' => User::query()
                ->orderByDesc('is_admin')
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'adminCount' => $this->adminCount(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        $requestedIsAdmin = (bool) ($validated['is_admin'] ?? false);

        // Safety rules
        // - You can't remove your own admin access
        // - You can't remove admin access from the last remaining admin
        if ($user->is_admin && !$requestedIsAdmin) {
            if ($user->id === (int) $request->user()->id) {
                return back()->withErrors(['is_admin' => 'You can’t remove your own admin access.']);
            }
            if ($this->adminCount() <= 1) {
                return back()->withErrors(['is_admin' => 'You can’t remove admin access from the last admin.']);
            }
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->is_admin = $requestedIsAdmin;
        $user->save();

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function toggleAdmin(Request $request, User $user): RedirectResponse
    {
        if ($user->id === (int) $request->user()->id) {
            return back()->withErrors(['status' => 'You can’t change your own admin role from here.']);
        }

        if ($user->is_admin && $this->adminCount() <= 1) {
            return back()->withErrors(['status' => 'You can’t demote the last admin.']);
        }

        $user->is_admin = !$user->is_admin;
        $user->save();

        return back()->with('status', $user->is_admin ? 'User promoted to admin.' : 'Admin access removed.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === (int) $request->user()->id) {
            return back()->withErrors(['status' => 'You can’t delete your own account from here.']);
        }

        if ($user->is_admin && $this->adminCount() <= 1) {
            return back()->withErrors(['status' => 'You can’t delete the last admin.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }

    private function adminCount(): int
    {
        return (int) User::query()->where('is_admin', true)->count();
    }
}
