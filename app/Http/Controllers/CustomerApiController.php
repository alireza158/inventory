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
                   ->orWhere('last_name','like',"%{$q}%")
                   ->orWhere('mobile','like',"%{$q}%");
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn($c)=>[
                'id' => $c->id,
                'first_name' => $c->first_name,
                'last_name' => $c->last_name,
                'mobile' => $c->mobile,
                'address' => $c->address,
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
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'mobile' => $customer->mobile,
                    'address' => $customer->address,
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
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'mobile'     => ['required','string','max:20', Rule::unique('customers','mobile')],
            'address'    => 'nullable|string|max:2000',
            'province_id'=> 'nullable|integer',
            'city_id'    => 'nullable|integer',
        ]);

        $customer = Customer::create($data);

        return response()->json([
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'mobile' => $customer->mobile,
                    'address' => $customer->address,
                    'province_id' => (int)($customer->province_id ?? 0),
                    'city_id' => (int)($customer->city_id ?? 0),
                ]
            ]
        ], 201);
    }
}
