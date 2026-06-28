<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;

class InventoryService
{
    public function adjustCentralStock(int $productId, int $quantityDelta, string $reference, string $note = ''): void
    {
        if ($quantityDelta === 0) {
            return;
        }

        $warehouseId = WarehouseStockService::centralWarehouseId();
        WarehouseStockService::change($warehouseId, $productId, $quantityDelta);

        $product = Product::query()->whereKey($productId)->lockForUpdate()->first();
        if (!$product) {
            return;
        }

        $before = (int) $product->stock;
        $after = $before + $quantityDelta;

        if ($after < 0) {
            abort(422, 'موجودی کالا نمی‌تواند منفی شود.');
        }

        $product->update(['stock' => $after]);

        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'type' => $quantityDelta > 0 ? 'in' : 'out',
            'reason' => $quantityDelta > 0 ? 'adjustment' : 'sale',
            'quantity' => abs($quantityDelta),
            'stock_before' => $before,
            'stock_after' => $after,
            'reference' => $reference,
            'note' => $note,
        ]);
    }
}
