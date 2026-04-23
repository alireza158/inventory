<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\WarehouseStock;
use App\Services\WarehouseStockService;
use Illuminate\Http\Request;

class PreinvoiceApiController extends Controller
{
    public function products(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $qDigits = preg_replace('/\D+/', '', $q); // فقط عدد

        $centralWarehouseId = WarehouseStockService::centralWarehouseId();

        $products = Product::query()
            ->select(['id', 'name', 'sku', 'short_barcode', 'code', 'price'])
            ->where('is_sellable', true)
            ->whereHas('variants', fn ($q) => $q->active()->where('stock', '>', 0))
            ->whereHas('warehouseStocks', function ($q) use ($centralWarehouseId) {
                $q->where('warehouse_id', $centralWarehouseId)->where('quantity', '>', 0);
            })
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
