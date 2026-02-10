<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with('supplier')
            ->withCount('items')
            ->latest('purchased_at')
            ->paginate(20);

        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();

        return view('purchases.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.code' => ['required', 'string', 'max:100'],
            'items.*.buy_price' => ['required', 'integer', 'min:0'],
            'items.*.sell_price' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'user_id' => auth()->id(),
                'purchased_at' => now(),
                'note' => $data['note'] ?? null,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($data['items'] as $item) {
                $quantity = (int) $item['quantity'];
                $buyPrice = (int) $item['buy_price'];
                $sellPrice = (int) $item['sell_price'];
                $lineTotal = $quantity * $buyPrice;

                $product = Product::where('sku', $item['code'])
                    ->orWhere('code', $item['code'])
                    ->lockForUpdate()
                    ->first();

                if ($product) {
                    $before = (int) $product->stock;
                    $after = $before + $quantity;

                    $product->update([
                        'name' => $item['name'],
                        'sku' => $item['code'],
                        'code' => $item['code'],
                        'stock' => $after,
                        'price' => $sellPrice,
                        'buy_retail' => $buyPrice,
                        'sale_retail' => $sellPrice,
                    ]);
                } else {
                    $before = 0;
                    $after = $quantity;

                    $product = Product::create([
                        'name' => $item['name'],
                        'sku' => $item['code'],
                        'code' => $item['code'],
                        'stock' => $after,
                        'reserved' => 0,
                        'unit' => 'عدد',
                        'price' => $sellPrice,
                        'buy_retail' => $buyPrice,
                        'sale_retail' => $sellPrice,
                    ]);
                }

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => 'in',
                    'reason' => 'purchase',
                    'quantity' => $quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference' => 'PUR-'.$purchase->id,
                    'note' => 'ثبت خرید کالا',
                ]);

                $purchase->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $item['name'],
                    'product_code' => $item['code'],
                    'quantity' => $quantity,
                    'buy_price' => $buyPrice,
                    'sell_price' => $sellPrice,
                    'line_total' => $lineTotal,
                ]);

                $totalAmount += $lineTotal;
            }

            $purchase->update(['total_amount' => $totalAmount]);
        });

        return redirect()->route('purchases.index')->with('success', 'خرید کالا با موفقیت ثبت شد.');
    }
}
