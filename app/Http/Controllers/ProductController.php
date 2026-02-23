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

        return view('products.index', compact('products', 'categoryTree'));
    }

    public function create()
    {
        $categories = Category::query()->orderBy('name')->get();

        // مدل‌لیست‌ها فقط آنهایی که کد دارند (۳ رقمی)
        $modelLists = ModelList::query()
            ->whereNotNull('code')
            ->where('code', '<>', '')
            ->orderBy('brand')
            ->orderBy('model_name')
            ->get(['id', 'brand', 'model_name', 'code']);

        return view('products.create', compact('categories', 'modelLists'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'variant_type' => ['required', 'in:design,model,both'],

            // مدل‌لیست لازم برای model و both
            'model_list_ids' => ['nullable', 'array'],
            'model_list_ids.*' => ['integer', 'exists:model_lists,id'],

            // تعداد طرح لازم برای design و both
            'design_count' => ['nullable', 'integer', 'min:1', 'max:99'],

            'buy_price' => ['nullable', 'integer', 'min:0'],
            'sell_price' => ['nullable', 'integer', 'min:0'],
        ]);

        $type = $data['variant_type'];

        // قوانین منطقی
        if (($type === 'model' || $type === 'both') && empty($data['model_list_ids'])) {
            abort(422, 'حداقل یک مدل‌لیست انتخاب کنید.');
        }
        if (($type === 'design' || $type === 'both') && empty($data['design_count'])) {
            abort(422, 'تعداد طرح را وارد کنید.');
        }

        DB::transaction(function () use ($data, $type) {

            $category = Category::query()->lockForUpdate()->findOrFail($data['category_id']);
            $cat2 = $this->normalizeCategory2($category->code);
            if ($cat2 === null) {
                abort(422, 'کد دسته‌بندی باید ۲ رقمی باشد (00 تا 99).');
            }

            // PPPP = شماره ترتیبی محصول در کل سیستم (۴ رقمی)
            $seq4 = $this->nextProductSeq4();
            $productCode6 = $cat2 . $seq4; // CCPP PP (6 رقم)

            $product = Product::create([
                'category_id' => $category->id,
                'name' => trim($data['name']),
                'sku' => 'AUTO-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'code' => $productCode6, // کد پایه محصول
                'stock' => 0,
                'price' => 0,
            ]);

            $sellPrice = (int) ($data['sell_price'] ?? 0);
            $buyPrice = isset($data['buy_price']) ? (int) $data['buy_price'] : null;

            // مدل‌ها
            $models = collect();
            if (!empty($data['model_list_ids'])) {
                $ids = array_values(array_map('intval', $data['model_list_ids']));
                $models = ModelList::query()->whereIn('id', $ids)->get(['id','model_name','code'])->keyBy('id');
                // حفظ ترتیب انتخاب کاربر
                $models = collect($ids)->map(fn($id) => $models->get($id))->filter()->values();
            }

            // ساخت تنوع‌ها
            if ($type === 'design') {
                $d = (int) $data['design_count'];
                for ($i=1; $i <= $d; $i++) {
                    $design2 = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
                    $model3 = '000';
                    $variantCode = $this->buildVariantCode11($productCode6, $model3, $design2);

                    ProductVariant::create([
                        'product_id' => $product->id,
                        'model_list_id' => null,
                        'variant_name' => $product->name . ' طرح ' . $i,
                        'variety_name' => 'طرح ' . $i,
                        'variety_code' => str_pad((string)$i, 4, '0', STR_PAD_LEFT),

                        'variant_code' => $variantCode,
                        'sku' => $variantCode,

                        'sell_price' => $sellPrice,
                        'buy_price' => $buyPrice,
                        'stock' => 0,
                        'reserved' => 0,
                    ]);
                }
            }

            if ($type === 'model') {
                foreach ($models as $m) {
                    $model3 = $this->normalizeModel3($m->code);
                    $design2 = '00';
                    $variantCode = $this->buildVariantCode11($productCode6, $model3, $design2);

                    ProductVariant::create([
                        'product_id' => $product->id,
                        'model_list_id' => $m->id,
                        'variant_name' => $product->name . ' ' . $m->model_name,
                        'variety_name' => '—',
                        'variety_code' => '0000',

                        'variant_code' => $variantCode,
                        'sku' => $variantCode,

                        'sell_price' => $sellPrice,
                        'buy_price' => $buyPrice,
                        'stock' => 0,
                        'reserved' => 0,
                    ]);
                }
            }

            if ($type === 'both') {
                $d = (int) $data['design_count'];
                foreach ($models as $m) {
                    $model3 = $this->normalizeModel3($m->code);
                    for ($i=1; $i <= $d; $i++) {
                        $design2 = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
                        $variantCode = $this->buildVariantCode11($productCode6, $model3, $design2);

                        ProductVariant::create([
                            'product_id' => $product->id,
                            'model_list_id' => $m->id,
                            'variant_name' => $product->name . ' ' . $m->model_name . ' طرح ' . $i,
                            'variety_name' => 'طرح ' . $i,
                            'variety_code' => str_pad((string)$i, 4, '0', STR_PAD_LEFT),

                            'variant_code' => $variantCode,
                            'sku' => $variantCode,

                            'sell_price' => $sellPrice,
                            'buy_price' => $buyPrice,
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
            'variants.*.model_list_id' => ['nullable', 'integer', 'exists:model_lists,id'],
            'variants.*.variety_name' => ['required', 'string', 'max:255'],
            'variants.*.variety_code' => ['required', 'digits:4'],

            'variants.*.sell_price' => ['required', 'integer', 'min:0'],
            'variants.*.buy_price' => ['nullable', 'integer', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $product) {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);

            $product->update([
                'category_id' => (int)$data['category_id'],
                'name' => $data['name'],
            ]);

            $keepIds = [];

            foreach ($data['variants'] as $v) {
                $modelCode3 = '000';
                $modelListId = $v['model_list_id'] ?? null;

                if (!empty($modelListId)) {
                    $model = ModelList::findOrFail($modelListId);
                    $modelCode3 = $this->normalizeModel3($model->code);
                }

                $design2 = substr((string)$v['variety_code'], -2);
                if (!preg_match('/^\d{2}$/', $design2)) $design2 = '00';

                $variantCode = $this->buildVariantCode11($product->code, $modelCode3, $design2);

                $payload = [
                    'variant_name' => $v['variant_name'],
                    'model_list_id' => $modelListId,
                    'variety_name' => $v['variety_name'],
                    'variety_code' => $v['variety_code'],
                    'variant_code' => $variantCode,
                    'sku' => $variantCode,
                    'sell_price' => (int)$v['sell_price'],
                    'buy_price' => isset($v['buy_price']) ? (int)$v['buy_price'] : null,
                    'stock' => (int)$v['stock'],
                ];

                if (!empty($v['id'])) {
                    $variant = ProductVariant::where('product_id', $product->id)->where('id', $v['id'])->first();
                    if ($variant) {
                        $variant->update($payload);
                        $keepIds[] = $variant->id;
                    }
                } else {
                    $variant = ProductVariant::create(array_merge($payload, [
                        'product_id' => $product->id,
                        'reserved' => 0,
                    ]));
                    $keepIds[] = $variant->id;
                }
            }

            ProductVariant::where('product_id', $product->id)
                ->when(count($keepIds) > 0, fn($q) => $q->whereNotIn('id', $keepIds))
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
            $query->where(fn($qq) => $qq->where('name','like',"%{$q}%")->orWhere('code','like',"%{$q}%"));
        }
        if ($request->filled('category_id')) $query->where('category_id', $request->category_id);

        $products = $query->orderBy('name')->paginate(50)->withQueryString();
        return view('products.pricelist', compact('products'));
    }

    public function syncCrm(CrmProductSyncService $service)
    {
        $res = $service->sync();
        return redirect()->route('products.index')->with('success', "همگام‌سازی انجام شد. ایجاد: {$res['created']} | بروزرسانی: {$res['updated']}");
    }

    // ---------------- Helpers ----------------

    private function recalcProductSummary(Product $product): void
    {
        $product->load('variants');
        if ($product->variants->count() === 0) {
            $product->update(['stock' => 0, 'price' => 0]);
            return;
        }

        $product->update([
            'stock' => max(0, (int)$product->variants->sum('stock')),
            'price' => max(0, (int)$product->variants->min('sell_price')),
        ]);
    }

    private function normalizeCategory2(?string $code): ?string
    {
        $c = trim((string)($code ?? ''));
        if ($c === '') return null;
        if (!preg_match('/^\d{2}$/', $c)) return null;
        return $c;
    }

    private function normalizeModel3(?string $code): string
    {
        $c = preg_replace('/\D+/', '', (string)($code ?? ''));
        $c = substr($c, 0, 3);
        return str_pad($c, 3, '0', STR_PAD_LEFT);
    }

    private function nextProductSeq4(): string
    {
        $mx = DB::table('products')
            ->lockForUpdate()
            ->selectRaw("MAX(CAST(SUBSTRING(code, 3, 4) AS UNSIGNED)) as mx")
            ->value('mx');

        $next = ((int)$mx) + 1;
        if ($next > 9999) abort(422, 'حداکثر 9999 کالا پشتیبانی می‌شود (PPPP چهار رقمی است).');

        return str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    private function buildVariantCode11(string $productCode6, string $model3, string $design2): string
    {
        // CCPP PP + MMM + DD
        $code = $productCode6 . $model3 . $design2;

        // اگر variant_code یونیک در DB است، این چک خیلی کمک می‌کند:
        if (ProductVariant::where('variant_code', $code)->exists()) {
            // این یعنی دو تنوع با مدل/طرح تکراری ساخته شده
            abort(422, "کد تنوع تکراری است: {$code} (مدل/طرح تکراری انتخاب شده).");
        }

        return $code;
    }
}