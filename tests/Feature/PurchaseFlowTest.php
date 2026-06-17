<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_create_and_update_store_purchase_note(): void
    {
        foreach (PermissionCatalog::all() as $permission) {
            Permission::findOrCreate($permission['key'], 'web')->update([
                'name' => $permission['name'],
                'group' => $permission['group'],
            ]);
        }

        $role = Role::findOrCreate('purchase-manager', 'web');
        $role->givePermissionTo(Permission::whereIn('key', ['stock_in.create', 'stock_in.edit'])->get());

        $user = User::factory()->create();
        $user->assignRole($role);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'لوازم جانبی',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supplier = Supplier::create(['name' => 'تامین‌کننده تست']);
        $product = Product::create([
            'category_id' => $categoryId,
            'name' => 'کالای تست',
            'sku' => 'SKU-TEST-1',
            'code' => 'P-TEST-1',
            'price' => 100000,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'variant_name' => 'تنوع تست',
            'variant_code' => 'V-TEST-1',
            'sell_price' => 150000,
            'buy_price' => 100000,
            'stock' => 0,
        ]);

        $note = 'توضیحات خرید باید ذخیره شود';

        $response = $this->actingAs($user)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'note' => $note,
            'items' => [
                [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => 2,
                    'buy_price' => 100000,
                    'sell_price' => 150000,
                ],
            ],
        ]);

        $response->assertRedirect(route('purchases.index'));
        $purchase = Purchase::first();
        $this->assertSame($note, $purchase?->note);

        $updatedNote = 'توضیحات ویرایش‌شده خرید باید در لیست نمایش داده شود';

        $updateResponse = $this->actingAs($user)->put(route('purchases.update', $purchase), [
            'supplier_id' => $supplier->id,
            'note' => '',
            'description' => $updatedNote,
            'items' => [
                [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => 2,
                    'buy_price' => 100000,
                    'sell_price' => 150000,
                ],
            ],
        ]);

        $updateResponse->assertRedirect(route('purchases.index'));
        $this->assertSame($updatedNote, $purchase->refresh()->note);
    }
}
