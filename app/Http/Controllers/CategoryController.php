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

        $categories = Category::query()
            ->with('parent')
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('rootCategories', 'categories'));
    }

    public function create()
    {
        // لیست دسته‌های ممکن برای انتخاب والد (فقط ریشه‌ها یا همه)
        $parents = Category::orderBy('name')->get();
        return view('categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required','string','max:255','unique:categories,name'],
            'parent_id' => ['nullable','integer','exists:categories,id'],
        ]);

        Category::create($data);

        return redirect()->route('categories.index')
            ->with('success', 'دسته‌بندی با موفقیت ثبت شد.');
    }

    public function edit(Category $category)
    {
        // جلوگیری از انتخاب خودش به عنوان والد
        $parents = Category::where('id', '!=', $category->id)->orderBy('name')->get();
        return view('categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'      => ['required','string','max:255','unique:categories,name,'.$category->id],
            'parent_id' => ['nullable','integer','exists:categories,id','not_in:'.$category->id],
        ]);

        $category->update($data);

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
        'name'      => ['required', 'string', 'max:255', 'unique:categories,name'],
        'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        // برای اینکه بعد از ساخت برگرده به صفحه محصولات و category انتخاب بمونه
        'redirect_to' => ['nullable', 'string'],
    ]);

    $category = Category::create([
        'name' => $data['name'],
        'parent_id' => $data['parent_id'] ?? null,
    ]);

    $redirectTo = $data['redirect_to'] ?? route('products.index');

    // بعد از ساخت، برگرده به صفحه محصولات و دسته انتخاب‌شده رو هم ست کنه
    return redirect($redirectTo . '?category_id=' . $category->id)
        ->with('success', 'دسته‌بندی با موفقیت ساخته شد.');
}

}
