<?php

use App\Http\Controllers\PreinvoiceController;
use App\Models\PreinvoiceOrder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\AriyajanebiSyncService;
use App\Services\DefaultProductDesignService;
use App\Services\InventoryWebhookService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;




Artisan::command('preinvoice:repair-reservations {--order= : Preinvoice order id} {--dry-run : Only report planned changes} {--apply : Apply the repair}', function () {
    $orderId = (int) $this->option('order');
    $apply = (bool) $this->option('apply');
    $dryRun = (bool) $this->option('dry-run') || ! $apply;

    if ($orderId <= 0) {
        $this->error('Use --order=<id>.');
        return 1;
    }

    if ($apply && (bool) $this->option('dry-run')) {
        $this->error('Use either --dry-run or --apply, not both.');
        return 1;
    }

    $activeStatuses = [
        PreinvoiceOrder::STATUS_RESERVED_WAITING_WAREHOUSE,
        PreinvoiceOrder::STATUS_WAREHOUSE_REVIEWING,
        PreinvoiceOrder::STATUS_WAREHOUSE_APPROVED_WAITING_FINANCE,
        PreinvoiceOrder::STATUS_FINANCE_REVIEWING,
        PreinvoiceOrder::STATUS_RETURNED_TO_WAREHOUSE,
    ];

    return DB::transaction(function () use ($orderId, $apply, $dryRun, $activeStatuses) {
        $order = PreinvoiceOrder::query()
            ->with('items')
            ->whereKey($orderId)
            ->lockForUpdate()
            ->firstOrFail();

        $isActive = in_array($order->status, $activeStatuses, true) && $order->stock_released_at === null;
        if (! $isActive) {
            $this->warn('This preinvoice is not an active unreleased order. No reservation was repaired.');
            return 0;
        }

        $required = $order->items
            ->groupBy('variant_id')
            ->map(fn ($rows) => [
                'product_id' => (int) $rows->first()->product_id,
                'variant_id' => (int) $rows->first()->variant_id,
                'quantity' => (int) $rows->sum('quantity'),
            ]);

        $existing = DB::table('preinvoice_draft_reservations')
            ->where('preinvoice_order_id', $order->id)
            ->whereNotNull('converted_at')
            ->select('variant_id', DB::raw('SUM(quantity) AS quantity'))
            ->groupBy('variant_id')
            ->pluck('quantity', 'variant_id')
            ->map(fn ($quantity) => (int) $quantity);

        $this->info(($dryRun ? 'DRY RUN: ' : 'APPLY: ') . "repairing preinvoice #{$order->id}");
        $this->table(['variant_id', 'product_id', 'required', 'converted_reserved', 'delta'], $required->map(fn ($row, $variantId) => [
            $variantId,
            $row['product_id'],
            $row['quantity'],
            (int) ($existing[(int) $variantId] ?? 0),
            $row['quantity'] - (int) ($existing[(int) $variantId] ?? 0),
        ])->values()->all());

        if ($order->stock_frozen_until !== null) {
            $this->line('Will set preinvoice_orders.stock_frozen_until to NULL.');
        }

        $convertedWithExpiry = DB::table('preinvoice_draft_reservations')
            ->where('preinvoice_order_id', $order->id)
            ->whereNotNull('converted_at')
            ->whereNotNull('expires_at')
            ->count();
        $this->line("Converted reservation rows with expires_at to NULL: {$convertedWithExpiry}");

        if (! $apply) {
            $this->warn('No changes applied. Re-run with --apply to repair.');
            return 0;
        }

        app(PreinvoiceController::class)->syncPreinvoiceReservations($order);

        $order->update(['stock_frozen_until' => null]);
        DB::table('preinvoice_draft_reservations')
            ->where('preinvoice_order_id', $order->id)
            ->whereNotNull('converted_at')
            ->update(['expires_at' => null, 'updated_at' => now()]);

        \App\Support\ActivityLogger::log('preinvoice_reservations_repaired', $order->fresh(), 'رزروهای پیش‌فاکتور فعال ترمیم شد.', [
            'command' => 'preinvoice:repair-reservations',
            'order_id' => $order->id,
            'required_by_variant' => $required->values()->all(),
            'previous_converted_by_variant' => $existing->all(),
        ]);

        $this->info('Preinvoice reservations were repaired.');
        return 0;
    });
})->purpose('Dry-run or repair active inventory reservations for one preinvoice');

Artisan::command('products:add-default-electric-colors', function () {
    $service = app(DefaultProductDesignService::class);
    $categoryIds = $service->electricCategoryIds();

    if (empty($categoryIds)) {
        $this->warn('Electric category not found. Expected slug "barghijat" or Persian name "برقیجات".');

        return 0;
    }

    $checkedProducts = 0;
    $createdVariants = 0;
    $alreadyExistingColors = 0;

    Product::query()
        ->whereIn('category_id', $categoryIds)
        ->with(['category.parent', 'variants'])
        ->orderBy('id')
        ->chunkById(100, function ($products) use ($service, &$checkedProducts, &$createdVariants, &$alreadyExistingColors) {
            foreach ($products as $product) {
                DB::transaction(function () use ($service, $product, &$checkedProducts, &$createdVariants, &$alreadyExistingColors) {
                    $result = $service->ensureElectricDefaultColors($product, null, null, null);

                    if (! $result['checked']) {
                        return;
                    }

                    $checkedProducts++;
                    $createdVariants += (int) $result['created'];
                    $alreadyExistingColors += (int) $result['already_existing'];
                });
            }
        });

    $this->info("Checked products: {$checkedProducts}");
    $this->info("Created default color variants: {$createdVariants}");
    $this->info("Already existing default colors: {$alreadyExistingColors}");

    return 0;
})->purpose('Add missing black and white default variants to electric category products');

Artisan::command('inventory:set-central-stock {quantity=500 : Quantity to set for every product variant} {--force : Disabled legacy option}', function (int $quantity) {
    $this->error('This legacy bulk stock overwrite command is disabled because inventory must come from documents or approved stock-count corrections.');
    $this->warn('Use php artisan inventory:audit-corruption first, take a backup, then apply a reviewed repair script/SQL only for confirmed corrupted rows.');

    return 1;
})->purpose('Disabled legacy bulk stock overwrite command');

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

Artisan::command('products:audit-variants {product : Product id/code/sku/short_barcode/barcode}', function (string $product) {
    $service = app(\App\Services\ProductVariantStructureService::class);
    $productModel = Product::query()
        ->where('id', $product)
        ->orWhere('code', $product)
        ->orWhere('sku', $product)
        ->orWhere('short_barcode', $product)
        ->orWhere('barcode', $product)
        ->first();

    if (! $productModel) {
        $this->error("Product not found: {$product}");
        return 1;
    }

    $audit = $service->audit($productModel);
    $this->info('Product: ' . ($productModel->code ?: $productModel->sku ?: $productModel->id) . ' - ' . $productModel->name);
    $this->line('Total variants: ' . $audit['total_variants']);
    $this->line('Active variants: ' . $audit['active_variants']);
    $this->line('Valid variants: ' . $audit['valid_variants']);
    $this->line('Invalid variants: ' . $audit['invalid_variants']);
    $this->line('Invalid variants with stock/reserved: ' . $audit['invalid_variants_with_stock']);
    $this->line('Invalid stock total: ' . $audit['invalid_stock_total']);
    $this->line('Invalid reserved total: ' . $audit['invalid_reserved_total']);

    if ($audit['invalid_with_stock']->isNotEmpty()) {
        $this->warn('Invalid variants with stock/reserved:');
        $audit['invalid_with_stock']->take(50)->each(function (ProductVariant $variant) {
            $this->line(sprintf(
                '#%d model_list_id=%s variety_code=%s variant_code=%s stock=%d reserved=%d active=%s',
                $variant->id,
                $variant->model_list_id ?? 'NULL',
                $variant->variety_code ?? 'NULL',
                $variant->variant_code ?? 'NULL',
                (int) $variant->stock,
                (int) $variant->reserved,
                $variant->is_active ? 'yes' : 'no'
            ));
        });
    }

    return 0;
})->purpose('Audit product variants against the current product structure without changing data');

Artisan::command('products:repair-variants {product : Product id/code/sku/short_barcode/barcode} {--dry-run : Show what would be changed} {--apply : Apply the repair}', function (string $product) {
    $service = app(\App\Services\ProductVariantStructureService::class);
    $productModel = Product::query()
        ->where('id', $product)
        ->orWhere('code', $product)
        ->orWhere('sku', $product)
        ->orWhere('short_barcode', $product)
        ->orWhere('barcode', $product)
        ->first();

    if (! $productModel) {
        $this->error("Product not found: {$product}");
        return 1;
    }

    if (! $this->option('dry-run') && ! $this->option('apply')) {
        $this->error('Use --dry-run to preview or --apply to deactivate invalid variants.');
        return 1;
    }

    $audit = $service->audit($productModel);
    $this->info('Product: ' . ($productModel->code ?: $productModel->sku ?: $productModel->id) . ' - ' . $productModel->name);
    $this->line('Invalid variants to deactivate: ' . $audit['invalid_variants']);
    $this->line('Invalid variants with stock/reserved: ' . $audit['invalid_variants_with_stock']);
    $this->line('No variants will be deleted.');

    if ($this->option('dry-run')) {
        $this->warn('Dry-run only. No data changed.');
        return 0;
    }

    $changed = 0;

    DB::transaction(function () use ($service, $productModel, &$changed) {
        $changed = $service->deactivateInvalidVariants($productModel);
        $service->recalculateProductSummary($productModel->refresh());
    });

    $this->info("Deactivated invalid variants: {$changed}");
    $this->info('Product summary recalculated from valid active variants.');

    return 0;
})->purpose('Deactivate invalid product variants without deleting stock/history');
