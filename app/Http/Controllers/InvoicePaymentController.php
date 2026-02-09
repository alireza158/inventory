<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InvoicePaymentController extends Controller
{
    public function store(string $uuid, Request $request)
    {
        $invoice = \App\Models\Invoice::where('uuid',$uuid)->firstOrFail();

        $data = $request->validate([
            'method' => 'required|in:cash,card,cheque',
            'amount' => 'required|integer|min:1',
            'paid_at' => 'nullable|date', // yyyy-mm-dd
            'note' => 'nullable|string|max:2000',
            'receipt_image' => 'nullable|image|max:4096',
        ]);

        $path = null;
        if ($request->hasFile('receipt_image')) {
            $path = $request->file('receipt_image')->store('invoices/receipts', 'public');
        }

        // ✅ اگر خالی بود، امروز ثبت شود
        $paidAt = $data['paid_at'] ?? now()->toDateString();

        $invoice->payments()->create([
            'method' => $data['method'],
            'amount' => (int) $data['amount'],
            'paid_at' => $paidAt,
            'note' => $data['note'] ?? null,
            'receipt_image' => $path,
        ]);

        return back()->with('success','✅ پرداخت ثبت شد.');
    }
}
