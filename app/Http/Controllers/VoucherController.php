<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\WarehouseStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $voucherNo = trim((string) $request->get('voucher_no', ''));
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = WarehouseTransfer::with(['fromWarehouse', 'toWarehouse', 'user'])
            ->when($voucherNo !== '', function ($q) use ($voucherNo) {
                $q->where(function ($inner) use ($voucherNo) {
                    $inner->where('id', (int) $voucherNo)
                        ->orWhere('reference', 'like', "%{$voucherNo}%");
                });
            })
            ->when($dateFrom, fn ($q) => $q->whereDate('transferred_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('transferred_at', '<=', $dateTo));

        $totalAllAmount = (int) WarehouseTransfer::sum('total_amount');
        $totalAllCount = (int) WarehouseTransfer::count();

        $vouchers = $query->latest('transferred_at')
            ->paginate(20)
            ->withQueryString();

        return view('vouchers.index', compact('vouchers', 'totalAllAmount', 'totalAllCount'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $products = Product::select('id', 'name', 'sku', 'category_id', 'price')->orderBy('name')->get();
        $warehouses = $this->selectableWarehouses();
        $voucher = null;

        return view('vouchers.create', compact('categories', 'products', 'warehouses', 'voucher'));
    }

    public function edit(WarehouseTransfer $voucher)
    {
        $categories = Category::orderBy('name')->get();
        $products = Product::select('id', 'name', 'sku', 'category_id', 'price')->orderBy('name')->get();
        $warehouses = $this->selectableWarehouses();
        $voucher->load('items.product');

        return view('vouchers.create', compact('voucher', 'categories', 'products', 'warehouses'));
    }

    public function store(Request $request)
    {
        $data = $this->validateTransfer($request);

        DB::transaction(function () use ($data) {
            $this->createTransfer($data, now());
        });

        return redirect()->route('vouchers.index')->with('success', 'سند حواله ثبت شد.');
    }

    public function update(Request $request, WarehouseTransfer $voucher)
    {
        $data = $this->validateTransfer($request);

        DB::transaction(function () use ($voucher, $data) {
            $this->rollbackTransfer($voucher);
            $voucher->items()->delete();
            $voucher->delete();
            $this->createTransfer($data, $voucher->transferred_at ?? now());
        });

        return redirect()->route('vouchers.index')->with('success', 'سند حواله با موفقیت ویرایش شد.');
    }

    public function destroy(WarehouseTransfer $voucher)
    {
        DB::transaction(function () use ($voucher) {
            $this->rollbackTransfer($voucher);
            $voucher->delete();
        });

        return back()->with('success', 'سند حواله حذف شد.');
    }

    private function validateTransfer(Request $request): array
    {
        $data = $request->validate([
            'from_warehouse_id' => ['required', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'exists:categories,id'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.personnel_asset_code' => ['nullable', 'digits:4'],
        ]);

        foreach ($data['items'] as $index => $item) {
            $belongsToCategory = Product::whereKey($item['product_id'])
                ->where('category_id', $item['category_id'])
                ->exists();

            if (!$belongsToCategory) {
                abort(422, "ردیف " . ($index + 1) . ": کالا در دسته‌بندی انتخابی نیست.");
            }
        }

        return $data;
    }

    private function createTransfer(array $data, $transferredAt): WarehouseTransfer
    {
        $toWarehouse = Warehouse::findOrFail($data['to_warehouse_id']);

        $transfer = WarehouseTransfer::create([
            'reference' => $data['reference'] ?? null,
            'from_warehouse_id' => $data['from_warehouse_id'],
            'to_warehouse_id' => $data['to_warehouse_id'],
            'user_id' => auth()->id(),
            'transferred_at' => $transferredAt,
            'total_amount' => 0,
            'note' => $data['note'] ?? null,
        ]);

        $sum = 0;

        foreach ($data['items'] as $item) {
            $product = Product::findOrFail($item['product_id']);
            $qty = (int) $item['quantity'];
            $unitPrice = (int) ($product->price ?? 0);
            $lineTotal = $qty * $unitPrice;
            $sum += $lineTotal;

            WarehouseStockService::change((int) $data['from_warehouse_id'], (int) $item['product_id'], -$qty);
            WarehouseStockService::change((int) $data['to_warehouse_id'], (int) $item['product_id'], $qty);

            $before = (int) $product->stock;

            $transfer->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'personnel_asset_code' => $toWarehouse->isPersonnelLeaf() ? ($item['personnel_asset_code'] ?? null) : null,
            ]);

            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => 'out',
                'reason' => 'transfer',
                'quantity' => $qty,
                'stock_before' => $before,
                'stock_after' => $before,
                'reference' => $transfer->reference ?: ('TR-' . $transfer->id),
                'note' => 'انتقال از ' . $transfer->fromWarehouse->name . ' به ' . $transfer->toWarehouse->name,
            ]);
        }

        $transfer->update(['total_amount' => $sum]);

        return $transfer;
    }

    private function rollbackTransfer(WarehouseTransfer $transfer): void
    {
        $transfer->load('items');

        foreach ($transfer->items as $item) {
            WarehouseStockService::change((int) $transfer->to_warehouse_id, (int) $item->product_id, -((int) $item->quantity));
            WarehouseStockService::change((int) $transfer->from_warehouse_id, (int) $item->product_id, (int) $item->quantity);
        }

        $reference = $transfer->reference ?: ('TR-' . $transfer->id);
        StockMovement::where('reason', 'transfer')->where('reference', $reference)->delete();
    }

    private function selectableWarehouses()
    {
        return Warehouse::query()
            ->with('parent')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('type', '!=', 'personnel')
                    ->orWhereNotNull('parent_id');
            })
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();
    }
}
