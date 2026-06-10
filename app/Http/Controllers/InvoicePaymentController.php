<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Services\PaymentRegistrationService;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoicePaymentController extends Controller
{
    public function __construct(private readonly PaymentRegistrationService $paymentService)
    {
    }

    public function store(string $uuid, Request $request)
    {
        abort_unless($this->canHandleFinanceActions(), 403);

        $invoice = Invoice::query()->where('uuid', $uuid)->firstOrFail();

        $payment = DB::transaction(function () use ($request, $invoice) {
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            return $this->createPaymentRecord($request, $invoice, $invoice->customer_id ? (int) $invoice->customer_id : null);
        });

        ActivityLogger::log('invoice_payment_added', $invoice->fresh(), 'پرداخت برای فاکتور ثبت شد.', [
            'payment_id' => $payment->id,
            'amount' => (int) $payment->amount,
            'method' => $payment->method,
        ]);

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
            'cheque_status' => 'nullable|in:pending,cleared,bounced,registered,unregistered',
            'cheque_image' => 'nullable|image|max:4096',
        ]);

        $invoice = Invoice::query()->findOrFail((int) $data['invoice_id']);

        $payment = DB::transaction(function () use ($invoice, $data, $request, $customer) {
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            return $this->persistPayment($invoice, $data, $request, $customer->id);
        });

        ActivityLogger::log('invoice_payment_added', $invoice->fresh(), 'پرداخت برای مشتری ثبت شد.', [
            'payment_id' => $payment->id,
            'amount' => (int) $payment->amount,
            'method' => $payment->method,
        ]);

        return back()->with('success', "✅ پرداخت {$this->methodLabel($payment->method)} برای مشتری ثبت شد.");
    }

    private function createPaymentRecord(Request $request, Invoice $invoice, ?int $fallbackCustomerId = null): InvoicePayment
    {
        $data = $request->validate([
            'method' => 'required|in:cash,cheque',
            'amount' => 'required|integer|min:1',
            'paid_at' => 'required|date',
            'bank_name' => 'required_if:method,cash|nullable|string|max:255',
            'note' => 'nullable|string|max:2000',
            'receipt_image' => 'nullable|image|max:4096',
            'cheque_bank_name' => 'nullable|string|max:255',
            'cheque_branch_name' => 'nullable|string|max:255',
            'cheque_number' => 'required_if:method,cheque|nullable|string|max:255',
            'cheque_amount' => 'nullable|integer|min:1',
            'cheque_due_date' => 'required_if:method,cheque|nullable|date',
            'cheque_received_at' => 'required_if:method,cheque|nullable|date',
            'cheque_customer_name' => 'nullable|string|max:255',
            'cheque_customer_code' => 'nullable|string|max:255',
            'cheque_account_number' => 'nullable|string|max:255',
            'cheque_account_holder' => 'nullable|string|max:255',
            'cheque_status' => 'nullable|in:pending,cleared,bounced,registered,unregistered',
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

        return $this->paymentService->registerForInvoice(
            $invoice,
            $data,
            $fallbackCustomerId,
            auth()->id(),
            $path,
            $chequeImagePath
        );
    }

    private function methodLabel(string $method): string
    {
        return $this->paymentService->methodLabel($method);
    }

    private function canHandleFinanceActions(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasAnyRole(['Admin', 'finance', 'Accountant']) || $user->can('finance.approve'));
    }
}
