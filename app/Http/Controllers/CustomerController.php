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
                Rule::unique('customers', 'mobile'),
            ],
            'address'    => ['nullable', 'string', 'max:1000'],
            'province_id'=> ['nullable', 'integer'],
            'city_id'    => ['nullable', 'integer'],
        ]);

        Customer::create($data);

        return redirect()
            ->route('customers.index')
            ->with('success', '✅ مشتری با موفقیت ساخته شد.');
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name'  => ['nullable', 'string', 'max:255'],
            'mobile'     => [
                'required',
                'string',
                'max:20',
                Rule::unique('customers', 'mobile')->ignore($customer->id),
            ],
            'address'    => ['nullable', 'string', 'max:1000'],
            'province_id'=> ['nullable', 'integer'],
            'city_id'    => ['nullable', 'integer'],
        ]);

        $customer->update($data);

        return redirect()
            ->route('customers.index')
            ->with('success', '✅ اطلاعات مشتری ویرایش شد.');
    }

    public function destroy(Customer $customer)
    {
        $title = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: $customer->mobile;
        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', "✅ مشتری {$title} حذف شد.");
    }
}
