<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::withCount('stocks')->orderBy('type')->orderBy('name')->get();
        return view('warehouses.index', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:central,return,scrap,personnel'],
            'personnel_name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['type'] === 'personnel' && empty($data['personnel_name'])) {
            return back()->withErrors(['personnel_name' => 'برای انبار پرسنل، نام پرسنل الزامی است.'])->withInput();
        }

        Warehouse::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'personnel_name' => $data['type'] === 'personnel' ? $data['personnel_name'] : null,
            'is_active' => true,
        ]);

        return redirect()->route('warehouses.index')->with('success', 'انبار با موفقیت تعریف شد.');
    }
}

