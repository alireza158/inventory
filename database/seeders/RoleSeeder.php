<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);
        $usersViewPermission = Permission::findOrCreate('users.view', 'web');
        $allPermission = Permission::findOrCreate('*', 'web');

        $adminRole->syncPermissions([$usersViewPermission, $allPermission]);
        $employeeRole->syncPermissions([$usersViewPermission]);

        // ادمین اولیه (اگر وجود نداشت)
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => Hash::make('Admin@12345')]
        );

        if (!$admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }
    }
}
