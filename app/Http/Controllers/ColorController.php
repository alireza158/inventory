<?php

namespace App\Http\Controllers;

use App\Models\Color;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    public function index()
    {
        $colors = Color::query()->orderBy('code')->paginate(100);

        return view('colors.index', compact('colors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'digits:2', 'unique:colors,code'],
        ]);

        Color::create($data);

        return back()->with('success', 'رنگ جدید ثبت شد.');
    }

    public function update(Request $request, Color $color): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'digits:2', 'unique:colors,code,' . $color->id],
        ]);

        $color->update($data);

        return back()->with('success', 'رنگ بروزرسانی شد.');
    }

    public function destroy(Color $color): RedirectResponse
    {
        $color->delete();

        return back()->with('success', 'رنگ حذف شد.');
    }

    public function seedDefaults(): RedirectResponse
    {
        $defaults = [
            'مشکی','سفید','نقره‌ای','خاکستری','طلایی','رزگلد','سرمه‌ای','آبی','آبی روشن','سبز','سبز روشن','زرد',
            'نارنجی','قرمز','زرشکی','بنفش','یاسی','صورتی','قهوه‌ای','نسکافه‌ای','کرم','فیروزه‌ای','لیمویی','زیتونی',
            'ذغالی','مسی','بژ','استخوانی','شفاف','چند رنگ','طرح‌دار','نامشخص',
        ];

        $inserted = 0;

        foreach ($defaults as $idx => $name) {
            $code = str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT);

            if (Color::query()->where('code', $code)->exists()) {
                continue;
            }

            Color::create([
                'name' => $name,
                'code' => $code,
            ]);

            $inserted++;
        }

        return back()->with('success', "{$inserted} رنگ پیش‌فرض بارگذاری شد.");
    }
}
