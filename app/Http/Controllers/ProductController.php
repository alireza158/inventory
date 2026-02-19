<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ModelList;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CrmProductSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->with(['category', 'variants']);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->stock_status === 'out') {
            $query->where('stock', 0);
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (int) preg_replace('/[^\d]/', '', $request->min_price));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (int) preg_replace('/[^\d]/', '', $request->max_price));
        }

        $products = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $categoryTree = Category::query()
            ->whereNull('parent_id')
            ->with(['children.children.children'])
            ->orderBy('name')
            ->get();

        $categories = Category::query()->orderBy('name')->get();
        $modelLists = ModelList::query()->whereNotNull('code')->orderBy('model_name')->get(['id', 'model_name', 'code']);

        return view('products.index', compact('products', 'categoryTree', 'categories', 'modelLists'));
    }

    public function create()
    {
        $categories = Category::query()->orderBy('name')->get();
        $modelLists = ModelList::query()->whereNotNull('code')->orderBy('model_name')->get(['id', 'model_name', 'code']);

        return view('products.create', compact('categories', 'modelLists'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'model_list_ids' => ['required', 'array', 'min:1'],
            'model_list_ids.*' => ['integer', 'exists:model_lists,id'],
            'design_count' => ['required', 'integer', 'min:1', 'max:20'],
            'buy_price' => ['nullable', 'integer', 'min:0'],
            'sell_price' => ['nullable', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            $category = Category::query()->findOrFail($data['category_id']);
            $product = Product::create([
                'category_id' => $category->id,
                'name' => trim($data['name']),
                'sku' => 'AUTO-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'code' => $this->generateProductCode($category),
                'stock' => 0,
                'price' => 0,
            ]);

            $modelLists = ModelList::query()->whereIn('id', $data['model_list_ids'])->get()->keyBy('id');

            foreach ($data['model_list_ids'] as $modelListId) {
                $model = $modelLists->get((int) $modelListId);
                if (!$model || !$model->code) {
                    continue;
                }

                for ($i = 1; $i <= (int) $data['design_count']; $i++) {
                    $varietyCode = str_pad((string) $i, 4, '0', STR_PAD_LEFT);
                    $varietyName = 'طرح ' . $i;
                    $variantCode = $this->generateVariantCode($category->code, $model->code, $varietyCode);

                    ProductVariant::create([
                        'product_id' => $product->id,
                        'model_list_id' => $model->id,
                        'variant_name' => $model->model_name . ' ' . $varietyName,
                        'variety_name' => $varietyName,
                        'variety_code' => $varietyCode,
                        'variant_code' => $variantCode,
                        'sku' => $variantCode,
                        'sell_price' => (int) ($data['sell_price'] ?? 0),
                        'buy_price' => isset($data['buy_price']) ? (int) $data['buy_price'] : null,
                        'stock' => 0,
                        'reserved' => 0,
                    ]);
                }
            }

            $this->recalcProductSummary($product);
        });

        return redirect()->route('products.index')->with('success', 'محصول و تنوع‌ها با موفقیت ساخته شدند.');
    }

    public function edit(Product $product)
    {
        $product->load('variants');
        $categories = Category::orderBy('name')->get();
        $modelListOptions = ModelList::query()->orderBy('model_name')->get(['id', 'model_name', 'code']);

        return view('products.edit', compact('product', 'categories', 'modelListOptions'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.variant_name' => ['required', 'string', 'max:255'],
            'variants.*.model_list_id' => ['required', 'integer', 'exists:model_lists,id'],
            'variants.*.variety_name' => ['required', 'string', 'max:255'],
            'variants.*.variety_code' => ['required', 'digits:4'],
            'variants.*.sell_price' => ['required', 'integer', 'min:0'],
            'variants.*.buy_price' => ['nullable', 'integer', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $product) {
            $category = Category::findOrFail($data['category_id']);
            $product->update([
                'category_id' => $category->id,
                'name' => $data['name'],
                'code' => $product->code ?: $this->generateProductCode($category),
            ]);

            $keepIds = [];
            foreach ($data['variants'] as $v) {
                $model = ModelList::findOrFail($v['model_list_id']);
                $payload = [
                    'variant_name' => $v['variant_name'],
                    'model_list_id' => $model->id,
                    'variety_name' => $v['variety_name'],
                    'variety_code' => $v['variety_code'],
                    'variant_code' => $this->generateVariantCode($category->code, $model->code, $v['variety_code'], $v['id'] ?? null),
                    'sku' => $this->generateVariantCode($category->code, $model->code, $v['variety_code'], $v['id'] ?? null),
                    'sell_price' => (int) $v['sell_price'],
                    'buy_price' => isset($v['buy_price']) ? (int) $v['buy_price'] : null,
                    'stock' => (int) $v['stock'],
                ];

                if (!empty($v['id'])) {
                    $variant = ProductVariant::where('product_id', $product->id)->where('id', $v['id'])->first();
                    if ($variant) {
                        $variant->update($payload);
                        $keepIds[] = $variant->id;
                    }
                } else {
                    $variant = ProductVariant::create(array_merge($payload, ['product_id' => $product->id, 'reserved' => 0]));
                    $keepIds[] = $variant->id;
                }
            }

            ProductVariant::where('product_id', $product->id)->when(count($keepIds) > 0, fn ($q) => $q->whereNotIn('id', $keepIds))->delete();
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
            $query->where(fn ($qq) => $qq->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"));
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
            $product->update(['stock' => 0, 'price' => 0]);
            return;
        }

        $product->update([
            'stock' => max(0, (int) $product->variants->sum('stock')),
            'price' => max(0, (int) $product->variants->min('sell_price')),
        ]);
    }

    private function generateVariantCode(string $categoryCode, string $modelCode, string $varietyCode, ?int $ignoreId = null): string
    {
        $base = $categoryCode . $modelCode . $varietyCode;
        $code = $base;
        $counter = 1;

        while (ProductVariant::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('variant_code', $code)
            ->exists()) {
            $code = substr($base, 0, 10) . str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $code;
    }

    private function generateProductCode(Category $category): string
    {
        $base = $category->code ?: '0000';

        $lastCode = Product::query()
            ->where('category_id', $category->id)
            ->where('code', 'like', $base . '%')
            ->orderByDesc('id')
            ->value('code');

        $next = 1;
        if ($lastCode && strlen($lastCode) >= 8) {
            $next = ((int) substr($lastCode, -4)) + 1;
        }

        return $base . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
