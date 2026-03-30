<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PreinvoiceOrder;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PreinvoiceController extends Controller
{
    public function create()
    {
        $shippingMethods = ShippingMethod::query()
            ->select(['id', 'name', 'price'])
            ->orderBy('name')
            ->get();

        return view('preinvoice.create', compact('shippingMethods'));
    }

    public function draftIndex()
    {
        $orders = PreinvoiceOrder::query()
            ->where('status', 'submitted_finance')
            ->orderByDesc('id')
            ->paginate(20);

        $canFinanceApprove = $this->canHandleFinanceActions();

        return view('preinvoice.drafts-index', compact('orders', 'canFinanceApprove'));
    }

    public function saveDraft(Request $request)
    {
        $validated = $this->validateDraftPayload($request);

        DB::transaction(function () use ($validated) {
            $customer = $this->resolveCustomer($validated);
            $shippingId = (int) $validated['shipping_id'];

            $order = PreinvoiceOrder::create([
                'uuid'        => (string) Str::uuid(),
                'created_by'  => auth()->id(),
                'status'      => 'submitted_finance',

                'customer_id'       => $customer?->id,
                'customer_name'     => $this->orderCustomerName($validated, $customer),
                'customer_mobile'   => $this->orderCustomerMobile($validated, $customer),
                'customer_address'  => $this->orderCustomerAddress($validated, $customer, $shippingId),
                'province_id'       => $this->orderProvinceId($validated, $customer, $shippingId),
                'city_id'           => $this->orderCityId($validated, $customer, $shippingId),

                'shipping_id'       => $shippingId,
                'shipping_price'    => (int) $this->resolveShippingPrice($shippingId),
                'discount_amount'   => (int) ($validated['discount_amount'] ?? 0),
                'total_price'       => (int) $validated['total_price'],
            ]);

            $this->syncItems($order, $validated['products']);
        });

        return redirect()->route('preinvoice.draft.index')
            ->with('success', '✅ پیش‌فاکتور ثبت و برای تایید مالی ارسال شد.');
    }

    public function editDraft(string $uuid)
    {
        $order = PreinvoiceOrder::with('items')->where('uuid', $uuid)->firstOrFail();

        $shippingMethods = ShippingMethod::query()
            ->select(['id', 'name', 'price'])
            ->orderBy('name')
            ->get();

        $canFinanceApprove = $this->canHandleFinanceActions();

        return view('preinvoice.edit', compact('order', 'shippingMethods', 'canFinanceApprove'));
    }

    public function updateDraft(string $uuid, Request $request)
    {
        $order = PreinvoiceOrder::with('items')->where('uuid', $uuid)->firstOrFail();
        abort_if($order->status !== 'submitted_finance', 403);

        $validated = $this->validateDraftPayload($request);

        DB::transaction(function () use ($order, $validated) {
            $customer = $this->resolveCustomer($validated);
            $shippingId = (int) $validated['shipping_id'];

            $order->update([
                'customer_id'       => $customer?->id,
                'customer_name'     => $this->orderCustomerName($validated, $customer),
                'customer_mobile'   => $this->orderCustomerMobile($validated, $customer),
                'customer_address'  => $this->orderCustomerAddress($validated, $customer, $shippingId),
                'province_id'       => $this->orderProvinceId($validated, $customer, $shippingId),
                'city_id'           => $this->orderCityId($validated, $customer, $shippingId),

                'shipping_id'       => $shippingId,
                'shipping_price'    => (int) $this->resolveShippingPrice($shippingId),
                'discount_amount'   => (int) ($validated['discount_amount'] ?? 0),
                'total_price'       => (int) $validated['total_price'],
            ]);

            $order->items()->delete();
            $this->syncItems($order, $validated['products']);
        });

        return back()->with('success', '✅ پیش‌فاکتور بروزرسانی شد.');
    }

    private function validateDraftPayload(Request $request): array
    {
        $shippingId = (int) $request->input('shipping_id');
        $isInPerson = $this->isInPersonShippingId($shippingId);

        return $request->validate([
            'customer_id'       => 'nullable|integer|exists:customers,id',
            'customer_name'     => 'required|string|max:255',
            'customer_mobile'   => 'required|string|max:20',
            'customer_address'  => $isInPerson ? 'nullable|string|max:1000' : 'required|string|max:1000',
            'province_id'       => $isInPerson ? 'nullable|integer' : 'required|integer',
            'city_id'           => 'nullable|integer',

            'shipping_id'       => 'required|integer|exists:shipping_methods,id',
            'shipping_price'    => 'nullable|integer|min:0',

            'discount_amount'   => 'nullable|integer|min:0',
            'total_price'       => 'required|integer|min:0',

            'products'              => 'required|array|min:1',
            'products.*.id'         => 'required|integer|exists:products,id,is_sellable,1',
            'products.*.variety_id' => 'required|integer',
            'products.*.quantity'   => 'required|integer|min:1',
            'products.*.price'      => 'nullable|integer|min:0',
        ], [
            'customer_name.required'    => 'نام مشتری الزامی است.',
            'customer_mobile.required'  => 'شماره موبایل مشتری الزامی است.',
            'customer_address.required' => 'برای روش‌های ارسال غیرحضوری، آدرس الزامی است.',
            'province_id.required'      => 'برای روش‌های ارسال غیرحضوری، استان الزامی است.',
            'products.required'         => 'حداقل یک محصول باید ثبت شود.',
            'products.min'              => 'حداقل یک محصول باید ثبت شود.',
        ]);
    }

    private function resolveCustomer(array $validated): ?Customer
    {
        $cid = (int) ($validated['customer_id'] ?? 0);
        if ($cid <= 0) return null;

        return Customer::query()->find($cid);
    }

    private function orderCustomerName(array $validated, ?Customer $customer): string
    {
        $name = trim((string) ($validated['customer_name'] ?? ''));
        if ($name !== '') return $name;

        if ($customer) {
            $full = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
            if ($full !== '') return $full;
        }

        return '';
    }

    private function orderCustomerMobile(array $validated, ?Customer $customer): string
    {
        $mobile = trim((string) ($validated['customer_mobile'] ?? ''));
        if ($mobile !== '') return $mobile;

        if ($customer && !empty($customer->mobile)) {
            return (string) $customer->mobile;
        }

        return '';
    }

    private function orderCustomerAddress(array $validated, ?Customer $customer, int $shippingId): string
    {
        if ($this->isInPersonShippingId($shippingId)) {
            return '';
        }

        $address = trim((string) ($validated['customer_address'] ?? ''));
        if ($address !== '') return $address;

        if ($customer && !empty($customer->address)) {
            return (string) $customer->address;
        }

        return '';
    }

    private function orderProvinceId(array $validated, ?Customer $customer, int $shippingId): ?int
    {
        if ($this->isInPersonShippingId($shippingId)) {
            return null;
        }

        if (!empty($validated['province_id'])) {
            return (int) $validated['province_id'];
        }

        if ($customer && !empty($customer->province_id)) {
            return (int) $customer->province_id;
        }

        return null;
    }

    private function orderCityId(array $validated, ?Customer $customer, int $shippingId): ?int
    {
        if ($this->isInPersonShippingId($shippingId)) {
            return null;
        }

        if (!empty($validated['city_id'])) {
            return (int) $validated['city_id'];
        }

        if ($customer && !empty($customer->city_id)) {
            return (int) $customer->city_id;
        }

        return null;
    }

    private function resolveShippingPrice(int $shippingId): int
    {
        return (int) ShippingMethod::query()->whereKey($shippingId)->value('price');
    }

    private function isInPersonShippingId(?int $shippingId): bool
    {
        $shippingId = (int) $shippingId;
        if ($shippingId <= 0) return false;

        $name = (string) ShippingMethod::query()->whereKey($shippingId)->value('name');
        if ($name === '') return false;

        return str_contains($name, 'حضوری') || str_contains($name, 'مراجعه');
    }

    private function syncItems(PreinvoiceOrder $order, array $products): void
    {
        foreach ($products as $p) {
            $order->items()->create([
                'product_id' => (int) $p['id'],
                'variant_id' => (int) $p['variety_id'],
                'quantity'   => (int) $p['quantity'],
                'price'      => (int) ($p['price'] ?? 0),
            ]);
        }
    }


    private function canHandleFinanceActions(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasAnyRole(['admin', 'finance']) || $user->can('finance.approve'));
    }

    public function finalize(string $uuid)
    {
        abort_unless($this->canHandleFinanceActions(), 403);
        $order = PreinvoiceOrder::with('items')->where('uuid', $uuid)->firstOrFail();
        abort_if($order->status !== 'submitted_finance', 403);

        $invoice = DB::transaction(function () use ($order) {
            $subtotal = 0;

            foreach ($order->items as $it) {
                $subtotal += ((int) $it->price) * ((int) $it->quantity);
            }

            $total = max($subtotal + (int) $order->shipping_price - (int) $order->discount_amount, 0);

            $invoice = Invoice::create([
                'uuid'                => (string) Str::uuid(),
                'preinvoice_order_id' => $order->id,

                'customer_id'         => $order->customer_id ?? null,
                'customer_name'       => $order->customer_name,
                'customer_mobile'     => $order->customer_mobile,
                'customer_address'    => $order->customer_address,
                'province_id'         => $order->province_id,
                'city_id'             => $order->city_id,

                'shipping_id'         => $order->shipping_id,
                'shipping_price'      => (int) $order->shipping_price,
                'discount_amount'     => (int) $order->discount_amount,
                'subtotal'            => (int) $subtotal,
                'total'               => (int) $total,
                'status'              => 'warehouse_pending',
            ]);

            $centralWarehouseId = \App\Services\WarehouseStockService::centralWarehouseId();

            foreach ($order->items as $it) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => (int) $it->product_id,
                    'variant_id' => (int) $it->variant_id,
                    'quantity'   => (int) $it->quantity,
                    'price'      => (int) $it->price,
                    'line_total' => (int) $it->price * (int) $it->quantity,
                ]);

                \App\Services\WarehouseStockService::change($centralWarehouseId, (int) $it->product_id, -((int) $it->quantity));

                $product = Product::query()->whereKey((int) $it->product_id)->lockForUpdate()->first();
                if ($product) {
                    $before = (int) $product->stock;
                    $after = max(0, $before - (int) $it->quantity);
                    $product->update(['stock' => $after]);

                    StockMovement::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => 'out',
                        'reason' => 'sale',
                        'quantity' => (int) $it->quantity,
                        'stock_before' => $before,
                        'stock_after' => $after,
                        'reference' => $invoice->uuid,
                        'note' => 'خروج بابت حواله فروش',
                    ]);
                }
            }

            if (!empty($invoice->customer_id)) {
                CustomerLedger::create([
                    'customer_id' => (int) $invoice->customer_id,
                    'type' => 'debit',
                    'amount' => (int) $invoice->total,
                    'reference_type' => Invoice::class,
                    'reference_id' => $invoice->id,
                    'note' => 'ثبت بدهکاری بابت فاکتور فروش ' . $invoice->uuid,
                ]);
            }

            $order->update(['status' => 'finance_approved']);

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice->uuid)
            ->with('success', '✅ تایید مالی انجام شد و پیش‌فاکتور به فاکتور/حواله انبار تبدیل شد.');
    }
}