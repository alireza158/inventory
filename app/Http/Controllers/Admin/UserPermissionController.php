<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessPermission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserPermissionController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()->orderBy('name')->get();
        $selectedUser = $request->integer('user_id')
            ? User::with('permissions')->find($request->integer('user_id'))
            : $users->first()?->load('permissions');

        $permissions = AccessPermission::query()
            ->whereNotNull('key')
            ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('group');

        $selectedPermissionIds = $selectedUser
            ? $selectedUser->permissions->pluck('id')->all()
            : [];

        return view('admin.permissions.index', compact('users', 'selectedUser', 'permissions', 'selectedPermissionIds'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissionIds = $validated['permissions'] ?? [];
        $user->permissions()->sync($permissionIds);

        return redirect()
            ->route('admin.permissions.index', ['user_id' => $user->id])
            ->with('success', 'دسترسی‌های کاربر با موفقیت ذخیره شد.');
    }
}
