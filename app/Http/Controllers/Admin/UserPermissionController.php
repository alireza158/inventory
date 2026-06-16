<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessPermission;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserPermissionController extends Controller
{
    public function index(Request $request): View
    {
        $this->syncCatalogPermissions();

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

        $sidebarPages = $this->sidebarPagesWithModels();

        return view('admin.permissions.index', compact('users', 'selectedUser', 'permissions', 'selectedPermissionIds', 'sidebarPages'));
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


    private function syncCatalogPermissions(): void
    {
        foreach (PermissionCatalog::all() as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $permission['key']],
                [
                    'name' => $permission['name'],
                    'group' => $permission['group'],
                    'guard_name' => 'web',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
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
