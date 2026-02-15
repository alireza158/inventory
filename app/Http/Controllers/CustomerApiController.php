<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerApiController extends Controller
{
    public function search(Request $request)
    {
        $q = trim((string)$request->query('q',''));
        if ($q === '') {
            return response()->json(['data' => ['customers' => []]]);
        }

        $items = Customer::query()
            ->withBalance()
            ->where(function($qq) use ($q){
                $qq->where('first_name','like',"%{$q}%")
                   ->orWhere('mobile','like',"%{$q}%");
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn($c)=>[
                'id' => $c->id,
                'name' => $c->display_name,
                'first_name' => $c->first_name,
                'last_name' => $c->last_name,
                'mobile' => $c->mobile,
                'address' => $c->address,
                'postal_code' => $c->postal_code,
                'extra_description' => $c->extra_description,
                'province_id' => (int)($c->province_id ?? 0),
                'city_id' => (int)($c->city_id ?? 0),
                'debt' => (int)$c->debt,
                'credit' => (int)$c->credit,
                'balance' => (int)$c->balance,
            ])
            ->values();

        return response()->json(['data' => ['customers' => $items]]);
    }

    public function show(Customer $customer)
    {
        $customer->loadSum(['ledgers as debit_sum' => fn($q)=>$q->where('type','debit')],'amount')
                 ->loadSum(['ledgers as credit_sum' => fn($q)=>$q->where('type','credit')],'amount');

        return response()->json([
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->display_name,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'mobile' => $customer->mobile,
                    'address' => $customer->address,
                    'postal_code' => $customer->postal_code,
                    'extra_description' => $customer->extra_description,
                    'province_id' => (int)($customer->province_id ?? 0),
                    'city_id' => (int)($customer->city_id ?? 0),
                    'debt' => (int)$customer->debt,
                    'credit' => (int)$customer->credit,
                    'balance' => (int)$customer->balance,
                ]
            ]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'mobile'     => ['required','string','max:20', Rule::unique('customers','mobile')],
            'address'    => 'nullable|string|max:2000',
            'postal_code' => 'nullable|string|max:20',
            'extra_description' => 'nullable|string|max:2000',
            'province_id'=> 'nullable|integer',
            'city_id'    => 'nullable|integer',
        ]);

        $customer = Customer::create([
            'first_name' => $data['customer_name'] ?? $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? null,
            'mobile' => $data['mobile'],
            'address' => $data['address'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'extra_description' => $data['extra_description'] ?? null,
            'province_id' => $data['province_id'] ?? null,
            'city_id' => $data['city_id'] ?? null,
        ]);

        return response()->json([
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->display_name,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'mobile' => $customer->mobile,
                    'address' => $customer->address,
                    'postal_code' => $customer->postal_code,
                    'extra_description' => $customer->extra_description,
                    'province_id' => (int)($customer->province_id ?? 0),
                    'city_id' => (int)($customer->city_id ?? 0),
                ]
            ]
        ], 201);
    }
}
