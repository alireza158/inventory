<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\InvoicePayment;
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

        $payments = InvoicePayment::query()
            ->with('cheque')
            ->whereIn('id', $paymentIds)
            ->get(['id', 'invoice_id', 'method', 'amount', 'paid_at', 'payment_identifier', 'note'])
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

        return view('account-statements.show', compact(
            'customer',
            'ledgers',
            'invoices',
            'payments',
            'totalDebit',
            'totalCredit',
            'netBalance'
        ));
    }
}
