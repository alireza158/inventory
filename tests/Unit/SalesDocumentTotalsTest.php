<?php

namespace Tests\Unit;

use App\Support\SalesDocumentTotals;
use PHPUnit\Framework\TestCase;

class SalesDocumentTotalsTest extends TestCase
{
    public function test_it_sums_line_level_discounts_as_row_amounts(): void
    {
        $items = collect(range(1, 5))->map(fn () => (object) [
            'quantity' => 3,
            'price' => 10_000_000,
            'line_discount_amount' => 1_000_000,
        ]);

        $totals = SalesDocumentTotals::calculate($items);

        $this->assertSame(150_000_000, $totals['subtotal_before_discount']);
        $this->assertSame(5_000_000, $totals['items_discount']);
        $this->assertSame(5_000_000, $totals['total_discount']);
        $this->assertSame(145_000_000, $totals['grand_total']);
    }

    public function test_it_adds_separate_invoice_discount_once(): void
    {
        $items = [
            (object) ['quantity' => 2, 'price' => 10_000, 'line_discount_amount' => 1_000],
            (object) ['quantity' => 1, 'price' => 20_000, 'line_discount_amount' => 2_000],
        ];

        $totals = SalesDocumentTotals::calculate($items, 3_000, 5_000);

        $this->assertSame(40_000, $totals['subtotal_before_discount']);
        $this->assertSame(3_000, $totals['items_discount']);
        $this->assertSame(3_000, $totals['invoice_discount']);
        $this->assertSame(6_000, $totals['total_discount']);
        $this->assertSame(39_000, $totals['grand_total']);
    }
}
