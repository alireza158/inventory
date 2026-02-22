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
            ->orderBy('brand')
            ->orderBy('model_name')
            ->paginate(100);

        $brands = array_keys(PhoneModelCatalog::brands());

        return view('model-lists.index', compact('modelLists', 'brands'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'brand' => ['required', 'string', 'max:100'],
            'model_name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'digits:3', 'unique:model_lists,code'],
        ]);

        ModelList::updateOrCreate([
            'brand' => trim($data['brand']),
            'model_name' => trim($data['model_name']),
        ], [
            'code' => $data['code'],
        ]);

        $code = $this->normalizeCode4($data['code']);
        if ($code === null) {
            return back()->withErrors(['code' => 'کد باید عددی و ۴ رقمی باشد (مثلاً 0016).']);
        }

        if (ModelList::query()->where('id', '!=', $modelList->id)->where('code', $code)->exists()) {
            return back()->withErrors(['code' => 'این کد قبلاً برای مدل دیگری ثبت شده است.']);
        }

        $modelList->update(['code' => $code]);

        return redirect()->route('model-lists.index')->with('success', 'کد مدل بروزرسانی شد.');
    }

    /**
     * تولید خودکار کد برای مدل‌هایی که کد ندارند
     */
    public function assignCodes()
    {
        $count = 0;
        $usedCodes = ModelList::query()->whereNotNull('code')->pluck('code')->all();

        ProductVariant::query()
            ->select('variant_name')
            ->whereNotNull('variant_name')
            ->where('variant_name', '<>', '')
            ->groupBy('variant_name')
            ->orderBy('variant_name')
            ->chunk(500, function ($variants) use (&$count, &$usedCodes) {
                foreach ($variants as $variant) {
                    $modelName = trim((string) $variant->variant_name);
                    if ($modelName === '') {
                        continue;
                    }

                    $brand = $this->detectBrand($modelName);
                    $code = $this->nextThreeDigitCode($usedCodes);

                    $created = ModelList::firstOrCreate([
                        'brand' => $brand,
                        'model_name' => $modelName,
                    ], [
                        'code' => $code,
                    ]);

                    if ($created->wasRecentlyCreated) {
                        $usedCodes[] = $code;
                        $count++;
                    }
                }

                $row->update(['code' => $next]);
                $next = $this->incrementCode4($next);
            }
        });

        return redirect()->route('model-lists.index')->with('success', 'برای مدل‌های بدون کد، کد خودکار ساخته شد.');
    }

    /**
     * اگر هنوز از این دکمه استفاده می‌کنی:
     * مدل‌ها را از variant_name استخراج می‌کند (قبل از "طرح X")
     */
    public function importFromProducts()
    {
        DB::transaction(function () {
            $names = ProductVariant::query()
                ->select('variant_name')
                ->distinct()
                ->pluck('variant_name');

            foreach ($names as $full) {
                $base = preg_replace('/\s*طرح\s*\d+$/u', '', (string) $full);
                $base = trim((string) $base);
                if ($base === '') continue;

                $exists = ModelList::query()->where('model_name', $base)->exists();
                if ($exists) continue;

                $code = $this->nextCode4();
                while (ModelList::query()->where('code', $code)->exists()) {
                    $code = $this->incrementCode4($code);
                }

                ModelList::create([
                    'model_name' => $base,
                    'code' => $code,
                ]);
            }
        });

        return redirect()->route('model-lists.index')->with('success', 'مدل‌ها از کالاهای موجود دریافت و با کد خودکار ذخیره شدند.');
    }

    // ---------------- Helpers ----------------

    private function normalizeCode4(?string $code): ?string
    {
        $code = trim((string) $code);
        if ($code === '') return null;

        // فقط عدد
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

    public function importPhoneCatalog(): RedirectResponse
    {
        $catalog = PhoneModelCatalog::brands();
        $usedCodes = ModelList::query()->whereNotNull('code')->pluck('code')->all();
        $inserted = 0;

        foreach ($catalog as $brand => $models) {
            foreach ($models as $modelName) {
                $normalizedName = trim((string) $modelName);
                if ($normalizedName === '') {
                    continue;
                }

                $exists = ModelList::query()
                    ->where('brand', $brand)
                    ->where('model_name', $normalizedName)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $code = $this->nextThreeDigitCode($usedCodes);

                ModelList::create([
                    'brand' => $brand,
                    'model_name' => $normalizedName,
                    'code' => $code,
                ]);

                $usedCodes[] = $code;
                $inserted++;
            }
        }

        return back()->with('success', "بانک مدل‌های موبایل با موفقیت بارگذاری شد. تعداد {$inserted} مدل جدید اضافه شد.");
    }

    private function nextThreeDigitCode(array $usedCodes): string
    {
        $lookup = array_flip(array_map('strval', $usedCodes));

        for ($i = 1; $i <= 999; $i++) {
            $code = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            if (!isset($lookup[$code])) {
                return $code;
            }
        }

        abort(422, 'کد سه‌رقمی خالی برای مدل لیست موجود نیست.');
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
