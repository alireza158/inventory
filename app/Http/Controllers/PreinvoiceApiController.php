<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class PreinvoiceApiController extends Controller
{
    public function products(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $items = Product::query()
            ->select(['id', 'name', 'sku', 'barcode', 'price', 'stock'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                       ->orWhere('sku', 'like', "%{$q}%")
                       ->orWhere('barcode', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(300)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
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
        $product->load(['variants' => fn($q) => $q->orderBy('variant_name')]);

        $payload = [
            'id' => $product->id,
            'title' => $product->name,
            'barcode' => $product->barcode,
            'price' => (int) ($product->price ?? 0),
            'quantity' => (int) ($product->stock ?? 0),
            'varieties' => $product->variants->map(fn($v) => [
                'id' => $v->id,
                'price' => (int) ($v->sell_price ?? 0),
                'quantity' => (int) ($v->stock ?? 0),

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
        // اگر Shipping تو DB داری، اینجا از DB بده.
        // فعلا نمونه‌ی ساده
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
