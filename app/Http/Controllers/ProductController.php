<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\ModelList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\CrmProductSyncService;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->with(['category', 'variants']);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                   ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->stock_status === 'out') {
            $query->where('stock', 0);
        }

        if ($request->filled('min_price')) {
            $min = (int) preg_replace('/[^\d]/', '', $request->min_price);
            $query->where('price', '>=', $min);
        }

        if ($request->filled('max_price')) {
            $max = (int) preg_replace('/[^\d]/', '', $request->max_price);
            $query->where('price', '<=', $max);
        }

        $products = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $categoryTree = Category::query()
            ->whereNull('parent_id')
            ->with(['children.children.children'])
            ->orderBy('name')
            ->get();

        return view('products.index', compact('products', 'categoryTree'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $modelListOptions = ModelList::query()
            ->orderBy('model_name')
            ->pluck('model_name')
            ->values()
            ->all();

        return view('products.create', compact('categories', 'modelListOptions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name'        => ['required', 'string', 'max:255'],
            'sku'         => ['required', 'string', 'max:80', 'unique:products,sku'],

            // variants (اختیاری)
            'variants'                 => ['nullable', 'array'],
            'variants.*.variant_name'  => ['required_with:variants', 'string', 'max:255'],
            'variants.*.sell_price'    => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.buy_price'     => ['nullable', 'integer', 'min:0'],
            'variants.*.stock'         => ['required_with:variants', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            $product = Product::create([
                'category_id' => $data['category_id'],
                'name'        => $data['name'],
                'sku'         => $data['sku'],

                // بعداً با variants پر می‌شود
                'stock'       => 0,
                'price'       => 0,
            ]);

            $variants = $data['variants'] ?? [];

            foreach ($variants as $v) {
                ProductVariant::create([
                    'product_id'   => $product->id,
                    'variant_name' => $v['variant_name'],
                    'sell_price'   => (int) $v['sell_price'],
                    'buy_price'    => isset($v['buy_price']) ? (int) $v['buy_price'] : null,
                    'stock'        => (int) $v['stock'],
                    'reserved'     => 0,
                ]);

                $this->syncVariantWithModelList($v['variant_name']);
            }

            $this->recalcProductSummary($product);
        });

        return redirect()->route('products.index')->with('success', 'محصول با موفقیت ثبت شد.');
    }

    public function edit(Product $product)
    {
        $product->load('variants');
        $categories = Category::orderBy('name')->get();
        $modelListOptions = ModelList::query()
            ->orderBy('model_name')
            ->pluck('model_name')
            ->values()
            ->all();

        return view('products.edit', compact('product', 'categories', 'modelListOptions'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name'        => ['required', 'string', 'max:255'],
            'sku'         => ['required', 'string', 'max:80', 'unique:products,sku,' . $product->id],

            // variants
            'variants'                 => ['nullable', 'array'],

            // id برای update ردیف‌های قبلی
            'variants.*.id'            => ['nullable', 'integer', 'exists:product_variants,id'],

            'variants.*.variant_name'  => ['required_with:variants', 'string', 'max:255'],
            'variants.*.sell_price'    => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.buy_price'     => ['nullable', 'integer', 'min:0'],
            'variants.*.stock'         => ['required_with:variants', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $product) {
            $product->update([
                'category_id' => $data['category_id'],
                'name'        => $data['name'],
                'sku'         => $data['sku'],
            ]);

            $incoming = $data['variants'] ?? [];

            // ids که از فرم آمده (برای تشخیص حذف)
            $keepIds = [];

            foreach ($incoming as $v) {
                $payload = [
                    'variant_name' => $v['variant_name'],
                    'sell_price'   => (int) $v['sell_price'],
                    'buy_price'    => isset($v['buy_price']) ? (int) $v['buy_price'] : null,
                    'stock'        => (int) $v['stock'],
                ];

                $this->syncVariantWithModelList($v['variant_name']);

                if (!empty($v['id'])) {
                    $variant = ProductVariant::where('product_id', $product->id)
                        ->where('id', $v['id'])
                        ->first();

                    if ($variant) {
                        $variant->update($payload);
                        $keepIds[] = $variant->id;
                    }
                } else {
                    $variant = ProductVariant::create(array_merge($payload, [
                        'product_id' => $product->id,
                        'reserved'   => 0,
                    ]));
                    $keepIds[] = $variant->id;
                }
            }

            // حذف variantهایی که دیگر در فرم نیستند
            ProductVariant::where('product_id', $product->id)
                ->when(count($keepIds) > 0, fn($q) => $q->whereNotIn('id', $keepIds))
                ->delete();

            $this->recalcProductSummary($product);
        });

        return redirect()->route('products.index')->with('success', 'محصول بروزرسانی شد.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'محصول حذف شد.');
    }

    public function priceList(Request $request)
    {
        $query = Product::query()->with('category');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                   ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->orderBy('name')->paginate(50)->withQueryString();
        return view('products.pricelist', compact('products'));
    }

    public function syncCrm(CrmProductSyncService $service)
    {
        $res = $service->sync();
        return redirect()->route('products.index')
            ->with('success', "همگام‌سازی انجام شد. ایجاد: {$res['created']} | بروزرسانی: {$res['updated']}");
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


    private function syncVariantWithModelList(?string $variantName): void
    {
        $modelName = trim((string) $variantName);
        if ($modelName === '') {
            return;
        }

        ModelList::firstOrCreate([
            'model_name' => $modelName,
        ]);
    }
}
