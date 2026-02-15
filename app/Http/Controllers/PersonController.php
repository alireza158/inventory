<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PersonController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;

        $customers = Customer::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('first_name', 'like', "%{$q}%")
                        ->orWhere('mobile', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->get();

        $suppliers = Supplier::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->get();

        $people = $this->mergePeople($customers, $suppliers);

        $items = $people->forPage($page, $perPage)->values();
        $pagination = new LengthAwarePaginator(
            $items,
            $people->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('persons.index', [
            'people' => $pagination,
            'q' => $q,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20'],
            'types' => ['required', 'array', 'min:1'],
            'types.*' => ['required', Rule::in(['customer', 'supplier'])],
            'address' => ['nullable', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:2000'],
            'province_id' => ['nullable', 'integer'],
            'city_id' => ['nullable', 'integer'],
        ]);

        $types = collect($data['types'])->unique()->values();

        if ($types->contains('customer')) {
            $duplicateCustomer = Customer::query()->where('mobile', $data['mobile'])->exists();
            if ($duplicateCustomer) {
                return back()
                    ->withErrors(['mobile' => 'شماره تماس برای یک مشتری دیگر ثبت شده است.'])
                    ->withInput();
            }
        }

        DB::transaction(function () use ($data, $types) {
            if ($types->contains('customer')) {
                Customer::create([
                    'first_name' => $data['name'],
                    'last_name' => null,
                    'mobile' => $data['mobile'],
                    'address' => $data['address'] ?? null,
                    'postal_code' => $data['postal_code'] ?? null,
                    'extra_description' => $data['description'] ?? null,
                    'province_id' => $data['province_id'] ?? null,
                    'city_id' => $data['city_id'] ?? null,
                ]);
            }

            if ($types->contains('supplier')) {
                $provinces = config('iran.provinces', []);
                $province = collect($provinces)->firstWhere('id', (int) ($data['province_id'] ?? 0));
                $city = collect($province['cities'] ?? [])->firstWhere('id', (int) ($data['city_id'] ?? 0));

                Supplier::create([
                    'name' => $data['name'],
                    'phone' => $data['mobile'],
                    'province' => $province['name'] ?? null,
                    'city' => $city['name'] ?? null,
                    'address' => $data['address'] ?? null,
                    'postal_code' => $data['postal_code'] ?? null,
                    'additional_notes' => $data['description'] ?? null,
                ]);
            }
        });

        return redirect()->route('persons.index')->with('success', '✅ شخص جدید با موفقیت ثبت شد.');
    }

    private function mergePeople(Collection $customers, Collection $suppliers): Collection
    {
        $people = collect();

        foreach ($customers as $customer) {
            $key = $customer->mobile ?: 'customer-'.$customer->id;
            $people[$key] = [
                'name' => $customer->display_name,
                'mobile' => $customer->mobile,
                'address' => $customer->address,
                'postal_code' => $customer->postal_code,
                'description' => $customer->extra_description,
                'is_customer' => true,
                'is_supplier' => false,
                'updated_at' => $customer->updated_at,
            ];
        }

        foreach ($suppliers as $supplier) {
            $key = $supplier->phone ?: 'supplier-'.$supplier->id;
            $existing = $people->get($key);

            if ($existing) {
                $people[$key] = [
                    ...$existing,
                    'name' => $existing['name'] ?: $supplier->name,
                    'mobile' => $existing['mobile'] ?: $supplier->phone,
                    'address' => $existing['address'] ?: $supplier->address,
                    'postal_code' => $existing['postal_code'] ?: $supplier->postal_code,
                    'description' => $existing['description'] ?: $supplier->additional_notes,
                    'is_supplier' => true,
                    'updated_at' => $existing['updated_at'] && $existing['updated_at']->gte($supplier->updated_at) ? $existing['updated_at'] : $supplier->updated_at,
                ];
                continue;
            }

            $people[$key] = [
                'name' => $supplier->name,
                'mobile' => $supplier->phone,
                'address' => $supplier->address,
                'postal_code' => $supplier->postal_code,
                'description' => $supplier->additional_notes,
                'is_customer' => false,
                'is_supplier' => true,
                'updated_at' => $supplier->updated_at,
            ];
        }

        return $people
            ->values()
            ->sortByDesc(fn (array $person) => $person['updated_at'])
            ->values();
    }
}
