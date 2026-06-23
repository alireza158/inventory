<?php

namespace Tests\Feature;

use App\Services\InventoryAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_audit_requires_dry_run(): void
    {
        $this->artisan('inventory:audit')->assertExitCode(1);
    }

    public function test_inventory_audit_outputs_json_without_mutating_data(): void
    {
        $this->seedVariant(stock: 0, purchased: 350, sold: 250);

        $before = DB::table('product_variants')->value('stock');

        $this->artisan('inventory:audit --dry-run --json')
            ->expectsOutputToContain('variant_stock_mismatch')
            ->assertExitCode(0);

        $this->assertSame($before, DB::table('product_variants')->value('stock'));
    }

    public function test_purchase_quantity_350_should_audit_to_expected_stock_350(): void
    {
        $variantId = $this->seedVariant(stock: 350, purchased: 350, sold: 0);

        $row = $this->variantRow($variantId);

        $this->assertSame(350, $row['purchased_qty']);
        $this->assertSame(350, $row['expected_stock']);
        $this->assertSame(0, $row['difference']);
    }

    public function test_sale_quantity_250_after_purchase_350_should_audit_to_expected_stock_100(): void
    {
        $variantId = $this->seedVariant(stock: 100, purchased: 350, sold: 250);

        $row = $this->variantRow($variantId);

        $this->assertSame(100, $row['expected_stock']);
        $this->assertSame(100, $row['product_variant_stock']);
    }

    public function test_editing_purchase_price_without_quantity_change_should_not_change_audited_stock(): void
    {
        $variantId = $this->seedVariant(stock: 350, purchased: 350, sold: 0, buyPrice: 10);
        DB::table('purchase_items')->update(['buy_price' => 99, 'sell_price' => 120]);

        $this->assertSame(350, $this->variantRow($variantId)['expected_stock']);
    }

    public function test_editing_purchase_quantity_should_be_detected_as_delta_based_stock_expectation(): void
    {
        $variantId = $this->seedVariant(stock: 400, purchased: 400, sold: 0);

        $this->assertSame(400, $this->variantRow($variantId)['expected_stock']);
    }

    public function test_preinvoice_must_have_reserved_stock_for_active_items(): void
    {
        $variantId = $this->seedVariant(stock: 10, purchased: 10, sold: 0, reserved: 0);
        $preinvoiceId = $this->seedPreinvoice($variantId, 10, 'reserved_waiting_warehouse');

        $problems = $this->runAudit(['preinvoice' => $preinvoiceId])['sections']['reserved_stock']['problems'];

        $this->assertContains('active_item_has_insufficient_reserved_stock', array_column($problems, 'code'));
    }

    public function test_preinvoice_reservation_diff_sync_mismatch_is_reported(): void
    {
        $variantId = $this->seedVariant(stock: 10, purchased: 10, sold: 0, reserved: 5);
        $preinvoiceId = $this->seedPreinvoice($variantId, 10, 'reserved_waiting_warehouse');
        DB::table('preinvoice_draft_reservations')->insert(['token' => 't', 'user_id' => 1, 'preinvoice_order_id' => $preinvoiceId, 'product_id' => 1, 'variant_id' => $variantId, 'quantity' => 5, 'created_at' => now(), 'updated_at' => now()]);

        $problems = $this->runAudit(['preinvoice' => $preinvoiceId])['sections']['reserved_stock']['problems'];

        $this->assertContains('reservation_row_quantity_mismatch', array_column($problems, 'code'));
    }

    public function test_warehouse_pending_item_with_zero_reservation_is_reported(): void
    {
        $variantId = $this->seedVariant(stock: 10, purchased: 10, sold: 0, reserved: 0);
        $preinvoiceId = $this->seedPreinvoice($variantId, 10, 'warehouse_reviewing');

        $problems = $this->runAudit(['preinvoice' => $preinvoiceId])['sections']['reserved_stock']['problems'];

        $this->assertContains('active_item_has_insufficient_reserved_stock', array_column($problems, 'code'));
    }

    public function test_stale_warehouse_snapshot_is_reported(): void
    {
        $variantId = $this->seedVariant(stock: 10, purchased: 10, sold: 0, reserved: 10);
        $preinvoiceId = $this->seedPreinvoice($variantId, 10, 'warehouse_reviewing', now());
        DB::table('warehouse_review_snapshots')->insert(['preinvoice_order_id' => $preinvoiceId, 'snapshot' => '{}', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);

        $problems = $this->runAudit(['preinvoice' => $preinvoiceId])['sections']['warehouse_approval']['problems'];

        $this->assertContains('warehouse_approval_stale_snapshot', array_column($problems, 'code'));
    }

    public function test_valid_finance_document_does_not_report_false_total_error(): void
    {
        $variantId = $this->seedVariant(stock: 10, purchased: 10, sold: 0, reserved: 10);
        $preinvoiceId = $this->seedPreinvoice($variantId, 2, 'warehouse_approved_waiting_finance', null, 200);

        $problems = $this->runAudit(['preinvoice' => $preinvoiceId])['sections']['finance_approval']['problems'];

        $this->assertNotContains('finance_approval_total_mismatch', array_column($problems, 'code'));
    }

    public function test_item_ordering_guard_reports_missing_sort_order_until_migration_is_added(): void
    {
        $problems = $this->runAudit()['sections']['item_ordering']['problems'];

        $this->assertContains('missing_sort_order', array_column($problems, 'code'));
    }

    public function test_shared_preinvoice_invoice_number_is_allowed_and_sequence_continues_from_current_max(): void
    {
        $variantId = $this->seedVariant(stock: 10, purchased: 10, sold: 0);
        $preinvoiceId = $this->seedPreinvoice($variantId, 1, 'converted_to_invoice', null, 100, '000150');
        DB::table('invoices')->insert(['id' => 1, 'uuid' => '000150', 'preinvoice_order_id' => $preinvoiceId, 'customer_name' => 'c', 'customer_mobile' => 'm', 'customer_address' => 'a', 'subtotal' => 100, 'total' => 100, 'status' => 'shipped', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('document_sequences')->updateOrInsert(['type' => 'invoice'], ['last_number' => 150, 'created_at' => now(), 'updated_at' => now()]);

        $rows = $this->runAudit()['sections']['document_numbering']['rows'];

        $this->assertContains(150, array_column($rows, 'last_number'));
    }

    private function runAudit(array $filters = []): array
    {
        return app(InventoryAuditService::class)->run($filters);
    }

    private function variantRow(int $variantId): array
    {
        return $this->runAudit(['variant' => $variantId])['sections']['variant_stock']['rows'][0];
    }

    private function seedVariant(int $stock, int $purchased, int $sold, int $reserved = 0, int $buyPrice = 10): int
    {
        DB::table('users')->insertOrIgnore(['id' => 1, 'name' => 'Audit', 'email' => 'audit@example.com', 'password' => 'x', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('categories')->insertOrIgnore(['id' => 1, 'name' => 'Cat', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('products')->insertOrIgnore(['id' => 1, 'category_id' => 1, 'name' => 'P', 'code' => 'P1', 'stock' => $stock, 'reserved' => $reserved, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('product_variants')->insert(['id' => 1, 'product_id' => 1, 'variant_name' => 'V', 'stock' => $stock, 'reserved' => $reserved, 'buy_price' => $buyPrice, 'sell_price' => 20, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('suppliers')->insertOrIgnore(['id' => 1, 'name' => 'S', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('purchases')->insert(['id' => 1, 'supplier_id' => 1, 'user_id' => 1, 'total_amount' => $purchased * $buyPrice, 'purchased_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('purchase_items')->insert(['purchase_id' => 1, 'product_id' => 1, 'product_variant_id' => 1, 'product_name' => 'P', 'product_code' => 'P1', 'quantity' => $purchased, 'buy_price' => $buyPrice, 'sell_price' => 20, 'line_total' => $purchased * $buyPrice, 'created_at' => now(), 'updated_at' => now()]);
        if ($sold > 0) {
            DB::table('invoices')->insert(['id' => 99, 'uuid' => '00999', 'customer_name' => 'c', 'customer_mobile' => 'm', 'customer_address' => 'a', 'subtotal' => $sold * 20, 'total' => $sold * 20, 'status' => 'shipped', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('invoice_items')->insert(['invoice_id' => 99, 'product_id' => 1, 'variant_id' => 1, 'quantity' => $sold, 'price' => 20, 'line_total' => $sold * 20, 'created_at' => now(), 'updated_at' => now()]);
        }
        return 1;
    }

    private function seedPreinvoice(int $variantId, int $quantity, string $status, $itemsUpdatedAt = null, int $total = 1000, string $uuid = '000151'): int
    {
        DB::table('preinvoice_orders')->insert(['id' => 1, 'uuid' => $uuid, 'created_by' => 1, 'status' => $status, 'customer_name' => 'c', 'customer_mobile' => 'm', 'customer_address' => 'a', 'total_price' => $total, 'items_updated_at' => $itemsUpdatedAt, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('preinvoice_order_items')->insert(['preinvoice_order_id' => 1, 'product_id' => 1, 'variant_id' => $variantId, 'quantity' => $quantity, 'price' => intdiv($total, max(1, $quantity)), 'created_at' => now(), 'updated_at' => now()]);
        return 1;
    }
}
