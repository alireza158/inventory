<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\WarehouseStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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


    public function salesVouchers(Request $request)
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
            ->whereIn('status', ['warehouse_pending', 'warehouse_collecting', 'warehouse_checking', 'warehouse_packing', 'warehouse_sent'])
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('vouchers.sales.index', compact('invoices', 'q'));
    }

    public function salesVoucherEdit(string $uuid)
    {
        $invoice = Invoice::query()->with(['items.product', 'items.variant'])->where('uuid', $uuid)->firstOrFail();

        return view('vouchers.sales.edit', compact('invoice'));
    }

    public function salesVoucherUpdate(string $uuid, Request $request)
    {
        $invoice = Invoice::query()->with('items')->where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'status' => 'required|in:warehouse_pending,warehouse_collecting,warehouse_checking,warehouse_packing,warehouse_sent,canceled',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($invoice, $data) {
            $centralWarehouseId = WarehouseStockService::centralWarehouseId();

            $oldQtyByProduct = $invoice->items->groupBy('product_id')->map(fn ($rows) => (int) $rows->sum('quantity'));
            $newQtyByProduct = [];

            foreach ($data['items'] as $row) {
                $item = $invoice->items->firstWhere('id', (int) $row['id']);
                if (!$item) {
                    throw ValidationException::withMessages(['items' => 'یکی از آیتم‌های فاکتور معتبر نیست.']);
                }

                $productId = (int) $item->product_id;
                $newQtyByProduct[$productId] = ($newQtyByProduct[$productId] ?? 0) + (int) $row['quantity'];
            }

            foreach ($newQtyByProduct as $productId => $newQty) {
                $oldQty = (int) ($oldQtyByProduct[$productId] ?? 0);
                $delta = $newQty - $oldQty;

                if ($delta > 0) {
                    WarehouseStockService::change($centralWarehouseId, $productId, -$delta);
                } elseif ($delta < 0) {
                    WarehouseStockService::change($centralWarehouseId, $productId, abs($delta));
                }

                if ($delta !== 0) {
                    $product = Product::query()->whereKey($productId)->lockForUpdate()->first();
                    if ($product) {
                        $before = (int) $product->stock;
                        $after = $before - $delta;
                        $product->update(['stock' => $after]);

                        StockMovement::create([
                            'product_id' => $product->id,
                            'user_id' => auth()->id(),
                            'type' => $delta > 0 ? 'out' : 'in',
                            'reason' => 'sale_edit',
                            'quantity' => abs($delta),
                            'stock_before' => $before,
                            'stock_after' => $after,
                            'reference' => $invoice->uuid,
                            'note' => 'اصلاح حواله فروش کالا',
                        ]);
                    }
                }
            }

            $subtotal = 0;
            foreach ($data['items'] as $row) {
                $item = $invoice->items->firstWhere('id', (int) $row['id']);
                $qty = (int) $row['quantity'];
                $price = (int) $row['price'];
                $line = $qty * $price;
                $subtotal += $line;

                $item->update([
                    'quantity' => $qty,
                    'price' => $price,
                    'line_total' => $line,
                ]);
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'total' => max($subtotal + (int) $invoice->shipping_price - (int) $invoice->discount_amount, 0),
                'status' => $data['status'],
            ]);
        });

        return redirect()->route('vouchers.sales.edit', $invoice->uuid)->with('success', '✅ حواله فروش بروزرسانی شد.');
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
