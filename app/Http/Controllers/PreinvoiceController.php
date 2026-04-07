<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Cheque;
use App\Models\PreinvoiceOrder;
use App\Models\ShippingMethod;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
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
            ->with(['creator:id,name'])
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

        $validated = $request->validate([
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
            'products.*.variety_id' => [
                'required',
                'integer',
                Rule::exists('product_variants', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
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

        foreach (($validated['products'] ?? []) as $index => $productRow) {
            $isValidVariant = \App\Models\ProductVariant::query()
                ->whereKey((int) $productRow['variety_id'])
                ->where('product_id', (int) $productRow['id'])
                ->where('is_active', true)
                ->exists();

            if (!$isValidVariant) {
                throw ValidationException::withMessages([
                    "products.{$index}.variety_id" => 'تنوع انتخابی برای این کالا نامعتبر یا غیرفعال است.',
                ]);
            }
        }

        return $validated;
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

    private function orderProvinceId(array $validated, ?Customer $customer, int $shippingId): int
    {
        if ($this->isInPersonShippingId($shippingId)) {
            return 0;
        }

        if (!empty($validated['province_id'])) {
            return (int) $validated['province_id'];
        }

        if ($customer && !empty($customer->province_id)) {
            return (int) $customer->province_id;
        }

        return 0;
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

    public function finance(string $uuid)
    {
        $order = PreinvoiceOrder::with(['items.product', 'items.variant', 'creator:id,name'])
            ->where('uuid', $uuid)
            ->firstOrFail();
        abort_if($order->status !== 'submitted_finance', 403);

        $customerBalanceStatus = 'تسویه';
        $customerBalanceAmount = 0;

        if (!empty($order->customer_id)) {
            $customer = Customer::query()
                ->withBalance()
                ->find((int) $order->customer_id);

            if ($customer) {
                $balance = (int) $customer->balance;

                if ($balance > 0) {
                    $customerBalanceStatus = 'بدهکار';
                    $customerBalanceAmount = $balance;
                } elseif ($balance < 0) {
                    $customerBalanceStatus = 'بستانکار';
                    $customerBalanceAmount = abs($balance);
                }
            }
        }

        return view('preinvoice.finance', compact('order', 'customerBalanceStatus', 'customerBalanceAmount'));
    }

    public function finalize(string $uuid, Request $request)
    {
        $order = PreinvoiceOrder::with('items')->where('uuid', $uuid)->firstOrFail();
        abort_if($order->status !== 'submitted_finance', 403);

        $validated = $request->validate([
            'payments' => 'nullable|array',
            'payments.*.method' => 'required_with:payments|in:cash,cheque',
            'payments.*.amount' => 'required_with:payments|integer|min:1',
            'payments.*.paid_at' => 'required_with:payments|date',
            'payments.*.note' => 'required_with:payments|string|max:2000',
            'payments.*.payment_identifier' => 'nullable|string|max:255',
            'payments.*.cheque_bank_name' => 'nullable|string|max:255',
            'payments.*.cheque_branch_name' => 'nullable|string|max:255',
            'payments.*.cheque_number' => 'nullable|string|max:255',
            'payments.*.cheque_amount' => 'nullable|integer|min:1',
            'payments.*.cheque_due_date' => 'nullable|date',
            'payments.*.cheque_received_at' => 'nullable|date',
            'payments.*.cheque_customer_name' => 'nullable|string|max:255',
            'payments.*.cheque_customer_code' => 'nullable|string|max:255',
            'payments.*.cheque_account_number' => 'nullable|string|max:255',
            'payments.*.cheque_account_holder' => 'nullable|string|max:255',
            'payments.*.cheque_status' => 'nullable|in:pending,cleared,bounced',
        ]);

        foreach (($validated['payments'] ?? []) as $index => $paymentRow) {
            if (($paymentRow['method'] ?? null) === 'cheque') {
                if (
                    empty($paymentRow['cheque_number']) ||
                    empty($paymentRow['cheque_amount']) ||
                    empty($paymentRow['cheque_due_date']) ||
                    empty($paymentRow['cheque_received_at']) ||
                    empty($paymentRow['cheque_customer_name']) ||
                    empty($paymentRow['cheque_customer_code']) ||
                    empty($paymentRow['cheque_bank_name']) ||
                    empty($paymentRow['cheque_branch_name']) ||
                    empty($paymentRow['cheque_account_holder'])
                ) {
                    throw ValidationException::withMessages([
                        "payments.{$index}.cheque_number" => 'برای پرداخت چکی، تکمیل اطلاعات اصلی چک الزامی است.',
                    ]);
                }
            }
        }

        $invoice = DB::transaction(function () use ($order, $validated, $request) {
            $subtotal = 0;

            foreach ($order->items as $it) {
                $subtotal += ((int) $it->price) * ((int) $it->quantity);
            }

            $total = max($subtotal + (int) $order->shipping_price - (int) $order->discount_amount, 0);

            $centralWarehouseId = \App\Services\WarehouseStockService::centralWarehouseId();

            $requiredByProduct = $order->items
                ->groupBy('product_id')
                ->map(fn ($rows) => (int) $rows->sum('quantity'));

            $availableByProduct = WarehouseStock::query()
                ->where('warehouse_id', $centralWarehouseId)
                ->whereIn('product_id', $requiredByProduct->keys())
                ->pluck('quantity', 'product_id');

            foreach ($requiredByProduct as $productId => $requiredQty) {
                $availableQty = (int) ($availableByProduct[(int) $productId] ?? 0);

                if ($availableQty < $requiredQty) {
                    $productName = (string) Product::query()->whereKey((int) $productId)->value('name');

                    throw ValidationException::withMessages([
                        'products' => "موجودی انبار مرکزی برای محصول «{$productName}» کافی نیست. موجودی: {$availableQty} | درخواست: {$requiredQty}",
                    ]);
                }
            }

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
                'status'              => Invoice::STATUS_PENDING_WAREHOUSE_APPROVAL,
            ]);

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
                    $after = $before - (int) $it->quantity;
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

            foreach (($validated['payments'] ?? []) as $index => $paymentRow) {
                $paymentAmount = (int) $paymentRow['amount'];
                if (($paymentRow['method'] ?? null) === 'cheque') {
                    $paymentAmount = (int) ($paymentRow['cheque_amount'] ?? 0);
                }

                $payment = InvoicePayment::create([
                    'invoice_id' => $invoice->id,
                    'method' => $paymentRow['method'],
                    'amount' => $paymentAmount,
                    'paid_at' => $paymentRow['paid_at'] ?? now()->toDateString(),
                    'payment_identifier' => $paymentRow['payment_identifier'] ?? null,
                    'note' => $paymentRow['note'] ?? null,
                    'receipt_image' => null,
                ]);

                if (!empty($invoice->customer_id)) {
                    CustomerLedger::create([
                        'customer_id' => (int) $invoice->customer_id,
                        'type' => 'credit',
                        'amount' => (int) $payment->amount,
                        'reference_type' => InvoicePayment::class,
                        'reference_id' => $payment->id,
                        'note' => 'ثبت پرداخت اولیه برای فاکتور ' . $invoice->uuid,
                    ]);
                }

                if (($paymentRow['method'] ?? null) === 'cheque') {
                    Cheque::create([
                        'invoice_payment_id' => $payment->id,
                        'bank_name' => $paymentRow['cheque_bank_name'] ?? null,
                        'branch_name' => $paymentRow['cheque_branch_name'] ?? null,
                        'cheque_number' => $paymentRow['cheque_number'] ?? null,
                        'amount' => (int) ($paymentRow['cheque_amount'] ?? 0),
                        'due_date' => $paymentRow['cheque_due_date'] ?? null,
                        'received_at' => $paymentRow['cheque_received_at'] ?? null,
                        'customer_name' => $paymentRow['cheque_customer_name'] ?? null,
                        'customer_code' => $paymentRow['cheque_customer_code'] ?? null,
                        'account_number' => $paymentRow['cheque_account_number'] ?? null,
                        'account_holder' => $paymentRow['cheque_account_holder'] ?? null,
                        'image' => null,
                        'status' => $paymentRow['cheque_status'] ?? 'pending',
                    ]);
                }
            }

            $order->update(['status' => 'finance_approved']);

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice->uuid)
            ->with('success', '✅ تایید مالی انجام شد و پیش‌فاکتور به فاکتور/حواله انبار تبدیل شد.');
    }
}
