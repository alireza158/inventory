<?php

namespace App\Services;

use App\Models\PreinvoiceOrder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryAuditService
{
    public function run(array $filters = []): array
    {
        $report = [
            'dry_run' => true,
            'filters' => array_filter($filters, fn ($v) => $v !== null && $v !== ''),
            'generated_at' => now()->toISOString(),
            'summary' => ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'total' => 0],
            'sections' => [],
            'data_changed' => false,
        ];

        foreach ([
            'variant_stock' => $this->auditVariantStock($filters),
            'warehouse_stock' => $this->auditWarehouseStock($filters),
            'reserved_stock' => $this->auditReservedStock($filters),
            'warehouse_approval' => $this->auditWarehouseApproval($filters),
            'finance_approval' => $this->auditFinanceApproval($filters),
            'item_ordering' => $this->auditItemOrdering($filters),
            'document_numbering' => $this->auditDocumentNumbering($filters),
            'warehouse_documents' => $this->auditWarehouseDocuments($filters),
        ] as $name => $section) {
            $report['sections'][$name] = $section;
            foreach ($section['problems'] as $problem) {
                $severity = $problem['severity'] ?? 'low';
                $report['summary'][$severity] = ($report['summary'][$severity] ?? 0) + 1;
                $report['summary']['total']++;
            }
        }

        return $report;
    }

    private function auditVariantStock(array $filters): array
    {
        if (! Schema::hasTable('product_variants')) return $this->missing('product_variants');
        $rows = [];
        $problems = [];
        $query = DB::table('product_variants')->select('id', 'product_id', 'variant_name', 'stock');
        $this->variantFilter($query, $filters, 'product_variants');
        foreach ($query->get() as $variant) {
            $purchased = $this->sum('purchase_items', 'quantity', 'product_variant_id', $variant->id)
                ?: $this->sum('purchase_items', 'quantity', 'variant_id', $variant->id);
            $sold = $this->sum('invoice_items', 'quantity', 'variant_id', $variant->id);
            $movementIn = $this->sumMovement($variant->id, 'in');
            $movementOut = $this->sumMovement($variant->id, 'out');
            $transferOut = $this->sum('warehouse_transfer_items', 'quantity', 'product_variant_id', $variant->id);
            $warehouseTotal = $this->sum('warehouse_stocks', 'quantity', 'product_variant_id', $variant->id);
            $expected = max(0, (int) $purchased + (int) $movementIn - (int) $sold - (int) $movementOut);
            $difference = (int) $variant->stock - $expected;
            $row = [
                'variant_id' => (int) $variant->id,
                'product_id' => (int) $variant->product_id,
                'variant_name' => $variant->variant_name,
                'purchased_qty' => (int) $purchased,
                'sold_qty' => (int) $sold,
                'warehouse_transfer_out_qty' => (int) $transferOut,
                'movement_in_qty' => (int) $movementIn,
                'movement_out_qty' => (int) $movementOut,
                'expected_stock' => $expected,
                'product_variant_stock' => (int) $variant->stock,
                'warehouse_stock_total' => (int) $warehouseTotal,
                'difference' => $difference,
            ];
            $rows[] = $row;
            if ($difference !== 0) $problems[] = $this->problem('critical', 'variant_stock_mismatch', 'Variant stock differs from audit expectation.', $row);
        }
        return compact('rows', 'problems') + ['description' => 'Purchase/sale/movement based variant stock dry-run.'];
    }

    private function auditWarehouseStock(array $filters): array
    {
        if (! Schema::hasTable('warehouse_stocks')) return $this->missing('warehouse_stocks');
        $rows = []; $problems = [];
        $query = DB::table('product_variants')->select('id', 'product_id', 'stock');
        $this->variantFilter($query, $filters, 'product_variants');
        foreach ($query->get() as $variant) {
            $total = $this->sum('warehouse_stocks', 'quantity', 'product_variant_id', $variant->id);
            $row = ['variant_id' => (int)$variant->id, 'product_id' => (int)$variant->product_id, 'product_variant_stock' => (int)$variant->stock, 'warehouse_stock_total' => (int)$total, 'difference' => (int)$total - (int)$variant->stock];
            $rows[] = $row;
            if ($row['difference'] !== 0) $problems[] = $this->problem('critical', 'warehouse_stock_mismatch', 'SUM(warehouse_stocks.quantity) differs from product_variants.stock.', $row);
        }
        return compact('rows', 'problems') + ['description' => 'Checks warehouse stock totals against variant stock.'];
    }

    private function auditReservedStock(array $filters): array
    {
        $rows = []; $problems = [];
        if (! Schema::hasTable('preinvoice_order_items')) return $this->missing('preinvoice_order_items');
        $active = [PreinvoiceOrder::STATUS_RESERVED_WAITING_WAREHOUSE, PreinvoiceOrder::STATUS_WAREHOUSE_REVIEWING, PreinvoiceOrder::STATUS_WAREHOUSE_APPROVED_WAITING_FINANCE, PreinvoiceOrder::STATUS_FINANCE_REVIEWING];
        $items = DB::table('preinvoice_order_items as i')->join('preinvoice_orders as o','o.id','=','i.preinvoice_order_id')->whereIn('o.status', $active)->select('i.*','o.status');
        $this->docFilter($items, $filters, 'o', 'preinvoice'); $this->variantFilter($items, $filters, 'i', 'variant_id');
        foreach ($items->get() as $item) {
            $reserved = $this->sum('preinvoice_draft_reservations', 'quantity', 'variant_id', $item->variant_id, ['preinvoice_order_id' => $item->preinvoice_order_id]);
            $variantReserved = Schema::hasTable('product_variants') ? (int) (DB::table('product_variants')->where('id',$item->variant_id)->value('reserved') ?? 0) : 0;
            $problem = null;
            if ($reserved !== 0 && $reserved !== (int)$item->quantity) $problem = 'reservation_row_quantity_mismatch';
            if ($reserved === 0 && $variantReserved < (int)$item->quantity) $problem = 'active_item_has_insufficient_reserved_stock';
            $row = ['preinvoice_id'=>(int)$item->preinvoice_order_id,'item_id'=>(int)$item->id,'product_variant_id'=>(int)$item->variant_id,'requested_qty'=>(int)$item->quantity,'reserved_qty'=>(int)max($reserved, min($variantReserved,(int)$item->quantity)),'warehouse_id'=>null,'status'=>$item->status,'problem'=>$problem];
            $rows[] = $row;
            if ($problem) $problems[] = $this->problem('critical', $problem, 'Preinvoice active item reservation is not synchronized.', $row);
        }
        if (Schema::hasTable('preinvoice_draft_reservations')) {
            $orphans = DB::table('preinvoice_draft_reservations as r')->leftJoin('preinvoice_orders as o','o.id','=','r.preinvoice_order_id')->whereNotNull('r.preinvoice_order_id')->where(fn($q)=>$q->whereNull('o.id')->orWhereNotIn('o.status',$active))->select('r.*','o.status')->get();
            foreach ($orphans as $r) $problems[] = $this->problem('critical', 'orphan_active_reservation', 'Reservation remains for missing/inactive document.', (array)$r);
        }
        return compact('rows', 'problems') + ['description' => 'Checks active preinvoice item reservations and orphan reservation rows.'];
    }

    private function auditWarehouseApproval(array $filters): array { return $this->auditQueue($filters, [PreinvoiceOrder::STATUS_RESERVED_WAITING_WAREHOUSE, PreinvoiceOrder::STATUS_WAREHOUSE_REVIEWING], 'high', 'warehouse_approval'); }
    private function auditFinanceApproval(array $filters): array { return $this->auditQueue($filters, [PreinvoiceOrder::STATUS_WAREHOUSE_APPROVED_WAITING_FINANCE, PreinvoiceOrder::STATUS_FINANCE_REVIEWING], 'high', 'finance_approval'); }

    private function auditQueue(array $filters, array $statuses, string $severity, string $name): array
    {
        $rows=[]; $problems=[];
        if (! Schema::hasTable('preinvoice_orders')) return $this->missing('preinvoice_orders');
        $orders = DB::table('preinvoice_orders')->whereIn('status',$statuses); $this->docFilter($orders,$filters,'preinvoice_orders','preinvoice');
        foreach ($orders->get() as $order) {
            $count = DB::table('preinvoice_order_items')->where('preinvoice_order_id',$order->id)->count();
            $sum = (int) DB::table('preinvoice_order_items')->where('preinvoice_order_id',$order->id)->selectRaw('COALESCE(SUM(quantity * price),0) as t')->value('t');
            $stale = Schema::hasTable('warehouse_review_snapshots') && $order->items_updated_at ? DB::table('warehouse_review_snapshots')->where('preinvoice_order_id',$order->id)->where('created_at','<',$order->items_updated_at)->exists() : false;
            $row = ['preinvoice_id'=>(int)$order->id,'status'=>$order->status,'items_count'=>$count,'items_sum'=>$sum,'document_total'=>(int)($order->total_price ?? 0),'snapshot_stale'=>$stale];
            $rows[]=$row;
            if ($count < 1) $problems[]=$this->problem($severity,$name.'_no_items','Queued document has no items.',$row);
            if ($stale) $problems[]=$this->problem($severity,$name.'_stale_snapshot','Queued document has stale warehouse snapshot.',$row);
            if (abs($sum - (int)($order->total_price ?? 0)) > 1) $problems[]=$this->problem($severity,$name.'_total_mismatch','Document total differs from item sum.',$row);
        }
        return compact('rows','problems') + ['description' => "Checks {$name} queue health."];
    }

    private function auditItemOrdering(array $filters): array
    {
        $problems=[]; $rows=[];
        foreach (['preinvoice_order_items','invoice_items'] as $table) {
            $has = Schema::hasTable($table) && Schema::hasColumn($table,'sort_order');
            $row=['table'=>$table,'has_sort_order'=>$has,'recommendation'=>$has ? 'Use max(sort_order)+1 for appended items.' : 'Add nullable/integer sort_order migration, backfill by id, then enforce append ordering.'];
            $rows[]=$row;
            if (! $has) $problems[]=$this->problem('medium','missing_sort_order','Item table has no sort_order column; print order may be unstable.',$row);
        }
        return compact('rows','problems') + ['description'=>'Checks deterministic item ordering support.'];
    }

    private function auditDocumentNumbering(array $filters): array
    {
        $rows=[]; $problems=[]; $codes=[]; $codeRows=[];
        foreach (['preinvoice_orders','invoices'] as $table) if (Schema::hasTable($table) && Schema::hasColumn($table,'uuid')) {
            $select = ['id', 'uuid'];
            if (Schema::hasColumn($table, 'preinvoice_order_id')) $select[] = 'preinvoice_order_id';
            foreach (DB::table($table)->select($select)->when($table==='preinvoice_orders' && ($filters['preinvoice']??null),fn($q)=>$q->where('id',$filters['preinvoice']))->when($table==='invoices' && ($filters['invoice']??null),fn($q)=>$q->where('id',$filters['invoice']))->get() as $r) {
                $entry=['table'=>$table,'id'=>(int)$r->id,'code'=>$r->uuid,'preinvoice_order_id'=>$r->preinvoice_order_id ?? null]; $rows[]=$entry; $codes[$r->uuid][]=$table.':'.$r->id; $codeRows[$r->uuid][]=$entry;
            }
        }
        foreach ($codes as $code=>$owners) {
            if (! $code || count($owners) < 2) continue;
            $entries = $codeRows[$code] ?? [];
            $allowedSharedPair = count($entries) === 2
                && collect($entries)->pluck('table')->sort()->values()->all() === ['invoices', 'preinvoice_orders']
                && (int) (collect($entries)->firstWhere('table', 'invoices')['preinvoice_order_id'] ?? 0) === (int) (collect($entries)->firstWhere('table', 'preinvoice_orders')['id'] ?? -1);
            if (! $allowedSharedPair) $problems[]=$this->problem('medium','duplicate_document_number','Document number is duplicated outside allowed preinvoice-to-invoice reuse.',['code'=>$code,'owners'=>$owners]);
        }
        $seq = Schema::hasTable('document_sequences') ? DB::table('document_sequences')->where('type','invoice')->first() : null;
        $rows[]=['table'=>'document_sequences','type'=>'invoice','last_number'=>$seq?->last_number,'recommendation'=>'Shared preinvoice/invoice sequence should continue from current max, not id.'];
        return compact('rows','problems') + ['description'=>'Checks shared preinvoice/invoice numbering.'];
    }

    private function auditWarehouseDocuments(array $filters): array
    {
        $rows=[]; $problems=[];
        if (! Schema::hasTable('warehouse_transfers')) return $this->missing('warehouse_transfers');
        foreach (DB::table('warehouse_transfers')->select('id','related_invoice_id','reference')->when($filters['invoice']??null,fn($q)=>$q->where('related_invoice_id',$filters['invoice']))->get() as $t) {
            $items = DB::table('warehouse_transfer_items')->where('warehouse_transfer_id',$t->id)->count();
            $row=['warehouse_transfer_id'=>(int)$t->id,'related_invoice_id'=>$t->related_invoice_id,'reference'=>$t->reference,'items_count'=>$items];
            $rows[]=$row; if ($t->related_invoice_id && $items<1) $problems[]=$this->problem('high','empty_invoice_havaleh','Invoice-linked warehouse document has no items.',$row);
        }
        return compact('rows','problems') + ['description'=>'Checks havaleh/invoice transfer linkage.'];
    }

    private function sum(string $table, string $sumColumn, string $whereColumn, mixed $value, array $extra = []): int
    { if (! Schema::hasTable($table) || ! Schema::hasColumn($table,$sumColumn) || ! Schema::hasColumn($table,$whereColumn)) return 0; $q=DB::table($table)->where($whereColumn,$value); foreach($extra as $c=>$v) if(Schema::hasColumn($table,$c)) $q->where($c,$v); return (int)$q->sum($sumColumn); }
    private function sumMovement(int $variantId, string $type): int { return Schema::hasColumn('stock_movements','product_variant_id') ? $this->sum('stock_movements','quantity','product_variant_id',$variantId,['type'=>$type]) : 0; }
    private function variantFilter($query, array $filters, string $table, string $column='id'): void { if ($filters['variant'] ?? null) $query->where("{$table}.{$column}",$filters['variant']); if (($filters['product'] ?? null) && Schema::hasColumn(trim($table,'`'),'product_id')) $query->where("{$table}.product_id", $filters['product']); }
    private function docFilter($query, array $filters, string $table, string $type): void { if ($filters[$type] ?? null) $query->where("{$table}.id", $filters[$type]); }
    private function problem(string $severity, string $code, string $message, array $context): array { return compact('severity','code','message','context'); }
    private function missing(string $table): array { return ['description'=>"Table {$table} is missing; skipped.",'rows'=>[],'problems'=>[$this->problem('low','missing_table',"Table {$table} is missing.",['table'=>$table])]]; }
}
