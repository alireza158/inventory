<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InventoryAuditCorruptionCommand extends Command
{
    protected $signature = 'inventory:audit-corruption
        {--base=30023 : Suspected additive corruption amount}
        {--from=2026-06-28 07:35:40 : Start of suspected corruption window}
        {--to=2026-06-28 07:35:58 : End of suspected corruption window}
        {--tolerance=2 : Allowed difference around the base amount}
        {--limit=200 : Maximum detail rows to display}';

    protected $description = 'Diagnose suspected additive inventory corruption without changing data.';

    public function handle(): int
    {
        $base = (int) $this->option('base');
        $from = (string) $this->option('from');
        $to = (string) $this->option('to');
        $tolerance = max(0, (int) $this->option('tolerance'));
        $limit = max(1, (int) $this->option('limit'));

        $purchaseSums = DB::table('purchase_items')
            ->select('product_variant_id', DB::raw('SUM(quantity) AS purchased_quantity'))
            ->whereNotNull('product_variant_id')
            ->groupBy('product_variant_id');

        $lastValidPurchaseIds = DB::table('purchase_items')
            ->select('product_variant_id', DB::raw('MAX(id) AS max_id'))
            ->whereNotNull('product_variant_id')
            ->where('buy_price', '>', 0)
            ->where('sell_price', '>', 0)
            ->groupBy('product_variant_id');

        $baseQuery = DB::table('warehouse_stocks AS ws')
            ->join('product_variants AS pv', 'pv.id', '=', 'ws.product_variant_id')
            ->leftJoinSub($purchaseSums, 'pi_sum', function ($join) {
                $join->on('pi_sum.product_variant_id', '=', 'ws.product_variant_id');
            })
            ->leftJoinSub($lastValidPurchaseIds, 'last_valid', function ($join) {
                $join->on('last_valid.product_variant_id', '=', 'ws.product_variant_id');
            })
            ->leftJoin('purchase_items AS last_pi', 'last_pi.id', '=', 'last_valid.max_id')
            ->whereNotNull('ws.product_variant_id')
            ->where('ws.quantity', '>=', $base)
            ->whereBetween('ws.updated_at', [$from, $to]);

        $totalHighInWindow = (clone $baseQuery)->count();

        $exactPattern = (clone $baseQuery)
            ->whereRaw('(ws.quantity - COALESCE(pi_sum.purchased_quantity, 0)) = ?', [$base])
            ->count();

        $nearPattern = (clone $baseQuery)
            ->whereRaw('ABS((ws.quantity - COALESCE(pi_sum.purchased_quantity, 0)) - ?) <= ?', [$base, $tolerance])
            ->count();

        $aggregateNullRows = DB::table('warehouse_stocks AS aggregate_ws')
            ->whereNull('aggregate_ws.product_variant_id')
            ->whereBetween('aggregate_ws.updated_at', [$from, $to])
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('product_variants AS pv')
                    ->whereColumn('pv.product_id', 'aggregate_ws.product_id');
            })
            ->count();

        $zeroPriceVariants = DB::table('product_variants')
            ->where(function ($query) {
                $query->whereNull('buy_price')
                    ->orWhere('buy_price', '<=', 0)
                    ->orWhereNull('sell_price')
                    ->orWhere('sell_price', '<=', 0);
            })
            ->count();

        $this->info('Inventory corruption audit only; no data was changed. Take a backup before any repair SQL.');
        $this->table(['metric', 'value'], [
            ['base', $base],
            ['window_from', $from],
            ['window_to', $to],
            ['variant warehouse rows >= base in window', $totalHighInWindow],
            ['exact quantity = purchase_sum + base', $exactPattern],
            ['near quantity = purchase_sum + base ± tolerance', $nearPattern],
            ['aggregate NULL-variant rows for variant products in window', $aggregateNullRows],
            ['variants with buy/sell price NULL or <= 0', $zeroPriceVariants],
        ]);

        $rows = (clone $baseQuery)
            ->whereRaw('ABS((ws.quantity - COALESCE(pi_sum.purchased_quantity, 0)) - ?) <= ?', [$base, $tolerance])
            ->orderByDesc('ws.updated_at')
            ->orderBy('ws.id')
            ->limit($limit)
            ->get([
                'ws.id AS warehouse_stock_id',
                'ws.warehouse_id',
                'ws.product_id',
                'ws.product_variant_id AS variant_id',
                'ws.quantity AS warehouse_quantity',
                DB::raw('COALESCE(pi_sum.purchased_quantity, 0) AS purchase_items_sum'),
                DB::raw('(ws.quantity - COALESCE(pi_sum.purchased_quantity, 0)) AS difference'),
                'pv.stock AS product_variant_stock',
                'pv.buy_price',
                'pv.sell_price',
                'last_pi.id AS last_valid_purchase_item_id',
                'last_pi.buy_price AS expected_buy_price',
                'last_pi.sell_price AS expected_sell_price',
                'ws.updated_at',
            ]);

        if ($rows->isNotEmpty()) {
            $this->table(array_keys((array) $rows->first()), $rows->map(fn ($row) => (array) $row)->all());
        }

        $this->warn('Suggested repair must be reviewed after backup: subtract the base only from confirmed variant rows in this time window, then rebuild NULL aggregate rows from variant sums. Do not run blindly.');

        return self::SUCCESS;
    }
}
