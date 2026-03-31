<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\WarehouseStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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


    public function edit(string $uuid)
    {
        $invoice = Invoice::query()
            ->with(['items.product', 'items.variant'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return view('invoices.edit', compact('invoice'));
    }

    public function update(string $uuid, Request $request)
    {
        $invoice = Invoice::query()->with('items')->where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_mobile' => 'required|string|max:50',
            'customer_address' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:invoice_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($invoice, $data) {
            $subtotal = 0;
            foreach ($data['items'] as $row) {
                $item = $invoice->items->firstWhere('id', (int) $row['id']);
                if (!$item) {
                    continue;
                }

                $lineTotal = (int) $row['quantity'] * (int) $row['price'];
                $item->update([
                    'quantity' => (int) $row['quantity'],
                    'price' => (int) $row['price'],
                    'line_total' => $lineTotal,
                ]);
                $subtotal += $lineTotal;
            }

            $total = max($subtotal + (int) $invoice->shipping_price - (int) $invoice->discount_amount, 0);

            $invoice->update([
                'customer_name' => $data['customer_name'],
                'customer_mobile' => $data['customer_mobile'],
                'customer_address' => $data['customer_address'] ?? '',
                'subtotal' => $subtotal,
                'total' => $total,
            ]);
        });

        return redirect()->route('invoices.show', $invoice->uuid)->with('success', '✅ فاکتور ویرایش شد.');
    }

    public function print(string $uuid)
    {
        $invoice = Invoice::query()->with(['items.product', 'items.variant'])->where('uuid', $uuid)->firstOrFail();

        return view('invoices.print', compact('invoice'));
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

        $canFinanceApprove = $this->canHandleFinanceActions();

        return view('invoices.show', compact('invoice', 'canFinanceApprove'));
    }

    private function canHandleFinanceActions(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasAnyRole(['admin', 'finance']) || $user->can('finance.approve'));
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
