<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use Illuminate\Http\Request;

class ChequeController extends Controller
{
    public function index(Request $request)
    {
        $query = Cheque::query()
            ->with(['payment.invoice'])
            ->whereIn('status', ['registered', 'unregistered']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('customer_name')) {
            $customerName = $request->string('customer_name')->toString();
            $query->where(function ($q) use ($customerName) {
                $q->where('customer_name', 'like', '%' . $customerName . '%')
                    ->orWhereHas('payment.invoice', function ($invoiceQ) use ($customerName) {
                        $invoiceQ->where('customer_name', 'like', '%' . $customerName . '%');
                    });
            });
        }
        if ($request->filled('cheque_number')) {
            $query->where('cheque_number', 'like', '%' . $request->string('cheque_number')->toString() . '%');
        }
        if ($request->filled('received_from')) {
            $query->whereDate('received_at', '>=', $request->string('received_from')->toString());
        }
        if ($request->filled('received_to')) {
            $query->whereDate('received_at', '<=', $request->string('received_to')->toString());
        }
        if ($request->filled('due_from')) {
            $query->whereDate('due_date', '>=', $request->string('due_from')->toString());
        }
        if ($request->filled('due_to')) {
            $query->whereDate('due_date', '<=', $request->string('due_to')->toString());
        }

        $cheques = $query->latest('received_at')->paginate(20)->withQueryString();

        return view('finance.registered-cheques-index', compact('cheques'));
    }

    public function store(\App\Models\InvoicePayment $payment, Request $request)
    {
        abort_unless($this->canHandleFinanceActions(), 403);
        abort_if($payment->method !== 'cheque', 403);

        $data = $request->validate([
            'bank_name' => 'nullable|string|max:255',
            'cheque_number' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'image' => 'nullable|image|max:4096',
            'status' => 'nullable|in:pending,cleared,bounced,registered,unregistered',
        ]);

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('invoices/cheques', 'public');
        }

        $payment->cheque()->create([
            'bank_name' => $data['bank_name'] ?? null,
            'cheque_number' => $data['cheque_number'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'image' => $path,
            'status' => $data['status'] ?? 'unregistered',
        ]);

        return back()->with('success', '✅ چک ثبت شد.');
    }

    private function canHandleFinanceActions(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasAnyRole(['admin', 'finance']) || $user->can('finance.approve'));
    }
}
