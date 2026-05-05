<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\DB;
use App\Models\PreinvoiceOrder;
use App\Models\ProductVariant;
use App\Models\Product;
use App\Services\WarehouseStockService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('crm:sync-users')
    ->when(fn () => config('crm.sync_enabled'))
    ->everyFifteenMinutes();

Artisan::command('preinvoice:release-expired-freezes', function () {
    DB::transaction(function () {
        $orders = PreinvoiceOrder::query()
            ->whereIn('status', [PreinvoiceOrder::STATUS_SUBMITTED_WAREHOUSE, PreinvoiceOrder::STATUS_SUBMITTED_FINANCE])
            ->whereNotNull('stock_frozen_until')
            ->whereNull('stock_released_at')
            ->where('stock_frozen_until', '<=', now())
            ->with('items')
            ->lockForUpdate()
            ->get();

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $variant = ProductVariant::query()->whereKey((int) $item->variant_id)->lockForUpdate()->first();
                if ($variant) {
                    $variant->reserved = max(0, (int) $variant->reserved - (int) $item->quantity);
                    $variant->save();
                }

                WarehouseStockService::change(WarehouseStockService::centralWarehouseId(), (int) $item->product_id, (int) $item->quantity);
                $product = Product::query()->whereKey((int) $item->product_id)->lockForUpdate()->first();
                if ($product) {
                    $product->update(['stock' => ((int) $product->stock) + ((int) $item->quantity)]);
                }
            }

            $order->update([
                'status' => PreinvoiceOrder::STATUS_SUBMITTED_WAREHOUSE,
                'stock_released_at' => now(),
                'warehouse_review_note' => null,
                'warehouse_reviewed_by' => null,
                'warehouse_reviewed_at' => null,
            ]);
        }
    });
})->purpose('Release expired preinvoice stock freezes');

Schedule::command('preinvoice:release-expired-freezes')->everyMinute();
