<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Color;
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
        $modelLists = ModelList::query()->whereNotNull('code')->orderBy('brand')->orderBy('model_name')->get(['id', 'brand', 'model_name', 'code']);

        return view('products.index', compact('products', 'categoryTree', 'categories', 'modelLists'));
    }

    public function create()
    {
        $categories = Category::query()->orderBy('name')->get();
        $modelLists = ModelList::query()->whereNotNull('code')->orderBy('brand')->orderBy('model_name')->get(['id', 'brand', 'model_name', 'code']);
        $colors = Color::query()->orderBy('code')->get(['id', 'name', 'code']);

        return view('products.create', compact('categories', 'modelLists', 'colors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'model_list_ids' => ['required', 'array', 'min:1'],
            'model_list_ids.*' => ['integer', 'exists:model_lists,id'],
            'design_count' => ['required', 'integer', 'min:1', 'max:500'],
            'has_colors' => ['nullable', 'boolean'],
            'color_ids' => ['nullable', 'array'],
            'color_ids.*' => ['integer', 'exists:colors,id'],
            'buy_price' => ['nullable', 'integer', 'min:0'],
            'sell_price' => ['nullable', 'integer', 'min:0'],
        ]);

        $hasColors = (bool) ($data['has_colors'] ?? false);
        $colorIds = array_values(array_unique(array_map('intval', $data['color_ids'] ?? [])));

        if ($hasColors && count($colorIds) === 0) {
            return back()->withErrors(['color_ids' => 'حداقل یک رنگ انتخاب کنید.'])->withInput();
        }

        DB::transaction(function () use ($data, $hasColors, $colorIds) {
            $category = Category::query()->lockForUpdate()->findOrFail($data['category_id']);
            $catCode = $this->normalizeCategory3($category->code);

            if ($catCode === null) {
                abort(422, 'برای این دسته‌بندی «کد عددی» معتبر تعریف نشده است.');
            }

            $modelIds = array_values(array_map('intval', $data['model_list_ids']));
            $designCount = (int) $data['design_count'];
            $colors = $hasColors
                ? Color::query()->whereIn('id', $colorIds)->get(['id', 'name'])->keyBy('id')
                : collect();

            $totalVariants = count($modelIds) * $designCount * max(1, $colors->count());
            if ($totalVariants > 9999) {
                abort(422, 'تعداد کل تنوع‌ها بیش از حد مجاز است (حداکثر 9999).');
            }

            $productCode = $this->generateProductCode8($category->id, $catCode);

            $product = Product::create([
                'category_id' => $category->id,
                'name' => trim($data['name']),
                'sku' => 'AUTO-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'code' => $productCode,
                'stock' => 0,
                'price' => 0,
                'has_colors' => $hasColors,
            ]);

            $modelLists = ModelList::query()->whereIn('id', $modelIds)->get(['id', 'model_name'])->keyBy('id');
            $orderedModels = collect($modelIds)->map(fn ($id) => $modelLists->get($id))->filter()->values();

            $variantSeq = 0;
            $colorLoop = $hasColors ? $colorIds : [null];

            foreach ($orderedModels as $model) {
                foreach ($colorLoop as $colorId) {
                    $colorName = $colorId ? ($colors->get($colorId)?->name ?? null) : null;

                    for ($i = 1; $i <= $designCount; $i++) {
                        $variantSeq++;
                        $suffix = str_pad((string) $variantSeq, 4, '0', STR_PAD_LEFT);
                        $variantCode = $this->generateVariantCode12($productCode, $suffix);

                        $varietyName = 'طرح ' . $i;
                        if ($colorName) {
                            $varietyName = $colorName . ' - ' . $varietyName;
                        }

                        $variantName = trim($model->model_name . ' ' . $varietyName);

                        ProductVariant::create([
                            'product_id' => $product->id,
                            'model_list_id' => $model->id,
                            'color_id' => $colorId,
                            'variant_name' => $variantName,
                            'variety_name' => $varietyName,
                            'variety_code' => str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                            'variant_code' => $variantCode,
                            'sku' => $variantCode,
                            'buy_price' => $data['buy_price'] ?? null,
                            'sell_price' => $data['sell_price'] ?? 0,
                            'stock' => 0,
                            'reserved' => 0,
                        ]);
                    }
                }
            }

            $this->recalcProductSummary($product);
        });

        return redirect()->route('products.index')->with('success', 'کالا و تنوع‌ها با موفقیت ساخته شدند.');
    }

    public function edit(Product $product)
    {
        $product->load('variants');
        $categories = Category::orderBy('name')->get();
        $modelListOptions = ModelList::query()->orderBy('brand')->orderBy('model_name')->get(['id', 'brand', 'model_name', 'code']);

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
            'variants.*.color_id' => ['nullable', 'integer', 'exists:colors,id'],
            'variants.*.variety_name' => ['required', 'string', 'max:255'],
            'variants.*.variety_code' => ['required', 'digits:4'],
            'variants.*.sell_price' => ['required', 'integer', 'min:0'],
            'variants.*.buy_price' => ['nullable', 'integer', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $product) {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);

            $product->update([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'has_colors' => collect($data['variants'])->contains(fn ($v) => !empty($v['color_id'])),
            ]);

            $keepIds = [];

            foreach ($data['variants'] as $v) {
                if (!empty($v['id'])) {
                    $variant = ProductVariant::where('product_id', $product->id)->where('id', $v['id'])->first();
                    if (!$variant) {
                        continue;
                    }

                    $variant->update([
                        'variant_name' => $v['variant_name'],
                        'model_list_id' => $v['model_list_id'],
                        'color_id' => $v['color_id'] ?? null,
                        'variety_name' => $v['variety_name'],
                        'variety_code' => $v['variety_code'],
                        'sell_price' => (int) $v['sell_price'],
                        'buy_price' => isset($v['buy_price']) ? (int) $v['buy_price'] : null,
                        'stock' => (int) $v['stock'],
                    ]);

                    $keepIds[] = $variant->id;
                    continue;
                }

                $suffix = str_pad((string) $this->getNextVariantSuffixInt($product), 4, '0', STR_PAD_LEFT);
                $variantCode = $this->generateVariantCode12((string) $product->code, $suffix);

                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'model_list_id' => $v['model_list_id'],
                    'color_id' => $v['color_id'] ?? null,
                    'variant_name' => $v['variant_name'],
                    'variety_name' => $v['variety_name'],
                    'variety_code' => $v['variety_code'],
                    'variant_code' => $variantCode,
                    'sku' => $variantCode,
                    'sell_price' => (int) $v['sell_price'],
                    'buy_price' => isset($v['buy_price']) ? (int) $v['buy_price'] : null,
                    'stock' => (int) $v['stock'],
                    'reserved' => 0,
                ]);

                $keepIds[] = $variant->id;
            }

            ProductVariant::where('product_id', $product->id)
                ->when(count($keepIds) > 0, fn ($q) => $q->whereNotIn('id', $keepIds))
                ->delete();

            $this->recalcProductSummary($product);
        });

        return redirect()->route('products.index')->with('success', 'کالا بروزرسانی شد.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', 'کالا حذف شد.');
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

        return redirect()->route('products.index')->with('success', "همگام‌سازی انجام شد. ایجاد: {$res['created']} | بروزرسانی: {$res['updated']}");
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

    private function normalizeCategory3(?string $code): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $code);
        if (strlen($digits) < 3) {
            return null;
        }

        return str_pad(substr($digits, -3), 3, '0', STR_PAD_LEFT);
    }

    private function generateProductCode8(int $categoryId, string $catCode): string
    {
        $lastCode = Product::query()
            ->where('category_id', $categoryId)
            ->where('code', 'like', $catCode . '%')
            ->orderByDesc('id')
            ->value('code');

        $seq = 1;
        if ($lastCode && preg_match('/^\d{8}$/', (string) $lastCode)) {
            $seq = ((int) substr((string) $lastCode, 3)) + 1;
        }

        if ($seq > 99999) {
            abort(422, 'امکان تولید کد یکتا برای کالا وجود ندارد.');
        }

        return $catCode . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    private function generateVariantCode12(string $productCode8, string $suffix4): string
    {
        return $productCode8 . str_pad(preg_replace('/\D/', '', $suffix4), 4, '0', STR_PAD_LEFT);
    }

    private function getNextVariantSuffixInt(Product $product): int
    {
        $max = ProductVariant::query()
            ->where('product_id', $product->id)
            ->pluck('variant_code')
            ->map(fn ($code) => (int) substr((string) $code, -4))
            ->max();

        return ((int) $max) + 1;
    }
}
