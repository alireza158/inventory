<?php

namespace App\Http\Controllers;

use App\Exports\PurchasesExport;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\WarehouseStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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


    public function exportExcel(): BinaryFileResponse
    {
        $filename = 'purchases-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new PurchasesExport, $filename);
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
            ->with(['variants.modelList:id,model_name,code'])
            ->orderBy('name')
            ->get(['id', 'name', 'category_id', 'code', 'short_barcode', 'sku']);

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

    public function productVariants(Product $product)
    {
        $variants = $product->variants()
            ->with(['modelList:id,model_name,code'])
            ->select([
                'id',
                'product_id',
                'model_list_id',
                'variant_name',
                'variant_code',
                'variety_name',
                'variety_code',
                'sku',
                'barcode',
                'sell_price',
                'buy_price',
                'stock',
                'reserved',
            ])
            ->orderBy('variant_code')
            ->orderBy('id')
            ->get()
            ->unique('id')
            ->values()
            ->map(fn (ProductVariant $variant) => $this->variantPayload($variant));

        return response()->json([
            'product_id' => (int) $product->id,
            'variants' => $variants,
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
            ->with(['variants.modelList:id,model_name,code'])
            ->orderBy('name')
            ->get(['id', 'name', 'category_id', 'code', 'short_barcode', 'sku']);

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
        if (! $request->filled('supplier_id')) {
            $request->merge(['supplier_id' => $purchase->supplier_id]);
        }

        $data = $this->validatePayload($request);

        DB::transaction(function () use ($purchase, $data) {
            $purchase = Purchase::whereKey($purchase->id)
                ->lockForUpdate()
                ->firstOrFail();

            $purchase->update([
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => (int) $data['warehouse_id'],
                'purchased_at' => $data['purchased_at'] ?? $purchase->purchased_at,
                'note' => $data['note'] ?? null,
                'user_id' => auth()->id(),
                'discount_type' => $data['invoice_discount_type'] ?? null,
                'discount_value' => (int) ($data['invoice_discount_value'] ?? 0),
            ]);

            $summary = $this->syncPurchaseItems($purchase, $data, (int) $data['warehouse_id']);
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


    private function variantPayload(ProductVariant $variant): array
    {
        return [
            'id' => (int) $variant->id,
            'product_id' => (int) $variant->product_id,
            'model_list_id' => $variant->model_list_id ? (int) $variant->model_list_id : 0,
            'name' => (string) ($variant->variant_name ?? ''),
            'title' => (string) ($variant->variant_name ?: ($variant->variety_name ?: 'تنوع اصلی')),
            'model_name' => (string) ($variant->modelList?->model_name ?? ''),
            'model_code' => (string) ($variant->modelList?->code ?? ''),
            'variety_name' => (string) ($variant->variety_name ?? ''),
            'variety_code' => (string) ($variant->variety_code ?? ''),
            'code' => (string) ($variant->variant_code ?: ($variant->sku ?: ($variant->barcode ?: ''))),
            'variant_code' => (string) ($variant->variant_code ?? ''),
            'sku' => (string) ($variant->sku ?? ''),
            'barcode' => (string) ($variant->barcode ?? ''),
            'central_stock' => (int) ($variant->stock ?? 0),
            'stock' => (int) ($variant->stock ?? 0),
            'reserved' => (int) ($variant->reserved ?? 0),
            'buy_price' => \App\Support\Currency::toRial($variant->buy_price ?? 0),
            'sell_price' => \App\Support\Currency::toRial($variant->sell_price ?? 0),
        ];
    }

    private function discountTypeLabel(?string $type): string
    {
        return match ($type) {
            'amount' => 'مبلغی',
            'percent' => 'درصدی',
            default => '',
        };
    }

    private function validatePayload(Request $request): array
    {
        $invoiceDiscountType = $request->input('invoice_discount_type', $request->input('discount_type'));
        $invoiceDiscountRaw = $request->input('invoice_discount_value', $request->input('discount_value'));
        $invoiceDiscountValue = $this->filledNumber($invoiceDiscountRaw) ? $this->normalizeNumber($invoiceDiscountRaw) : null;
        $note = $request->input('note', $request->input('notes'));

        $items = collect((array) $request->input('items', []))
            ->map(function ($item) {
                if (array_key_exists('quantity', $item)) {
                    $item['quantity'] = $this->normalizeNumber($item['quantity']);
                }

                if (array_key_exists('qty', $item)) {
                    $item['qty'] = $this->normalizeNumber($item['qty']);
                }

                if (array_key_exists('discount_value', $item) && $this->filledNumber($item['discount_value'])) {
                    $item['discount_value'] = $this->normalizeNumber($item['discount_value']);
                }

                return $item;
            })
            ->filter(fn ($item) => $this->normalizeNumber($item['quantity'] ?? $item['qty'] ?? 0) > 0)
            ->values()
            ->all();

        $request->merge([
            'invoice_discount_type' => $invoiceDiscountType,
            'invoice_discount_value' => $invoiceDiscountValue,
            'note' => $note,
            'warehouse_id' => WarehouseStockService::centralWarehouseId(),
            'items' => $items,
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

            'items.*.buy_price' => ['nullable'],
            'items.*.sell_price' => ['nullable'],
            'items.*.product_buy_price' => ['nullable'],
            'items.*.product_sell_price' => ['nullable'],

            'items.*.discount_type' => ['nullable', 'in:amount,percent'],
            'items.*.discount_value' => ['nullable', 'integer', 'min:0'],
        ], [
            'items.required' => 'حداقل یک قلم با تعداد معتبر باید برای خرید وارد شود.',
            'items.min' => 'حداقل یک قلم با تعداد معتبر باید برای خرید وارد شود.',
        ]);

        $data['items'] = array_values(array_map(function ($item, $index) {
            $qty = $this->normalizeNumber($item['quantity'] ?? $item['qty'] ?? 0);

            $buyRaw = $item['buy_price'] ?? null;
            $sellRaw = $item['sell_price'] ?? null;
            $productBuyRaw = $item['product_buy_price'] ?? null;
            $productSellRaw = $item['product_sell_price'] ?? null;

            $buySource = $this->filledPrice($buyRaw) ? $buyRaw : $productBuyRaw;
            $sellSource = $this->filledPrice($sellRaw) ? $sellRaw : $productSellRaw;

            if (! $this->validPrice($buySource) || ! $this->validPrice($sellSource)) {
                throw ValidationException::withMessages([
                    "items.{$index}.buy_price" => 'برای همه اقلام خرید باید قیمت خرید و قیمت فروش مشخص شود.',
                ]);
            }

            $item['quantity'] = max(1, $qty);
            $item['buy_price'] = max(0, $this->parsePrice($buySource));
            $item['sell_price'] = max(0, $this->parsePrice($sellSource));
            $item['product_buy_price'] = $this->filledPrice($productBuyRaw) ? max(0, $this->parsePrice($productBuyRaw)) : null;
            $item['product_sell_price'] = $this->filledPrice($productSellRaw) ? max(0, $this->parsePrice($productSellRaw)) : null;
            $item['discount_value'] = $this->filledNumber($item['discount_value'] ?? null)
                ? max(0, $this->normalizeNumber($item['discount_value']))
                : 0;

            return $item;
        }, $data['items'], array_keys($data['items'])));

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
                throw ValidationException::withMessages([
                    "items.{$index}.variant_id" => 'تنوع انتخاب‌شده متعلق به این کالا نیست.',
                ]);
            }
        }

        $seenVariants = [];
        foreach ($data['items'] as $index => $item) {
            $key = ((int) $item['product_id']) . ':' . ((int) $item['variant_id']);
            if (isset($seenVariants[$key])) {
                abort(422, 'مدل/طرح تکراری در ردیف ' . ($index + 1) . ' مجاز نیست. هر مدل برای هر کالا فقط یک بار قابل ثبت است.');
            }
            $seenVariants[$key] = true;
        }

        return $data;
    }


    private function filledPrice(mixed $value): bool
    {
        return $this->filledNumber($value);
    }

    private function filledNumber(mixed $value): bool
    {
        return trim((string) ($value ?? '')) !== '';
    }

    private function validPrice(mixed $value): bool
    {
        return $this->filledPrice($value) && preg_match('/\d/', $this->normalizePriceDigits($value)) === 1;
    }

    private function parsePrice(mixed $value): int
    {
        return $this->normalizeNumber($value);
    }

    private function normalizeNumber(mixed $value): int
    {
        $normalized = $this->normalizePriceDigits($value);

        return (int) preg_replace('/[^\d]/', '', $normalized);
    }

    private function normalizePriceDigits(mixed $value): string
    {
        return strtr((string) ($value ?? ''), [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);
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

            WarehouseStockService::change($warehouseId, $product->id, $quantity, (int) $variant->id);

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

    private function syncPurchaseItems(Purchase $purchase, array $data, int $warehouseId): array
    {
        $purchase->load('items');

        $oldItemGroups = $purchase->items
            ->filter(fn ($item) => (int) $item->product_variant_id > 0)
            ->groupBy(fn ($item) => (int) $item->product_variant_id);

        $newItems = collect($data['items'])
            ->keyBy(fn ($item) => (int) $item['variant_id']);

        $variantIds = $oldItemGroups->keys()
            ->merge($newItems->keys())
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->sort()
            ->values();

        $variants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $productIds = $variants->pluck('product_id')
            ->merge($newItems->pluck('product_id'))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->sort()
            ->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $subtotalAmount = 0;
        $itemsDiscountTotal = 0;
        $affectedProductIds = [];

        foreach ($variantIds as $variantId) {
            $variant = $variants->get($variantId);
            if (!$variant) {
                continue;
            }

            $oldGroup = $oldItemGroups->get($variantId, collect());
            $oldItem = $oldGroup->first();
            $newItem = $newItems->get($variantId);

            $oldQty = (int) $oldGroup->sum('quantity');
            $newQty = $newItem ? (int) $newItem['quantity'] : 0;
            $delta = $newQty - $oldQty;

            if ($delta < 0 && (int) $variant->stock < abs($delta)) {
                $message = $newItem
                    ? 'امکان کاهش تعداد این آیتم وجود ندارد، چون بخشی از موجودی قبلاً مصرف یا فروخته شده است.'
                    : 'امکان حذف این آیتم وجود ندارد، چون بخشی از موجودی آن قبلاً فروخته یا مصرف شده است.';

                abort(422, $message);
            }

            if ($newItem) {
                $product = $products->get((int) $newItem['product_id']);
                if (!$product) {
                    abort(422, 'کالای انتخاب‌شده معتبر نیست.');
                }

                $buyPrice = (int) $newItem['buy_price'];
                $sellPrice = (int) $newItem['sell_price'];
                $quantity = (int) $newItem['quantity'];
                $lineSubtotal = $quantity * $buyPrice;
                $itemDiscountType = $newItem['discount_type'] ?? null;
                $itemDiscountValue = (int) ($newItem['discount_value'] ?? 0);
                $itemDiscountAmount = $this->calculateDiscount($lineSubtotal, $itemDiscountType, $itemDiscountValue);
                $lineTotal = max(0, $lineSubtotal - $itemDiscountAmount);

                $variantPayload = [
                    'buy_price' => $buyPrice,
                    'sell_price' => $sellPrice,
                ];

                if ($delta !== 0) {
                    $before = (int) $variant->stock;
                    $after = $before + $delta;
                    $variantPayload['stock'] = $after;
                }

                if ($this->purchaseCanRefreshVariantPrice($purchase, $variantId)) {
                    $variant->update($variantPayload);
                } elseif ($delta !== 0) {
                    $variant->update(['stock' => $variantPayload['stock']]);
                }

                if ($delta !== 0) {
                    StockMovement::create([
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouseId,
                        'user_id' => auth()->id(),
                        'type' => $delta > 0 ? 'in' : 'out',
                        'reason' => 'adjustment',
                        'transaction_type' => 'purchase_adjustment',
                        'quantity' => abs($delta),
                        'stock_before' => $before,
                        'stock_after' => $after,
                        'reference' => 'PUR-ADJ-' . $purchase->id,
                        'reference_type' => Purchase::class,
                        'reference_id' => $purchase->id,
                        'note' => 'اصلاح تعداد خرید کالا - مدل: ' . $variant->variant_name,
                    ]);

                    WarehouseStockService::change($warehouseId, $product->id, $delta, (int) $variant->id);
                }

                $payload = [
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
                ];

                if ($oldItem) {
                    $oldItem->update($payload);
                    $oldGroup->slice(1)->each->delete();
                } else {
                    $purchase->items()->create($payload);
                }

                $subtotalAmount += $lineSubtotal;
                $itemsDiscountTotal += $itemDiscountAmount;
                $affectedProductIds[] = $product->id;
            } elseif ($oldItem) {
                if ($delta !== 0) {
                    $before = (int) $variant->stock;
                    $after = $before + $delta;

                    $variant->update([
                        'stock' => $after,
                    ]);

                    StockMovement::create([
                        'product_id' => $variant->product_id,
                        'warehouse_id' => $warehouseId,
                        'user_id' => auth()->id(),
                        'type' => 'out',
                        'reason' => 'adjustment',
                        'transaction_type' => 'purchase_adjustment',
                        'quantity' => abs($delta),
                        'stock_before' => $before,
                        'stock_after' => $after,
                        'reference' => 'PUR-ADJ-' . $purchase->id,
                        'reference_type' => Purchase::class,
                        'reference_id' => $purchase->id,
                        'note' => 'حذف آیتم از سند خرید - مدل: ' . $variant->variant_name,
                    ]);

                    WarehouseStockService::change($warehouseId, (int) $variant->product_id, $delta, (int) $variant->id);
                }

                $affectedProductIds[] = (int) $variant->product_id;
                $oldGroup->each->delete();
            }
        }

        foreach (array_unique($affectedProductIds) as $productId) {
            $product = $products->get((int) $productId) ?: Product::find($productId);
            if ($product) $this->recalcProductSummary($product);
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

    private function purchaseCanRefreshVariantPrice(Purchase $purchase, int $variantId): bool
    {
        return PurchaseItem::query()
            ->where('product_variant_id', $variantId)
            ->where('purchase_id', '!=', $purchase->id)
            ->whereHas('purchase', function ($query) use ($purchase) {
                $query->where('purchased_at', '>', $purchase->purchased_at)
                    ->orWhere(function ($nested) use ($purchase) {
                        $nested->where('purchased_at', $purchase->purchased_at)
                            ->where('id', '>', $purchase->id);
                    });
            })
            ->doesntExist();
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

            WarehouseStockService::change($warehouseId, (int) $variant->product_id, -((int) $item->quantity), (int) $variant->id);

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
