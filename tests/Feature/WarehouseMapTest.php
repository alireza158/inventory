<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\WarehouseMapService;
use App\Services\WarehouseStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WarehouseMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_is_unique_per_warehouse_zone_rack_box(): void
    {
        $warehouse = Warehouse::create(['name' => 'انبار مرکزی', 'type' => 'central', 'is_active' => true]);

        WarehouseLocation::firstOrCreate(['warehouse_id' => $warehouse->id, 'zone' => 'Z01', 'rack' => 'R02', 'box' => 'B05'], ['code' => 'Z01-R02-B05']);
        WarehouseLocation::firstOrCreate(['warehouse_id' => $warehouse->id, 'zone' => 'Z01', 'rack' => 'R02', 'box' => 'B05'], ['code' => 'Z01-R02-B05']);

        $this->assertSame(1, WarehouseLocation::count());
    }

    public function test_assign_and_transfer_keeps_total_stock_unchanged(): void
    {
        [$warehouse, $variant, $first, $second] = $this->fixture();
        $service = app(WarehouseMapService::class);

        $service->assignLocation($variant->id, $warehouse->id, $first->id, 30, null);
        $this->assertSame(30, $service->mappedQuantity($variant->id, $warehouse->id));
        $this->assertSame(70, $service->unmappedQuantity($variant->id, $warehouse->id));

        $service->assignLocation($variant->id, $warehouse->id, $second->id, 20, null);
        $this->assertSame(50, $service->mappedQuantity($variant->id, $warehouse->id));
        $this->assertSame(50, $service->unmappedQuantity($variant->id, $warehouse->id));

        try {
            $service->assignLocation($variant->id, $warehouse->id, $second->id, 60, null);
            $this->fail('Expected validation exception.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('بیشتر از موجودی بدون نقشه', $e->getMessage());
        }

        $service->transfer($variant->id, $warehouse->id, $first->id, $second->id, 10, null);
        $this->assertSame(100, $service->totalQuantity($variant->id, $warehouse->id));
        $this->assertSame(20, $variant->locationStocks()->where('warehouse_location_id', $first->id)->value('quantity'));
        $this->assertSame(30, $variant->locationStocks()->where('warehouse_location_id', $second->id)->value('quantity'));
    }

    public function test_transfer_more_than_source_stock_fails(): void
    {
        [$warehouse, $variant, $first, $second] = $this->fixture();
        $service = app(WarehouseMapService::class);
        $service->assignLocation($variant->id, $warehouse->id, $first->id, 10, null);

        $this->expectException(ValidationException::class);
        $service->transfer($variant->id, $warehouse->id, $first->id, $second->id, 11, null);
    }

    private function fixture(): array
    {
        $warehouse = Warehouse::create(['name' => 'انبار مرکزی', 'type' => 'central', 'is_active' => true]);
        $category = Category::create(['name' => 'قاب', 'code' => '01']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'قاب آیفون ۱۳', 'sku' => 'P1', 'code' => '010001', 'stock' => 100, 'price' => 0]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'variant_name' => 'مشکی', 'variety_name' => 'مشکی', 'variety_code' => '0001', 'variant_code' => '01000100001', 'sell_price' => 0, 'stock' => 100, 'reserved' => 0]);
        WarehouseStockService::set($warehouse->id, $product->id, $variant->id, 100);
        $first = WarehouseLocation::create(['warehouse_id' => $warehouse->id, 'zone' => 'Z01', 'rack' => 'R02', 'box' => 'B05', 'code' => 'Z01-R02-B05', 'is_active' => true]);
        $second = WarehouseLocation::create(['warehouse_id' => $warehouse->id, 'zone' => 'Z01', 'rack' => 'R03', 'box' => 'B01', 'code' => 'Z01-R03-B01', 'is_active' => true]);
        return [$warehouse, $variant, $first, $second];
    }
}
