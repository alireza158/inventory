<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\WarehouseStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $supplierId = $request->integer('supplier_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Purchase::with('supplier')
            ->withCount('items')
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->when($dateFrom, fn ($q) => $q->whereDate('purchased_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('purchased_at', '<=', $dateTo));

        $totalAllAmount = (int) Purchase::sum('total_amount');
        $totalAllCount = (int) Purchase::count();

        $purchases = $query->latest('purchased_at')
            ->paginate(20)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get();

        return view('purchases.index', compact(
            'purchases',
            'suppliers',
            'totalAllAmount',
            'totalAllCount'
        ));
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.variant', 'items.product']);
        return view('purchases.show', compact('purchase'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'phone']);

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'parent_id']);

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'category_id', 'code']);

        $variants = ProductVariant::query()
            ->leftJoin('model_lists', 'model_lists.id', '=', 'product_variants.model_list_id')
            ->select([
                'product_variants.id',
                'product_variants.product_id',
                'product_variants.model_list_id',
                'product_variants.variant_code',
                'product_variants.variant_name',
                'product_variants.variety_name',
                'product_variants.variety_code',
                'product_variants.sell_price',
                'product_variants.buy_price',
                'product_variants.stock',
                'product_variants.reserved',
                'model_lists.model_name as model_name',
                'model_lists.code as model_code',
            ])
            ->get()
            ->map(function ($v) {
                $design2 = substr((string) $v->variant_code, -2);
                if (!preg_match('/^\d{2}$/', $design2)) $design2 = '00';

                return [
                    'id' => (int) $v->id,
                    'product_id' => (int) $v->product_id,
                    'model_list_id' => $v->model_list_id ? (int) $v->model_list_id : 0,
                    'variant_code' => (string) $v->variant_code,
                    'variant_name' => (string) ($v->variant_name ?? ''),
                    'variety_name' => (string) ($v->variety_name ?? ''),
                    'variety_code' => (string) ($v->variety_code ?? ''),
                    'design2' => $design2,
                    'design_title' => (string) ($v->variety_name ?? ''),
                    'sell_price' => (int) ($v->sell_price ?? 0),
                    'buy_price' => is_null($v->buy_price) ? null : (int) $v->buy_price,
                    'stock' => (int) ($v->stock ?? 0),
                    'reserved' => (int) ($v->reserved ?? 0),
                    'model_name' => (string) ($v->model_name ?? ''),
                    'model_code' => (string) ($v->model_code ?? ''),
                ];
            });

        return view('purchases.create', [
            'suppliers' => $suppliers,
            'categories' => $categories,
            'products' => $products,
            'variants' => $variants,
            'purchase' => null,
        ]);
    }

    public function edit(Purchase $purchase)
    {
        $purchase->load(['items', 'items.product', 'items.variant']);

        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'phone']);

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'parent_id']);

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'category_id', 'code']);

        $variants = ProductVariant::query()
            ->leftJoin('model_lists', 'model_lists.id', '=', 'product_variants.model_list_id')
            ->select([
                'product_variants.id',
                'product_variants.product_id',
                'product_variants.model_list_id',
                'product_variants.variant_code',
                'product_variants.variant_name',
                'product_variants.variety_name',
                'product_variants.variety_code',
                'product_variants.sell_price',
                'product_variants.buy_price',
                'product_variants.stock',
                'product_variants.reserved',
                'model_lists.model_name as model_name',
                'model_lists.code as model_code',
            ])
            ->get()
            ->map(function ($v) {
                $design2 = substr((string) $v->variant_code, -2);
                if (!preg_match('/^\d{2}$/', $design2)) $design2 = '00';

                return [
                    'id' => (int) $v->id,
                    'product_id' => (int) $v->product_id,
                    'model_list_id' => $v->model_list_id ? (int) $v->model_list_id : 0,
                    'variant_code' => (string) $v->variant_code,
                    'variant_name' => (string) ($v->variant_name ?? ''),
                    'variety_name' => (string) ($v->variety_name ?? ''),
                    'variety_code' => (string) ($v->variety_code ?? ''),
                    'design2' => $design2,
                    'design_title' => (string) ($v->variety_name ?? ''),
                    'sell_price' => (int) ($v->sell_price ?? 0),
                    'buy_price' => is_null($v->buy_price) ? null : (int) $v->buy_price,
                    'stock' => (int) ($v->stock ?? 0),
                    'reserved' => (int) ($v->reserved ?? 0),
                    'model_name' => (string) ($v->model_name ?? ''),
                    'model_code' => (string) ($v->model_code ?? ''),
                ];
            });

        return view('purchases.create', [
            'suppliers' => $suppliers,
            'categories' => $categories,
            'products' => $products,
            'variants' => $variants,
            'purchase' => $purchase,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);

        DB::transaction(function () use ($data) {
            $centralWarehouseId = WarehouseStockService::centralWarehouseId();

            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $centralWarehouseId,
                'user_id' => auth()->id(),
                'purchased_at' => now(),
                'note' => $data['note'] ?? null,
                'subtotal_amount' => 0,
                'discount_type' => $data['invoice_discount_type'] ?? null,
                'discount_value' => (int) ($data['invoice_discount_value'] ?? 0),
                'total_discount' => 0,
                'total_amount' => 0,
            ]);

            $summary = $this->applyItems($purchase, $data, $centralWarehouseId);
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
                'warehouse_id' => (int) $data['warehouse_id'],
                'purchased_at' => $data['purchased_at'] ?? $purchase->purchased_at,
                'note' => $data['note'] ?? null,
                'user_id' => auth()->id(),
                'discount_type' => $data['invoice_discount_type'] ?? null,
                'discount_value' => (int) ($data['invoice_discount_value'] ?? 0),
            ]);

            $summary = $this->applyItems($purchase, $data, (int) $data['warehouse_id']);
            $purchase->update($summary);
        });

        return redirect()->route('purchases.index')->with('success', 'سند خرید با موفقیت ویرایش شد.');
    }

    public function destroy(Purchase $purchase)
    {
        DB::transaction(function () use ($purchase) {
            $this->rollbackPurchase($purchase);
            $purchase->delete();
        });

        return redirect()->route('purchases.index')->with('success', 'سند خرید با موفقیت حذف شد.');
    }

    private function validatePayload(Request $request): array
    {
        $invoiceDiscountType = $request->input('invoice_discount_type', $request->input('discount_type'));
        $invoiceDiscountValue = $request->input('invoice_discount_value', $request->input('discount_value'));
        $note = $request->input('note', $request->input('notes'));

        $request->merge([
            'invoice_discount_type' => $invoiceDiscountType,
            'invoice_discount_value' => $invoiceDiscountValue,
            'note' => $note,
            'warehouse_id' => WarehouseStockService::centralWarehouseId(),
        ]);

        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'purchased_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],

            'invoice_discount_type' => ['nullable', 'in:amount,percent,none'],
            'invoice_discount_value' => ['nullable', 'integer', 'min:0'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['required', 'integer', 'exists:product_variants,id'],

            'items.*.qty' => ['nullable', 'integer', 'min:1'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],

            'items.*.buy_price' => ['required'],
            'items.*.sell_price' => ['required'],

            'items.*.discount_type' => ['nullable', 'in:amount,percent'],
            'items.*.discount_value' => ['nullable', 'integer', 'min:0'],
        ]);

        // ✅ اینجا فقط پرانتز اصلاح شد
        $data['items'] = array_values(array_map(function ($item) {
            $qty = (int) ($item['quantity'] ?? $item['qty'] ?? 0);

            $buy = (int) preg_replace('/[^\d]/', '', (string) ($item['buy_price'] ?? 0));
            $sell = (int) preg_replace('/[^\d]/', '', (string) ($item['sell_price'] ?? 0));

            $item['quantity'] = max(1, $qty);
            $item['buy_price'] = max(0, $buy);
            $item['sell_price'] = max(0, $sell);

            return $item;
        }, $data['items'])); // ✅ درست: فقط دو تا )

        if (($data['invoice_discount_type'] ?? null) === 'none') {
            $data['invoice_discount_type'] = null;
            $data['invoice_discount_value'] = 0;
        }

        foreach ($data['items'] as $index => $item) {
            $isValidVariant = ProductVariant::query()
                ->whereKey($item['variant_id'])
                ->where('product_id', $item['product_id'])
                ->exists();

            if (!$isValidVariant) {
                abort(422, 'مدل انتخاب‌شده برای ردیف ' . ($index + 1) . ' متعلق به کالای انتخابی نیست.');
            }
        }

        return $data;
    }

    private function applyItems(Purchase $purchase, array $data, int $warehouseId): array
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

            $product = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();

            $variant = ProductVariant::where('product_id', $product->id)
                ->whereKey($item['variant_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $before = (int) $variant->stock;
            $after = $before + $quantity;

            $variant->update([
                'buy_price' => $buyPrice,
                'sell_price' => $sellPrice,
                'stock' => $after,
            ]);

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
                'note' => 'ثبت/ویرایش خرید کالا - مدل: ' . $variant->variant_name,
            ]);

            WarehouseStockService::change($warehouseId, $product->id, $quantity);

            $purchase->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'product_name' => $product->name,
                'product_code' => $product->code,
                'variant_name' => $variant->variant_name,
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
        if ($baseAmount <= 0 || !$discountType || $discountValue <= 0) return 0;

        if ($discountType === 'percent') {
            $value = min($discountValue, 100);
            return (int) floor($baseAmount * $value / 100);
        }

        return min($discountValue, $baseAmount);
    }

    private function rollbackPurchase(Purchase $purchase): void
    {
        $purchase->load(['items', 'items.product']);

        $warehouseId = (int) ($purchase->warehouse_id ?? 0);
        if ($warehouseId <= 0) $warehouseId = (int) WarehouseStockService::centralWarehouseId();

        $affectedProductIds = [];

        foreach ($purchase->items as $item) {
            if (!$item->product_variant_id) continue;

            $variant = ProductVariant::whereKey($item->product_variant_id)
                ->lockForUpdate()
                ->first();

            if (!$variant) continue;

            if ((int) $variant->stock < (int) $item->quantity) {
                abort(422, 'امکان ویرایش/حذف این سند وجود ندارد؛ موجودی فعلی یکی از مدل‌ها کمتر از مقدار خرید قبلی است.');
            }

            $variant->update([
                'stock' => (int) $variant->stock - (int) $item->quantity,
            ]);

            WarehouseStockService::change($warehouseId, (int) $variant->product_id, -((int) $item->quantity));

            $affectedProductIds[] = $variant->product_id;
        }

        $purchase->items()->delete();

        StockMovement::where('reference', 'PUR-' . $purchase->id)
            ->where('reason', 'purchase')
            ->delete();

        foreach (array_unique($affectedProductIds) as $productId) {
            $product = Product::find($productId);
            if ($product) $this->recalcProductSummary($product);
        }
    }

    private function recalcProductSummary(Product $product): void
    {
        $product->load('variants');

        if ($product->variants->count() === 0) {
            $product->update(['stock' => 0, 'price' => 0]);
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
