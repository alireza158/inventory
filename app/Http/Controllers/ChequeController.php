<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
class ChequeController extends Controller
{
    public function store(\App\Models\InvoicePayment $payment, Request $request)
    {
        abort_if($payment->method !== 'cheque', 403);

        $data = $request->validate([
            'bank_name' => 'nullable|string|max:255',
            'cheque_number' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'image' => 'nullable|image|max:4096',
            'status' => 'nullable|in:pending,cleared,bounced',
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
            'status' => $data['status'] ?? 'pending',
        ]);

        return back()->with('success','✅ چک ثبت شد.');
    }
}
