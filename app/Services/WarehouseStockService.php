<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;
use App\Services\AriyajanebiSyncService;

class WarehouseStockService
{
    public static function change(int $warehouseId, int $productId, int $delta, ?int $variantId = null): WarehouseStock
    {
        return DB::transaction(function () use ($warehouseId, $productId, $variantId, $delta) {
            self::assertVariantBelongsToProduct($productId, $variantId);

            $stock = self::lockOrCreateStock($warehouseId, $productId, $variantId);

            $newQty = (int) $stock->quantity + $delta;

            if ($newQty < 0) {
                abort(422, 'موجودی این مدل/تنوع در انبار مبدا کافی نیست.');
            }

            $stock->update([
                'quantity' => $newQty,
            ]);

            if ($variantId) {
                self::syncVariantStockFromCentral((int) $variantId);
            }
            self::syncProductStockFromCentral($productId);
            self::syncWarehouseProductAggregate($warehouseId, $productId);
            self::syncExternalProductIfCentralWarehouse($warehouseId, $productId);

            return $stock->fresh(['warehouse', 'product', 'variant']);
        });
    }

    public static function set(int $warehouseId, int $productId, int $variantId, int $quantity): WarehouseStock
    {
        return DB::transaction(function () use ($warehouseId, $productId, $variantId, $quantity) {
            if ($quantity < 0) {
                abort(422, 'موجودی نمی‌تواند منفی باشد.');
            }

            self::assertVariantBelongsToProduct($productId, $variantId);

            $stock = self::lockOrCreateStock($warehouseId, $productId, $variantId);

            $stock->update([
                'quantity' => $quantity,
            ]);

            if ($variantId) {
                self::syncVariantStockFromCentral((int) $variantId);
            }
            self::syncProductStockFromCentral($productId);
            self::syncWarehouseProductAggregate($warehouseId, $productId);
            self::syncExternalProductIfCentralWarehouse($warehouseId, $productId);

            return $stock->fresh(['warehouse', 'product', 'variant']);
        });
    }

    public static function available(int $warehouseId, int $productId, int $variantId): int
    {
        self::assertVariantBelongsToProduct($productId, $variantId);

        $stock = self::ensureStockExists($warehouseId, $productId, $variantId);

        return max(0, (int) $stock->quantity);
    }

    public static function syncVariantStockFromCentral(int $variantId): void
    {
        $variant = ProductVariant::query()
            ->whereKey($variantId)
            ->first();

        if (!$variant) {
            return;
        }

        $centralId = self::centralWarehouseId();

        $centralQty = (int) WarehouseStock::query()
            ->where('warehouse_id', $centralId)
            ->where('product_variant_id', $variantId)
            ->sum('quantity');

        $variant->update([
            'stock' => max(0, $centralQty),
        ]);
    }

    public static function syncProductStockFromCentral(int $productId): void
    {
        $centralId = self::centralWarehouseId();

        $centralQty = (int) WarehouseStock::query()
            ->where('warehouse_id', $centralId)
            ->where('product_id', $productId)
            ->whereNotNull('product_variant_id')
            ->sum('quantity');

        Product::query()
            ->whereKey($productId)
            ->update([
                'stock' => max(0, $centralQty),
            ]);
    }

    public static function syncProductSummaryFromVariants(int $productId): void
    {
        $product = Product::query()
            ->with('variants')
            ->whereKey($productId)
            ->first();

        if (!$product) {
            return;
        }

        $product->update([
            'stock' => max(0, (int) $product->variants->sum('stock')),
            'price' => max(0, (int) ($product->variants->where('is_active', true)->min('sell_price') ?? 0)),
        ]);
    }

    public static function syncAllCentralVariantStocksFromVariants(): void
    {
        $centralId = self::centralWarehouseId();

        DB::transaction(function () use ($centralId) {
            ProductVariant::query()
                ->orderBy('id')
                ->chunkById(200, function ($variants) use ($centralId) {
                    foreach ($variants as $variant) {
                        $productId = (int) $variant->product_id;
                        $variantId = (int) $variant->id;
                        $qty = max(0, (int) ($variant->stock ?? 0));

                        if ($productId <= 0 || $variantId <= 0) {
                            continue;
                        }

                        $stock = WarehouseStock::query()
                            ->where('warehouse_id', $centralId)
                            ->where('product_id', $productId)
                            ->where('product_variant_id', $variantId)
                            ->lockForUpdate()
                            ->first();

                        if ($stock) {
                            $stock->update([
                                'quantity' => $qty,
                            ]);
                        } else {
                            WarehouseStock::create([
                                'warehouse_id' => $centralId,
                                'product_id' => $productId,
                                'product_variant_id' => $variantId,
                                'quantity' => $qty,
                            ]);
                        }

                        self::syncVariantStockFromCentral($variantId);
                        self::syncProductStockFromCentral($productId);
                    }
                });
        });
    }

    public static function migrateSingleVariantOldWarehouseStocks(): void
    {
        DB::transaction(function () {
            WarehouseStock::query()
                ->whereNull('product_variant_id')
                ->orderBy('id')
                ->chunkById(200, function ($stocks) {
                    foreach ($stocks as $oldStock) {
                        $variants = ProductVariant::query()
                            ->where('product_id', (int) $oldStock->product_id)
                            ->get(['id']);

                        if ($variants->count() !== 1) {
                            continue;
                        }

                        $variantId = (int) $variants->first()->id;

                        $existing = WarehouseStock::query()
                            ->where('warehouse_id', (int) $oldStock->warehouse_id)
                            ->where('product_id', (int) $oldStock->product_id)
                            ->where('product_variant_id', $variantId)
                            ->lockForUpdate()
                            ->first();

                        if ($existing) {
                            $existing->update([
                                'quantity' => (int) $existing->quantity + (int) $oldStock->quantity,
                            ]);
                        } else {
                            WarehouseStock::create([
                                'warehouse_id' => (int) $oldStock->warehouse_id,
                                'product_id' => (int) $oldStock->product_id,
                                'product_variant_id' => $variantId,
                                'quantity' => max(0, (int) $oldStock->quantity),
                            ]);
                        }

                        self::syncVariantStockFromCentral($variantId);
                        self::syncProductStockFromCentral((int) $oldStock->product_id);
                    }
                });
        });
    }


    private static function assertVariantBelongsToProduct(int $productId, ?int $variantId): void
    {
        if (!$variantId) {
            $hasVariants = ProductVariant::query()->where('product_id', $productId)->exists();

            if ($hasVariants) {
                abort(422, 'تغییر موجودی کالای دارای تنوع بدون product_variant_id مجاز نیست.');
            }

            return;
        }

        $exists = ProductVariant::query()
            ->whereKey($variantId)
            ->where('product_id', $productId)
            ->exists();

        if (!$exists) {
            abort(422, 'تنوع انتخاب‌شده برای این محصول معتبر نیست.');
        }
    }

    private static function lockOrCreateStock(int $warehouseId, int $productId, ?int $variantId): WarehouseStock
    {
        $stock = WarehouseStock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            return $stock;
        }

        return WarehouseStock::create([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'quantity' => 0,
        ]);
    }

    private static function ensureStockExists(int $warehouseId, int $productId, ?int $variantId): WarehouseStock
    {
        $stock = WarehouseStock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($stock) {
            return $stock;
        }

        return WarehouseStock::create([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'quantity' => 0,
        ]);
    }



    private static function syncExternalProductIfCentralWarehouse(int $warehouseId, int $productId): void
    {
        if ($warehouseId !== self::centralWarehouseId()) {
            return;
        }

        DB::afterCommit(function () use ($productId) {
            $product = Product::query()
                ->with('variants')
                ->whereKey($productId)
                ->first();

            if (!$product) {
                return;
            }

            AriyajanebiSyncService::syncProduct($product);
        });
    }

    private static function syncWarehouseProductAggregate(int $warehouseId, int $productId): void
    {
        if (ProductVariant::query()->where('product_id', $productId)->exists()) {
            return;
        }

        $total = (int) WarehouseStock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->whereNull('product_variant_id')
            ->sum('quantity');

        Product::query()
            ->whereKey($productId)
            ->update(['stock' => max(0, $total)]);
    }

    public static function centralWarehouseId(): int
    {
        $warehouse = Warehouse::firstOrCreate(
            [
                'type' => 'central',
                'name' => 'انبار مرکزی',
            ],
            [
                'is_active' => true,
            ]
        );

        return (int) $warehouse->id;
    }
}
