<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerApiSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_preinvoice_customer_search_finds_last_name_full_name_and_normalized_mobile(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));

        $customer = Customer::create([
            'first_name' => 'علی',
            'last_name' => 'رضایی',
            'mobile' => '0912-345-6789',
        ]);

        $this->actingAs($user)
            ->getJson(route('api.customers.search', ['q' => 'رضایی']))
            ->assertOk()
            ->assertJsonPath('data.customers.0.id', $customer->id);

        $this->actingAs($user)
            ->getJson(route('api.customers.search', ['q' => 'علی رضایی']))
            ->assertOk()
            ->assertJsonPath('data.customers.0.id', $customer->id);

        $this->actingAs($user)
            ->getJson(route('api.customers.search', ['q' => '۰۹۱۲۳۴۵۶۷۸۹']))
            ->assertOk()
            ->assertJsonPath('data.customers.0.id', $customer->id);
    }
}
