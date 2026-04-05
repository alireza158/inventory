<?php

namespace App\Services;

use App\Models\CustomerLedger;
use App\Models\Invoice;

class CustomerLedgerService
{
    public function syncInvoiceDebit(Invoice $invoice): void
    {
        if (empty($invoice->customer_id)) {
            return;
        }

        CustomerLedger::query()->updateOrCreate(
            [
                'customer_id' => (int) $invoice->customer_id,
                'reference_type' => Invoice::class,
                'reference_id' => (int) $invoice->id,
                'type' => 'debit',
            ],
            [
                'amount' => (int) $invoice->total,
                'note' => 'ثبت/بروزرسانی بدهکاری بابت حواله فروش ' . $invoice->uuid,
            ]
        );
    }
}
