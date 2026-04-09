<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\WarehouseTransfer;
use Illuminate\Http\Request;

class AccountStatementController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $customers = Customer::query()
            ->withBalance()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('first_name', 'like', "%{$q}%")
                        ->orWhere('mobile', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('account-statements.index', compact('customers', 'q'));
    }

    public function show(Customer $customer)
    {
        $ledgers = CustomerLedger::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('created_at')
            ->paginate(25);

        $invoiceIds = $ledgers->getCollection()
            ->where('reference_type', Invoice::class)
            ->pluck('reference_id')
            ->filter()
            ->unique()
            ->values();

        $paymentIds = $ledgers->getCollection()
            ->where('reference_type', InvoicePayment::class)
            ->pluck('reference_id')
            ->filter()
            ->unique()
            ->values();

        $transferIds = $ledgers->getCollection()
            ->where('reference_type', WarehouseTransfer::class)
            ->pluck('reference_id')
            ->filter()
            ->unique()
            ->values();

        $payments = InvoicePayment::query()
            ->with([
                'cheque',
                'creator:id,name',
                'invoice:id,uuid,total,customer_name',
            ])
            ->whereIn('id', $paymentIds)
            ->get(['id', 'invoice_id', 'customer_id', 'created_by', 'method', 'amount', 'paid_at', 'bank_name', 'note'])
            ->keyBy('id');

        $transfers = WarehouseTransfer::query()
            ->whereIn('id', $transferIds)
            ->get(['id', 'reference', 'voucher_type'])
            ->keyBy('id');

        $relatedInvoiceIds = $invoiceIds
            ->merge($payments->pluck('invoice_id')->filter()->unique()->values())
            ->unique()
            ->values();

        $invoices = Invoice::query()
            ->whereIn('id', $relatedInvoiceIds)
            ->get(['id', 'uuid', 'total'])
            ->keyBy('id');

        $totalDebit = (int) CustomerLedger::query()
            ->where('customer_id', $customer->id)
            ->where('type', 'debit')
            ->sum('amount');

        $totalCredit = (int) CustomerLedger::query()
            ->where('customer_id', $customer->id)
            ->where('type', 'credit')
            ->sum('amount');

        $netBalance = (int) $customer->opening_balance + $totalDebit - $totalCredit;

        $customerInvoices = Invoice::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('id')
            ->get(['id', 'uuid', 'total']);

        return view('account-statements.show', compact(
            'customer',
            'ledgers',
            'invoices',
            'payments',
            'transfers',
            'netBalance',
            'customerInvoices'
        ));
    }

    public function showInvoice(string $uuid)
    {
        $invoice = Invoice::query()
            ->with(['items.product', 'items.variant'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return view('account-statements.documents.invoice-view', compact('invoice'));
    }

    public function showReturnFromSale(WarehouseTransfer $voucher)
    {
        abort_unless($voucher->voucher_type === WarehouseTransfer::TYPE_CUSTOMER_RETURN, 404);

        $voucher->load([
            'items.product',
            'items.variant',
            'fromWarehouse',
            'toWarehouse',
            'relatedInvoice',
            'customer',
            'user',
        ]);

        return view('account-statements.documents.return-from-sale-view', compact('voucher'));
    }

    public function showPayment(InvoicePayment $payment)
    {
        $payment->load([
            'cheque',
            'invoice:id,uuid,customer_name,customer_mobile,total',
        ]);

        return view('account-statements.documents.payment-view', compact('payment'));
    }
}
