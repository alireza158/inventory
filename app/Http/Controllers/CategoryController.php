<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
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

        Category::create([
            'name' => $data['name'],
            'code' => $this->nextCategoryCode(),
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        return redirect()->route('categories.index')
            ->with('success', 'دسته‌بندی با موفقیت ثبت شد.');
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

        $category->update([
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        return redirect()->route('categories.index')
            ->with('success', 'دسته‌بندی بروزرسانی شد.');
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

        $category = Category::create([
            'name' => $data['name'],
            'code' => $this->nextCategoryCode(),
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        $redirectTo = $data['redirect_to'] ?? route('products.index');

        return redirect($redirectTo . '?category_id=' . $category->id)
            ->with('success', 'دسته‌بندی با موفقیت ساخته شد.');
    }

    private function nextCategoryCode(): string
    {
        $used = Category::query()
            ->whereNotNull('code')
            ->pluck('code')
            ->map(fn ($code) => preg_replace('/\D/', '', (string) $code))
            ->filter(fn ($code) => $code !== '')
            ->map(fn ($code) => (int) $code)
            ->all();

        $lookup = array_flip($used);

        for ($i = 1; $i <= 999; $i++) {
            if (!isset($lookup[$i])) {
                return str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            }
        }

        abort(422, 'کد ۳ رقمی خالی برای دسته‌بندی وجود ندارد.');
    }
}
