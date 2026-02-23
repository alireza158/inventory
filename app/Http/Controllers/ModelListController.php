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
    /**
     * گروه‌بندی برندها برای UI آکاردئون + انتخاب برند
     * نکته: ما یک "برند گروهی" ذخیره می‌کنیم تا دقیقاً با دسته‌بندی‌ها یکی باشد.
     */
    private function brandGroups(): array
    {
        return [
            'Samsung' => [
                'title' => 'سامسونگ',
                'values' => ['Samsung', 'سامسونگ'],
            ],
            'XiaomiRealme' => [
                'title' => 'شیائومی و ریلمی',
                'values' => ['Xiaomi/Realme', 'Xiaomi', 'Realme', 'شیائومی', 'ریلمی'],
            ],
            'Apple' => [
                'title' => 'آیفون',
                'values' => ['Apple (iPhone)', 'Apple', 'iPhone', 'آیفون', 'اپل'],
            ],
            'HuaweiHonor' => [
                'title' => 'هواوی و هانر',
                'values' => ['Huawei/Honor', 'Huawei', 'Honor', 'هواوی', 'هانر'],
            ],
            'Other' => [
                'title' => 'سایر',
                'values' => ['سایر', 'Other', ''],
            ],
        ];
    }

    /**
     * گزینه‌های انتخاب برند (دقیقاً مطابق آکاردئون)
     */
    private function brandSelectOptions(): array
    {
        return [
            'Samsung' => 'سامسونگ',
            'Xiaomi/Realme' => 'شیائومی و ریلمی',
            'Apple (iPhone)' => 'آیفون',
            'Huawei/Honor' => 'هواوی و هانر',
            'سایر' => 'سایر',
        ];
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $all = ModelList::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('model_name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('brand', 'like', "%{$q}%");
            })
            ->orderByRaw('CASE WHEN code IS NULL OR code = "" THEN 1 ELSE 0 END')
            ->orderByRaw('CAST(code AS UNSIGNED) ASC')
            ->orderBy('brand')
            ->orderBy('model_name')
            ->get();

        // گروه‌بندی آیتم‌ها برای آکاردئون
        $groups = [];
        foreach ($this->brandGroups() as $key => $cfg) {
            $groups[$key] = [
                'title' => $cfg['title'],
                'items' => $all->filter(function ($row) use ($cfg) {
                    $b = trim((string) ($row->brand ?? ''));
                    if ($b === '') $b = 'سایر';
                    return in_array($b, $cfg['values'], true);
                })->values(),
            ];
        }

        return view('model-lists.index', [
            'q' => $q,
            'groups' => $groups,
            'brandOptions' => $this->brandSelectOptions(),
        ]);
    }

    /**
     * افزودن مدل جدید
     * - کد ۳ رقمی خودکار و ترتیبی ساخته می‌شود
     * - کاربر کد وارد نمی‌کند
     * - model_name در دیتابیس UNIQUE است → فقط یکبار ثبت می‌شود
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'brand' => ['required', 'string', 'max:100'],
            'model_name' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data) {
            $brand = trim((string) $data['brand']);
            $modelName = trim((string) $data['model_name']);

            // چون model_name یونیک است:
            $exists = ModelList::query()->where('model_name', $modelName)->lockForUpdate()->first();
            if ($exists) {
                abort(422, 'این مدل قبلاً ثبت شده است.');
            }

            // کد ۳ رقمی ترتیبی
            $code = $this->nextCode3();
            ModelList::create([
                'brand' => $brand,
                'model_name' => $modelName,
                'code' => $code,
            ]);
        });

        return redirect()->route('model-lists.index')->with('success', 'مدل با موفقیت ذخیره شد.');
    }

    /**
     * ویرایش کد مدل (فقط ۳ رقم)
     */
    public function update(Request $request, ModelList $modelList): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:3'],
        ]);

        $code = $this->normalizeCode3Strict($data['code']);
        if ($code === null) {
            return back()->withErrors(['code' => 'کد باید دقیقاً ۳ رقمی باشد (مثلاً 016 یا 001).']);
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
     * حذف مدل
     */
    public function destroy(ModelList $modelList): RedirectResponse
    {
        $modelList->delete();
        return redirect()->route('model-lists.index')->with('success', 'مدل حذف شد.');
    }

    /**
     * ✅ اصلاح کدها:
     * - هر مدلی که کد ندارد یا کدش ۳ رقمی نیست یا کد تکراری دارد → کد ۳ رقمی جدید می‌گیرد
     * - این همون چیزی است که گفتی: بعضی‌ها ۴ رقمی شدن و باید درست شوند
     */
    public function assignCodes(): RedirectResponse
    {
        DB::transaction(function () {

            $rows = ModelList::query()
                ->lockForUpdate()
                ->orderBy('id')
                ->get();

            // مجموعه کدهای معتبر و یکتا
            $used = [];

            // اول: کدهای صحیح و یکتا را نگه می‌داریم
            foreach ($rows as $row) {
                $c = $this->normalizeCode3Strict($row->code);
                if ($c === null) continue; // نامعتبر
                if (isset($used[$c])) continue; // تکراری
                $used[$c] = true;
            }

            // نقطه شروع تولید: max+1 (ترتیبی)
            $nextInt = $this->maxUsedInt($used) + 1;
            if ($nextInt < 1) $nextInt = 1;

            // دوم: هر چیزی که نیاز به اصلاح دارد را دوباره کد می‌دهیم
            foreach ($rows as $row) {
                $current = $this->normalizeCode3Strict($row->code);

                $needsFix = false;

                // کد خالی/نامعتبر
                if ($current === null) $needsFix = true;
                // کد معتبر ولی اگر چند رکورد تکراری بوده، یکی باید اصلاح شود
                if ($current !== null) {
                    // اگر این کد در used هست، ولی ممکنه این رکورد یکی از تکراری‌ها باشد.
                    // برای تشخیص ساده: اگر بیش از یک رکورد همین کد دارد، رکوردهای بعدی اصلاح می‌شوند.
                    $dupCount = ModelList::query()->where('code', $current)->count();
                    if ($dupCount > 1) {
                        // اولین را نگه می‌داریم: با id کم‌تر
                        $firstId = ModelList::query()->where('code', $current)->min('id');
                        if ($row->id != $firstId) $needsFix = true;
                    }
                }

                if (!$needsFix) continue;

                // تولید کد ۳ رقمی جدید و یکتا
                $new = $this->nextFreeFrom($used, $nextInt);
                $row->update(['code' => $new]);
                $used[$new] = true;

                $nextInt = (int)$new + 1;
            }
        });

        return redirect()->route('model-lists.index')->with('success', 'کدهای مدل‌لیست اصلاح و ۳ رقمی شدند.');
    }

    /**
     * دریافت مدل‌ها از کالاهای موجود
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

                $base = preg_replace('/\s*طرح\s*\d+$/u', '', $full);
                $base = trim((string) $base);
                if ($base === '') continue;

                // چون model_name یونیک است:
                $exists = ModelList::query()->where('model_name', $base)->exists();
                if ($exists) continue;

                $brand = $this->detectBrand($base);
                $code = $this->nextCode3();

                ModelList::create([
                    'brand' => $brand,
                    'model_name' => $base,
                    'code' => $code,
                ]);
            }
        });

        return redirect()->route('model-lists.index')->with('success', 'مدل‌ها از کالاهای موجود دریافت و با کد ۳ رقمی ذخیره شدند.');
    }

    /**
     * (اگر هنوز route اش را داری) بارگذاری بانک کامل مدل‌ها
     * - چون model_name یونیک است: اگر وجود داشت skip
     * - brand را به گروه‌های جدید تبدیل می‌کنیم
     */
    public function importPhoneCatalog(): RedirectResponse
    {
        DB::transaction(function () {
            $catalog = PhoneModelCatalog::brands();

            foreach ($catalog as $brand => $models) {
                foreach ($models as $modelName) {
                    $normalizedName = trim((string) $modelName);
                    if ($normalizedName === '') continue;

                    $existing = ModelList::query()->where('model_name', $normalizedName)->lockForUpdate()->first();
                    if ($existing) {
                        // اگر برندش خالی/سایر بود بهترش کن
                        if (!$existing->brand || $existing->brand === 'سایر') {
                            $existing->update(['brand' => $brand]);
                        }
                        // اگر کدش نامعتبر بود بعداً با assignCodes اصلاح می‌شود
                        continue;
                    }

                    $code = $this->nextCode3();

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

    /**
     * فقط ۳ رقم را قبول می‌کند (۱ تا ۳ رقم هم می‌گیرد و تبدیل به ۳ رقمی می‌کند)
     * اما اگر ۴ رقمی/حروفی باشد → null
     */
    private function normalizeCode3Strict(?string $code): ?string
    {
        $code = trim((string) ($code ?? ''));
        if ($code === '') return null;

        // فقط 1 تا 3 رقم
        if (!preg_match('/^\d{1,3}$/', $code)) {
            return null;
        }

        return str_pad($code, 3, '0', STR_PAD_LEFT);
    }

    /**
     * تولید کد ۳ رقمی ترتیبی:
     * - از max کدهای معتبر + 1 شروع می‌کند
     * - اگر تکراری بود، جلو می‌رود
     */
    private function nextCode3(): string
    {
        $codes = ModelList::query()->whereNotNull('code')->pluck('code')->all();

        $max = 0;
        $used = [];
        foreach ($codes as $c) {
            $n = $this->normalizeCode3Strict($c);
            if ($n === null) continue;
            $used[$n] = true;
            $iv = (int)$n;
            if ($iv > $max) $max = $iv;
        }

        $start = $max + 1;
        if ($start < 1) $start = 1;

        return $this->nextFreeFrom($used, $start);
    }

    private function nextFreeFrom(array $used, int $start): string
    {
        for ($i = $start; $i <= 999; $i++) {
            $c = str_pad((string)$i, 3, '0', STR_PAD_LEFT);
            if (!isset($used[$c])) return $c;
        }
        // اگر از start تا 999 پر بود، از 001 جستجو کن
        for ($i = 1; $i <= 999; $i++) {
            $c = str_pad((string)$i, 3, '0', STR_PAD_LEFT);
            if (!isset($used[$c])) return $c;
        }

        abort(422, 'کد ۳ رقمی خالی برای مدل لیست موجود نیست.');
    }

    private function maxUsedInt(array $used): int
    {
        $max = 0;
        foreach ($used as $code => $true) {
            $iv = (int)$code;
            if ($iv > $max) $max = $iv;
        }
        return $max;
    }

    /**
     * تشخیص برند برای importFromProducts
     * خروجی‌ها را به "گروه‌های جدید" برمی‌گردانیم
     */
    private function detectBrand(string $modelName): string
    {
        $name = mb_strtolower($modelName);

        return match (true) {
            str_contains($name, 'iphone') || str_contains($name, 'اپل') => 'Apple (iPhone)',
            str_contains($name, 'samsung') || str_contains($name, 'سامسونگ') || str_contains($name, 'galaxy') => 'Samsung',
            str_contains($name, 'xiaomi') || str_contains($name, 'شیائومی') || str_contains($name, 'redmi') || str_contains($name, 'poco') => 'Xiaomi/Realme',
            str_contains($name, 'realme') || str_contains($name, 'ریلمی') => 'Xiaomi/Realme',
            str_contains($name, 'huawei') || str_contains($name, 'هواوی') => 'Huawei/Honor',
            str_contains($name, 'honor') || str_contains($name, 'هانر') => 'Huawei/Honor',
            default => 'سایر',
        };
    }
}