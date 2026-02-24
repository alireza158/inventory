<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class PreinvoiceApiController extends Controller
{
    public function products(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $qDigits = preg_replace('/\D+/', '', $q); // فقط عدد

        $items = Product::query()
            ->select(['id', 'name', 'sku', 'short_barcode', 'code', 'price', 'stock'])
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
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->name,

                // ✅ در پیش‌فاکتور به‌جای sku بهتره همون PPPP نمایش داده بشه
                'sku' => $p->short_barcode ?: ($p->sku ?: ''),

                // اگر بعداً خواستی توی UI نشون بدی:
                'code' => $p->code,
                'short_barcode' => $p->short_barcode,

                'price' => (int) ($p->price ?? 0),
                'quantity' => (int) ($p->stock ?? 0),
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
        $product->load(['variants' => fn ($q) => $q->orderBy('variant_name')]);

        $payload = [
            'id' => $product->id,
            'title' => $product->name,

            // ✅ کد سریع 4 رقمی برای UI
            'sku' => $product->short_barcode ?: ($product->sku ?: ''),
            'short_barcode' => $product->short_barcode,
            'code' => $product->code,

            'price' => (int) ($product->price ?? 0),
            'quantity' => (int) ($product->stock ?? 0),

            'varieties' => $product->variants->map(fn ($v) => [
                'id' => $v->id,
                'price' => (int) ($v->sell_price ?? 0),
                'quantity' => (int) ($v->stock ?? 0),

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
        return response()->json([
            'data' => [
                'shippings' => [
                    'data' => [
                        ['id' => 1, 'name' => 'ارسال فوری', 'price' => 50000],
                        ['id' => 2, 'name' => 'پیک', 'price' => 30000],
                        ['id' => 3, 'name' => 'مراجعه حضوری', 'price' => 0],
                    ],
                ],
            ],
        ]);
    }
}