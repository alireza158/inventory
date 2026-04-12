<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UsersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_page_displays_external_users(): void
    {
        config()->set('crm.base_url', 'https://crm.ariyajanebi.ir');
        config()->set('crm.users_endpoint', '/external/users');
        config()->set('crm.api_token', 'test-token');
        config()->set('crm.sync_enabled', true);

        Http::fake([
            'https://crm.ariyajanebi.ir/external/users' => Http::response([
                'data' => [
                    ['id' => 10, 'name' => 'Ali', 'email' => 'ali@example.com'],
                ],
            ], 200),
        ]);

        $role = Role::findOrCreate('admin', 'web');
        $permission = Permission::findOrCreate('users.view', 'web');
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('Ali');
        $response->assertSee('ali@example.com');
        $response->assertSee('همگام‌سازی با CRM');
    }

    public function test_users_sync_button_calls_external_api_and_redirects_with_success_message(): void
    {
        config()->set('crm.base_url', 'https://crm.ariyajanebi.ir');
        config()->set('crm.users_endpoint', '/external/users');
        config()->set('crm.api_token', 'test-token');
        config()->set('crm.sync_enabled', true);

        Http::fake([
            'https://crm.ariyajanebi.ir/external/users' => Http::response([
                'data' => [
                    ['id' => 1, 'name' => 'User 1'],
                    ['id' => 2, 'name' => 'User 2'],
                ],
            ], 200),
        ]);

        $role = Role::findOrCreate('admin', 'web');
        $permission = Permission::findOrCreate('users.view', 'web');
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->post(route('users.sync'));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('sync_success');

        Http::assertSentCount(1);
    }
}
