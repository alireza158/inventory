<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Models\WarehouseLocationMovement;
use App\Services\WarehouseMapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WarehouseMapController extends Controller
{
    public function index(Request $request, WarehouseMapService $service)
    {
        $warehouseId = (int) $request->integer('warehouse_id', Warehouse::query()->where('type', 'central')->value('id') ?: Warehouse::query()->value('id'));
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();
        $categories = Category::query()->orderBy('name')->get();

        $locations = WarehouseLocation::query()->with('warehouse')
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($request->filled('zone'), fn ($q) => $q->where('zone', 'like', '%'.$request->zone.'%'))
            ->when($request->filled('rack'), fn ($q) => $q->where('rack', 'like', '%'.$request->rack.'%'))
            ->when($request->filled('box'), fn ($q) => $q->where('box', 'like', '%'.$request->box.'%'))
            ->orderBy('code')->paginate(15, ['*'], 'locations_page')->withQueryString();

        $variantsQuery = ProductVariant::query()
            ->with(['product.category', 'locationStocks' => fn ($q) => $q->where('warehouse_id', $warehouseId)->with('location')])
            ->whereHas('product')
            ->when($request->filled('category_id'), fn ($q) => $q->whereHas('product', fn ($p) => $p->where('category_id', $request->category_id)))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->q);
                $q->where(function ($qq) use ($term) {
                    $qq->where('variant_name', 'like', "%{$term}%")
                        ->orWhere('variant_code', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%")
                        ->orWhere('barcode', 'like', "%{$term}%")
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%")->orWhere('barcode', 'like', "%{$term}%"));
                });
            });

        $variants = $variantsQuery->orderByDesc('id')->paginate(20, ['*'], 'variants_page')->withQueryString();
        $variantRows = $variants->getCollection()->map(function (ProductVariant $variant) use ($service, $warehouseId) {
            $total = $service->totalQuantity((int) $variant->id, $warehouseId);
            $mapped = (int) $variant->locationStocks->sum('quantity');
            $unmapped = $total - $mapped;
            return compact('variant', 'total', 'mapped', 'unmapped');
        });
        $status = $request->get('map_status');
        if ($status) {
            $variantRows = $variantRows->filter(fn ($r) => $status === 'mapped' ? $r['mapped'] > 0 : ($status === 'unmapped' ? $r['unmapped'] > 0 : $r['variant']->locationStocks->where('quantity', '>', 0)->count() > 1))->values();
        }
        $variants->setCollection($variantRows);

        $allLocations = WarehouseLocation::query()->where('warehouse_id', $warehouseId)->where('is_active', true)->orderBy('code')->get();

        return view('warehouse-map.index', compact('warehouses', 'categories', 'locations', 'variants', 'allLocations', 'warehouseId'));
    }

    public function storeLocation(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'zone' => ['required', 'string', 'max:20'],
            'rack' => ['required', 'string', 'max:20'],
            'box' => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $zone = WarehouseLocation::normalizePart($data['zone'], 'Z');
        $rack = WarehouseLocation::normalizePart($data['rack'], 'R');
        $box = WarehouseLocation::normalizePart($data['box'], 'B');

        WarehouseLocation::firstOrCreate([
            'warehouse_id' => (int) $data['warehouse_id'], 'zone' => $zone, 'rack' => $rack, 'box' => $box,
        ], [
            'code' => WarehouseLocation::makeCode($zone, $rack, $box),
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'مکان انبار با موفقیت ثبت شد؛ مکان تکراری دوباره ساخته نمی‌شود.');
    }

    public function updateLocation(Request $request, WarehouseLocation $location)
    {
        $data = $request->validate(['description' => ['nullable','string','max:1000'], 'is_active' => ['nullable','boolean']]);
        $location->update(['description' => $data['description'] ?? null, 'is_active' => $request->boolean('is_active')]);
        return back()->with('success', 'مکان انبار بروزرسانی شد.');
    }

    public function assign(Request $request, WarehouseMapService $service)
    {
        $data = $request->validate([
            'warehouse_id' => ['required','exists:warehouses,id'],
            'product_variant_id' => ['required','exists:product_variants,id'],
            'warehouse_location_id' => ['required','exists:warehouse_locations,id'],
            'quantity' => ['required','integer','min:1'],
            'note' => ['nullable','string','max:1000'],
        ]);
        $service->assignLocation((int)$data['product_variant_id'], (int)$data['warehouse_id'], (int)$data['warehouse_location_id'], (int)$data['quantity'], auth()->id(), $data['note'] ?? null);
        return back()->with('success', 'موجودی مکانی تنوع با موفقیت ثبت شد.');
    }

    public function transfer(Request $request, WarehouseMapService $service)
    {
        $data = $request->validate([
            'warehouse_id' => ['required','exists:warehouses,id'],
            'product_variant_id' => ['required','exists:product_variants,id'],
            'from_location_id' => ['required','exists:warehouse_locations,id'],
            'to_location_id' => ['required','exists:warehouse_locations,id', 'different:from_location_id'],
            'quantity' => ['required','integer','min:1'],
            'note' => ['nullable','string','max:1000'],
        ]);
        $service->transfer((int)$data['product_variant_id'], (int)$data['warehouse_id'], (int)$data['from_location_id'], (int)$data['to_location_id'], (int)$data['quantity'], auth()->id(), $data['note'] ?? null);
        return back()->with('success', 'جابه‌جایی مکانی بدون تغییر موجودی کل ثبت شد.');
    }

    public function history(ProductVariant $variant, Request $request)
    {
        $warehouseId = (int) $request->integer('warehouse_id');
        $movements = WarehouseLocationMovement::query()->with(['fromLocation','toLocation','user'])
            ->where('product_variant_id', $variant->id)
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->latest()->paginate(30);
        return view('warehouse-map.history', compact('variant', 'movements'));
    }
}
