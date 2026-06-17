<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessPermission;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserPermissionController extends Controller
{
    public function index(Request $request): View
    {
        $this->syncCatalogPermissions();

        $users = User::query()->with('roles')->orderBy('name')->get();
        $selectedUser = $request->integer('user_id')
            ? User::with(['permissions', 'roles'])->find($request->integer('user_id'))
            : $users->first()?->load(['permissions', 'roles']);

        $permissions = AccessPermission::query()
            ->whereNotNull('key')
            ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('group');

        $selectedPermissionIds = $selectedUser
            ? $selectedUser->permissions->pluck('id')->all()
            : [];

        $roles = Role::query()->orderBy('name')->get();
        $selectedRoleNames = $selectedUser
            ? $selectedUser->roles->pluck('name')->all()
            : [];

        $sidebarPages = $this->sidebarPagesWithModels();

        return view('admin.permissions.index', compact('users', 'selectedUser', 'permissions', 'selectedPermissionIds', 'sidebarPages', 'roles', 'selectedRoleNames'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $permissionIds = $validated['permissions'] ?? [];
        $roleNames = $validated['roles'] ?? [];

        if ($user->is(auth()->user()) && $user->isSuperAdmin() && ! in_array('super_admin', $roleNames, true)) {
            $superAdminCount = User::role('super_admin')->count();
            if ($superAdminCount <= 1) {
                return back()->with('error', 'برای جلوگیری از قفل شدن پنل، امکان حذف تنها نقش مدیرکل از حساب خودتان وجود ندارد.');
            }
        }

        $user->permissions()->sync($permissionIds);
        if (auth()->user()?->can('permissions.assign_roles')) {
            $user->syncRoles($roleNames);
        }

        return redirect()
            ->route('admin.permissions.index', ['user_id' => $user->id])
            ->with('success', 'دسترسی‌های کاربر با موفقیت ذخیره شد.');
    }


    private function syncCatalogPermissions(): void
    {
        PermissionCatalog::syncToDatabase();
    }

    private function sidebarPagesWithModels(): array
    {
        $sidebarPermissionKeys = collect(PermissionCatalog::sidebarPages())
            ->flatten(1)
            ->pluck('permission')
            ->unique()
            ->values();

        $permissionsByKey = AccessPermission::query()
            ->whereIn('key', $sidebarPermissionKeys)
            ->get()
            ->keyBy('key');

        return collect(PermissionCatalog::sidebarPages())
            ->map(fn (array $pages): array => collect($pages)
                ->map(fn (array $page): array => $page + ['model' => $permissionsByKey->get($page['permission'])])
                ->filter(fn (array $page): bool => $page['model'] !== null)
                ->values()
                ->all())
            ->filter(fn (array $pages): bool => $pages !== [])
            ->all();
    }
}
