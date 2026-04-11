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

        $groups = [];
        foreach ($this->brandGroups() as $key => $cfg) {
            $groups[$key] = [
                'title' => $cfg['title'],
                'items' => $all->filter(function ($row) use ($cfg) {
                    $b = trim((string) ($row->brand ?? ''));
                    if ($b === '') {
                        $b = 'سایر';
                    }
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

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'brand' => ['required', 'string', 'max:100'],
            'model_name' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data) {
            $brand = trim((string) $data['brand']);
            $modelName = trim((string) $data['model_name']);

            $exists = ModelList::query()->where('model_name', $modelName)->lockForUpdate()->first();
            if ($exists) {
                abort(422, 'این مدل قبلاً ثبت شده است.');
            }

            $code = $this->nextCode3();
            ModelList::create([
                'brand' => $brand,
                'model_name' => $modelName,
                'code' => $code,
            ]);
        });

        return redirect()->route('model-lists.index')->with('success', 'مدل با موفقیت ذخیره شد.');
    }

    public function quickStore(Request $request)
    {
        $data = $request->validate([
            'brand' => ['required', 'string', 'max:100'],
            'model_name' => ['required', 'string', 'max:255'],
        ]);

        $brand = trim((string) $data['brand']);
        $modelName = trim((string) $data['model_name']);

        if ($modelName === '') {
            return response()->json(['message' => 'نام مدل نمی‌تواند خالی باشد.'], 422);
        }

        $result = ['model' => null, 'created' => false];

        DB::transaction(function () use (&$result, $brand, $modelName) {
            $existing = ModelList::query()
                ->whereRaw('LOWER(TRIM(model_name)) = ?', [mb_strtolower($modelName)])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $result['model'] = $existing;
                return;
            }

            $code = $this->nextCode3();
            $result['model'] = ModelList::create([
                'brand' => $brand,
                'model_name' => $modelName,
                'code' => $code,
            ]);
            $result['created'] = true;
        });

        $model = $result['model'];
        $created = (bool) $result['created'];

        return response()->json([
            'id' => (int) $model->id,
            'brand' => (string) ($model->brand ?? ''),
            'model_name' => (string) ($model->model_name ?? ''),
            'code' => (string) ($model->code ?? ''),
            'created' => $created,
            'message' => $created
                ? 'مدل لیست جدید با موفقیت ثبت شد.'
                : 'این مدل از قبل وجود دارد و انتخاب شد.',
        ]);
    }

    public function update(Request $request, ModelList $modelList): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:3'],
            'model_name' => ['required', 'string', 'max:255'],
        ]);

        $code = $this->normalizeCode3Strict($data['code']);
        if ($code === null) {
            return back()->withErrors(['code' => 'کد باید دقیقاً ۳ رقمی باشد (مثلاً 016 یا 001).']);
        }

        $modelName = trim((string) $data['model_name']);

        $existsByCode = ModelList::query()
            ->where('id', '!=', $modelList->id)
            ->where('code', $code)
            ->exists();

        if ($existsByCode) {
            return back()->withErrors(['code' => 'این کد قبلاً برای مدل دیگری ثبت شده است.']);
        }

        $existsByName = ModelList::query()
            ->where('id', '!=', $modelList->id)
            ->where('model_name', $modelName)
            ->exists();

        if ($existsByName) {
            return back()->withErrors(['model_name' => 'این نام مدل قبلاً ثبت شده است.']);
        }

        $modelList->update([
            'code' => $code,
            'model_name' => $modelName,
        ]);

        return redirect()->route('model-lists.index')->with('success', 'مدل لیست بروزرسانی شد.');
    }

    public function destroy(ModelList $modelList): RedirectResponse
    {
        $modelList->delete();
        return redirect()->route('model-lists.index')->with('success', 'مدل حذف شد.');
    }

    public function assignCodes(): RedirectResponse
    {
        DB::transaction(function () {
            $rows = ModelList::query()->lockForUpdate()->orderBy('id')->get();

            $used = [];
            foreach ($rows as $row) {
                $c = $this->normalizeCode3Strict($row->code);
                if ($c === null || isset($used[$c])) {
                    continue;
                }
                $used[$c] = true;
            }

            $nextInt = $this->maxUsedInt($used) + 1;
            if ($nextInt < 1) {
                $nextInt = 1;
            }

            foreach ($rows as $row) {
                $current = $this->normalizeCode3Strict($row->code);

                $needsFix = $current === null;
                if ($current !== null) {
                    $dupCount = ModelList::query()->where('code', $current)->count();
                    if ($dupCount > 1) {
                        $firstId = ModelList::query()->where('code', $current)->min('id');
                        if ($row->id !== $firstId) {
                            $needsFix = true;
                        }
                    }
                }

                if (!$needsFix) {
                    continue;
                }

                $new = $this->nextFreeFrom($used, $nextInt);
                $row->update(['code' => $new]);
                $used[$new] = true;
                $nextInt = (int) $new + 1;
            }
        });

        return redirect()->route('model-lists.index')->with('success', 'کدهای مدل‌لیست اصلاح و ۳ رقمی شدند.');
    }

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
                if ($full === '') {
                    continue;
                }

                $base = preg_replace('/\s*طرح\s*\d+$/u', '', $full);
                $base = trim((string) $base);
                if ($base === '') {
                    continue;
                }

                $exists = ModelList::query()->where('model_name', $base)->exists();
                if ($exists) {
                    continue;
                }

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

    public function importPhoneCatalog(): RedirectResponse
    {
        DB::transaction(function () {
            $catalog = PhoneModelCatalog::brands();

            foreach ($catalog as $brand => $models) {
                foreach ($models as $modelName) {
                    $normalizedName = trim((string) $modelName);
                    if ($normalizedName === '') {
                        continue;
                    }

                    $existing = ModelList::query()->where('model_name', $normalizedName)->lockForUpdate()->first();
                    if ($existing) {
                        if (!$existing->brand || $existing->brand === 'سایر') {
                            $existing->update(['brand' => $brand]);
                        }
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

    private function normalizeCode3Strict(?string $code): ?string
    {
        $code = trim((string) ($code ?? ''));
        if ($code === '') {
            return null;
        }

        if (!preg_match('/^\d{1,3}$/', $code)) {
            return null;
        }

        return str_pad($code, 3, '0', STR_PAD_LEFT);
    }

    private function nextCode3(): string
    {
        $codes = ModelList::query()->whereNotNull('code')->pluck('code')->all();

        $max = 0;
        $used = [];
        foreach ($codes as $c) {
            $n = $this->normalizeCode3Strict($c);
            if ($n === null) {
                continue;
            }
            $used[$n] = true;
            $iv = (int) $n;
            if ($iv > $max) {
                $max = $iv;
            }
        }

        $start = max($max + 1, 1);
        return $this->nextFreeFrom($used, $start);
    }

    private function nextFreeFrom(array $used, int $start): string
    {
        for ($i = $start; $i <= 999; $i++) {
            $c = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            if (!isset($used[$c])) {
                return $c;
            }
        }

        for ($i = 1; $i <= 999; $i++) {
            $c = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            if (!isset($used[$c])) {
                return $c;
            }
        }

        abort(422, 'کد ۳ رقمی خالی برای مدل لیست موجود نیست.');
    }

    private function maxUsedInt(array $used): int
    {
        $max = 0;
        foreach ($used as $code => $true) {
            $iv = (int) $code;
            if ($iv > $max) {
                $max = $iv;
            }
        }
        return $max;
    }

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
