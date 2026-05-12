<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\Product;

class WarehouseStockService
{
    public static function change(int $warehouseId, int $productId, int $delta): WarehouseStock
    {
        $stock = WarehouseStock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if (!$stock) {
            $stock = WarehouseStock::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'quantity' => 0,
            ]);
            $stock->refresh();
        }

        $newQty = (int) $stock->quantity + $delta;

        if ($newQty < 0) {
            abort(422, 'موجودی انبار مبدا کافی نیست.');
        }

        $stock->update(['quantity' => $newQty]);


        self::syncProductStockFromCentral((int) $productId);

        return $stock->fresh();
    }


    public static function syncProductStockFromCentral(int $productId): void
    {
        $centralId = self::centralWarehouseId();

        $centralQty = (int) (WarehouseStock::query()
            ->where('warehouse_id', $centralId)
            ->where('product_id', $productId)
            ->value('quantity') ?? 0);

        Product::query()->where('id', $productId)->update([
            'stock' => max(0, $centralQty),
        ]);
    }

    public static function centralWarehouseId(): int
    {
        $warehouse = Warehouse::firstOrCreate(
            ['type' => 'central', 'name' => 'انبار مرکزی'],
            ['is_active' => true]
        );

        return (int) $warehouse->id;
    }
}

