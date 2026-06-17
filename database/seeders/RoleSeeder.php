<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            'super_admin' => collect(PermissionCatalog::all())->pluck('key')->all(),
            'admin' => collect(PermissionCatalog::all())->pluck('key')->reject(fn (string $key): bool => in_array($key, ['roles.delete'], true))->values()->all(),
            'staff' => ['dashboard.view', 'products.view', 'inventory.view', 'stock_in.view', 'stock_out.view', 'issues.view'],
            'editor' => ['dashboard.view', 'products.view', 'products.edit', 'products.export', 'reports.products'],
            'union_expert' => ['dashboard.view', 'customers.view', 'preinvoices.own.view', 'tickets.view', 'tickets.reply'],
            'user' => ['dashboard.view', 'preinvoices.own.view'],
            'employee' => ['dashboard.view', 'users.view'],
        ];

        foreach ($roles as $roleName => $permissionKeys) {
            $role = Role::findOrCreate($roleName, 'web');

            $permissionIds = DB::table('permissions')
                ->whereIn('key', $permissionKeys)
                ->pluck('id')
                ->all();

            DB::table('role_has_permissions')->where('role_id', $role->id)->delete();

            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => Hash::make('Admin@12345'), 'is_active' => true]
        );

        if (! $admin->hasAnyRole(['super_admin', 'admin'])) {
            $admin->assignRole('super_admin');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
