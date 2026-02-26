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
            $q = trim((string) $request->q);

            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('short_barcode', 'like', "%{$q}%")
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

        $modelLists = ModelList::query()
            ->whereNotNull('code')
            ->where('code', '<>', '')
            ->orderBy('brand')
            ->orderBy('model_name')
            ->get(['id', 'brand', 'model_name', 'code']);

        $previewSeq4 = $this->peekNextProductSeq4();

        return view('products.create', compact('categories', 'modelLists', 'previewSeq4'));
    }

    /**
     * PPPP پیش‌نمایش
     */
    private function peekNextProductSeq4(): string
    {
        $mx = DB::table('products')
            ->selectRaw("MAX(CAST(COALESCE(NULLIF(short_barcode,''), SUBSTRING(code, 3, 4)) AS UNSIGNED)) as mx")
            ->value('mx');

        $next = ((int) $mx) + 1;
        if ($next < 1) $next = 1;
        if ($next > 9999) $next = 9999;

        return str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name'        => ['required', 'string', 'max:255'],

            'use_models'        => ['nullable'],
            'use_designs'       => ['nullable'],
            'model_brand_group' => ['nullable', 'string', 'max:100'],

            'model_list_ids'   => ['nullable', 'array'],
            'model_list_ids.*' => ['integer', 'exists:model_lists,id'],

            // چون DD دو رقمی است، طرح‌ها تا 99 منطقی است
            'design_count'      => ['nullable', 'integer', 'min:1', 'max:99'],
            'design_notes'      => ['nullable', 'array'],
            'design_notes.*'    => ['nullable', 'string', 'max:120'],

            'buy_price'  => ['nullable', 'integer', 'min:0'],
            'sell_price' => ['nullable', 'integer', 'min:0'],
        ]);

        $useModels  = $request->boolean('use_models');
        $useDesigns = $request->boolean('use_designs');

        if ($useModels && empty($data['model_brand_group'])) {
            abort(422, 'برای مدل‌لیست: ابتدا گروه برند را انتخاب کنید.');
        }
        if ($useModels && empty($data['model_list_ids'])) {
            abort(422, 'برای مدل‌لیست: حداقل یک مدل انتخاب کنید.');
        }
        if ($useDesigns && empty($data['design_count'])) {
            abort(422, 'برای طرح‌بندی: تعداد طرح را وارد کنید.');
        }

        $designNotes = collect($data['design_notes'] ?? [])
            ->map(fn ($note) => trim((string) $note))
            ->values();

        DB::transaction(function () use ($data, $useModels, $useDesigns, $designNotes) {

            $category = Category::query()->lockForUpdate()->findOrFail($data['category_id']);

            // CC (2 digit)
            $cat2 = $this->normalizeCategory2($category->code);
            if ($cat2 === null) {
                abort(422, 'کد دسته‌بندی باید ۲ رقمی باشد (00 تا 99).');
            }

            // PPPP (global)
            $seq4 = $this->nextProductSeq4();

            // base product code (CCPPPP) => 6 digits
            $productCode6 = $cat2 . $seq4;

            $product = Product::create([
                'category_id'   => $category->id,
                'name'          => trim($data['name']),
                'sku'           => 'AUTO-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'code'          => $productCode6,
                'short_barcode' => $seq4,
                'stock'         => 0,
                'price'         => 0,
            ]);

            $sellPrice = (int) ($data['sell_price'] ?? 0);
            $buyPrice  = isset($data['buy_price']) ? (int) $data['buy_price'] : null;

            // مدل‌ها با حفظ ترتیب انتخاب کاربر
            $models = collect();
            if ($useModels) {
                $ids = array_values(array_map('intval', $data['model_list_ids']));
                $map = ModelList::query()->whereIn('id', $ids)->get(['id', 'model_name', 'code'])->keyBy('id');
                $models = collect($ids)->map(fn ($id) => $map->get($id))->filter()->values();
            }

            // CASE 1: بدون مدل و بدون طرح
            if (!$useModels && !$useDesigns) {
                $variantCode = $this->buildVariantCode11($productCode6, '000', '00');

                ProductVariant::create([
                    'product_id'    => $product->id,
                    'model_list_id' => null,
                    'variant_name'  => $product->name,
                    'variety_name'  => '—',
                    'variety_code'  => '0000',
                    'variant_code'  => $variantCode,
                    'sell_price'    => $sellPrice,
                    'buy_price'     => $buyPrice,
                    'stock'         => 0,
                    'reserved'      => 0,
                ]);
            }

            // CASE 2: فقط طرح
            if ($useDesigns && !$useModels) {
                $d = (int) $data['design_count'];

                for ($i = 1; $i <= $d; $i++) {
                    $design2 = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                    $variantCode = $this->buildVariantCode11($productCode6, '000', $design2);

                    $designNote  = (string) ($designNotes->get($i - 1) ?? '');
                    $designTitle = $designNote !== '' ? ('طرح ' . $i . ' (' . $designNote . ')') : ('طرح ' . $i);

                    ProductVariant::create([
                        'product_id'    => $product->id,
                        'model_list_id' => null,
                        'variant_name'  => $product->name . ' ' . $designTitle,
                        'variety_name'  => $designTitle,
                        'variety_code'  => str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                        'variant_code'  => $variantCode,
                        'sell_price'    => $sellPrice,
                        'buy_price'     => $buyPrice,
                        'stock'         => 0,
                        'reserved'      => 0,
                    ]);
                }
            }

            // CASE 3: فقط مدل
            if ($useModels && !$useDesigns) {
                foreach ($models as $m) {
                    $model3 = $this->normalizeModel3($m->code);
                    $variantCode = $this->buildVariantCode11($productCode6, $model3, '00');

                    ProductVariant::create([
                        'product_id'    => $product->id,
                        'model_list_id' => $m->id,
                        'variant_name'  => $product->name . ' ' . $m->model_name,
                        'variety_name'  => '—',
                        'variety_code'  => '0000',
                        'variant_code'  => $variantCode,
                        'sell_price'    => $sellPrice,
                        'buy_price'     => $buyPrice,
                        'stock'         => 0,
                        'reserved'      => 0,
                    ]);
                }
            }

            // CASE 4: مدل + طرح
            if ($useModels && $useDesigns) {
                $d = (int) $data['design_count'];

                foreach ($models as $m) {
                    $model3 = $this->normalizeModel3($m->code);

                    for ($i = 1; $i <= $d; $i++) {
                        $design2 = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                        $variantCode = $this->buildVariantCode11($productCode6, $model3, $design2);

                        $designNote  = (string) ($designNotes->get($i - 1) ?? '');
                        $designTitle = $designNote !== '' ? ('طرح ' . $i . ' (' . $designNote . ')') : ('طرح ' . $i);

                        ProductVariant::create([
                            'product_id'    => $product->id,
                            'model_list_id' => $m->id,
                            'variant_name'  => $product->name . ' ' . $m->model_name . ' ' . $designTitle,
                            'variety_name'  => $designTitle,
                            'variety_code'  => str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                            'variant_code'  => $variantCode,
                            'sell_price'    => $sellPrice,
                            'buy_price'     => $buyPrice,
                            'stock'         => 0,
                            'reserved'      => 0,
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
        $modelListOptions = ModelList::query()
            ->orderBy('brand')
            ->orderBy('model_name')
            ->get(['id', 'brand', 'model_name', 'code']);

        return view('products.edit', compact('product', 'categories', 'modelListOptions'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name'        => ['required', 'string', 'max:255'],

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

            // کد محصول ثابت می‌ماند
            $product->update([
                'category_id' => (int) $data['category_id'],
                'name' => $data['name'],
            ]);

            $keepIds = [];

            foreach ($data['variants'] as $v) {
                $ignoreId = !empty($v['id']) ? (int) $v['id'] : null;

                $modelCode3 = '000';
                $modelListId = $v['model_list_id'] ?? null;

                if (!empty($modelListId)) {
                    $model = ModelList::findOrFail($modelListId);
                    $modelCode3 = $this->normalizeModel3($model->code);
                }

                // DD = آخرین 2 رقم variety_code
                $design2 = substr((string) $v['variety_code'], -2);
                if (!preg_match('/^\d{2}$/', $design2)) $design2 = '00';

                $variantCode = $this->buildVariantCode11((string) $product->code, $modelCode3, $design2, $ignoreId);

                $payload = [
                    'variant_name' => $v['variant_name'],
                    'model_list_id' => $modelListId,
                    'variety_name' => $v['variety_name'],
                    'variety_code' => $v['variety_code'],
                    'variant_code' => $variantCode,
                    'sell_price' => (int) $v['sell_price'],
                    'buy_price' => isset($v['buy_price']) ? (int) $v['buy_price'] : null,
                    'stock' => (int) $v['stock'],
                ];

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
                        'reserved' => 0,
                    ]));
                    $keepIds[] = $variant->id;
                }
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
            $q = trim((string) $request->q);
            $query->where(fn ($qq) => $qq->where('name', 'like', "%{$q}%")
                ->orWhere('code', 'like', "%{$q}%")
                ->orWhere('short_barcode', 'like', "%{$q}%"));
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

    // ---------------- Helpers ----------------

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

    private function normalizeCategory2(?string $code): ?string
    {
        $c = trim((string) ($code ?? ''));
        if ($c === '') return null;
        if (!preg_match('/^\d{2}$/', $c)) return null;
        return $c;
    }

    private function normalizeModel3(?string $code): string
    {
        $c = preg_replace('/\D+/', '', (string) ($code ?? ''));
        $c = substr($c, 0, 3);
        return str_pad($c, 3, '0', STR_PAD_LEFT);
    }

    /**
     * تولید PPPP واقعی (قفل داخل تراکنش)
     */
    private function nextProductSeq4(): string
    {
        $mx = DB::table('products')
            ->lockForUpdate()
            ->selectRaw("MAX(CAST(COALESCE(NULLIF(short_barcode,''), SUBSTRING(code, 3, 4)) AS UNSIGNED)) as mx")
            ->value('mx');

        $next = ((int) $mx) + 1;
        if ($next > 9999) abort(422, 'حداکثر 9999 کالا پشتیبانی می‌شود (PPPP چهار رقمی است).');

        return str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * ساخت کد 11 رقمی: CCPPPPMMMDD
     */
    private function buildVariantCode11(string $productCode6, string $model3, string $design2, ?int $ignoreId = null): string
    {
        $code = $productCode6 . $model3 . $design2;

        $exists = ProductVariant::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('variant_code', $code)
            ->exists();

        if ($exists) {
            abort(422, "کد تنوع تکراری است: {$code} (مدل/طرح تکراری انتخاب شده).");
        }

        return $code;
    }
}