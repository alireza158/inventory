<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    private function normalizeCategoryName(string $value): string
    {
        $value = preg_replace('/[\x{200C}\x{200D}\x{200E}\x{200F}\x{FEFF}]/u', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', (string) $value);
        $value = trim((string) $value);
        return mb_strtolower($value, 'UTF-8');
    }

    public function index()
    {
        $rootCategories = Category::query()
            ->whereNull('parent_id')
            ->with(['children.children.children'])
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('rootCategories'));
    }

    public function create()
    {
        $parents = Category::orderBy('name')->get();
        return view('categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        DB::transaction(function () use ($data) {
            $code = $this->generateUniqueTwoDigitCode();

            Category::create([
                'name' => $data['name'],
                'parent_id' => $data['parent_id'] ?? null,
                'code' => $code,
            ]);
        });

        return redirect()->route('categories.index')->with('success', 'دسته‌بندی با موفقیت ثبت شد.');
    }

    public function edit(Category $category)
    {
        $parents = Category::where('id', '!=', $category->id)->orderBy('name')->get();
        return view('categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,' . $category->id],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', 'not_in:' . $category->id],
        ]);

        DB::transaction(function () use ($data, $category) {
            if (!$this->isTwoDigitCode($category->code)) {
                $category->code = $this->generateUniqueTwoDigitCode($category->id);
            }

            $category->update([
                'name' => $data['name'],
                'parent_id' => $data['parent_id'] ?? null,
                'code' => $category->code,
            ]);
        });

        return redirect()->route('categories.index')->with('success', 'دسته‌بندی بروزرسانی شد.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'دسته‌بندی حذف شد.');
    }

    public function quickStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        $category = null;

        DB::transaction(function () use (&$category, $data) {
            $code = $this->generateUniqueTwoDigitCode();

            $category = Category::create([
                'name' => $data['name'],
                'code' => $code,
                'parent_id' => $data['parent_id'] ?? null,
            ]);
        });

        $redirectTo = $data['redirect_to'] ?? route('products.index');

        return redirect($redirectTo . '?category_id=' . $category->id)
            ->with('success', 'دسته‌بندی با موفقیت ساخته شد.');
    }

    public function ensure(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ], [
            'name.required' => 'نام دسته‌بندی الزامی است.',
            'name.max' => 'نام دسته‌بندی نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد.',
            'parent_id.exists' => 'دسته والد انتخاب‌شده معتبر نیست.',
        ]);

        $rawName = preg_replace('/\s+/u', ' ', trim((string) $data['name']));
        if ($rawName === '') {
            return response()->json([
                'ok' => false,
                'message' => 'نام دسته‌بندی نمی‌تواند خالی باشد.',
            ], 422);
        }

        $normalizedInput = $this->normalizeCategoryName($rawName);
        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;

        $category = null;
        $created = false;

        DB::transaction(function () use (&$category, &$created, $rawName, $normalizedInput, $parentId) {
            $existing = Category::query()->lockForUpdate()->get()->first(function (Category $row) use ($normalizedInput) {
                return $this->normalizeCategoryName((string) $row->name) === $normalizedInput;
            });

            if ($existing) {
                $category = $existing;
                return;
            }

            $category = Category::create([
                'name' => $rawName,
                'code' => $this->generateUniqueTwoDigitCode(),
                'parent_id' => $parentId,
            ]);
            $created = true;
        });

        return response()->json([
            'ok' => true,
            'created' => $created,
            'message' => $created ? 'دسته‌بندی جدید با موفقیت ساخته شد.' : 'این دسته‌بندی قبلاً ثبت شده بود و همان مورد انتخاب شد.',
            'item' => [
                'id' => $category->id,
                'name' => $category->name,
                'code' => $category->code,
                'parent_id' => $category->parent_id,
            ],
        ]);
    }

    public function fixCodes()
    {
        DB::transaction(function () {
            $total = Category::count();
            if ($total > 100) {
                abort(422, 'تعداد دسته‌بندی‌ها بیش از 100 است و کد 2 رقمی یونیک ممکن نیست.');
            }

            $categories = Category::query()->lockForUpdate()->orderBy('id')->get();
            $used = [];

            foreach ($categories as $cat) {
                $newCode = $this->generateUniqueTwoDigitCode($cat->id, $used);
                $used[$newCode] = true;
                $cat->update(['code' => $newCode]);
            }
        });

        return redirect()->route('categories.index')->with('success', 'کدهای دسته‌بندی‌ها به ۲ رقمی یونیک اصلاح شدند.');
    }

    private function isTwoDigitCode(?string $code): bool
    {
        $code = trim((string) ($code ?? ''));
        return (bool) preg_match('/^\d{2}$/', $code);
    }

    private function generateUniqueTwoDigitCode(?int $ignoreId = null, ?array $usedSet = null): string
    {
        $existing = Category::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->pluck('code')
            ->map(fn ($c) => trim((string) $c))
            ->filter()
            ->all();

        $used = [];
        foreach ($existing as $c) {
            if (preg_match('/^\d{2}$/', $c)) {
                $used[$c] = true;
            }
        }

        if (is_array($usedSet)) {
            foreach ($usedSet as $k => $v) {
                if ($v === true) {
                    $used[$k] = true;
                }
            }
        }

        if (count($used) >= 100) {
            abort(422, 'کد ۲ رقمی خالی موجود نیست.');
        }

        for ($try = 0; $try < 300; $try++) {
            $n = random_int(0, 99);
            $code = str_pad((string) $n, 2, '0', STR_PAD_LEFT);
            if (!isset($used[$code])) {
                return $code;
            }
        }

        for ($i = 0; $i <= 99; $i++) {
            $code = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            if (!isset($used[$code])) {
                return $code;
            }
        }

        abort(422, 'کد ۲ رقمی خالی موجود نیست.');
    }
}
