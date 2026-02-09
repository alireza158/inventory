<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PreinvoiceOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Invoice;
use App\Models\InvoiceItem;
class PreinvoiceController extends Controller
{
    public function create()
    {
        return view('preinvoice.create');
    }

    public function draftIndex()
    {
        $orders = PreinvoiceOrder::query()
            ->where('status', 'draft')
            ->orderByDesc('id')
            ->paginate(20);

        return view('preinvoice.drafts-index', compact('orders'));
    }

    public function saveDraft(Request $request)
    {
        $validated = $this->validateDraftPayload($request);

        DB::transaction(function () use ($validated) {

            $customer = $this->resolveCustomer($validated);

            $order = PreinvoiceOrder::create([
                'uuid'        => (string) Str::uuid(),
                'created_by'  => auth()->id(),
                'status'      => 'draft',

                // ✅ اتصال مشتری
                'customer_id' => $customer?->id,

                // ✅ snapshot اطلاعات مشتری روی سفارش (برای گزارش و جلوگیری از تغییرات بعدی)
                'customer_name'    => $this->orderCustomerName($validated, $customer),
                'customer_mobile'  => $this->orderCustomerMobile($validated, $customer),
                'customer_address' => $this->orderCustomerAddress($validated, $customer),

                'province_id' => (int) $this->orderProvinceId($validated, $customer),
                'city_id'     => $this->orderCityId($validated, $customer),

                'shipping_id'      => (int) $validated['shipping_id'],
                'shipping_price'   => (int) $validated['shipping_price'],
                'discount_amount'  => (int) ($validated['discount_amount'] ?? 0),
                'total_price'      => (int) $validated['total_price'],
            ]);

            $this->syncItems($order, $validated['products']);
        });

        return redirect()->route('preinvoice.draft.index')
            ->with('success', '✅ پیش‌نویس ذخیره شد.');
    }

    public function editDraft(string $uuid)
    {
        $order = PreinvoiceOrder::with('items')->where('uuid', $uuid)->firstOrFail();
        return view('preinvoice.edit', compact('order'));
    }

    public function updateDraft(string $uuid, Request $request)
    {
        $order = PreinvoiceOrder::with('items')->where('uuid', $uuid)->firstOrFail();
        abort_if($order->status !== 'draft', 403);

        $validated = $this->validateDraftPayload($request);

        DB::transaction(function () use ($order, $validated) {

            $customer = $this->resolveCustomer($validated);

            $order->update([
                // ✅ اتصال مشتری
                'customer_id' => $customer?->id,

                // ✅ snapshot اطلاعات مشتری روی سفارش
                'customer_name'    => $this->orderCustomerName($validated, $customer),
                'customer_mobile'  => $this->orderCustomerMobile($validated, $customer),
                'customer_address' => $this->orderCustomerAddress($validated, $customer),

                'province_id' => (int) $this->orderProvinceId($validated, $customer),
                'city_id'     => $this->orderCityId($validated, $customer),

                'shipping_id'     => (int) $validated['shipping_id'],
                'shipping_price'  => (int) $validated['shipping_price'],
                'discount_amount' => (int) ($validated['discount_amount'] ?? 0),
                'total_price'     => (int) $validated['total_price'],
            ]);

            // آیتم‌ها
            $order->items()->delete();
            $this->syncItems($order, $validated['products']);
        });

        return back()->with('success', '✅ پیش‌نویس بروزرسانی شد.');
    }

    /* =========================
       Validation
    ========================= */
    private function validateDraftPayload(Request $request): array
    {
        return $request->validate([
            // ✅ اگر مشتری انتخاب شد این میاد
            'customer_id' => 'nullable|integer|exists:customers,id',

            // ✅ همچنان فیلدهای دستی را نگه می‌داریم (برای حالت مشتری جدید/بدون مشتری)
            'customer_name'    => 'required|string|max:255',
            'customer_mobile'  => 'required|string|max:20',
            'customer_address' => 'required|string|max:1000',

            'province_id' => 'required|integer',
            'city_id'     => 'nullable|integer',

            'shipping_id'    => 'required|integer',
            'shipping_price' => 'required|integer|min:0',

            'discount_amount' => 'nullable|integer|min:0',
            'total_price'     => 'required|integer|min:0',

            'products' => 'required|array|min:1',
            'products.*.id' => 'required|integer',
            'products.*.variety_id' => 'required|integer', // 0 هم قابل قبول است (بدون مدل)
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'nullable|integer|min:0',
        ]);
    }

    /* =========================
       Customer helpers
    ========================= */
    private function resolveCustomer(array $validated): ?Customer
    {
        $cid = (int)($validated['customer_id'] ?? 0);
        if ($cid <= 0) return null;

        return Customer::query()->find($cid);
    }

    private function orderCustomerName(array $validated, ?Customer $customer): string
    {
        // اگر customer_id هست و اسم تو customer هست، از اون استفاده کن
        if ($customer) {
            $full = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
            if ($full !== '') return $full;
        }

        return (string)$validated['customer_name'];
    }

    private function orderCustomerMobile(array $validated, ?Customer $customer): string
    {
        if ($customer && !empty($customer->mobile)) return (string)$customer->mobile;
        return (string)$validated['customer_mobile'];
    }

    private function orderCustomerAddress(array $validated, ?Customer $customer): string
    {
        if ($customer && !empty($customer->address)) return (string)$customer->address;
        return (string)$validated['customer_address'];
    }

    private function orderProvinceId(array $validated, ?Customer $customer): int
    {
        if ($customer && !empty($customer->province_id)) return (int)$customer->province_id;
        return (int)$validated['province_id'];
    }

    private function orderCityId(array $validated, ?Customer $customer): ?int
    {
        // city_id می‌تواند null باشد
        if ($customer && !empty($customer->city_id)) return (int)$customer->city_id;
        return !empty($validated['city_id']) ? (int)$validated['city_id'] : null;
    }

    /* =========================
       Items helper
    ========================= */
    private function syncItems(PreinvoiceOrder $order, array $products): void
    {
        foreach ($products as $p) {
            $order->items()->create([
                'product_id' => (int) $p['id'],
                'variant_id' => (int) $p['variety_id'], // 0 یعنی بدون مدل
                'quantity'   => (int) $p['quantity'],
                'price'      => (int) ($p['price'] ?? 0),
            ]);
        }
    }
    public function finalize(string $uuid)
    {
        $order = PreinvoiceOrder::with('items')->where('uuid', $uuid)->firstOrFail();
        abort_if($order->status !== 'draft', 403);

        $invoice = DB::transaction(function () use ($order) {

            $subtotal = 0;
            foreach ($order->items as $it) {
                $subtotal += ((int)$it->price) * ((int)$it->quantity);
            }

            $total = max($subtotal + (int)$order->shipping_price - (int)$order->discount_amount, 0);

            $invoice = Invoice::create([
                'uuid' => (string) Str::uuid(),
                'preinvoice_order_id' => $order->id,

                'customer_id' => $order->customer_id ?? null,
                'customer_name' => $order->customer_name,
                'customer_mobile' => $order->customer_mobile,
                'customer_address' => $order->customer_address,
                'province_id' => $order->province_id,
                'city_id' => $order->city_id,

                'shipping_id' => $order->shipping_id,
                'shipping_price' => (int)$order->shipping_price,
                'discount_amount' => (int)$order->discount_amount,
                'subtotal' => (int)$subtotal,
                'total' => (int)$total,
                'status' => 'processing',
            ]);

            foreach ($order->items as $it) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => (int)$it->product_id,
                    'variant_id' => (int)$it->variant_id,
                    'quantity' => (int)$it->quantity,
                    'price' => (int)$it->price,
                    'line_total' => (int)$it->price * (int)$it->quantity,
                ]);
            }

            $order->update(['status' => 'final']); // یا هر چیزی که دوست داری

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice->uuid)
            ->with('success', '✅ پیش‌فاکتور ثبت نهایی شد و به فاکتور تبدیل شد.');
    }
}
