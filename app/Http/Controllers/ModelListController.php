<?php

namespace App\Http\Controllers;

use App\Models\ModelList;
use App\Models\ProductVariant;
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
                    ->orWhere('code', 'like', "%{$q}%");
            })
            ->orderByRaw('CASE WHEN code IS NULL OR code = "" THEN 1 ELSE 0 END')
            ->orderByRaw('CAST(code AS UNSIGNED) ASC')
            ->orderBy('model_name')
            ->paginate(30)
            ->withQueryString();

        return view('model-lists.index', compact('modelLists', 'q'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'model_name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:4'],
        ]);

        DB::transaction(function () use ($data) {
            $modelName = trim($data['model_name']);

            // اگر همین مدل وجود داشت، ایجاد نکن
            $exists = ModelList::query()->where('model_name', $modelName)->exists();
            if ($exists) {
                abort(422, 'این مدل قبلاً ثبت شده است.');
            }

            // کد اگر خالی بود → خودکار بساز
            $code = $this->normalizeCode4($data['code'] ?? null);
            if ($code === null) {
                $code = $this->nextCode4();
            } else {
                // یونیک بودن کد
                if (ModelList::query()->where('code', $code)->exists()) {
                    abort(422, 'این کد قبلاً استفاده شده است.');
                }
            }

            ModelList::create([
                'model_name' => $modelName,
                'code' => $code,
            ]);
        });

        return redirect()->route('model-lists.index')->with('success', 'مدل با موفقیت ذخیره شد.');
    }

    public function update(Request $request, ModelList $modelList)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:4'],
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
        DB::transaction(function () {
            // قفل برای جلوگیری از تولید همزمان کد تکراری
            $rows = ModelList::query()
                ->lockForUpdate()
                ->whereNull('code')
                ->orWhere('code', '')
                ->orderBy('id')
                ->get();

            if ($rows->count() === 0) {
                return;
            }

            $next = $this->nextCode4();

            foreach ($rows as $row) {
                // اگر وسط کار این کد تکراری شد، یکی بالا ببر
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
}