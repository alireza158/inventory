<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->query('q',''));

        $customers = Customer::query()
            ->withBalance()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function($qq) use ($q){
                    $qq->where('first_name','like',"%{$q}%")
                       ->orWhere('last_name','like',"%{$q}%")
                       ->orWhere('mobile','like',"%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', compact('customers','q'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name'  => ['nullable', 'string', 'max:255'],
            'mobile'     => [
                'required',
                'string',
                'max:20',
                // اگر می‌خوای موبایل یکتا باشه:
                Rule::unique('customers', 'mobile'),
            ],
            'address'    => ['nullable', 'string', 'max:1000'],
        ]);

        Customer::create([
            'first_name' => $data['first_name'] ?? null,
            'last_name'  => $data['last_name'] ?? null,
            'mobile'     => $data['mobile'],
            'address'    => $data['address'] ?? null,
        ]);

        return redirect()
            ->route('customers.index')
            ->with('success', '✅ مشتری با موفقیت ساخته شد.');
    }
}
