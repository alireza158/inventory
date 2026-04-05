<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Services\SalesHavalehStatusService;
use PHPUnit\Framework\TestCase;

class SalesHavalehStatusServiceTest extends TestCase
{
    public function test_all_statuses_are_declared(): void
    {
        $service = new SalesHavalehStatusService();

        $this->assertContains(SalesHavalehStatusService::PENDING_WAREHOUSE_APPROVAL, $service->all());
        $this->assertContains(SalesHavalehStatusService::SHIPPED, $service->all());
    }

    public function test_shipped_invoice_is_not_editable(): void
    {
        $service = new SalesHavalehStatusService();
        $invoice = new Invoice(['status' => SalesHavalehStatusService::SHIPPED]);

        $this->assertFalse($service->isEditable($invoice, null));
    }

    public function test_regular_user_can_only_move_to_next_step(): void
    {
        $service = new SalesHavalehStatusService();
        $invoice = new Invoice(['status' => SalesHavalehStatusService::COLLECTING]);

        $service->assertValidTransition($invoice, SalesHavalehStatusService::CHECKING_DISCREPANCY, null);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->assertValidTransition($invoice, SalesHavalehStatusService::PACKING, null);
    }
}
