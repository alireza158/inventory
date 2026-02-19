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
            ->orderBy('model_name')
            ->paginate(30);

        return view('model-lists.index', compact('modelLists'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'model_name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'digits:4', 'unique:model_lists,code'],
        ]);

        ModelList::firstOrCreate([
            'model_name' => trim($data['model_name']),
        ], [
            'code' => $data['code'],
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
            ->groupBy('variant_name')
            ->orderBy('variant_name')
            ->chunk(500, function ($variants) use (&$count) {
                foreach ($variants as $variant) {
                    $modelName = trim((string) $variant->variant_name);
                    if ($modelName === '') {
                        continue;
                    }

                    $created = ModelList::firstOrCreate([
                        'model_name' => $modelName,
                    ]);

                    if ($created->wasRecentlyCreated) {
                        $count++;
                    }
                }
            });

        return back()->with('success', "تعداد {$count} مدل از کالاهای موجود به لیست مدل‌ها اضافه شد.");
    }
}
