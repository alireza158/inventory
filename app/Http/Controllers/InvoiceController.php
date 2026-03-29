<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $invoices = Invoice::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('uuid', 'like', "%{$q}%")
                       ->orWhere('customer_name', 'like', "%{$q}%")
                       ->orWhere('customer_mobile', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('invoices.index', compact('invoices', 'q'));
    }

    public function show(string $uuid)
    {
        $invoice = Invoice::query()
            ->with([
                'items.product',     // ✅ برای نمایش نام محصول
                'items.variant',     // ✅ برای نمایش نام مدل/واریانت
                'payments.cheque',
                'notes',
            ])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return view('invoices.show', compact('invoice'));
    }

    public function updateStatus(string $uuid, Request $request)
    {
        $invoice = Invoice::where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'status' => 'required|in:warehouse_pending,warehouse_collecting,warehouse_checking,warehouse_packing,warehouse_sent,canceled',
        ]);

        $invoice->update([
            'status' => $data['status'],
        ]);

        return back()->with('success', '✅ وضعیت بروزرسانی شد.');
    }
}
