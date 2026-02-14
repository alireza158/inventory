<?php

namespace App\Http\Controllers;

use App\Models\ModelList;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ModelListController extends Controller
{
    public function index()
    {
        $modelLists = ModelList::query()
            ->orderBy('brand')
            ->orderBy('model_name')
            ->paginate(30);

        return view('model-lists.index', compact('modelLists'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'brand' => ['required', 'string', 'max:100'],
            'model_name' => ['required', 'string', 'max:255'],
        ]);

        ModelList::firstOrCreate([
            'brand' => trim($data['brand']),
            'model_name' => trim($data['model_name']),
        ]);

        return back()->with('success', 'مدل با موفقیت ذخیره شد.');
    }

    public function importFromProducts(): RedirectResponse
    {
        $count = 0;

        ProductVariant::query()
            ->select('variant_name')
            ->whereNotNull('variant_name')
            ->where('variant_name', '<>', '')
            ->distinct()
            ->chunk(500, function ($variants) use (&$count) {
                foreach ($variants as $variant) {
                    [$brand, $modelName] = self::splitVariantName($variant->variant_name);
                    if ($brand === '' || $modelName === '') {
                        continue;
                    }

                    $created = ModelList::firstOrCreate([
                        'brand' => $brand,
                        'model_name' => $modelName,
                    ]);

                    if ($created->wasRecentlyCreated) {
                        $count++;
                    }
                }
            });

        return back()->with('success', "تعداد {$count} مدل از کالاهای موجود به لیست مدل‌ها اضافه شد.");
    }

    public static function splitVariantName(string $variantName): array
    {
        $value = trim($variantName);
        if ($value === '') {
            return ['', ''];
        }

        if (str_contains($value, '-')) {
            [$brand, $model] = array_map('trim', explode('-', $value, 2));
            return [$brand, $model];
        }

        $parts = preg_split('/\s+/u', $value);
        if (count($parts) < 2) {
            return ['', ''];
        }

        $brand = trim(array_shift($parts));
        $model = trim(implode(' ', $parts));

        return [$brand, $model];
    }
}
