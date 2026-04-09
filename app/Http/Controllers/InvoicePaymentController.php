<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\CustomerLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoicePaymentController extends Controller
{
    public function store(string $uuid, Request $request)
    {
        abort_unless($this->canHandleFinanceActions(), 403);

        $invoice = Invoice::query()->where('uuid', $uuid)->firstOrFail();

        $payment = $this->createPaymentRecord($request, $invoice, $invoice->customer_id ? (int) $invoice->customer_id : null);

        return back()->with('success', "✅ پرداخت {$this->methodLabel($payment->method)} با موفقیت ثبت شد.");
    }

    public function storeForCustomer(Customer $customer, Request $request)
    {
        abort_unless($this->canHandleFinanceActions(), 403);

        $data = $request->validate([
            'invoice_id' => [
                'required',
                'integer',
                Rule::exists('invoices', 'id')->where(fn ($q) => $q->where('customer_id', $customer->id)),
            ],
            'method' => 'required|in:cash,card,cheque',
            'amount' => 'required|integer|min:1',
            'paid_at' => 'nullable|date',
            'payment_identifier' => 'nullable|string|max:190',
            'note' => 'nullable|string|max:2000',
            'receipt_image' => 'nullable|image|max:4096',
            'cheque_number' => 'required_if:method,cheque|nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'cheque_status' => 'nullable|in:pending,cleared,bounced',
            'cheque_image' => 'nullable|image|max:4096',
        ]);

        $invoice = Invoice::query()->findOrFail((int) $data['invoice_id']);

        $payment = $this->persistPayment($invoice, $data, $request, $customer->id);

        return back()->with('success', "✅ پرداخت {$this->methodLabel($payment->method)} برای مشتری ثبت شد.");
    }

    private function createPaymentRecord(Request $request, Invoice $invoice, ?int $fallbackCustomerId = null): InvoicePayment
    {
        $data = $request->validate([
            'method' => 'required|in:cash,card,cheque',
            'amount' => 'required|integer|min:1',
            'paid_at' => 'nullable|date',
            'payment_identifier' => 'nullable|string|max:190',
            'note' => 'nullable|string|max:2000',
            'receipt_image' => 'nullable|image|max:4096',
            'cheque_number' => 'required_if:method,cheque|nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'cheque_status' => 'nullable|in:pending,cleared,bounced',
            'cheque_image' => 'nullable|image|max:4096',
        ]);

        return $this->persistPayment($invoice, $data, $request, $fallbackCustomerId);
    }

    private function persistPayment(Invoice $invoice, array $data, Request $request, ?int $fallbackCustomerId = null): InvoicePayment
    {
        $path = null;
        if ($request->hasFile('receipt_image')) {
            $path = $request->file('receipt_image')->store('invoices/receipts', 'public');
        }

        $chequeImagePath = null;
        if ($request->hasFile('cheque_image')) {
            $chequeImagePath = $request->file('cheque_image')->store('invoices/cheques', 'public');
        }

        $paidAt = $data['paid_at'] ?? now()->toDateString();
        $customerId = $invoice->customer_id ? (int) $invoice->customer_id : $fallbackCustomerId;

        return DB::transaction(function () use ($invoice, $data, $path, $paidAt, $customerId, $chequeImagePath) {
            $payment = $invoice->payments()->create([
                'customer_id' => $customerId ?: null,
                'created_by' => auth()->id(),
                'method' => $data['method'],
                'amount' => (int) $data['amount'],
                'paid_at' => $paidAt,
                'payment_identifier' => $data['payment_identifier'] ?? null,
                'note' => $data['note'] ?? null,
                'receipt_image' => $path,
            ]);

            if ($payment->method === 'cheque') {
                $payment->cheque()->updateOrCreate(
                    [],
                    [
                        'bank_name' => $data['bank_name'] ?? null,
                        'cheque_number' => $data['cheque_number'] ?? null,
                        'due_date' => $data['due_date'] ?? null,
                        'image' => $chequeImagePath,
                        'status' => $data['cheque_status'] ?? 'pending',
                    ]
                );
            }

            if (!empty($customerId)) {
                CustomerLedger::create([
                    'customer_id' => (int) $customerId,
                    'type' => 'credit',
                    'amount' => (int) $payment->amount,
                    'reference_type' => InvoicePayment::class,
                    'reference_id' => $payment->id,
                    'note' => 'ثبت پرداخت برای فاکتور ' . $invoice->uuid . ' (' . $this->methodLabel($payment->method) . ')',
                ]);
            }

            return $payment;
        });
    }

    private function methodLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'نقدی',
            'card' => 'کارتی',
            'cheque' => 'چکی',
            default => $method,
        };
    }

    private function canHandleFinanceActions(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasAnyRole(['admin', 'finance']) || $user->can('finance.approve'));
    }
}
