<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ModelList;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CrmProductSyncService;
use App\Services\WarehouseStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->with([
                'category',
                'variants.warehouseStocks.warehouse',
                'warehouseStocks.warehouse',
            ])
            ->withMin('variants', 'buy_price');

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

        if ($request->sellable_status === 'sellable') {
            $query->where('is_sellable', true);
        } elseif ($request->sellable_status === 'unsellable') {
            $query->where('is_sellable', false);
        }

        $sort = (string) $request->get('sort', 'id');
        $dir = strtolower((string) $request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'short_barcode' => 'short_barcode',
            'barcode' => 'barcode',
            'name' => 'name',
            'stock' => 'stock',
            'variants_buy_price_min' => 'variants_min_buy_price',
            'price' => 'price',
            'id' => 'id',
        ];

        $sortColumn = $allowedSorts[$sort] ?? 'id';

        $products = $query
            ->orderBy($sortColumn, $dir)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $categoryTree = Category::query()
            ->whereNull('parent_id')
            ->with(['children.children.children'])
            ->orderBy('name')
            ->get();

        return view('products.index', compact('products', 'categoryTree', 'sort', 'dir'));
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

    private function peekNextProductSeq4(): string
    {
        $next = $this->nextProductSeqNumber(false);
        if ($next > 9999) {
            $next = 9999;
        }

        return str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],

            'use_models' => ['nullable'],
            'use_designs' => ['nullable'],
            'model_brand_group' => ['nullable', 'string', 'max:100'],

            'model_list_ids' => ['nullable', 'array'],
            'model_list_ids.*' => ['integer', 'exists:model_lists,id'],

            'design_count' => ['nullable', 'integer', 'min:1', 'max:99'],
            'design_notes' => ['nullable', 'array'],
            'design_notes.*' => ['nullable', 'string', 'max:120'],

            'buy_price' => ['nullable', 'integer', 'min:0'],
            'sell_price' => ['nullable', 'integer', 'min:0'],
            'is_sellable' => ['nullable', 'boolean'],
        ]);

        $useModels = $request->boolean('use_models');
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

        $isSellable = $request->boolean('is_sellable', true);

        $imagePath = $this->storeProductImage($request);

        DB::transaction(function () use ($data, $useModels, $useDesigns, $designNotes, $isSellable, $imagePath) {
            $category = Category::query()->lockForUpdate()->findOrFail($data['category_id']);

            $cat2 = $this->normalizeCategory2($category->code);
            if ($cat2 === null) {
                abort(422, 'کد دسته‌بندی باید ۲ رقمی باشد (00 تا 99).');
            }

            $seq4 = $this->nextProductSeq4();
            $productCode6 = $cat2 . $seq4;

            $product = Product::create([
                'category_id' => $category->id,
                'name' => trim($data['name']),
                'image_path' => $imagePath,
                'sku' => 'AUTO-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'code' => $productCode6,
                'short_barcode' => $seq4,
                'stock' => 0,
                'price' => 0,
                'is_sellable' => $isSellable,
            ]);

            $sellPrice = (int) ($data['sell_price'] ?? 0);
            $buyPrice = isset($data['buy_price']) ? (int) $data['buy_price'] : null;

            $models = collect();
            if ($useModels) {
                $ids = array_values(array_map('intval', $data['model_list_ids']));
                $map = ModelList::query()
                    ->whereIn('id', $ids)
                    ->get(['id', 'model_name', 'code'])
                    ->keyBy('id');

                $models = collect($ids)->map(fn ($id) => $map->get($id))->filter()->values();
            }

            if (!$useModels && !$useDesigns) {
                $variantCode = $this->buildVariantCode11($productCode6, '000', '00');

                ProductVariant::create([
                    'product_id' => $product->id,
                    'model_list_id' => null,
                    'variant_name' => $product->name,
                    'variety_name' => '—',
                    'variety_code' => '0000',
                    'variant_code' => $variantCode,
                    'sell_price' => $sellPrice,
                    'buy_price' => $buyPrice,
                    'stock' => 0,
                    'reserved' => 0,
                ]);
            }

            if ($useDesigns && !$useModels) {
                $d = (int) $data['design_count'];

                for ($i = 1; $i <= $d; $i++) {
                    $design2 = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                    $variantCode = $this->buildVariantCode11($productCode6, '000', $design2);

                    $designNote = (string) ($designNotes->get($i - 1) ?? '');
                    $designTitle = $designNote !== '' ? ('طرح ' . $i . ' (' . $designNote . ')') : ('طرح ' . $i);

                    ProductVariant::create([
                        'product_id' => $product->id,
                        'model_list_id' => null,
                        'variant_name' => $product->name . ' ' . $designTitle,
                        'variety_name' => $designTitle,
                        'variety_code' => str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                        'variant_code' => $variantCode,
                        'sell_price' => $sellPrice,
                        'buy_price' => $buyPrice,
                        'stock' => 0,
                        'reserved' => 0,
                    ]);
                }
            }

            if ($useModels && !$useDesigns) {
                foreach ($models as $m) {
                    $model3 = $this->normalizeModel3($m->code);
                    $variantCode = $this->buildVariantCode11($productCode6, $model3, '00');

                    ProductVariant::create([
                        'product_id' => $product->id,
                        'model_list_id' => $m->id,
                        'variant_name' => $product->name . ' ' . $m->model_name,
                        'variety_name' => '—',
                        'variety_code' => '0000',
                        'variant_code' => $variantCode,
                        'sell_price' => $sellPrice,
                        'buy_price' => $buyPrice,
                        'stock' => 0,
                        'reserved' => 0,
                    ]);
                }
            }

            if ($useModels && $useDesigns) {
                $d = (int) $data['design_count'];

                foreach ($models as $m) {
                    $model3 = $this->normalizeModel3($m->code);

                    for ($i = 1; $i <= $d; $i++) {
                        $design2 = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                        $variantCode = $this->buildVariantCode11($productCode6, $model3, $design2);

                        $designNote = (string) ($designNotes->get($i - 1) ?? '');
                        $designTitle = $designNote !== '' ? ('طرح ' . $i . ' (' . $designNote . ')') : ('طرح ' . $i);

                        ProductVariant::create([
                            'product_id' => $product->id,
                            'model_list_id' => $m->id,
                            'variant_name' => $product->name . ' ' . $m->model_name . ' ' . $designTitle,
                            'variety_name' => $designTitle,
                            'variety_code' => str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                            'variant_code' => $variantCode,
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
        $product->load(['variants.warehouseStocks.warehouse']);

        $categories = Category::orderBy('name')->get();

        $modelListOptions = ModelList::query()
            ->orderBy('brand')
            ->orderBy('model_name')
            ->get(['id', 'brand', 'model_name', 'code']);

        $modelLists = $modelListOptions;
        $previewSeq4 = $product->short_barcode ?: substr((string) $product->code, 2, 4);

        return view('products.edit', compact('product', 'categories', 'modelListOptions', 'modelLists', 'previewSeq4'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'remove_image' => ['nullable', 'boolean'],
            'is_sellable' => ['nullable', 'boolean'],

            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.variant_name' => ['required', 'string', 'max:255'],
            'variants.*.model_list_id' => ['nullable', 'integer', 'exists:model_lists,id'],
            'variants.*.variety_name' => ['required', 'string', 'max:255'],
            'variants.*.variety_code' => ['required', 'digits:4'],
            'variants.*.sell_price' => ['required', 'integer', 'min:0'],
            'variants.*.buy_price' => ['nullable', 'integer', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'variants.*.variety_id' => ['nullable', 'integer', 'min:1'],
            'variant_site_ids' => ['nullable', 'array'],
            'variant_site_ids.*' => ['nullable', 'integer', 'min:1'],
            'warehouse_zone' => ['nullable', 'integer', 'min:1', 'max:7'],
            'warehouse_rows' => ['nullable', 'array'],
            'warehouse_rows.*' => ['integer', 'distinct', 'min:1', 'max:40'],
            'warehouse_bins' => ['nullable', 'array'],
            'warehouse_bins.*' => ['integer', 'distinct', 'min:1', 'max:10'],
        ]);

        $newImagePath = $this->storeProductImage($request);
        $removeImage = $request->boolean('remove_image');
        $oldImagePath = null;

        DB::transaction(function () use ($data, $product, $newImagePath, $removeImage, &$oldImagePath) {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);

            $productUpdate = [
                'category_id' => (int) $data['category_id'],
                'name' => $data['name'],
                'is_sellable' => (bool) ($data['is_sellable'] ?? false),
                'warehouse_zone' => isset($data['warehouse_zone']) ? (int) $data['warehouse_zone'] : null,
                'warehouse_rows' => array_values(array_map('intval', $data['warehouse_rows'] ?? [])),
                'warehouse_bins' => array_values(array_map('intval', $data['warehouse_bins'] ?? [])),
            ];

            if ($newImagePath || $removeImage) {
                $oldImagePath = $product->image_path;
                $productUpdate['image_path'] = $newImagePath;
            }

            $product->update($productUpdate);

            $keepIds = [];
            $centralWarehouseId = WarehouseStockService::centralWarehouseId();

            foreach ($data['variants'] as $v) {
                $ignoreId = !empty($v['id']) ? (int) $v['id'] : null;

                $modelCode3 = '000';
                $modelListId = $v['model_list_id'] ?? null;

                if (!empty($modelListId)) {
                    $model = ModelList::findOrFail($modelListId);
                    $modelCode3 = $this->normalizeModel3($model->code);
                }

                $design2 = substr((string) $v['variety_code'], -2);
                if (!preg_match('/^\d{2}$/', $design2)) {
                    $design2 = '00';
                }

                $variantCode = $this->buildVariantCode11((string) $product->code, $modelCode3, $design2, $ignoreId);

                $payload = [
                    'variant_name' => $v['variant_name'],
                    'model_list_id' => $modelListId,
                    'variety_name' => $v['variety_name'],
                    'variety_code' => $v['variety_code'],
                    'variant_code' => $variantCode,
                    'sell_price' => (int) $v['sell_price'],
                    'buy_price' => isset($v['buy_price']) ? (int) $v['buy_price'] : null,
                    'is_active' => (bool) ($v['is_active'] ?? true),
                    'variety_id' => isset($v['variety_id']) && $v['variety_id'] !== '' ? (int) $v['variety_id'] : null,
                ];

                $variant = null;

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
                        'stock' => 0,
                    ]));

                    $keepIds[] = $variant->id;
                }

                if ($variant) {
                    WarehouseStockService::set(
                        $centralWarehouseId,
                        (int) $product->id,
                        (int) $variant->id,
                        (int) $v['stock']
                    );
                }
            }

            ProductVariant::where('product_id', $product->id)
                ->when(count($keepIds) > 0, fn ($q) => $q->whereNotIn('id', $keepIds))
                ->delete();

            foreach (($data['variant_site_ids'] ?? []) as $variantId => $siteVariantId) {
                if ($siteVariantId === null || $siteVariantId === '') {
                    continue;
                }

                ProductVariant::where('product_id', $product->id)
                    ->where('id', (int) $variantId)
                    ->update(['variety_id' => (int) $siteVariantId]);
            }

            $this->recalcProductSummary($product);
        });

        if ($oldImagePath && ($newImagePath || $removeImage)) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return redirect()->route('products.index')->with('success', 'کالا بروزرسانی شد.');
    }

    public function destroy(Product $product)
    {
        $imagePath = $product->image_path;

        $product->delete();

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

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

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                "همگام‌سازی انجام شد. ایجاد: {$res['created']} | بروزرسانی: {$res['updated']} | خطا: {$res['failed']}"
            );
    }

    private function storeProductImage(Request $request): ?string
    {
        $file = $request->file('image');

        if (!$file) {
            return null;
        }

        if (is_array($file) || !$file instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'image' => 'فایل عکس کالا معتبر نیست؛ لطفاً فقط یک تصویر انتخاب کنید.',
            ]);
        }

        if (!$file->isValid()) {
            throw ValidationException::withMessages([
                'image' => $this->productImageUploadErrorMessage($file->getError()),
            ]);
        }

        $filePath = $file->getRealPath() ?: $file->getPathname();

        if (!is_string($filePath) || trim($filePath) === '' || !is_file($filePath)) {
            throw ValidationException::withMessages([
                'image' => 'مسیر فایل آپلودشده خالی است؛ لطفاً عکس را دوباره انتخاب و ارسال کنید.',
            ]);
        }

        if (($file->getSize() ?: 0) > 4096 * 1024) {
            throw ValidationException::withMessages([
                'image' => 'حجم عکس کالا نباید بیشتر از ۴ مگابایت باشد.',
            ]);
        }

        if (@getimagesize($filePath) === false) {
            throw ValidationException::withMessages([
                'image' => 'فایل انتخاب‌شده تصویر معتبر نیست.',
            ]);
        }

        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $extension = preg_replace('/[^a-z0-9]+/', '', $extension) ?: 'jpg';
        $filename = now()->format('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $storedPath = 'products/' . $filename;
        $stream = @fopen($filePath, 'rb');

        if ($stream === false) {
            throw ValidationException::withMessages([
                'image' => 'خواندن فایل آپلودشده ناموفق بود؛ لطفاً عکس را دوباره انتخاب و ارسال کنید.',
            ]);
        }

        try {
            $stored = Storage::disk('public')->put($storedPath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (!$stored) {
            throw ValidationException::withMessages([
                'image' => 'ذخیره عکس کالا ناموفق بود؛ لطفاً دوباره تلاش کنید.',
            ]);
        }

        return $storedPath;
    }

    private function productImageUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'حجم عکس کالا بیش از حد مجاز سرور است؛ لطفاً عکس کوچک‌تری انتخاب کنید.',
            UPLOAD_ERR_PARTIAL => 'آپلود عکس کامل انجام نشد؛ لطفاً دوباره تلاش کنید.',
            UPLOAD_ERR_NO_FILE => 'هیچ عکسی برای کالا انتخاب نشده است.',
            UPLOAD_ERR_NO_TMP_DIR => 'پوشه موقت آپلود روی سرور در دسترس نیست.',
            UPLOAD_ERR_CANT_WRITE => 'امکان نوشتن فایل آپلودشده روی سرور وجود ندارد.',
            UPLOAD_ERR_EXTENSION => 'آپلود عکس توسط تنظیمات سرور متوقف شد.',
            default => 'فایل عکس کالا معتبر نیست؛ لطفاً یک تصویر سالم با حجم حداکثر ۴ مگابایت انتخاب کنید.',
        };
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
            'price' => max(0, (int) ($product->variants->where('is_active', true)->min('sell_price') ?? 0)),
        ]);
    }

    private function normalizeCategory2(?string $code): ?string
    {
        $c = trim((string) ($code ?? ''));
        if ($c === '') {
            return null;
        }
        if (!preg_match('/^\d{2}$/', $c)) {
            return null;
        }

        return $c;
    }

    private function normalizeModel3(?string $code): string
    {
        $c = preg_replace('/\D+/', '', (string) ($code ?? ''));
        $c = substr($c, 0, 3);

        return str_pad($c, 3, '0', STR_PAD_LEFT);
    }

    private function nextProductSeq4(): string
    {
        $next = $this->nextProductSeqNumber(true);
        if ($next > 9999) {
            abort(422, 'حداکثر 9999 کالا پشتیبانی می‌شود (PPPP چهار رقمی است).');
        }

        return str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function nextProductSeqNumber(bool $lockProducts): int
    {
        $productQuery = DB::table('products')
            ->selectRaw("MAX(CAST(COALESCE(NULLIF(short_barcode,''), SUBSTRING(code, 3, 4)) AS UNSIGNED)) as mx");

        if ($lockProducts) {
            $productQuery->lockForUpdate();
        }

        $productMax = (int) $productQuery->value('mx');

        $variantQuery = DB::table('product_variants')
            ->whereNotNull('variant_code')
            ->where('variant_code', '<>', '')
            ->selectRaw("MAX(CAST(SUBSTRING(variant_code, 3, 4) AS UNSIGNED)) as mx");

        if ($lockProducts) {
            $variantQuery->lockForUpdate();
        }

        $variantMax = (int) $variantQuery->value('mx');
        $next = max($productMax, $variantMax) + 1;

        return max(1, $next);
    }

    private function buildVariantCode11(string $productCode6, string $model3, string $design2, ?int $ignoreId = null): string
    {
        $code = $productCode6 . $model3 . $design2;

        $exists = ProductVariant::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('variant_code', $code)
            ->exists();

        if ($exists) {
            $existingVariant = ProductVariant::query()
                ->with('product:id,name,code')
                ->where('variant_code', $code)
                ->first();
            $productName = $existingVariant?->product?->name ?: 'کالای دیگر';
            $productCode = $existingVariant?->product?->code ?: '---';

            abort(422, "کد تنوع تکراری است: {$code}. این کد قبلاً برای «{$productName}» با کد کالا {$productCode} ثبت شده است؛ لطفاً دوباره ثبت را بزنید تا کد جدید ساخته شود.");
        }

        return $code;
    }
}
