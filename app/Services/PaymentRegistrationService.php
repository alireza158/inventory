<?php

namespace App\Services;

use App\Models\Cheque;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\InvoicePayment;

class PaymentRegistrationService
{
    public function registerForInvoice(
        Invoice $invoice,
        array $payload,
        ?int $customerId = null,
        ?int $createdBy = null,
        ?string $receiptImagePath = null,
        ?string $chequeImagePath = null
    ): InvoicePayment {
        $method = (string) ($payload['method'] ?? 'cash');
        $amount = (int) ($payload['amount'] ?? 0);
        $paidAt = $payload['paid_at'] ?? now()->toDateString();

        $payment = $invoice->payments()->create([
            'customer_id' => $customerId ?: ($invoice->customer_id ?: null),
            'created_by' => $createdBy,
            'method' => $method,
            'amount' => $amount,
            'paid_at' => $paidAt,
            'bank_name' => $method === 'cash' ? ($payload['bank_name'] ?? null) : null,
            'receipt_image' => $receiptImagePath,
            'note' => $payload['note'] ?? null,
        ]);

        if ($method === 'cheque') {
            Cheque::create([
                'invoice_payment_id' => $payment->id,
                'bank_name' => $payload['cheque_bank_name'] ?? null,
                'branch_name' => $payload['cheque_branch_name'] ?? null,
                'cheque_number' => $payload['cheque_number'] ?? null,
                'amount' => (int) ($payload['cheque_amount'] ?? $amount),
                'due_date' => $payload['cheque_due_date'] ?? null,
                'received_at' => $payload['cheque_received_at'] ?? null,
                'customer_name' => $payload['cheque_customer_name'] ?? null,
                'customer_code' => $payload['cheque_customer_code'] ?? null,
                'account_number' => $payload['cheque_account_number'] ?? null,
                'account_holder' => $payload['cheque_account_holder'] ?? null,
                'image' => $chequeImagePath,
                'status' => $payload['cheque_status'] ?? 'pending',
            ]);
        }

        $ledgerCustomerId = (int) ($payment->customer_id ?: 0);
        if ($ledgerCustomerId > 0) {
            CustomerLedger::create([
                'customer_id' => $ledgerCustomerId,
                'type' => 'credit',
                'amount' => (int) $payment->amount,
                'reference_type' => InvoicePayment::class,
                'reference_id' => $payment->id,
                'note' => 'ثبت پرداخت برای فاکتور ' . $invoice->uuid . ' (' . $this->methodLabel($method) . ')',
            ]);
        }

        return $payment;
    }

    public function methodLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'نقدی',
            'cheque' => 'چکی',
            default => $method,
        };
    }
}
