<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'key')) {
                $table->string('key')->nullable()->unique()->after('name');
            }

            if (!Schema::hasColumn('permissions', 'group')) {
                $table->string('group')->nullable()->after('key');
            }
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'permission_id']);
        });

        $permissions = [
            ['group' => 'داشبورد', 'key' => 'dashboard.view', 'name' => 'مشاهده داشبورد'],
            ['group' => 'کالاها', 'key' => 'products.view', 'name' => 'مشاهده کالاها'],
            ['group' => 'کالاها', 'key' => 'products.create', 'name' => 'ایجاد کالا'],
            ['group' => 'کالاها', 'key' => 'products.edit', 'name' => 'ویرایش کالا'],
            ['group' => 'کالاها', 'key' => 'products.delete', 'name' => 'حذف کالا'],
            ['group' => 'کالاها', 'key' => 'categories.manage', 'name' => 'مدیریت دسته‌بندی‌ها'],
            ['group' => 'انبار', 'key' => 'stock.in', 'name' => 'ورود کالا به انبار'],
            ['group' => 'انبار', 'key' => 'stock.out', 'name' => 'خروج کالا از انبار'],
            ['group' => 'انبار', 'key' => 'warehouse.transfer', 'name' => 'انتقال بین انبارها'],
            ['group' => 'انبار', 'key' => 'warehouse.receipt', 'name' => 'رسید انبار'],
            ['group' => 'انبار', 'key' => 'inventory.view', 'name' => 'مشاهده موجودی'],
            ['group' => 'گزارشات', 'key' => 'reports.inventory', 'name' => 'گزارش موجودی'],
            ['group' => 'گزارشات', 'key' => 'reports.stock_movement', 'name' => 'گزارش گردش کالا'],
            ['group' => 'گزارشات', 'key' => 'reports.low_stock', 'name' => 'گزارش کمبود موجودی'],
            ['group' => 'اشخاص', 'key' => 'customers.manage', 'name' => 'مدیریت مشتریان'],
            ['group' => 'اشخاص', 'key' => 'suppliers.manage', 'name' => 'مدیریت تامین‌کنندگان'],
            ['group' => 'کاربران و تنظیمات', 'key' => 'users.manage', 'name' => 'مدیریت کاربران'],
            ['group' => 'کاربران و تنظیمات', 'key' => 'permissions.manage', 'name' => 'مدیریت دسترسی‌ها'],
            ['group' => 'کاربران و تنظیمات', 'key' => 'roles.manage', 'name' => 'مدیریت نقش‌ها'],
            ['group' => 'کاربران و تنظیمات', 'key' => 'settings.manage', 'name' => 'مدیریت تنظیمات'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $permission['key']],
                $permission + ['guard_name' => 'web', 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');

        Schema::table('permissions', function (Blueprint $table) {
            if (Schema::hasColumn('permissions', 'key')) {
                $table->dropUnique(['key']);
                $table->dropColumn('key');
            }
            if (Schema::hasColumn('permissions', 'group')) {
                $table->dropColumn('group');
            }
        });
    }
};
