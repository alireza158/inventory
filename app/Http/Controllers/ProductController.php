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

        $modelLists = ModelList::query()
            ->whereNotNull('code')
            ->orderBy('model_name')
            ->get(['id', 'model_name', 'code']);

        return view('products.index', compact('products', 'categoryTree', 'categories', 'modelLists'));
    }

    public function create()
    {
        $categories = Category::query()->orderBy('name')->get();

        $modelLists = ModelList::query()
            ->whereNotNull('code')
            ->orderBy('model_name')
            ->get(['id', 'model_name', 'code']);

        return view('products.create', compact('categories', 'modelLists'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],

            'model_list_ids' => ['required', 'array', 'min:1'],
            'model_list_ids.*' => ['integer', 'exists:model_lists,id'],

            // تعداد طرح برای هر مدل
            'design_count' => ['required', 'integer', 'min:1', 'max:500'],

            // قیمت‌های اولیه اختیاری (برای تنوع‌ها)
            'buy_price' => ['nullable', 'integer', 'min:0'],
            'sell_price' => ['nullable', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {

            // قفل کردن دسته‌بندی برای جلوگیری از تولید کد تکراری همزمان
            $category = Category::query()->lockForUpdate()->findOrFail($data['category_id']);

            // استخراج کد 3 رقمی دسته‌بندی
            $catCode = $this->normalizeCategory3($category->code);
            if ($catCode === null) {
                abort(422, 'برای این دسته‌بندی «کد عددی» تعریف نشده یا معتبر نیست. (حداقل 3 رقم لازم است)');
            }

            $modelIds = array_values(array_map('intval', $data['model_list_ids']));
            $modelCount = count($modelIds);
            $designCount = (int) $data['design_count'];

            $totalVariants = $modelCount * $designCount;
            if ($totalVariants > 9999) {
                abort(422, 'تعداد کل تنوع‌ها بیش از حد مجاز است (حداکثر 9999 تنوع برای هر کالا). تعداد مدل‌ها یا تعداد طرح را کمتر کنید.');
            }

            // تولید کد کالا: CCC + PPPPP (8 رقم)
            $productCode = $this->generateProductCode8($category->id, $catCode);

            $product = Product::create([
                'category_id' => $category->id,
                'name' => trim($data['name']),
                'sku' => 'AUTO-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'code' => $productCode,
                'stock' => 0,
                'price' => 0,
            ]);

            // مدل لیست‌ها را یکجا بگیر (و ترتیب انتخاب کاربر را حفظ کن)
            $modelLists = ModelList::query()
                ->whereIn('id', $modelIds)
                ->get(['id', 'model_name'])
                ->keyBy('id');

            $orderedModels = collect($modelIds)
                ->map(fn ($id) => $modelLists->get($id))
                ->filter()
                ->values();

            // ساخت تنوع‌ها
            $variantSeq = 0; // VVVV از 0001
            foreach ($orderedModels as $model) {
                for ($i = 1; $i <= $designCount; $i++) {

                    $variantSeq++;
                    $suffix = str_pad((string) $variantSeq, 4, '0', STR_PAD_LEFT); // VVVV
                    $variantCode = $this->generateVariantCode12($productCode, $suffix);

                    $varietyName = 'طرح ' . $i;
                    $varietyCode = str_pad((string) $i, 4, '0', STR_PAD_LEFT); // صرفاً برای نمایش/مرتب‌سازی

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

        return redirect()->route('products.index')->with('success', 'کالا و تنوع‌ها با موفقیت ساخته شدند.');
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

            // قفل محصول و دسته‌بندی برای ریسک همزمانی کمتر
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $category = Category::query()->findOrFail($data['category_id']);

            // نکته: کد کالا را ثابت نگه می‌داریم (برای اینکه بارکد/کدهای چاپ شده بهم نریزه)
            // فقط اگر خالی بود تولید می‌کنیم
            if (!$product->code) {
                $catCode = $this->normalizeCategory3($category->code);
                if ($catCode === null) {
                    abort(422, 'برای این دسته‌بندی «کد عددی» تعریف نشده یا معتبر نیست. (حداقل 3 رقم لازم است)');
                }
                $product->code = $this->generateProductCode8($category->id, $catCode);
            }

            $product->update([
                'category_id' => $category->id,
                'name' => $data['name'],
                'code' => $product->code,
            ]);

            // برای ساخت تنوع‌های جدید، از بیشترین suffix موجود شروع می‌کنیم
            $nextSuffixInt = $this->getNextVariantSuffixInt($product);

            $keepIds = [];

            foreach ($data['variants'] as $v) {
                $model = ModelList::findOrFail($v['model_list_id']);

                if (!empty($v['id'])) {
                    $variant = ProductVariant::where('product_id', $product->id)->where('id', $v['id'])->first();
                    if ($variant) {
                        // کد تنوع را ثابت نگه می‌داریم (فقط اگر خالی باشد تولید می‌کنیم)
                        $variantCode = $variant->variant_code;
                        if (!$variantCode) {
                            $suffix = str_pad((string) $nextSuffixInt, 4, '0', STR_PAD_LEFT);
                            $variantCode = $this->generateVariantCode12($product->code, $suffix, $variant->id);
                            $nextSuffixInt++;
                        }

                        $variant->update([
                            'variant_name' => $v['variant_name'],
                            'model_list_id' => $model->id,
                            'variety_name' => $v['variety_name'],
                            'variety_code' => $v['variety_code'],
                            'variant_code' => $variantCode,
                            'sku' => $variantCode,
                            'sell_price' => (int) $v['sell_price'],
                            'buy_price' => isset($v['buy_price']) ? (int) $v['buy_price'] : null,
                            'stock' => (int) $v['stock'],
                        ]);

                        $keepIds[] = $variant->id;
                    }
                } else {
                    // تنوع جدید
                    $suffix = str_pad((string) $nextSuffixInt, 4, '0', STR_PAD_LEFT);
                    $variantCode = $this->generateVariantCode12($product->code, $suffix);
                    $nextSuffixInt++;

                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'reserved' => 0,

                        'variant_name' => $v['variant_name'],
                        'model_list_id' => $model->id,
                        'variety_name' => $v['variety_name'],
                        'variety_code' => $v['variety_code'],

                        'variant_code' => $variantCode,
                        'sku' => $variantCode,

                        'sell_price' => (int) $v['sell_price'],
                        'buy_price' => isset($v['buy_price']) ? (int) $v['buy_price'] : null,
                        'stock' => (int) $v['stock'],
                    ]);

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

    /**
     * تبدیل کد دسته‌بندی به 3 رقم (CCC)
     */
    private function normalizeCategory3(?string $code): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) ($code ?? ''));
        if ($digits === '') {
            return null;
        }
        // اگر بیشتر از 3 رقم بود، 3 رقم اول را برمی‌داریم
        $digits = substr($digits, 0, 3);

        if (strlen($digits) < 3) {
            $digits = str_pad($digits, 3, '0', STR_PAD_LEFT);
        }

        return $digits;
    }

    /**
     * تولید کد کالا 8 رقمی: CCC + PPPPP
     * - CCC از کد دسته‌بندی
     * - PPPPP شماره ترتیبی کالا در همان دسته
     */
    private function generateProductCode8(int $categoryId, string $catCode3): string
    {
        // آخرین کد این دسته
        $last = Product::query()
            ->where('category_id', $categoryId)
            ->whereNotNull('code')
            ->where('code', 'like', $catCode3 . '%')
            ->orderByDesc('code')
            ->lockForUpdate()
            ->first();

        $lastSeq = 0;
        if ($last && strlen((string)$last->code) >= 8) {
            $lastSeq = (int) substr((string)$last->code, 3, 5);
        }

        $nextSeq = $lastSeq + 1;

        return $catCode3 . str_pad((string)$nextSeq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * تولید کد تنوع 12 رقمی: (کد کالا 8) + (suffix 4)
     * suffix = VVVV
     * تضمین می‌کند variant_code یکتا باشد.
     */
    private function generateVariantCode12(string $productCode8, string $suffix4, ?int $ignoreId = null): string
    {
        $base = $productCode8 . $suffix4; // 12 digit
        $code = $base;

        $counter = (int) $suffix4;

        while (ProductVariant::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('variant_code', $code)
            ->exists()) {

            $counter++;
            if ($counter > 9999) {
                abort(422, 'امکان تولید کد یکتا برای تنوع وجود ندارد (بیش از 9999 تنوع).');
            }

            $code = $productCode8 . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
        }

        return $code;
    }

    /**
     * پیدا کردن suffix بعدی برای ساخت تنوع جدید (بر اساس بزرگترین VVVV موجود)
     */
    private function getNextVariantSuffixInt(Product $product): int
    {
        $product->loadMissing('variants');

        $max = 0;
        foreach ($product->variants as $v) {
            $c = (string) ($v->variant_code ?? '');
            if (strlen($c) >= 12 && str_starts_with($c, (string)$product->code)) {
                $suffix = (int) substr($c, -4);
                if ($suffix > $max) $max = $suffix;
            }
        }

        return $max + 1;
    }
}