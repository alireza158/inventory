<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\ProductVariant;

class InventoryService
{
    public function adjustCentralStock(
        int $productId,
        int $variantId,
        int $quantityDelta,
        string $reference,
        string $note = '',
        array $meta = []
    ): void {
        if ($quantityDelta === 0) {
            return;
        }

        if ($variantId <= 0) {
            abort(422, 'تغییر موجودی کالا بدون مشخص بودن تنوع کالا مجاز نیست.');
        }

        $variant = ProductVariant::query()
            ->whereKey($variantId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if (! $variant) {
            abort(422, 'تنوع انتخاب‌شده برای این کالا معتبر نیست.');
        }

        $warehouseId = (int) ($meta['warehouse_id'] ?? WarehouseStockService::centralWarehouseId());
        $before = WarehouseStockService::available($warehouseId, $productId, $variantId);
        $after = $before + $quantityDelta;

        if ($after < 0) {
            abort(422, 'موجودی این تنوع در انبار مرکزی کافی نیست و نمی‌تواند منفی شود.');
        }

        WarehouseStockService::change($warehouseId, $productId, $quantityDelta, $variantId);

        StockMovement::create([
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'warehouse_id' => $warehouseId,
            'user_id' => auth()->id(),
            'type' => $quantityDelta > 0 ? StockMovement::TYPE_IN : StockMovement::TYPE_OUT,
            'reason' => $meta['reason'] ?? ($quantityDelta > 0 ? StockMovement::REASON_ADJUSTMENT : StockMovement::REASON_SALE),
            'transaction_type' => $meta['transaction_type'] ?? null,
            'quantity' => abs($quantityDelta),
            'stock_before' => $before,
            'stock_after' => $after,
            'reference' => $reference,
            'reference_type' => $meta['reference_type'] ?? Invoice::class,
            'reference_id' => $meta['reference_id'] ?? null,
            'note' => $note,
        ]);
    }
}
