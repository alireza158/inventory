<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\WarehouseStock;
use App\Services\AriyajanebiSyncService;
use App\Services\InventoryWebhookService;
use App\Services\WarehouseStockService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inventory:set-central-stock {quantity=500 : Quantity to set for every product variant}', function (int $quantity) {
    if ($quantity < 0) {
        $this->error('Quantity cannot be negative.');

        return 1;
    }

    $centralWarehouseId = WarehouseStockService::centralWarehouseId();
    $productCount = 0;
    $variantCount = 0;

    Product::query()
        ->with(['variants:id,product_id'])
        ->orderBy('id')
        ->chunkById(100, function ($products) use ($centralWarehouseId, $quantity, &$productCount, &$variantCount) {
            DB::transaction(function () use ($products, $centralWarehouseId, $quantity, &$productCount, &$variantCount) {
                foreach ($products as $product) {
                    $productCount++;
                    $productId = (int) $product->id;
                    $productVariantCount = 0;

                    foreach ($product->variants as $variant) {
                        $variantCount++;
                        $productVariantCount++;
                        $variantId = (int) $variant->id;

                        WarehouseStock::query()->updateOrCreate(
                            [
                                'warehouse_id' => $centralWarehouseId,
                                'product_variant_id' => $variantId,
                            ],
                            [
                                'product_id' => $productId,
                                'quantity' => $quantity,
                            ]
                        );
                    }

                    $productQuantity = $productVariantCount > 0 ? $productVariantCount * $quantity : $quantity;

                    WarehouseStock::query()->updateOrCreate(
                        [
                            'warehouse_id' => $centralWarehouseId,
                            'product_id' => $productId,
                            'product_variant_id' => null,
                        ],
                        [
                            'quantity' => $productQuantity,
                        ]
                    );

                    $product->forceFill(['stock' => $productQuantity])->save();
                }

                ProductVariant::query()
                    ->whereIn('product_id', $products->pluck('id'))
                    ->update(['stock' => $quantity]);
            });
        });

    $this->info("Central warehouse stock set to {$quantity} for {$productCount} products and {$variantCount} variants.");

    return 0;
})->purpose('Set central warehouse stock quantity for every product and product variant');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('crm:sync-users')
    ->when(fn () => config('crm.sync_enabled'))
    ->everyFifteenMinutes();

Schedule::command('crm:sync-customers')
    ->when(fn () => config('crm.sync_enabled'))
    ->everyThreeMinutes();

Schedule::call(function () {
    InventoryWebhookService::processPending();
    AriyajanebiSyncService::processPending();
})->everyMinute();

Schedule::command('ariya:import-orders')->everyFiveMinutes();
