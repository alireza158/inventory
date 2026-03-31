<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductVariant;
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

    private function scrapWarehouse(): Warehouse
    {
        $warehouse = Warehouse::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('type', 'scrap')
                  ->orWhere('name', 'like', '%ضایعات%');
            })
            ->orderBy('id')
            ->first();

        abort_if(!$warehouse, 422, 'انبار ضایعات پیدا نشد. لطفاً یک انبار ضایعات تعریف کن.');

        return $warehouse;
    }

    public function hub()
    {
        return view('vouchers.hub');
    }

    public function salesIndex(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $invoices = Invoice::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('uuid', 'like', "%{$q}%")
                        ->orWhere('customer_name', 'like', "%{$q}%")
                        ->orWhere('customer_mobile', 'like', "%{$q}%");
                });
            })
            ->whereIn('status', ['warehouse_pending', 'warehouse_collecting', 'warehouse_checking', 'warehouse_packing', 'warehouse_sent'])
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('vouchers.sales.index', compact('invoices', 'q'));
    }

    public function salesEdit(string $uuid)
    {
        $invoice = Invoice::query()
            ->with(['items.product', 'items.variant'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return view('vouchers.sales.edit', compact('invoice'));
    }

    public function salesUpdate(string $uuid, Request $request)
    {
        $invoice = Invoice::query()
            ->with(['items.product'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:invoice_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($invoice, $data) {
            $centralWarehouseId = WarehouseStockService::centralWarehouseId();
            $itemsById = $invoice->items->keyBy('id');

            foreach ($data['items'] as $row) {
                $item = $itemsById[(int) $row['id']] ?? null;
                if (!$item) {
                    abort(422, 'آیتم نامعتبر است.');
                }

                $newQty = (int) $row['quantity'];
                $newPrice = (int) $row['price'];
                $deltaQty = $newQty - (int) $item->quantity;

                if ($deltaQty !== 0) {
                    WarehouseStockService::change($centralWarehouseId, (int) $item->product_id, -$deltaQty);

                    $product = Product::query()->whereKey((int) $item->product_id)->lockForUpdate()->first();
                    if ($product) {
                        $product->update(['stock' => (int) $product->stock - $deltaQty]);
                    }
                }

                $item->update([
                    'quantity' => $newQty,
                    'price' => $newPrice,
                    'line_total' => $newQty * $newPrice,
                ]);
            }

            $subtotal = (int) $invoice->items()->sum('line_total');
            $total = max($subtotal + (int) $invoice->shipping_price - (int) $invoice->discount_amount, 0);
            $invoice->update([
                'subtotal' => $subtotal,
                'total' => $total,
            ]);
        });

        return redirect()->route('vouchers.sales.index')->with('success', '✅ آیتم‌های حواله فروش بروزرسانی شد.');
    }

    public function sectionIndex(string $type)
    {
        $voucherType = $this->resolveSectionType($type);
        $customerId = $requestCustomerId = request()->integer('customer_id');
        $dateFrom = request()->get('date_from');
        $dateTo = request()->get('date_to');
        $returnReason = (string) request()->get('return_reason', '');
        $returnReasons = WarehouseTransfer::returnReasonOptions();
        $customers = collect();

        if ($voucherType === WarehouseTransfer::TYPE_CUSTOMER_RETURN) {
            $customers = Customer::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'mobile']);
        }

        $vouchers = WarehouseTransfer::query()
            ->with(['fromWarehouse', 'toWarehouse', 'user', 'relatedInvoice', 'customer'])
            ->where('voucher_type', $voucherType)
            ->when(
                $voucherType === WarehouseTransfer::TYPE_CUSTOMER_RETURN && $customerId > 0,
                fn ($q) => $q->where('customer_id', $customerId)
            )
            ->when($dateFrom, fn ($q) => $q->whereDate('transferred_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('transferred_at', '<=', $dateTo))
            ->when(
                $voucherType === WarehouseTransfer::TYPE_CUSTOMER_RETURN
                && $returnReason !== ''
                && isset($returnReasons[$returnReason]),
                fn ($q) => $q->where('return_reason', $returnReason)
            )
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $view = match ($type) {
            'return-from-sale' => 'vouchers.section',
            'scrap' => 'vouchers.scrap.index',
            'personnel' => 'vouchers.personnel.index',
            'transfer' => 'vouchers.transfer.index',
            default => 'vouchers.section',
        };

        return view($view, compact(
            'vouchers',
            'type',
            'voucherType',
            'customers',
            'requestCustomerId',
            'dateFrom',
            'dateTo',
            'returnReason',
            'returnReasons'
        ));
    }

    public function sectionCreate(string $type)
    {
        if ($type === 'return-from-sale') {
            return $this->returnCreate();
        }

        return match ($type) {
            'scrap' => $this->scrapCreate(),
            'personnel' => $this->personnelCreate(),
            'transfer' => $this->transferCreate(),
            default => abort(404),
        };
    }

    public function sectionStore(string $type, Request $request)
    {
        if ($type === 'return-from-sale') {
            return $this->returnStore($request);
        }

        $fixedVoucherType = $this->resolveSectionType($type);
        $request->merge(['voucher_type' => $fixedVoucherType]);

        return $this->store($request);
    }

    public function customerInvoices(Customer $customer)
    {
        $invoices = Invoice::query()
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->get(['uuid', 'customer_name', 'created_at']);

        return response()->json($invoices);
    }

    public function returnCreate()
    {
        $customers = Customer::query()
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'mobile']);

        $warehouses = $this->selectableWarehouses()
            ->where('type', '!=', 'personnel')
            ->values();

        $returnReasons = WarehouseTransfer::returnReasonOptions();

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $variants = ProductVariant::query()
            ->orderBy('variant_name')
            ->get(['id', 'product_id', 'variant_name', 'variant_code', 'stock', 'reserved']);

        return view('vouchers.return-create', compact('customers', 'warehouses', 'returnReasons', 'products', 'variants'));
    }

    public function returnStore(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'related_invoice_uuid' => ['required', 'exists:invoices,uuid'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'return_reason' => ['required', 'in:' . implode(',', array_keys(WarehouseTransfer::returnReasonOptions()))],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $invoice = Invoice::query()->with('items')->where('uuid', $data['related_invoice_uuid'])->firstOrFail();

        if ((int) $invoice->customer_id !== (int) $data['customer_id']) {
            abort(422, 'فاکتور انتخابی متعلق به مشتری انتخاب‌شده نیست.');
        }

        foreach ($data['items'] as $index => $row) {
            $validVariant = ProductVariant::query()
                ->whereKey((int) $row['variant_id'])
                ->where('product_id', (int) $row['product_id'])
                ->exists();

            if (!$validVariant) {
                abort(422, 'ردیف ' . ($index + 1) . ': تنوع انتخابی برای محصول معتبر نیست.');
            }
        }

        $requestedByVariant = [];
        $seenVariantRows = [];

        foreach ($data['items'] as $index => $row) {
            $variantId = (int) $row['variant_id'];
            $key = ((int) $row['product_id']) . ':' . $variantId;

            if (isset($seenVariantRows[$key])) {
                abort(422, 'ردیف ' . ($index + 1) . ': این محصول/تنوع تکراری است و فقط یک‌بار می‌تواند ثبت شود.');
            }

            $seenVariantRows[$key] = true;
            $requestedByVariant[$variantId] = ($requestedByVariant[$variantId] ?? 0) + (int) $row['quantity'];
        }

        $alreadyReturnedByVariant = WarehouseTransfer::query()
            ->where('voucher_type', WarehouseTransfer::TYPE_CUSTOMER_RETURN)
            ->where('related_invoice_id', $invoice->id)
            ->with('items')
            ->get()
            ->flatMap->items
            ->groupBy('product_variant_id')
            ->map(fn ($items) => (int) $items->sum('quantity'));

        $invoiceQtyByVariant = $invoice->items
            ->groupBy('variant_id')
            ->map(fn ($items) => (int) $items->sum('quantity'));

        foreach ($requestedByVariant as $variantId => $requestedQty) {
            $invoiced = (int) ($invoiceQtyByVariant[$variantId] ?? 0);
            $alreadyReturned = (int) ($alreadyReturnedByVariant[$variantId] ?? 0);
            $remaining = max($invoiced - $alreadyReturned, 0);

            if ($requestedQty > $remaining) {
                abort(422, "مقدار برگشتی برای این تنوع بیشتر از تعداد مجاز است. باقی‌مانده مجاز: {$remaining}");
            }
        }

        $centralWarehouseId = WarehouseStockService::centralWarehouseId();

        $payload = [
            'voucher_type' => WarehouseTransfer::TYPE_CUSTOMER_RETURN,
            'from_warehouse_id' => $centralWarehouseId,
            'to_warehouse_id' => (int) $data['to_warehouse_id'],
            'related_invoice_uuid' => $data['related_invoice_uuid'],
            'return_reason' => $data['return_reason'],
            'reference' => $data['reference'] ?? null,
            'note' => $data['note'] ?? null,
            'items' => array_map(fn ($it) => [
                'category_id' => (int) Product::query()->whereKey((int) $it['product_id'])->value('category_id'),
                'product_id' => (int) $it['product_id'],
                'variant_id' => (int) $it['variant_id'],
                'quantity' => (int) $it['quantity'],
            ], $data['items']),
        ];

        DB::transaction(function () use ($payload) {
            $this->createTransfer($payload, now());
        });

        return redirect()->route('vouchers.section.index', 'return-from-sale')->with('success', 'برگشت از فروش ثبت شد.');
    }


    public function saleDeliveryIndex(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $invoices = Invoice::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('uuid', 'like', "%{$q}%")
                        ->orWhere('customer_name', 'like', "%{$q}%")
                        ->orWhere('customer_mobile', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('vouchers.sale-delivery.index', compact('invoices', 'q'));
    }

    public function saleDeliveryEdit(string $uuid)
    {
        $invoice = Invoice::query()->with(['items.product', 'items.variant'])->where('uuid', $uuid)->firstOrFail();

        return view('vouchers.sale-delivery.edit', compact('invoice'));
    }

    public function saleDeliveryUpdate(string $uuid, Request $request)
    {
        $invoice = Invoice::query()->with(['items.product'])->where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'status' => ['required', 'in:warehouse_pending,warehouse_collecting,warehouse_checking,warehouse_packing,warehouse_sent,canceled'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:invoice_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($invoice, $data) {
            $centralWarehouseId = WarehouseStockService::centralWarehouseId();
            $map = collect($data['items'])->keyBy(fn ($it) => (int) $it['id']);

            foreach ($invoice->items as $item) {
                $payload = $map[(int) $item->id] ?? null;
                if (!$payload) {
                    continue;
                }

                $oldQty = (int) $item->quantity;
                $newQty = (int) $payload['quantity'];
                $delta = $newQty - $oldQty;

                if ($delta !== 0) {
                    WarehouseStockService::change($centralWarehouseId, (int) $item->product_id, -$delta);

                    $product = Product::query()->whereKey((int) $item->product_id)->lockForUpdate()->first();
                    if ($product) {
                        $before = (int) $product->stock;
                        $after = $before - $delta;
                        $product->update(['stock' => $after]);

                        StockMovement::create([
                            'product_id' => $product->id,
                            'user_id' => auth()->id(),
                            'type' => $delta > 0 ? 'out' : 'in',
                            'reason' => 'sale_edit',
                            'quantity' => abs($delta),
                            'stock_before' => $before,
                            'stock_after' => $after,
                            'reference' => $invoice->uuid,
                            'note' => 'اصلاح اقلام حواله فروش کالا',
                        ]);
                    }
                }

                $newPrice = (int) $payload['price'];
                $item->update([
                    'quantity' => $newQty,
                    'price' => $newPrice,
                    'line_total' => $newQty * $newPrice,
                ]);
            }

            $subtotal = (int) $invoice->items()->sum('line_total');
            $total = max($subtotal + (int) $invoice->shipping_price - (int) $invoice->discount_amount, 0);

            $invoice->update([
                'subtotal' => $subtotal,
                'total' => $total,
                'status' => $data['status'],
            ]);
        });

        return redirect()->route('vouchers.sale-delivery.index')->with('success', 'حواله فروش کالا بروزرسانی شد.');
    }

    public function outputs(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $outputs = StockMovement::query()
            ->with(['product', 'user'])
            ->where('type', 'out')
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$q}%"));
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
            ->when(
                $voucherType !== 'all' && $voucherType !== WarehouseTransfer::TYPE_SALE,
                fn ($q) => $q->where('voucher_type', $voucherType)
            )
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

    private function scrapCreate()
    {
        $products = Product::query()
            ->select('id', 'name', 'sku', 'code', 'category_id', 'stock')
            ->orderBy('name')
            ->get();

        $variants = ProductVariant::query()
            ->orderBy('product_id')
            ->orderBy('variant_name')
            ->get([
                'id',
                'product_id',
                'variant_name',
                'variant_code',
                'stock',
                'reserved',
            ]);

        $scrapWarehouse = $this->scrapWarehouse();

        $fromWarehouses = $this->selectableWarehouses()
            ->where('type', '!=', 'personnel')
            ->filter(fn ($w) => (int) $w->id !== (int) $scrapWarehouse->id)
            ->values();

        return view('vouchers.scrap.create', compact(
            'products',
            'variants',
            'fromWarehouses',
            'scrapWarehouse'
        ));
    }

    private function personnelCreate()
    {
        $categories = Category::orderBy('name')->get();
        $products = Product::select('id', 'name', 'sku', 'category_id', 'price')->orderBy('name')->get();

        $variants = ProductVariant::query()
            ->leftJoin('model_lists', 'model_lists.id', '=', 'product_variants.model_list_id')
            ->orderBy('product_variants.variant_name')
            ->get([
                'product_variants.id',
                'product_variants.product_id',
                'product_variants.variant_name',
                'product_variants.variant_code',
                'product_variants.variety_code',
                'product_variants.stock',
                'model_lists.model_name as model_name',
            ]);

        $warehouses = $this->selectableWarehouses();
        $fromWarehouses = $warehouses->where('type', '!=', 'personnel')->values();
        $personnelWarehouses = $warehouses->where('type', 'personnel')->whereNotNull('parent_id')->values();

        return view('vouchers.personnel.create', compact('categories', 'products', 'variants', 'fromWarehouses', 'personnelWarehouses'));
    }

    private function transferCreate()
    {
        $categories = Category::orderBy('name')->get();
        $products = Product::select('id', 'name', 'sku', 'category_id', 'price')->orderBy('name')->get();

        $variants = ProductVariant::query()
            ->leftJoin('model_lists', 'model_lists.id', '=', 'product_variants.model_list_id')
            ->orderBy('product_variants.variant_name')
            ->get([
                'product_variants.id',
                'product_variants.product_id',
                'product_variants.variant_name',
                'product_variants.variant_code',
                'product_variants.variety_code',
                'product_variants.stock',
                'model_lists.model_name as model_name',
            ]);

        $warehouses = $this->selectableWarehouses()->where('type', '!=', 'personnel')->values();

        return view('vouchers.transfer.create', compact('categories', 'products', 'variants', 'warehouses'));
    }

    private function createWithType(?string $fixedVoucherType = null, ?string $sectionSlug = null)
    {
        $categories = Category::orderBy('name')->get();
        $products = Product::select('id', 'name', 'sku', 'category_id', 'price')->orderBy('name')->get();
        $warehouses = $this->selectableWarehouses();
        $invoices = Invoice::query()->latest('id')->limit(300)->get(['id', 'uuid', 'customer_name']);
        $centralWarehouseId = WarehouseStockService::centralWarehouseId();
        $voucher = null;

        return view('vouchers.create', compact(
            'categories',
            'products',
            'warehouses',
            'voucher',
            'invoices',
            'centralWarehouseId',
            'fixedVoucherType',
            'sectionSlug'
        ));
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

        return view('vouchers.create', compact(
            'voucher',
            'categories',
            'products',
            'warehouses',
            'invoices',
            'centralWarehouseId',
            'fixedVoucherType',
            'sectionSlug'
        ));
    }

    public function invoiceProducts(string $uuid)
    {
        $invoice = Invoice::query()->with('items.product', 'items.variant')->where('uuid', $uuid)->firstOrFail();

        $returnedQtyByVariant = WarehouseTransfer::query()
            ->where('voucher_type', WarehouseTransfer::TYPE_CUSTOMER_RETURN)
            ->where('related_invoice_id', $invoice->id)
            ->with('items')
            ->get()
            ->flatMap->items
            ->groupBy('product_variant_id')
            ->map(fn ($items) => (int) $items->sum('quantity'));

        $products = $invoice->items
            ->groupBy(function ($item) {
                return ((int) $item->product_id) . ':' . ((int) $item->variant_id);
            })
            ->map(function ($items) use ($returnedQtyByVariant) {
                $first = $items->first();
                $invoicedQty = (int) $items->sum('quantity');
                $variantId = (int) ($first->variant_id ?? 0);
                $returnedQty = (int) ($returnedQtyByVariant[$variantId] ?? 0);

                return [
                    'product_id' => (int) $first->product_id,
                    'variant_id' => $variantId,
                    'category_id' => (int) ($first->product?->category_id ?? 0),
                    'name' => $first->product?->name ?? ('#' . (int) $first->product_id),
                    'product_code' => (string) ($first->product?->code ?? ''),
                    'variant_name' => (string) ($first->variant?->variant_name ?? ''),
                    'variant_code' => (string) ($first->variant?->variant_code ?? ''),
                    'variant_stock' => (int) ($first->variant?->stock ?? 0),
                    'qty' => $invoicedQty,
                    'remaining_qty' => max($invoicedQty - $returnedQty, 0),
                ];
            })
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
        $returnReasons = implode(',', array_keys(WarehouseTransfer::returnReasonOptions()));

        $data = $request->validate([
            'voucher_type' => ['required', 'in:' . $types],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
            'related_invoice_uuid' => ['nullable', 'string', 'exists:invoices,uuid'],
            'beneficiary_name' => ['nullable', 'string', 'max:255'],
            'return_reason' => ['nullable', 'in:' . $returnReasons],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'exists:categories,id'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.personnel_asset_code' => ['nullable', 'digits:4'],
        ]);

        $voucherType = (string) ($data['voucher_type'] ?? '');
        $fromWarehouse = Warehouse::findOrFail((int) $data['from_warehouse_id']);
        $toWarehouse = Warehouse::findOrFail((int) $data['to_warehouse_id']);
        $scrapWarehouse = $this->scrapWarehouse();

        if ($voucherType === WarehouseTransfer::TYPE_SCRAP) {
            if ((int) $toWarehouse->id !== (int) $scrapWarehouse->id) {
                abort(422, 'در حواله ضایعات، مقصد باید انبار ضایعات باشد.');
            }

            if ((int) $fromWarehouse->id === (int) $scrapWarehouse->id) {
                abort(422, 'در حواله ضایعات، مبدا نمی‌تواند خود انبار ضایعات باشد.');
            }
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
        $invoiceVariantIds = [];
        $invoiceVariantRemaining = [];

        if (!empty($data['related_invoice_uuid'])) {
            $invoice = Invoice::query()->with('items')->where('uuid', $data['related_invoice_uuid'])->firstOrFail();

            $invoiceProductIds = $invoice->items->pluck('product_id')->map(fn ($v) => (int) $v)->unique()->values()->all();
            $invoiceVariantIds = $invoice->items->pluck('variant_id')->map(fn ($v) => (int) $v)->unique()->values()->all();

            $alreadyReturnedByVariant = WarehouseTransfer::query()
                ->where('voucher_type', WarehouseTransfer::TYPE_CUSTOMER_RETURN)
                ->where('related_invoice_id', $invoice->id)
                ->with('items')
                ->get()
                ->flatMap->items
                ->groupBy('product_variant_id')
                ->map(fn ($items) => (int) $items->sum('quantity'));

            $invoicedByVariant = $invoice->items
                ->groupBy('variant_id')
                ->map(fn ($items) => (int) $items->sum('quantity'));

            foreach ($invoicedByVariant as $variantId => $qty) {
                $invoiceVariantRemaining[(int) $variantId] = max(
                    (int) $qty - (int) ($alreadyReturnedByVariant[(int) $variantId] ?? 0),
                    0
                );
            }
        }

        $seenVariants = [];

        foreach ($data['items'] as $index => $item) {
            $rowNo = $index + 1;

            $belongsToCategory = Product::query()
                ->whereKey((int) $item['product_id'])
                ->where('category_id', (int) $item['category_id'])
                ->exists();

            if (!$belongsToCategory) {
                abort(422, 'ردیف ' . $rowNo . ': کالا در دسته‌بندی انتخابی نیست.');
            }

            $variant = ProductVariant::query()
                ->whereKey((int) $item['variant_id'])
                ->where('product_id', (int) $item['product_id'])
                ->first();

            if (!$variant) {
                abort(422, 'ردیف ' . $rowNo . ': تنوع/طرح انتخابی متعلق به کالای انتخاب‌شده نیست.');
            }

            if ($voucherType === WarehouseTransfer::TYPE_CUSTOMER_RETURN && !in_array((int) $item['product_id'], $invoiceProductIds, true)) {
                abort(422, 'ردیف ' . $rowNo . ': کالا باید از اقلام همان فاکتور مشتری انتخاب شود.');
            }

            if ($voucherType === WarehouseTransfer::TYPE_CUSTOMER_RETURN && !in_array((int) $item['variant_id'], $invoiceVariantIds, true)) {
                abort(422, 'ردیف ' . $rowNo . ': تنوع باید از اقلام همان فاکتور مشتری انتخاب شود.');
            }

            if ($voucherType !== WarehouseTransfer::TYPE_CUSTOMER_RETURN) {
                $available = max(0, ((int) $variant->stock) - ((int) ($variant->reserved ?? 0)));

                if ((int) $item['quantity'] > $available) {
                    abort(422, 'ردیف ' . $rowNo . ': مقدار انتخابی از موجودی تنوع بیشتر است. موجودی فعلی: ' . $available);
                }
            } else {
                $remaining = (int) ($invoiceVariantRemaining[(int) $item['variant_id']] ?? 0);

                if ((int) $item['quantity'] > $remaining) {
                    abort(422, 'ردیف ' . $rowNo . ': مقدار از سقف مجاز مرجوعی این تنوع بیشتر است. باقیمانده مجاز: ' . $remaining);
                }
            }

            $dupKey = ((int) $item['product_id']) . ':' . ((int) $item['variant_id']);
            if (isset($seenVariants[$dupKey])) {
                abort(422, 'ردیف ' . $rowNo . ': این محصول/تنوع تکراری است و فقط یک‌بار می‌تواند ثبت شود.');
            }
            $seenVariants[$dupKey] = true;
        }

        if (($data['voucher_type'] ?? null) === WarehouseTransfer::TYPE_CUSTOMER_RETURN && empty($data['return_reason'])) {
            abort(422, 'در حواله مرجوعی مشتری، انتخاب علت برگشت الزامی است.');
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
            'return_reason' => $data['return_reason'] ?? null,
            'user_id' => auth()->id(),
            'transferred_at' => $transferredAt,
            'total_amount' => 0,
            'note' => $data['note'] ?? null,
        ]);

        $sum = 0;

        foreach ($data['items'] as $item) {
            $product = Product::findOrFail($item['product_id']);

            $variant = ProductVariant::query()
                ->whereKey((int) ($item['variant_id'] ?? 0))
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (!$variant) {
                abort(422, 'تنوع انتخابی برای کالا معتبر نیست.');
            }

            $qty = (int) $item['quantity'];

            $invoiceItemPrice = null;
            if ($voucherType === WarehouseTransfer::TYPE_CUSTOMER_RETURN && $relatedInvoice) {
                $invoiceItemPrice = (int) ($relatedInvoice->items()
                    ->where('product_id', $product->id)
                    ->where('variant_id', $variant->id)
                    ->value('price') ?? 0);
            }

            $unitPrice = in_array($voucherType, [WarehouseTransfer::TYPE_SCRAP, WarehouseTransfer::TYPE_SHOWROOM], true)
                ? 0
                : ($voucherType === WarehouseTransfer::TYPE_CUSTOMER_RETURN ? (int) $invoiceItemPrice : (int) ($product->price ?? 0));

            $lineTotal = $qty * $unitPrice;
            $sum += $lineTotal;

            if ($voucherType === WarehouseTransfer::TYPE_CUSTOMER_RETURN) {
                WarehouseStockService::change((int) $data['to_warehouse_id'], (int) $item['product_id'], $qty);
                $this->syncGlobalProductStock((int) $item['product_id'], +$qty);

                $variant->update([
                    'stock' => (int) $variant->stock + $qty,
                ]);

                $movementType = 'in';
                $movementNote = 'مرجوعی از مشتری - ' . ($variant->variant_name ?: 'تنوع');
            } elseif ($voucherType === WarehouseTransfer::TYPE_SCRAP) {
                $available = max(0, ((int) $variant->stock) - ((int) ($variant->reserved ?? 0)));
                if ($qty > $available) {
                    abort(422, 'موجودی تنوع برای ثبت ضایعات کافی نیست.');
                }

                WarehouseStockService::change((int) $data['from_warehouse_id'], (int) $item['product_id'], -$qty);
                WarehouseStockService::change((int) $data['to_warehouse_id'], (int) $item['product_id'], $qty);

                $this->syncGlobalProductStock((int) $item['product_id'], -$qty);

                $variant->update([
                    'stock' => (int) $variant->stock - $qty,
                ]);

                $movementType = 'out';
                $movementNote = 'انتقال به انبار ضایعات - ' . ($variant->variant_name ?: 'تنوع');
            } else {
                WarehouseStockService::change((int) $data['from_warehouse_id'], (int) $item['product_id'], -$qty);
                WarehouseStockService::change((int) $data['to_warehouse_id'], (int) $item['product_id'], $qty);

                $movementType = 'out';
                $movementNote = 'انتقال از ' . $transfer->fromWarehouse->name . ' به ' . $transfer->toWarehouse->name . ' - ' . ($variant->variant_name ?: 'تنوع');
            }

            $before = (int) $product->stock;

            $transfer->items()->create([
                'product_id' => $item['product_id'],
                'product_variant_id' => $variant->id,
                'variant_name' => $variant->variant_name,
                'variant_code' => $variant->variant_code,
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
            $variant = null;

            if (!empty($item->product_variant_id)) {
                $variant = ProductVariant::query()
                    ->whereKey((int) $item->product_variant_id)
                    ->lockForUpdate()
                    ->first();
            }

            if ($transfer->voucher_type === WarehouseTransfer::TYPE_CUSTOMER_RETURN) {
                WarehouseStockService::change((int) $transfer->to_warehouse_id, (int) $item->product_id, -((int) $item->quantity));
                $this->syncGlobalProductStock((int) $item->product_id, -((int) $item->quantity));

                if ($variant) {
                    if ((int) $variant->stock < (int) $item->quantity) {
                        abort(422, 'امکان ویرایش/حذف وجود ندارد؛ موجودی تنوع کمتر از مقدار مرجوعی است.');
                    }

                    $variant->update([
                        'stock' => (int) $variant->stock - (int) $item->quantity,
                    ]);
                }
            } elseif ($transfer->voucher_type === WarehouseTransfer::TYPE_SCRAP) {
                WarehouseStockService::change((int) $transfer->from_warehouse_id, (int) $item->product_id, (int) $item->quantity);
                WarehouseStockService::change((int) $transfer->to_warehouse_id, (int) $item->product_id, -((int) $item->quantity));

                $this->syncGlobalProductStock((int) $item->product_id, (int) $item->quantity);

                if ($variant) {
                    $variant->update([
                        'stock' => (int) $variant->stock + (int) $item->quantity,
                    ]);
                }
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

        StockMovement::whereIn('reason', ['transfer', 'return'])
            ->where('reference', $reference)
            ->delete();
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