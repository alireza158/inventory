<?php

namespace App\Http\Controllers;

use App\Models\ModelList;
use App\Models\ProductVariant;
use App\Support\PhoneModelCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModelListController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $modelLists = ModelList::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('model_name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('brand', 'like', "%{$q}%");
            })
            ->orderBy('brand')
            ->orderByRaw('CASE WHEN code IS NULL OR code = "" THEN 1 ELSE 0 END')
            ->orderByRaw('CAST(code AS UNSIGNED) ASC')
            ->orderBy('model_name')
            ->paginate(100)
            ->withQueryString();

        $brands = array_keys(PhoneModelCatalog::brands());

        return view('model-lists.index', compact('modelLists', 'brands', 'q'));
    }

    /**
     * افزودن مدل جدید
     * - code می‌تواند خالی باشد → خودکار ساخته می‌شود
     * - code چهار رقمی و یونیک
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'brand' => ['required', 'string', 'max:100'],
            'model_name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:4'],
        ]);

        DB::transaction(function () use ($data) {

            $brand = trim($data['brand']);
            $modelName = trim($data['model_name']);

            // اگر مدل با همین برند+نام وجود داشت، از تکرار جلوگیری کن
            $exists = ModelList::query()
                ->where('brand', $brand)
                ->where('model_name', $modelName)
                ->exists();

            if ($exists) {
                abort(422, 'این مدل قبلاً ثبت شده است.');
            }

            // کد چهار رقمی
            $code = $this->normalizeCode4($data['code'] ?? null);
            if ($code === null) {
                // اگر خالی بود، خودکار بساز
                $code = $this->nextCode4();
            } else {
                // اگر دستی وارد شد، نباید تکراری باشد
                if (ModelList::query()->where('code', $code)->exists()) {
                    abort(422, 'این کد قبلاً استفاده شده است.');
                }
            }

            ModelList::create([
                'brand' => $brand,
                'model_name' => $modelName,
                'code' => $code,
            ]);
        });

        return redirect()->route('model-lists.index')->with('success', 'مدل با موفقیت ذخیره شد.');
    }

    /**
     * بروزرسانی کد یک مدل
     */
    public function update(Request $request, ModelList $modelList): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:4'],
        ]);

        $code = $this->normalizeCode4($data['code']);
        if ($code === null) {
            return back()->withErrors(['code' => 'کد باید عددی و ۴ رقمی باشد (مثلاً 0016).']);
        }

        $exists = ModelList::query()
            ->where('id', '!=', $modelList->id)
            ->where('code', $code)
            ->exists();

        if ($exists) {
            return back()->withErrors(['code' => 'این کد قبلاً برای مدل دیگری ثبت شده است.']);
        }

        $modelList->update(['code' => $code]);

        return redirect()->route('model-lists.index')->with('success', 'کد مدل بروزرسانی شد.');
    }

    /**
     * تولید خودکار کد برای مدل‌هایی که کد ندارند
     */
    public function assignCodes(): RedirectResponse
    {
        DB::transaction(function () {

            // رکوردهای بدون کد
            $rows = ModelList::query()
                ->lockForUpdate()
                ->whereNull('code')
                ->orWhere('code', '')
                ->orderBy('id')
                ->get();

            if ($rows->isEmpty()) {
                return;
            }

            $next = $this->nextCode4();

            foreach ($rows as $row) {
                // اگر وسط کار تکراری شد، افزایش بده
                while (ModelList::query()->where('code', $next)->exists()) {
                    $next = $this->incrementCode4($next);
                }

                $row->update(['code' => $next]);
                $next = $this->incrementCode4($next);
            }
        });

        return redirect()->route('model-lists.index')->with('success', 'برای مدل‌های بدون کد، کد خودکار ساخته شد.');
    }

    /**
     * دریافت مدل‌ها از کالاهای موجود
     * - از variant_name استخراج می‌کند
     * - "طرح X" را حذف می‌کند
     * - برند را حدس می‌زند
     * - کد ۴ رقمی خودکار می‌دهد
     */
    public function importFromProducts(): RedirectResponse
    {
        DB::transaction(function () {

            $names = ProductVariant::query()
                ->select('variant_name')
                ->whereNotNull('variant_name')
                ->where('variant_name', '<>', '')
                ->distinct()
                ->pluck('variant_name');

            foreach ($names as $full) {
                $full = trim((string) $full);
                if ($full === '') continue;

                // حذف "طرح X"
                $base = preg_replace('/\s*طرح\s*\d+$/u', '', $full);
                $base = trim((string) $base);
                if ($base === '') continue;

                $brand = $this->detectBrand($base);

                $exists = ModelList::query()
                    ->where('brand', $brand)
                    ->where('model_name', $base)
                    ->exists();

                if ($exists) continue;

                $code = $this->nextCode4();
                while (ModelList::query()->where('code', $code)->exists()) {
                    $code = $this->incrementCode4($code);
                }

                ModelList::create([
                    'brand' => $brand,
                    'model_name' => $base,
                    'code' => $code,
                ]);
            }
        });

        return redirect()->route('model-lists.index')->with('success', 'مدل‌ها از کالاهای موجود دریافت و با کد خودکار ذخیره شدند.');
    }

    /**
     * ایمپورت بانک مدل‌ها از PhoneModelCatalog
     */
    public function importPhoneCatalog(): RedirectResponse
    {
        DB::transaction(function () {
            $catalog = PhoneModelCatalog::brands();

            foreach ($catalog as $brand => $models) {
                foreach ($models as $modelName) {
                    $normalizedName = trim((string) $modelName);
                    if ($normalizedName === '') continue;

                    $exists = ModelList::query()
                        ->where('brand', $brand)
                        ->where('model_name', $normalizedName)
                        ->exists();

                    if ($exists) continue;

                    $code = $this->nextCode4();
                    while (ModelList::query()->where('code', $code)->exists()) {
                        $code = $this->incrementCode4($code);
                    }

                    ModelList::create([
                        'brand' => $brand,
                        'model_name' => $normalizedName,
                        'code' => $code,
                    ]);
                }
            }
        });

        return back()->with('success', 'بانک مدل‌های موبایل با موفقیت بارگذاری شد.');
    }

    // ---------------- Helpers ----------------

    private function normalizeCode4(?string $code): ?string
    {
        $code = trim((string) $code);
        if ($code === '') return null;

        // فقط عدد 1 تا 4 رقم
        if (!preg_match('/^\d{1,4}$/', $code)) {
            return null;
        }

        return str_pad($code, 4, '0', STR_PAD_LEFT);
    }

    private function nextCode4(): string
    {
        $last = ModelList::query()
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->orderByRaw('CAST(code AS UNSIGNED) DESC')
            ->lockForUpdate()
            ->value('code');

        $n = $last ? (int) $last : 0;
        $n++;

        if ($n > 9999) {
            abort(422, 'بیش از 9999 مدل لیست ثبت شده. امکان تولید کد جدید نیست.');
        }

        return str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    private function incrementCode4(string $code): string
    {
        $n = (int) $code;
        $n++;

        if ($n > 9999) {
            abort(422, 'امکان تولید کد جدید نیست (بیش از 9999).');
        }

        return str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    private function detectBrand(string $modelName): string
    {
        $name = mb_strtolower($modelName);

        return match (true) {
            str_contains($name, 'iphone') || str_contains($name, 'اپل') => 'Apple (iPhone)',
            str_contains($name, 'samsung') || str_contains($name, 'سامسونگ') || str_contains($name, 'galaxy') => 'Samsung',
            str_contains($name, 'xiaomi') || str_contains($name, 'شیائومی') || str_contains($name, 'redmi') || str_contains($name, 'poco') => 'Xiaomi',
            str_contains($name, 'realme') || str_contains($name, 'ریلمی') => 'Realme',
            str_contains($name, 'huawei') || str_contains($name, 'هواوی') => 'Huawei',
            str_contains($name, 'honor') || str_contains($name, 'هانر') => 'Honor',
            default => 'سایر',
        };
    }
}