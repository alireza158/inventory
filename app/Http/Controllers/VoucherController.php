<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\WarehouseStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{

    private function sectionTypeMap(): array
    {
        return [
            'return-from-sale' => WarehouseTransfer::TYPE_CUSTOMER_RETURN,
            'scrap' => WarehouseTransfer::TYPE_SCRAP,
            'personnel' => WarehouseTransfer::TYPE_PERSONNEL_ASSET,
            'transfer' => WarehouseTransfer::TYPE_BETWEEN_WAREHOUSES,
        ];
    }

    private function resolveSectionType(string $type): string
    {
        $map = $this->sectionTypeMap();
        abort_unless(isset($map[$type]), 404);

        return $map[$type];
    }

    public function hub()
    {
        return view('vouchers.hub');
    }

    public function sectionIndex(string $type)
    {
        $voucherType = $this->resolveSectionType($type);

        $vouchers = WarehouseTransfer::query()
            ->with(['fromWarehouse', 'toWarehouse', 'user', 'relatedInvoice'])
            ->where('voucher_type', $voucherType)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('vouchers.section', compact('vouchers', 'type', 'voucherType'));
    }

    public function sectionCreate(string $type)
    {
        $fixedVoucherType = $this->resolveSectionType($type);
        return $this->createWithType($fixedVoucherType, $type);
    }

    public function sectionStore(string $type, Request $request)
    {
        $fixedVoucherType = $this->resolveSectionType($type);
        $request->merge(['voucher_type' => $fixedVoucherType]);

        return $this->store($request);
    }

    public function outputs(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $outputs = StockMovement::query()
            ->with(['product', 'user'])
            ->where('type', 'out')
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('product', fn($p) => $p->where('name', 'like', "%{$q}%"));
            })
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('vouchers.outputs', compact('outputs', 'q'));
    }
    public function index(Request $request)
    {
        $voucherNo = trim((string) $request->get('voucher_no', ''));
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $voucherType = (string) $request->get('voucher_type', 'all');

        $query = WarehouseTransfer::with(['fromWarehouse', 'toWarehouse', 'user', 'relatedInvoice', 'customer'])
            ->when($voucherNo !== '', function ($q) use ($voucherNo) {
                $q->where(function ($inner) use ($voucherNo) {
                    $inner->where('id', (int) $voucherNo)
                        ->orWhere('reference', 'like', "%{$voucherNo}%");
                });
            })
            ->when($voucherType !== 'all' && $voucherType !== WarehouseTransfer::TYPE_SALE, fn ($q) => $q->where('voucher_type', $voucherType))
            ->when($dateFrom, fn ($q) => $q->whereDate('transferred_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('transferred_at', '<=', $dateTo));

        $salesInvoices = Invoice::query()
            ->with(['items.product'])
            ->when($voucherNo !== '', function ($q) use ($voucherNo) {
                $q->where(function ($inner) use ($voucherNo) {
                    $inner->where('uuid', 'like', "%{$voucherNo}%")
                        ->orWhere('customer_name', 'like', "%{$voucherNo}%")
                        ->orWhere('customer_mobile', 'like', "%{$voucherNo}%");
                });
            })
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'sales_page')
            ->withQueryString();

        $totalAllAmount = (int) WarehouseTransfer::sum('total_amount');
        $totalAllCount = (int) WarehouseTransfer::count();

        $vouchers = $query->latest('transferred_at')
            ->paginate(20, ['*'], 'vouchers_page')
            ->withQueryString();

        return view('vouchers.index', compact('vouchers', 'salesInvoices', 'totalAllAmount', 'totalAllCount', 'voucherType'));
    }

    public function create()
    {
        return $this->createWithType();
    }

    private function createWithType(?string $fixedVoucherType = null, ?string $sectionSlug = null)
    {
        $categories = Category::orderBy('name')->get();
        $products = Product::select('id', 'name', 'sku', 'category_id', 'price')->orderBy('name')->get();
        $warehouses = $this->selectableWarehouses();
        $invoices = Invoice::query()->latest('id')->limit(300)->get(['id', 'uuid', 'customer_name']);
        $centralWarehouseId = WarehouseStockService::centralWarehouseId();
        $voucher = null;

        return view('vouchers.create', compact('categories', 'products', 'warehouses', 'voucher', 'invoices', 'centralWarehouseId', 'fixedVoucherType', 'sectionSlug'));
    }

    public function edit(WarehouseTransfer $voucher)
    {
        $categories = Category::orderBy('name')->get();
        $products = Product::select('id', 'name', 'sku', 'category_id', 'price')->orderBy('name')->get();
        $warehouses = $this->selectableWarehouses();
        $invoices = Invoice::query()->latest('id')->limit(300)->get(['id', 'uuid', 'customer_name']);
        $centralWarehouseId = WarehouseStockService::centralWarehouseId();
        $voucher->load('items.product', 'relatedInvoice');

                $fixedVoucherType = null;
        $sectionSlug = null;

        return view('vouchers.create', compact('voucher', 'categories', 'products', 'warehouses', 'invoices', 'centralWarehouseId', 'fixedVoucherType', 'sectionSlug'));
    }

    public function invoiceProducts(string $uuid)
    {
        $invoice = Invoice::query()->with('items.product')->where('uuid', $uuid)->firstOrFail();

        $products = $invoice->items
            ->map(fn ($item) => [
                'product_id' => (int) $item->product_id,
                'category_id' => (int) ($item->product?->category_id ?? 0),
                'name' => $item->product?->name ?? ('#' . $item->product_id),
                'qty' => (int) $item->quantity,
            ])
            ->values();

        return response()->json([
            'invoice_uuid' => $invoice->uuid,
            'products' => $products,
        ]);
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
        $types = implode(',', array_keys(WarehouseTransfer::typeOptions()));

        $data = $request->validate([
            'voucher_type' => ['required', 'in:' . $types],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
            'related_invoice_uuid' => ['nullable', 'string', 'exists:invoices,uuid'],
            'beneficiary_name' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'exists:categories,id'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.personnel_asset_code' => ['nullable', 'digits:4'],
        ]);

        $voucherType = (string) ($data['voucher_type'] ?? '');
        $fromWarehouse = Warehouse::findOrFail((int) $data['from_warehouse_id']);
        $toWarehouse = Warehouse::findOrFail((int) $data['to_warehouse_id']);
        $centralWarehouseId = WarehouseStockService::centralWarehouseId();

        if ($voucherType === WarehouseTransfer::TYPE_SCRAP && (int) $fromWarehouse->id !== $centralWarehouseId) {
            abort(422, 'برای حواله ضایعات، مبدا باید انبار مرکزی باشد.');
        }

        if ($voucherType === WarehouseTransfer::TYPE_PERSONNEL_ASSET && !$toWarehouse->isPersonnelLeaf()) {
            abort(422, 'در حواله اموال پرسنل، مقصد باید فقط یکی از پرسنل تعریف‌شده باشد.');
        }

        if (in_array($voucherType, [WarehouseTransfer::TYPE_BETWEEN_WAREHOUSES, WarehouseTransfer::TYPE_SHOWROOM], true)) {
            if ($toWarehouse->isPersonnelWarehouse() || $fromWarehouse->isPersonnelWarehouse()) {
                abort(422, 'در حواله بین انباری/شوروم، انبار پرسنل مجاز نیست.');
            }
        }

        if ($voucherType === WarehouseTransfer::TYPE_CUSTOMER_RETURN && empty($data['related_invoice_uuid'])) {
            abort(422, 'در حواله مرجوعی مشتری، انتخاب فاکتور مشتری الزامی است.');
        }

        $invoiceProductIds = [];
        if (!empty($data['related_invoice_uuid'])) {
            $invoice = Invoice::query()->with('items')->where('uuid', $data['related_invoice_uuid'])->firstOrFail();
            $invoiceProductIds = $invoice->items->pluck('product_id')->map(fn($v)=>(int)$v)->unique()->values()->all();
        }

        foreach ($data['items'] as $index => $item) {
            $belongsToCategory = Product::whereKey($item['product_id'])
                ->where('category_id', $item['category_id'])
                ->exists();

            if (!$belongsToCategory) {
                abort(422, 'ردیف ' . ($index + 1) . ': کالا در دسته‌بندی انتخابی نیست.');
            }

            if ($voucherType === WarehouseTransfer::TYPE_CUSTOMER_RETURN && !in_array((int) $item['product_id'], $invoiceProductIds, true)) {
                abort(422, 'ردیف ' . ($index + 1) . ': کالا باید از اقلام همان فاکتور مشتری انتخاب شود.');
            }

            if ($voucherType === WarehouseTransfer::TYPE_CUSTOMER_RETURN && !in_array((int) $item['product_id'], $invoiceProductIds, true)) {
                abort(422, 'ردیف ' . ($index + 1) . ': کالا باید از اقلام همان فاکتور مشتری انتخاب شود.');
            }
        }

        if (($data['voucher_type'] ?? null) === WarehouseTransfer::TYPE_CUSTOMER_RETURN && empty($data['related_invoice_uuid'])) {
            abort(422, 'در حواله مرجوعی مشتری، انتخاب فاکتور مشتری الزامی است.');
        }

        return $data;
    }

    private function createTransfer(array $data, $transferredAt): WarehouseTransfer
    {
        $toWarehouse = Warehouse::findOrFail($data['to_warehouse_id']);
        $voucherType = (string) $data['voucher_type'];
        $relatedInvoice = null;

        if (!empty($data['related_invoice_uuid'])) {
            $relatedInvoice = Invoice::where('uuid', $data['related_invoice_uuid'])->firstOrFail();
        }

        $transfer = WarehouseTransfer::create([
            'reference' => $data['reference'] ?? null,
            'voucher_type' => $voucherType,
            'from_warehouse_id' => $data['from_warehouse_id'],
            'to_warehouse_id' => $data['to_warehouse_id'],
            'related_invoice_id' => $relatedInvoice?->id,
            'customer_id' => $relatedInvoice?->customer_id,
            'beneficiary_name' => $data['beneficiary_name'] ?? null,
            'user_id' => auth()->id(),
            'transferred_at' => $transferredAt,
            'total_amount' => 0,
            'note' => $data['note'] ?? null,
        ]);

        $sum = 0;

        foreach ($data['items'] as $item) {
            $product = Product::findOrFail($item['product_id']);
            $qty = (int) $item['quantity'];
            $unitPrice = in_array($voucherType, [WarehouseTransfer::TYPE_SCRAP, WarehouseTransfer::TYPE_SHOWROOM], true)
                ? 0
                : (int) ($product->price ?? 0);
            $lineTotal = $qty * $unitPrice;
            $sum += $lineTotal;

            if ($voucherType === WarehouseTransfer::TYPE_CUSTOMER_RETURN) {
                WarehouseStockService::change((int) $data['to_warehouse_id'], (int) $item['product_id'], $qty);
                $this->syncGlobalProductStock((int) $item['product_id'], +$qty);
                $movementType = 'in';
                $movementNote = 'مرجوعی از مشتری';
            } elseif ($voucherType === WarehouseTransfer::TYPE_SCRAP) {
                WarehouseStockService::change((int) $data['from_warehouse_id'], (int) $item['product_id'], -$qty);
                $this->syncGlobalProductStock((int) $item['product_id'], -$qty);
                $movementType = 'out';
                $movementNote = 'خروج ضایعات از انبار مرکزی';
            } else {
                WarehouseStockService::change((int) $data['from_warehouse_id'], (int) $item['product_id'], -$qty);
                WarehouseStockService::change((int) $data['to_warehouse_id'], (int) $item['product_id'], $qty);
                $movementType = 'out';
                $movementNote = 'انتقال از ' . $transfer->fromWarehouse->name . ' به ' . $transfer->toWarehouse->name;
            }

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
                'type' => $movementType,
                'reason' => $voucherType === WarehouseTransfer::TYPE_CUSTOMER_RETURN ? 'return' : 'transfer',
                'quantity' => $qty,
                'stock_before' => $before,
                'stock_after' => (int) Product::find($product->id)->stock,
                'reference' => $transfer->reference ?: ('TR-' . $transfer->id),
                'note' => $movementNote,
            ]);
        }

        $transfer->update(['total_amount' => $sum]);

        if ($voucherType === WarehouseTransfer::TYPE_CUSTOMER_RETURN && $relatedInvoice && $relatedInvoice->customer_id) {
            CustomerLedger::create([
                'customer_id' => $relatedInvoice->customer_id,
                'type' => 'credit',
                'amount' => max($sum, 0),
                'reference_type' => WarehouseTransfer::class,
                'reference_id' => $transfer->id,
                'note' => 'مرجوعی کالا از فاکتور ' . $relatedInvoice->uuid,
            ]);
        }

        return $transfer;
    }

    private function rollbackTransfer(WarehouseTransfer $transfer): void
    {
        $transfer->load('items', 'relatedInvoice');

        foreach ($transfer->items as $item) {
            if ($transfer->voucher_type === WarehouseTransfer::TYPE_CUSTOMER_RETURN) {
                WarehouseStockService::change((int) $transfer->to_warehouse_id, (int) $item->product_id, -((int) $item->quantity));
                $this->syncGlobalProductStock((int) $item->product_id, -((int) $item->quantity));
            } elseif ($transfer->voucher_type === WarehouseTransfer::TYPE_SCRAP) {
                WarehouseStockService::change((int) $transfer->from_warehouse_id, (int) $item->product_id, (int) $item->quantity);
                $this->syncGlobalProductStock((int) $item->product_id, (int) $item->quantity);
            } else {
                WarehouseStockService::change((int) $transfer->to_warehouse_id, (int) $item->product_id, -((int) $item->quantity));
                WarehouseStockService::change((int) $transfer->from_warehouse_id, (int) $item->product_id, (int) $item->quantity);
            }
        }

        if ($transfer->voucher_type === WarehouseTransfer::TYPE_CUSTOMER_RETURN && $transfer->customer_id) {
            CustomerLedger::query()
                ->where('reference_type', WarehouseTransfer::class)
                ->where('reference_id', $transfer->id)
                ->where('type', 'credit')
                ->delete();
        }

        $reference = $transfer->reference ?: ('TR-' . $transfer->id);
        StockMovement::whereIn('reason', ['transfer', 'return'])->where('reference', $reference)->delete();
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

    private function syncGlobalProductStock(int $productId, int $delta): void
    {
        $product = Product::query()->whereKey($productId)->lockForUpdate()->first();
        if (!$product) {
            return;
        }

        $newStock = max(0, ((int) $product->stock) + $delta);
        $product->update(['stock' => $newStock]);
    }
}
