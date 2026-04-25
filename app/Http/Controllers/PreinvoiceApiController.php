<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\WarehouseStock;
use App\Services\WarehouseStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PreinvoiceApiController extends Controller
{
    private function variantSellableStock(?ProductVariant $variant): int
    {
        if (!$variant) return 0;
        return max(0, ((int) $variant->stock - (int) $variant->reserved));
    }

    public function quickSearch(string $input): JsonResponse
    {
        $rawInput = trim($input);
        $normalizedInput = preg_replace('/[^\p{L}\p{N}\-_]+/u', '', $rawInput);
        $digitsOnly = preg_replace('/\D+/', '', $rawInput);
        $isParentCode = $digitsOnly !== '' && strlen($digitsOnly) === 4 && preg_match('/^\d{4}$/', $rawInput);

        if ($isParentCode) {
            $parent = Product::query()
                ->where('is_sellable', true)
                ->where('short_barcode', $digitsOnly)
                ->first();

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'محصولی با این کد ۴ رقمی پیدا نشد.',
                ], 404);
            }

            $modelLists = ProductVariant::query()
                ->active()
                ->where('product_id', $parent->id)
                ->where('stock', '>', 0)
                ->whereNotNull('model_list_id')
                ->with('modelList:id,model_name')
                ->get()
                ->filter(fn ($variant) => $this->variantSellableStock($variant) > 0 && $variant->modelList)
                ->groupBy('model_list_id')
                ->map(function ($rows) {
                    $first = $rows->first();
                    return [
                        'id' => (int) $first->model_list_id,
                        'name' => (string) ($first->modelList?->model_name ?? ''),
                    ];
                })
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            return response()->json([
                'type' => 'parent',
                'product_parent' => [
                    'id' => (int) $parent->id,
                    'short_code' => (string) ($parent->short_barcode ?? ''),
                    'name' => (string) $parent->name,
                ],
                'model_lists' => $modelLists,
            ]);
        }

        $queryText = trim((string) $normalizedInput);
        if ($queryText === '') {
            return response()->json([
                'success' => false,
                'message' => 'ورودی نامعتبر است.',
            ], 422);
        }

        $variant = ProductVariant::query()
            ->active()
            ->with('product:id,name,is_sellable')
            ->where(function ($q) use ($queryText) {
                $q->where('barcode', $queryText)
                    ->orWhere('variant_code', $queryText)
                    ->orWhere('sku', $queryText);
            })
            ->first();

        if (!$variant || !$variant->product || !$variant->product->is_sellable) {
            return response()->json([
                'success' => false,
                'message' => 'کالایی با این بارکد یا پارت‌نامبر پیدا نشد.',
            ], 404);
        }

        $sellableStock = $this->variantSellableStock($variant);
        $itemName = trim(implode(' ', array_filter([
            $variant->product->name,
            $variant->modelList?->model_name,
            $variant->variant_name ?: $variant->variety_name,
        ])));

        return response()->json([
            'type' => 'exact_item',
            'item' => [
                'id' => (int) $variant->id,
                'product_id' => (int) $variant->product_id,
                'name' => $itemName !== '' ? $itemName : (string) $variant->product->name,
                'part_number' => (string) ($variant->variant_code ?: $variant->sku ?: ''),
                'barcode' => (string) ($variant->barcode ?: ''),
                'sellable_stock' => $sellableStock,
                'price' => (int) ($variant->sell_price ?? 0),
            ],
        ]);
    }

    public function quickVariants(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => 'required|integer|exists:products,id',
            'model_list_id' => 'required|integer|exists:model_lists,id',
        ]);

        $variants = ProductVariant::query()
            ->active()
            ->with(['product:id,name,is_sellable'])
            ->where('product_id', (int) $validated['parent_id'])
            ->where('model_list_id', (int) $validated['model_list_id'])
            ->orderBy('variant_name')
            ->get()
            ->filter(fn ($variant) => $variant->product && $variant->product->is_sellable && $this->variantSellableStock($variant) > 0)
            ->values();

        return response()->json([
            'success' => true,
            'variants' => $variants->map(function ($variant) {
                $name = trim(implode(' ', array_filter([
                    $variant->product?->name,
                    $variant->modelList?->model_name,
                    $variant->variant_name ?: $variant->variety_name,
                ])));

                return [
                    'id' => (int) $variant->id,
                    'product_id' => (int) $variant->product_id,
                    'name' => $name !== '' ? $name : (string) ($variant->product?->name ?? ''),
                    'pattern_name' => (string) ($variant->variety_name ?: '—'),
                    'variant_name' => (string) ($variant->variant_name ?: '—'),
                    'part_number' => (string) ($variant->variant_code ?: $variant->sku ?: ''),
                    'barcode' => (string) ($variant->barcode ?: ''),
                    'sellable_stock' => $this->variantSellableStock($variant),
                    'price' => (int) ($variant->sell_price ?? 0),
                ];
            })->values(),
        ]);
    }

    public function quickAddItems(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'proforma_id' => 'nullable',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ], [
            'customer_id.required' => 'انتخاب مشتری الزامی است.',
            'items.required' => 'حداقل یک کالا باید انتخاب شود.',
            'items.*.quantity.min' => 'تعداد باید بزرگ‌تر از صفر باشد.',
        ]);

        Customer::query()->whereKey((int) $validated['customer_id'])->firstOrFail();

        $itemsById = collect($validated['items'])
            ->groupBy(fn ($item) => (int) $item['item_id'])
            ->map(fn ($rows) => (int) $rows->sum('quantity'));

        $variants = ProductVariant::query()
            ->active()
            ->with('product:id,name,is_sellable')
            ->whereIn('id', $itemsById->keys())
            ->get()
            ->keyBy('id');

        $errors = [];
        foreach ($itemsById as $itemId => $qty) {
            /** @var ProductVariant|null $variant */
            $variant = $variants->get((int) $itemId);
            if (!$variant || !$variant->product || !$variant->product->is_sellable) {
                $errors["items.{$itemId}"] = 'کالای انتخابی معتبر نیست.';
                continue;
            }

            $sellableStock = $this->variantSellableStock($variant);
            if ($qty > $sellableStock) {
                $errors["items.{$itemId}"] = "موجودی قابل فروش برای «{$variant->product->name}» کافی نیست.";
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        $resolvedItems = $itemsById->map(function ($qty, $itemId) use ($variants) {
            $variant = $variants->get((int) $itemId);
            return [
                'item_id' => (int) $itemId,
                'product_id' => (int) $variant->product_id,
                'quantity' => (int) $qty,
                'price' => (int) ($variant->sell_price ?? 0),
                'sellable_stock' => $this->variantSellableStock($variant),
                'name' => (string) ($variant->product?->name ?? ''),
            ];
        })->values();

        $subtotal = (int) $resolvedItems->sum(fn ($item) => ((int) $item['quantity']) * ((int) $item['price']));

        return response()->json([
            'success' => true,
            'items' => $resolvedItems,
            'totals' => [
                'subtotal' => $subtotal,
            ],
        ]);
    }

    public function products(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $qDigits = preg_replace('/\D+/', '', $q); // فقط عدد

        $centralWarehouseId = WarehouseStockService::centralWarehouseId();

        $products = Product::query()
            ->select(['id', 'name', 'sku', 'short_barcode', 'code', 'price'])
            ->where('is_sellable', true)
            ->whereHas('variants', fn ($q) => $q->active()->where('stock', '>', 0))
            ->when($q !== '', function ($query) use ($q, $qDigits) {

                // ✅ اگر عدد وارد شد و طولش <= 4 یعنی PPPP
                if ($qDigits !== '' && strlen($qDigits) <= 4) {
                    $pppp = str_pad($qDigits, 4, '0', STR_PAD_LEFT);
                    $query->where('short_barcode', $pppp);
                    return;
                }

                // ✅ اگر طولش 6 بود احتمالاً code محصول (CCPPPP) است
                if ($qDigits !== '' && strlen($qDigits) === 6) {
                    $query->where('code', $qDigits);
                    return;
                }

                // ✅ سرچ عمومی
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                        ->orWhere('short_barcode', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(300)
            ->get();

        $stockByProductId = WarehouseStock::query()
            ->where('warehouse_id', $centralWarehouseId)
            ->whereIn('product_id', $products->pluck('id'))
            ->pluck('quantity', 'product_id');

        $items = $products
            ->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->name,

                // ✅ در پیش‌فاکتور به‌جای sku بهتره همون PPPP نمایش داده بشه
                'sku' => $p->short_barcode ?: ($p->sku ?: ''),

                // اگر بعداً خواستی توی UI نشون بدی:
                'code' => $p->code,
                'short_barcode' => $p->short_barcode,

                'price' => (int) ($p->price ?? 0),
                'quantity' => (int) ($stockByProductId[(int) $p->id] ?? 0),
            ])
            ->values();

        return response()->json([
            'data' => [
                'products' => [
                    'data' => $items,
                    'last_page' => 1,
                ],
            ],
        ]);
    }

    public function product(Product $product)
    {
        abort_unless((bool) $product->is_sellable, 404);
        abort_unless($product->variants()->active()->where('stock', '>', 0)->exists(), 404);

        $product->load(['variants' => fn ($q) => $q->active()->where('stock', '>', 0)->with('modelList')->orderBy('variant_name')]);

        $centralWarehouseId = WarehouseStockService::centralWarehouseId();
        $centralStock = (int) WarehouseStock::query()
            ->where('warehouse_id', $centralWarehouseId)
            ->where('product_id', $product->id)
            ->value('quantity');

        $payload = [
            'id' => $product->id,
            'title' => $product->name,

            // ✅ کد سریع 4 رقمی برای UI
            'sku' => $product->short_barcode ?: ($product->sku ?: ''),
            'short_barcode' => $product->short_barcode,
            'code' => $product->code,

            'price' => (int) ($product->price ?? 0),
            'quantity' => $centralStock,

            'varieties' => $product->variants->map(fn ($v) => [
                'id' => $v->id,
                'price' => (int) ($v->sell_price ?? 0),
                'quantity' => (int) ($v->stock ?? 0),
                'variant_name' => (string) ($v->variant_name ?? ''),
                'variety_name' => (string) ($v->variety_name ?? ''),
                'variety_code' => (string) ($v->variety_code ?? ''),
                'model_list_name' => (string) ($v->modelList?->model_name ?? ''),

                // ✅ بارکد 11 رقمی تنوع (برای اسکن/نمایش آینده)
                'barcode' => $v->variant_code,

                // سازگار با JS قبلی
                'attributes' => [
                    ['pivot' => ['value' => $v->variant_name]],
                ],
                'unique_attributes_key' => (string) $v->variant_name,
            ])->values(),
        ];

        return response()->json(['data' => ['product' => $payload]]);
    }

    public function area()
    {
        return response()->json([
            'data' => [
                'provinces' => config('iran.provinces', []),
            ],
        ]);
    }

    public function shippings()
    {
        $items = ShippingMethod::query()
            ->select(['id', 'name', 'price'])
            ->orderBy('name')
            ->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => $item->name,
                'price' => (int) $item->price,
            ])
            ->values();

        return response()->json([
            'data' => [
                'shippings' => [
                    'data' => $items,
                ],
            ],
        ]);
    }
}
