<?php

namespace App\Http\Controllers;

use App\Models\ModelList;
use App\Models\ProductVariant;
use App\Support\PhoneModelCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ModelListController extends Controller
{
    public function index()
    {
        $modelLists = ModelList::query()
            ->orderBy('brand')
            ->orderBy('model_name')
            ->paginate(100);

        $brands = array_keys(PhoneModelCatalog::brands());

        return view('model-lists.index', compact('modelLists', 'brands'));
    }

    public function store(Request $request): RedirectResponse
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

        return back()->with('success', 'مدل با موفقیت ذخیره شد.');
    }

    public function importFromProducts(): RedirectResponse
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
            });

        return back()->with('success', "تعداد {$count} مدل از کالاهای موجود به لیست مدل‌ها اضافه شد.");
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
