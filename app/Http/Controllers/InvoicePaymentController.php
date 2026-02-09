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
            'paid_at' => 'nullable|date',
            'note' => 'nullable|string|max:2000',
            'receipt_image' => 'nullable|image|max:4096', // 4MB
        ]);

        $path = null;
        if ($request->hasFile('receipt_image')) {
            $path = $request->file('receipt_image')->store('invoices/receipts', 'public');
        }

        $payment = $invoice->payments()->create([
            'method' => $data['method'],
            'amount' => (int)$data['amount'],
            'paid_at' => $data['paid_at'] ?? null,
            'note' => $data['note'] ?? null,
            'receipt_image' => $path,
        ]);

        // اگر پرداخت چکی بود، بعدش از route جدا cheque رو ثبت می‌کنی
        return back()->with('success','✅ پرداخت ثبت شد.');
    }
}
