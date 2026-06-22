<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Customer;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountStatementSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_statement_search_finds_customer_by_name_mobile_code_and_city(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin', 'web'));

        $province = Province::create(['name' => 'گیلان']);
        $city = City::create(['province_id' => $province->id, 'name' => 'رشت']);

        $matchingCustomer = Customer::create([
            'crm_customer_id' => 'CUST-2457',
            'first_name' => 'محمد',
            'last_name' => 'شفیع',
            'mobile' => '0911-222-3344',
            'province_id' => $province->id,
            'city_id' => $city->id,
        ]);

        Customer::create([
            'first_name' => 'علی',
            'last_name' => 'احمدی',
            'mobile' => '0912-999-8888',
        ]);

        foreach (['محمد', 'شفیع', 'محمد شفیع', '09112223344', '3344', '2457', 'رشت'] as $term) {
            $this->actingAs($user)
                ->get(route('account-statements.index', ['q' => $term]))
                ->assertOk()
                ->assertSee($matchingCustomer->display_name)
                ->assertDontSee('علی احمدی')
                ->assertSee('value="'.$term.'"', false);
        }
    }

    public function test_account_statement_search_empty_state_message_is_specific(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin', 'web'));

        Customer::create([
            'first_name' => 'علی',
            'last_name' => 'احمدی',
            'mobile' => '0912-999-8888',
        ]);

        $this->actingAs($user)
            ->get(route('account-statements.index', ['q' => 'عبارت ناموجود']))
            ->assertOk()
            ->assertSee('موردی با این مشخصات پیدا نشد.');
    }
}
