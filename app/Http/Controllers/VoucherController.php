<?php

namespace App\Http\Controllers;

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
        $q = trim((string)$request->get('q', ''));

        $vouchers = WarehouseTransfer::with(['fromWarehouse','toWarehouse','user'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where('reference', 'like', "%{$q}%")
                    ->orWhereHas('fromWarehouse', fn ($w) => $w->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('toWarehouse', fn ($w) => $w->where('name', 'like', "%{$q}%"));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $voucher = null;
        return view('vouchers.create', compact('products', 'warehouses', 'voucher'));
    }

    public function edit(WarehouseTransfer $voucher)
    {
        $products = Product::orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $voucher->load('items');

        return view('vouchers.create', compact('voucher', 'products', 'warehouses'));
    }

    public function store(Request $request)
    {
        $data = $this->validateTransfer($request);

        DB::transaction(function () use ($data) {
            $this->createTransfer($data);
        });

        return redirect()->route('vouchers.index')->with('success', 'حواله ثبت شد و موجودی بروزرسانی شد.');
    }

    public function update(Request $request, WarehouseTransfer $voucher)
    {
        $data = $this->validateTransfer($request);

        DB::transaction(function () use ($voucher, $data) {
            $this->rollbackTransfer($voucher);
            $voucher->items()->delete();
            $voucher->delete();
            $this->createTransfer($data);
        });

        return redirect()->route('vouchers.index')->with('success', 'حواله با موفقیت ویرایش شد.');
    }

    public function destroy(WarehouseTransfer $voucher)
    {
        DB::transaction(function () use ($voucher) {
            $this->rollbackTransfer($voucher);
            $voucher->delete();
        });

        return back()->with('success', 'حواله حذف شد و موجودی انبارها برگشت داده شد.');
    }

    private function validateTransfer(Request $request): array
    {
        return $request->validate([
            'from_warehouse_id' => ['required', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'transferred_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'integer', 'min:0'],
            'items.*.personnel_asset_code' => ['nullable', 'digits:4'],
        ]);
    }

    private function createTransfer(array $data): WarehouseTransfer
    {
        $toWarehouse = Warehouse::findOrFail($data['to_warehouse_id']);

        $transfer = WarehouseTransfer::create([
            'reference' => $data['reference'] ?? null,
            'from_warehouse_id' => $data['from_warehouse_id'],
            'to_warehouse_id' => $data['to_warehouse_id'],
            'user_id' => auth()->id(),
            'transferred_at' => $data['transferred_at'],
            'total_amount' => 0,
            'note' => $data['note'] ?? null,
        ]);

        $sum = 0;

        foreach ($data['items'] as $item) {
            $qty = (int) $item['quantity'];
            $unitPrice = (int) ($item['unit_price'] ?? 0);
            $lineTotal = $qty * $unitPrice;
            $sum += $lineTotal;

            WarehouseStockService::change((int) $data['from_warehouse_id'], (int) $item['product_id'], -$qty);
            WarehouseStockService::change((int) $data['to_warehouse_id'], (int) $item['product_id'], $qty);

            $product = Product::findOrFail($item['product_id']);
            $before = (int) $product->stock;

            $transfer->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'personnel_asset_code' => $toWarehouse->isPersonnelWarehouse() ? ($item['personnel_asset_code'] ?? null) : null,
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
}
