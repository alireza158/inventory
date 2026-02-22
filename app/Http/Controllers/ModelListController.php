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
            'code' => ['required', 'digits:3'],
        ]);

        $normalizedName = trim($data['model_name']);
        $brand = trim($data['brand']);

        $existingByCode = ModelList::query()->where('code', $data['code'])->first();
        if ($existingByCode && trim((string) $existingByCode->model_name) !== $normalizedName) {
            return back()->withErrors(['code' => 'این کد قبلاً برای مدل دیگری ثبت شده است.'])->withInput();
        }

        $record = ModelList::query()->where('model_name', $normalizedName)->first();
        if ($record) {
            $record->update([
                'brand' => $brand,
                'code' => $data['code'],
            ]);
        } else {
            ModelList::create([
                'brand' => $brand,
                'model_name' => $normalizedName,
                'code' => $data['code'],
            ]);
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
        $usedCodes = $this->usedCodeNumbers();

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
                    $record = ModelList::query()->where('model_name', $modelName)->first();

                    if ($record) {
                        if (!$record->brand) {
                            $record->update(['brand' => $brand]);
                        }
                        continue;
                    }

                    $code = $this->nextThreeDigitCode($usedCodes);

                    ModelList::create([
                        'brand' => $brand,
                        'model_name' => $modelName,
                        'code' => $code,
                    ]);

                    $usedCodes[] = (int) $code;
                    $count++;
                }

                $code = $this->nextThreeDigitCode($usedCodes);

                ModelList::create([
                    'brand' => $brand,
                    'model_name' => $normalizedName,
                    'code' => $code,
                ]);

                $usedCodes[] = (int) $code;
                $inserted++;
            }
        }

        return back()->with('success', "بانک مدل‌های موبایل همگام‌سازی شد. جدید: {$inserted} | بروزرسانی: {$updated}");
    }

    private function usedCodeNumbers(): array
    {
        return ModelList::query()
            ->whereNotNull('code')
            ->pluck('code')
            ->map(fn ($code) => (int) preg_replace('/\D/', '', (string) $code))
            ->filter(fn ($num) => $num > 0 && $num <= 999)
            ->unique()
            ->values()
            ->all();
    }

    private function nextThreeDigitCode(array $usedCodeNumbers): string
    {
        $lookup = array_flip($usedCodeNumbers);

        for ($i = 1; $i <= 999; $i++) {
            if (!isset($lookup[$i])) {
                return str_pad((string) $i, 3, '0', STR_PAD_LEFT);
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
            str_contains($name, 'xiaomi') || str_contains($name, 'شیائومی') || str_contains($name, 'redmi') || str_contains($name, 'poco') || str_contains($name, 'realme') || str_contains($name, 'ریلمی') || str_contains($name, 'rmx') => 'Xiaomi / Realme',
            str_contains($name, 'huawei') || str_contains($name, 'هواوی') || str_contains($name, 'honor') || str_contains($name, 'هانر') => 'Huawei / Honor',
            default => 'سایر',
        };
    }

    public function importPhoneCatalog(): RedirectResponse
    {
        $catalog = PhoneModelCatalog::brands();
        $usedCodes = $this->usedCodeNumbers();
        $inserted = 0;
        $updated = 0;

        foreach ($catalog as $brand => $models) {
            foreach ($models as $modelName) {
                $normalizedName = trim((string) $modelName);
                if ($normalizedName === '') {
                    continue;
                }

                $record = ModelList::query()->where('model_name', $normalizedName)->first();

                if ($record) {
                    $payload = ['brand' => $brand];
                    if (!$record->code || !preg_match('/^\d{3}$/', (string) $record->code)) {
                        $code = $this->nextThreeDigitCode($usedCodes);
                        $payload['code'] = $code;
                        $usedCodes[] = (int) $code;
                    }
                    $record->update($payload);
                    $updated++;
                    continue;
                }

                $code = $this->nextThreeDigitCode($usedCodes);

                ModelList::create([
                    'brand' => $brand,
                    'model_name' => $normalizedName,
                    'code' => $code,
                ]);

                $usedCodes[] = (int) $code;
                $inserted++;
            }
        }

        return back()->with('success', "بانک مدل‌های موبایل همگام‌سازی شد. جدید: {$inserted} | بروزرسانی: {$updated}");
    }

    private function usedCodeNumbers(): array
    {
        return ModelList::query()
            ->whereNotNull('code')
            ->pluck('code')
            ->map(fn ($code) => (int) preg_replace('/\D/', '', (string) $code))
            ->filter(fn ($num) => $num > 0 && $num <= 999)
            ->unique()
            ->values()
            ->all();
    }

    private function nextThreeDigitCode(array $usedCodeNumbers): string
    {
        $lookup = array_flip($usedCodeNumbers);

        for ($i = 1; $i <= 999; $i++) {
            if (!isset($lookup[$i])) {
                return str_pad((string) $i, 3, '0', STR_PAD_LEFT);
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
            str_contains($name, 'xiaomi') || str_contains($name, 'شیائومی') || str_contains($name, 'redmi') || str_contains($name, 'poco') || str_contains($name, 'realme') || str_contains($name, 'ریلمی') || str_contains($name, 'rmx') => 'Xiaomi / Realme',
            str_contains($name, 'huawei') || str_contains($name, 'هواوی') || str_contains($name, 'honor') || str_contains($name, 'هانر') => 'Huawei / Honor',
            default => 'سایر',
        };
    }
}
