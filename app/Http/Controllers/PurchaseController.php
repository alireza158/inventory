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
    public function index(Request $request)
    {
        $supplierId = $request->integer('supplier_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $minTotalToman = $request->filled('min_total') ? (int) $request->get('min_total') : null;
        $maxTotalToman = $request->filled('max_total') ? (int) $request->get('max_total') : null;

        $query = Purchase::with('supplier')
            ->withCount('items')
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->when($dateFrom, fn ($q) => $q->whereDate('purchased_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('purchased_at', '<=', $dateTo));

        if (!is_null($minTotalToman)) {
            $query->where('total_amount', '>=', $minTotalToman * 10);
        }

        if (!is_null($maxTotalToman)) {
            $query->where('total_amount', '<=', $maxTotalToman * 10);
        }

        $totalAllAmount = (int) Purchase::sum('total_amount');
        $totalFilteredAmount = (int) (clone $query)->sum('total_amount');

        $purchases = $query->latest('purchased_at')
            ->paginate(20)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get();

        return view('purchases.index', compact(
            'purchases',
            'suppliers',
            'totalAllAmount',
            'totalFilteredAmount'
        ));
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.variant', 'items.product']);

        return view('purchases.show', compact('purchase'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::with('variants')->orderBy('name')->get();

        return view('purchases.create', [
            'suppliers' => $suppliers,
            'products' => $products,
            'purchase' => null,
        ]);
    }

    public function edit(Purchase $purchase)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::with('variants')->orderBy('name')->get();
        $purchase->load('items');

        return view('purchases.create', compact('suppliers', 'products', 'purchase'));
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);

        DB::transaction(function () use ($data) {
            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'user_id' => auth()->id(),
                'purchased_at' => now(),
                'note' => $data['note'] ?? null,
                'subtotal_amount' => 0,
                'discount_type' => $data['invoice_discount_type'] ?? null,
                'discount_value' => (int) ($data['invoice_discount_value'] ?? 0),
                'total_discount' => 0,
                'total_amount' => 0,
            ]);

            $summary = $this->applyItems($purchase, $data);

            $purchase->update($summary);
        });

        return redirect()->route('purchases.index')->with('success', 'خرید کالا با موفقیت ثبت شد.');
    }

    public function update(Request $request, Purchase $purchase)
    {
        $data = $this->validatePayload($request);

        DB::transaction(function () use ($purchase, $data) {
            $this->rollbackPurchase($purchase);

            $purchase->update([
                'supplier_id' => $data['supplier_id'],
                'note' => $data['note'] ?? null,
                'user_id' => auth()->id(),
                'discount_type' => $data['invoice_discount_type'] ?? null,
                'discount_value' => (int) ($data['invoice_discount_value'] ?? 0),
            ]);

            $summary = $this->applyItems($purchase, $data);

            $purchase->update($summary);
        });

        return redirect()->route('purchases.index')->with('success', 'سند خرید با موفقیت ویرایش شد.');
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'note' => ['nullable', 'string', 'max:1000'],

            'invoice_discount_type' => ['nullable', 'in:amount,percent'],
            'invoice_discount_value' => ['nullable', 'integer', 'min:0'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.code' => ['required', 'string', 'max:100'],
            'items.*.variant_name' => ['required', 'string', 'max:255'],
            'items.*.buy_price' => ['required', 'integer', 'min:0'],
            'items.*.sell_price' => ['required', 'integer', 'min:0'],
            'items.*.discount_type' => ['nullable', 'in:amount,percent'],
            'items.*.discount_value' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function applyItems(Purchase $purchase, array $data): array
    {
        $subtotalAmount = 0;
        $itemsDiscountTotal = 0;

        foreach ($data['items'] as $item) {
            $quantity = (int) $item['quantity'];
            $buyPrice = (int) $item['buy_price'];
            $sellPrice = (int) $item['sell_price'];
            $lineSubtotal = $quantity * $buyPrice;

            $itemDiscountType = $item['discount_type'] ?? null;
            $itemDiscountValue = (int) ($item['discount_value'] ?? 0);
            $itemDiscountAmount = $this->calculateDiscount($lineSubtotal, $itemDiscountType, $itemDiscountValue);
            $lineTotal = max(0, $lineSubtotal - $itemDiscountAmount);

            $product = $this->resolveOrCreateProduct($item);

            $before = 0;
            $after = 0;
            $variant = $this->resolveOrCreateVariant($product, $item, $buyPrice, $sellPrice, $quantity, $before, $after);

            $this->recalcProductSummary($product);

            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => 'in',
                'reason' => 'purchase',
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'reference' => 'PUR-' . $purchase->id,
                'note' => 'ثبت/ویرایش خرید کالا - مدل: ' . $item['variant_name'],
            ]);

            $purchase->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'product_name' => $product->name,
                'product_code' => $product->code ?: $product->sku,
                'variant_name' => $item['variant_name'],
                'quantity' => $quantity,
                'buy_price' => $buyPrice,
                'sell_price' => $sellPrice,
                'line_subtotal' => $lineSubtotal,
                'discount_type' => $itemDiscountType,
                'discount_value' => $itemDiscountValue,
                'discount_amount' => $itemDiscountAmount,
                'line_total' => $lineTotal,
            ]);

            $subtotalAmount += $lineSubtotal;
            $itemsDiscountTotal += $itemDiscountAmount;
        }

        $baseAfterItemDiscount = max(0, $subtotalAmount - $itemsDiscountTotal);
        $invoiceDiscountType = $data['invoice_discount_type'] ?? null;
        $invoiceDiscountValue = (int) ($data['invoice_discount_value'] ?? 0);
        $invoiceDiscountAmount = $this->calculateDiscount($baseAfterItemDiscount, $invoiceDiscountType, $invoiceDiscountValue);

        $totalDiscount = $itemsDiscountTotal + $invoiceDiscountAmount;
        $totalAmount = max(0, $subtotalAmount - $totalDiscount);

        return [
            'subtotal_amount' => $subtotalAmount,
            'discount_type' => $invoiceDiscountType,
            'discount_value' => $invoiceDiscountValue,
            'total_discount' => $totalDiscount,
            'total_amount' => $totalAmount,
        ];
    }

    private function calculateDiscount(int $baseAmount, ?string $discountType, int $discountValue): int
    {
        if ($baseAmount <= 0 || !$discountType || $discountValue <= 0) {
            return 0;
        }

        if ($discountType === 'percent') {
            $value = min($discountValue, 100);
            return (int) floor($baseAmount * $value / 100);
        }

        return min($discountValue, $baseAmount);
    }

    private function resolveOrCreateProduct(array $item): Product
    {
        $product = null;

        if (!empty($item['product_id'])) {
            $product = Product::whereKey($item['product_id'])->lockForUpdate()->first();
        }

        if (!$product && !empty($item['code'])) {
            $product = Product::where('sku', $item['code'])
                ->orWhere('code', $item['code'])
                ->lockForUpdate()
                ->first();
        }

        if (!$product && !empty($item['name'])) {
            $product = Product::where('name', $item['name'])
                ->lockForUpdate()
                ->first();
        }

        if ($product) {
            $product->update([
                'name' => $item['name'],
            ]);

            return $product;
        }

        $defaultCategory = Category::query()->orderBy('id')->first();

        if (!$defaultCategory) {
            abort(422, 'برای ثبت خرید جدید، ابتدا حداقل یک دسته‌بندی کالا بسازید.');
        }

        return Product::create([
            'category_id' => $defaultCategory->id,
            'name' => $item['name'],
            'sku' => $item['code'],
            'code' => $item['code'],
            'stock' => 0,
            'reserved' => 0,
            'unit' => 'عدد',
            'price' => 0,
        ]);
    }

    private function resolveOrCreateVariant(
        Product $product,
        array $item,
        int $buyPrice,
        int $sellPrice,
        int $quantity,
        int &$before,
        int &$after
    ): ProductVariant {
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

            return $variant;
        }

        $before = 0;
        $after = $quantity;

        return ProductVariant::create([
            'product_id' => $product->id,
            'variant_name' => $item['variant_name'],
            'buy_price' => $buyPrice,
            'sell_price' => $sellPrice,
            'stock' => $after,
            'reserved' => 0,
        ]);
    }

    private function rollbackPurchase(Purchase $purchase): void
    {
        $purchase->load('items');

        $affectedProductIds = [];

        foreach ($purchase->items as $item) {
            if (!$item->product_variant_id) {
                continue;
            }

            $variant = ProductVariant::whereKey($item->product_variant_id)
                ->lockForUpdate()
                ->first();

            if (!$variant) {
                continue;
            }

            if ((int) $variant->stock < (int) $item->quantity) {
                abort(422, 'امکان ویرایش این سند وجود ندارد؛ موجودی فعلی یکی از مدل‌ها کمتر از مقدار خرید قبلی است.');
            }

            $variant->update([
                'stock' => (int) $variant->stock - (int) $item->quantity,
            ]);

            $affectedProductIds[] = $variant->product_id;
        }

        $purchase->items()->delete();

        StockMovement::where('reference', 'PUR-' . $purchase->id)
            ->where('reason', 'purchase')
            ->delete();

        foreach (array_unique($affectedProductIds) as $productId) {
            $product = Product::find($productId);
            if ($product) {
                $this->recalcProductSummary($product);
            }
        }
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
