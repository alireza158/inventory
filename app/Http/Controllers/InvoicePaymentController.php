<?php

namespace App\Http\Controllers;

use App\Models\CustomerLedger;
use Illuminate\Http\Request;

class InvoicePaymentController extends Controller
{
    public function store(string $uuid, Request $request)
    {
        abort_unless($this->canHandleFinanceActions(), 403);

        $invoice = \App\Models\Invoice::where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'method' => 'required|in:cash,card,cheque',
            'amount' => 'required|integer|min:1',
            'paid_at' => 'nullable|date',
            'note' => 'nullable|string|max:2000',
            'receipt_image' => 'nullable|image|max:4096',
        ]);

        $path = null;
        if ($request->hasFile('receipt_image')) {
            $path = $request->file('receipt_image')->store('invoices/receipts', 'public');
        }

        $paidAt = $data['paid_at'] ?? now()->toDateString();

        $payment = $invoice->payments()->create([
            'method' => $data['method'],
            'amount' => (int) $data['amount'],
            'paid_at' => $paidAt,
            'note' => $data['note'] ?? null,
            'receipt_image' => $path,
        ]);

        if (!empty($invoice->customer_id)) {
            CustomerLedger::create([
                'customer_id' => (int) $invoice->customer_id,
                'type' => 'credit',
                'amount' => (int) $payment->amount,
                'reference_type' => get_class($payment),
                'reference_id' => $payment->id,
                'note' => 'ثبت پرداخت برای فاکتور ' . $invoice->uuid,
            ]);
        }

        return back()->with('success', '✅ پرداخت ثبت شد.');
    }

    private function canHandleFinanceActions(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasAnyRole(['admin', 'finance']) || $user->can('finance.approve'));
    }
}
