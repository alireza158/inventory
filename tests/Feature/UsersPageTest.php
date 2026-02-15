<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UsersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_page_displays_external_users(): void
    {
        config()->set('services.external_sync.base_url', 'https://crm.ariyajanebi.ir');
        config()->set('services.external_sync.token', 'test-token');

        Http::fake([
            'https://crm.ariyajanebi.ir/external/users' => Http::response([
                'data' => [
                    ['id' => 10, 'name' => 'Ali', 'email' => 'ali@example.com'],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('Ali');
        $response->assertSee('ali@example.com');
        $response->assertSee('سینک کاربران');
    }

    public function test_users_sync_button_calls_external_api_and_redirects_with_success_message(): void
    {
        config()->set('services.external_sync.base_url', 'https://crm.ariyajanebi.ir');
        config()->set('services.external_sync.token', 'test-token');

        Http::fake([
            'https://crm.ariyajanebi.ir/external/users' => Http::response([
                'data' => [
                    ['id' => 1, 'name' => 'User 1'],
                    ['id' => 2, 'name' => 'User 2'],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('users.sync'));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('sync_success');

        Http::assertSentCount(1);
    }
}
