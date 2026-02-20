<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserAdminController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $role = (string) $request->query('role', ''); // '', 'admin', 'member'
        $sort = (string) $request->query('sort', 'name_asc');

        $base = User::query();

        if ($q !== '') {
            $base->where(function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%');
            });
        }

        // Counts for tabs (reflect current search).
        $countsBase = clone $base;
        $counts = [
            'all' => (clone $countsBase)->count(),
            'admins' => (clone $countsBase)->where('is_admin', true)->count(),
            'members' => (clone $countsBase)->where('is_admin', false)->count(),
        ];

        if ($role === 'admin') {
            $base->where('is_admin', true);
        } elseif ($role === 'member') {
            $base->where('is_admin', false);
        } else {
            $role = '';
        }

        switch ($sort) {
            case 'name_desc':
                $base->orderByDesc('name');
                break;
            case 'email_asc':
                $base->orderBy('email');
                break;
            case 'email_desc':
                $base->orderByDesc('email');
                break;
            case 'created_desc':
                $base->orderByDesc('created_at');
                break;
            case 'created_asc':
                $base->orderBy('created_at');
                break;
            case 'name_asc':
            default:
                $sort = 'name_asc';
                // WordPress-ish: show admins first, then name.
                $base->orderByDesc('is_admin')->orderBy('name');
                break;
        }
        $base->orderBy('id');

        return view('admin.users.index', [
            'adminCount' => $counts['admins'],
            'counts' => $counts,
            'users' => $base->paginate(25)->withQueryString(),
            'currentQuery' => $q,
            'currentRole' => $role,
            'currentSort' => $sort,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'adminCount' => $this->adminCount(),
        ]);
    }

    public function trash(): View
    {
        return view('admin.users.trash', [
            'users' => User::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate(25),
            'adminCount' => $this->adminCount(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);
        $user->is_admin = (bool) ($validated['is_admin'] ?? false);
        $user->save();

        return redirect()->route('admin.users.edit', $user)->with('status', 'User created.');
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

    public function sendResetLink(Request $request, User $user): RedirectResponse
    {
        // Only admins can access this controller group already.
        // Safety: avoid sending reset links to yourself from here (use standard profile reset instead).
        if ($user->id === (int) $request->user()->id) {
            return back()->withErrors(['status' => 'Use your profile page to update your own password.']);
        }

        if (empty($user->email)) {
            return back()->withErrors(['status' => 'This user has no email address.']);
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Password reset link sent.');
        }

        return back()->withErrors([
            'status' => 'Could not send reset link. Check your mail settings in .env (SMTP) and try again.',
        ]);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === (int) $request->user()->id) {
            return back()->withErrors(['status' => 'You can’t trash your own account from here.']);
        }

        if ($user->is_admin && $this->adminCount() <= 1) {
            return back()->withErrors(['status' => 'You can’t trash the last admin.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User moved to trash ✅');
    }

    /**
     * Restore from trash
     */
    public function restore(User $userTrash): RedirectResponse
    {
        $userTrash->restore();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User restored ✅');
    }

    /**
     * Delete permanently (force delete)
     */
    public function forceDestroy(Request $request, User $userTrash): RedirectResponse
    {
        // Hard safety: never allow the currently-authenticated user to be force-deleted from here.
        if ($userTrash->id === (int) $request->user()->id) {
            return back()->withErrors(['status' => 'You can’t delete your own account from here.']);
        }

        // Don’t allow permanently deleting the last active admin.
        if ($userTrash->is_admin && $this->adminCount() <= 0) {
            return back()->withErrors(['status' => 'You can’t permanently delete the last admin. Restore or create another admin first.']);
        }

        $userTrash->forceDelete();

        return redirect()
            ->route('admin.users.trash')
            ->with('status', 'User deleted permanently ✅');
    }

    private function adminCount(): int
    {
        return (int) User::query()->where('is_admin', true)->count();
    }
}
