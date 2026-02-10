<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
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
        $products = Product::with('variants')->orderBy('name')->get();

        return view('purchases.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.code' => ['required', 'string', 'max:100'],
            'items.*.variant_name' => ['required', 'string', 'max:255'],
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

                $product = null;

                if (!empty($item['product_id'])) {
                    $product = Product::whereKey($item['product_id'])->lockForUpdate()->first();
                }

                if (!$product) {
                    $product = Product::where('sku', $item['code'])
                        ->orWhere('code', $item['code'])
                        ->lockForUpdate()
                        ->first();
                }

                if (!$product) {
                    $defaultCategory = Category::query()->orderBy('id')->first();

                    if (!$defaultCategory) {
                        abort(422, 'برای ثبت خرید جدید، ابتدا حداقل یک دسته‌بندی کالا بسازید.');
                    }

                    $product = Product::create([
                        'category_id' => $defaultCategory->id,
                        'name' => $item['name'],
                        'sku' => $item['code'],
                        'code' => $item['code'],
                        'stock' => 0,
                        'reserved' => 0,
                        'unit' => 'عدد',
                        'price' => 0,
                    ]);
                } else {
                    $product->update([
                        'name' => $item['name'],
                        'sku' => $item['code'],
                        'code' => $item['code'],
                    ]);
                }

                $variant = null;

                if (!empty($item['variant_id'])) {
                    $variant = ProductVariant::where('product_id', $product->id)
                        ->where('id', $item['variant_id'])
                        ->lockForUpdate()
                        ->first();
                }

                if (!$variant) {
                    $variant = ProductVariant::where('product_id', $product->id)
                        ->where('variant_name', $item['variant_name'])
                        ->lockForUpdate()
                        ->first();
                }

                if ($variant) {
                    $before = (int) $variant->stock;
                    $after = $before + $quantity;

                    $variant->update([
                        'variant_name' => $item['variant_name'],
                        'buy_price' => $buyPrice,
                        'sell_price' => $sellPrice,
                        'stock' => $after,
                    ]);
                } else {
                    $before = 0;
                    $after = $quantity;

                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'variant_name' => $item['variant_name'],
                        'buy_price' => $buyPrice,
                        'sell_price' => $sellPrice,
                        'stock' => $after,
                        'reserved' => 0,
                    ]);
                }

                $this->recalcProductSummary($product);

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => 'in',
                    'reason' => 'purchase',
                    'quantity' => $quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference' => 'PUR-'.$purchase->id,
                    'note' => 'ثبت خرید کالا - مدل: '.$item['variant_name'],
                ]);

                $purchase->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $item['name'],
                    'product_code' => $item['code'],
                    'variant_name' => $item['variant_name'],
                    'quantity' => $quantity,
                    'buy_price' => $buyPrice,
                    'sell_price' => $sellPrice,
                    'line_total' => $lineTotal,
                ]);

                $totalAmount += $lineTotal;
            }

            $purchase->update(['total_amount' => $totalAmount]);
        });

        return redirect()->route('purchases.index')->with('success', 'خرید کالا با موفقیت ثبت شد و با بخش کالاها همگام‌سازی شد.');
    }

    private function recalcProductSummary(Product $product): void
    {
        $product->load('variants');

        if ($product->variants->count() === 0) {
            $product->update([
                'stock' => 0,
                'price' => 0,
            ]);
            return;
        }

        $stock = (int) $product->variants->sum('stock');
        $minPrice = (int) $product->variants->min('sell_price');

        $product->update([
            'stock' => max(0, $stock),
            'price' => max(0, $minPrice),
        ]);
    }
}
