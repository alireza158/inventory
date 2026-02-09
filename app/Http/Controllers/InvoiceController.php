<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->query('q',''));

        $invoices = \App\Models\Invoice::query()
            ->when($q !== '', function($query) use ($q){
                $query->where('uuid','like',"%{$q}%")
                    ->orWhere('customer_name','like',"%{$q}%")
                    ->orWhere('customer_mobile','like',"%{$q}%");
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('invoices.index', compact('invoices','q'));
    }

    public function show(string $uuid)
    {
        $invoice = \App\Models\Invoice::with(['items','payments.cheque','notes'])
            ->where('uuid',$uuid)->firstOrFail();

        return view('invoices.show', compact('invoice'));
    }

    public function updateStatus(string $uuid, Request $request)
    {
        $invoice = \App\Models\Invoice::where('uuid',$uuid)->firstOrFail();

        $data = $request->validate([
            'status' => 'required|in:processing,shipped,delivered,canceled',
        ]);

        $invoice->update(['status' => $data['status']]);

        return back()->with('success','✅ وضعیت بروزرسانی شد.');
    }
}
