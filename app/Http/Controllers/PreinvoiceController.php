<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PreinvoiceOrder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\StockMovement;
use App\Models\WarehouseStock;
use App\Services\WarehouseStockService;
use App\Services\PaymentRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PreinvoiceController extends Controller
{
    public function __construct(private readonly PaymentRegistrationService $paymentService)
    {
    }

    public function create()
    {
        $shippingMethods = ShippingMethod::query()
            ->select(['id', 'name', 'price'])
            ->orderBy('name')
            ->get();

        return view('preinvoice.create', compact('shippingMethods'));
    }

    public function warehouseQueue()
    {
        abort_unless($this->canHandleWarehouseActions(), 403);

        $orders = PreinvoiceOrder::query()
            ->where('status', PreinvoiceOrder::STATUS_SUBMITTED_WAREHOUSE)
            ->with(['creator:id,name'])
            ->withCount('items')
            ->orderByDesc('id')
            ->paginate(20);

        return view('preinvoice.warehouse-index', compact('orders'));
    }

    public function warehouseReview(string $uuid)
    {
        abort_unless($this->canHandleWarehouseActions(), 403);

        $order = PreinvoiceOrder::query()
            ->with([
                'items.product:id,name',
                'items.variant:id,product_id,variant_name,stock,reserved,is_active',
                'creator:id,name',
                'warehouseReviewer:id,name',
                'reviews.user:id,name',
            ])
            ->where('uuid', $uuid)
            ->firstOrFail();

        abort_if(!in_array($order->status, [PreinvoiceOrder::STATUS_SUBMITTED_WAREHOUSE, PreinvoiceOrder::STATUS_WAREHOUSE_REJECTED], true), 403);

        $products = Product::query()
            ->where('is_sellable', true)
            ->whereHas('variants', fn ($q) => $q->where('is_active', true))
            ->with(['variants' => fn ($q) => $q->where('is_active', true)->orderBy('variant_name')])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('preinvoice.warehouse-review', compact('order', 'products'));
    }

    public function warehouseSave(string $uuid, Request $request)
    {
        abort_unless($this->canHandleWarehouseActions(), 403);

        $order = PreinvoiceOrder::query()->with('items')->where('uuid', $uuid)->firstOrFail();
        abort_if(!in_array($order->status, [PreinvoiceOrder::STATUS_SUBMITTED_WAREHOUSE, PreinvoiceOrder::STATUS_WAREHOUSE_REJECTED], true), 403);

        $data = $this->validateWarehouseReviewPayload($request);

        DB::transaction(function () use ($order, $data) {
            $before = $this->snapshotItems($order);
            $this->replaceOrderItems($order, $data['items']);

            $order->update([
                'status' => PreinvoiceOrder::STATUS_SUBMITTED_WAREHOUSE,
                'warehouse_review_note' => $data['warehouse_review_note'] ?? null,
                'warehouse_reject_reason' => null,
                'warehouse_reviewed_by' => auth()->id(),
                'warehouse_reviewed_at' => now(),
                'total_price' => $this->calculateOrderTotal($order),
            ]);

            $order->reviews()->create([
                'user_id' => auth()->id(),
                'action' => 'warehouse_saved',
                'reason' => $data['warehouse_review_note'] ?? null,
                'before_items' => $before,
                'after_items' => $this->snapshotItems($order->fresh('items.product', 'items.variant')),
            ]);
        });

        return back()->with('success', '✅ تغییرات انبار ذخیره شد.');
    }

    public function warehouseApprove(string $uuid, Request $request)
    {
        abort_unless($this->canHandleWarehouseActions(), 403);

        $order = PreinvoiceOrder::query()->with('items')->where('uuid', $uuid)->firstOrFail();
        abort_if(!in_array($order->status, [PreinvoiceOrder::STATUS_SUBMITTED_WAREHOUSE, PreinvoiceOrder::STATUS_WAREHOUSE_REJECTED], true), 403);

        $data = $this->validateWarehouseReviewPayload($request, true);

        DB::transaction(function () use ($order, $data) {
            $before = $this->snapshotItems($order);
            $this->replaceOrderItems($order, $data['items']);

            $order->refresh()->load('items');
            $this->assertOrderHasStock($order);

            $order->update([
                'status' => PreinvoiceOrder::STATUS_SUBMITTED_FINANCE,
                'warehouse_review_note' => $data['warehouse_review_note'] ?? null,
                'warehouse_reject_reason' => null,
                'warehouse_reviewed_by' => auth()->id(),
                'warehouse_reviewed_at' => now(),
                'total_price' => $this->calculateOrderTotal($order),
            ]);

            $order->reviews()->create([
                'user_id' => auth()->id(),
                'action' => 'warehouse_approved',
                'reason' => $data['warehouse_review_note'] ?? null,
                'before_items' => $before,
                'after_items' => $this->snapshotItems($order->fresh('items.product', 'items.variant')),
            ]);
        });

        return redirect()->route('preinvoice.warehouse.index')
            ->with('success', '✅ تایید انبار انجام شد و پیش‌فاکتور به صف مالی ارسال شد.');
    }

    public function warehouseReject(string $uuid, Request $request)
    {
        abort_unless($this->canHandleWarehouseActions(), 403);

        $order = PreinvoiceOrder::query()->with('items')->where('uuid', $uuid)->firstOrFail();
        abort_if(!in_array($order->status, [PreinvoiceOrder::STATUS_SUBMITTED_WAREHOUSE, PreinvoiceOrder::STATUS_WAREHOUSE_REJECTED], true), 403);

        $data = $request->validate([
            'warehouse_reject_reason' => 'required|string|max:2000',
        ], [
            'warehouse_reject_reason.required' => 'دلیل رد پیش‌فاکتور را وارد کنید.',
        ]);

        DB::transaction(function () use ($order, $data) {
            $order->update([
                'status' => PreinvoiceOrder::STATUS_WAREHOUSE_REJECTED,
                'warehouse_reject_reason' => $data['warehouse_reject_reason'],
                'warehouse_reviewed_by' => auth()->id(),
                'warehouse_reviewed_at' => now(),
            ]);

            $order->reviews()->create([
                'user_id' => auth()->id(),
                'action' => 'warehouse_rejected',
                'reason' => $data['warehouse_reject_reason'],
                'before_items' => $this->snapshotItems($order),
                'after_items' => $this->snapshotItems($order),
            ]);
        });

        return back()->with('success', '✅ پیش‌فاکتور رد شد و دلیل برگشت ثبت شد.');
    }

    public function draftIndex()
    {
        $orders = PreinvoiceOrder::query()
            ->where('status', PreinvoiceOrder::STATUS_SUBMITTED_FINANCE)
            ->with(['creator:id,name'])
            ->orderByDesc('id')
            ->paginate(20);

        $canFinanceApprove = $this->canHandleFinanceActions();

        return view('preinvoice.drafts-index', compact('orders', 'canFinanceApprove'));
    }

    public function saveDraft(Request $request)
    {
        abort_unless(auth()->check(), 403);
        $validated = $this->validateDraftPayload($request);

        DB::transaction(function () use ($validated) {
            $customer = $this->resolveCustomer($validated);
            $shippingId = (int) $validated['shipping_id'];

            $order = PreinvoiceOrder::create([
                'uuid' => (string) Str::uuid(),
                'created_by' => auth()->id(),
                'status' => PreinvoiceOrder::STATUS_SUBMITTED_WAREHOUSE,

                'customer_id' => $customer?->id,
                'customer_name' => $this->orderCustomerName($validated, $customer),
                'customer_mobile' => $this->orderCustomerMobile($validated, $customer),
                'customer_address' => $this->orderCustomerAddress($validated, $customer, $shippingId),
                'province_id' => $this->orderProvinceId($validated, $customer, $shippingId),
                'city_id' => $this->orderCityId($validated, $customer, $shippingId),

                'shipping_id' => $shippingId,
                'shipping_price' => (int) $this->resolveShippingPrice($shippingId),
                'discount_amount' => (int) ($validated['discount_amount'] ?? 0),
                'total_price' => (int) $validated['total_price'],
            ]);

            $this->syncItems($order, $validated['products']);
        });

        return redirect()->route('preinvoice.warehouse.index')
            ->with('success', '✅ پیش‌فاکتور ثبت و برای تایید انبار ارسال شد.');
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
        abort_unless(auth()->check(), 403);
        $order = PreinvoiceOrder::with('items')->where('uuid', $uuid)->firstOrFail();
        abort_if($order->status !== PreinvoiceOrder::STATUS_SUBMITTED_FINANCE, 403);

        $validated = $this->validateDraftPayload($request);

        DB::transaction(function () use ($order, $validated) {
            $customer = $this->resolveCustomer($validated);
            $shippingId = (int) $validated['shipping_id'];

            $order->update([
                'customer_id' => $customer?->id,
                'customer_name' => $this->orderCustomerName($validated, $customer),
                'customer_mobile' => $this->orderCustomerMobile($validated, $customer),
                'customer_address' => $this->orderCustomerAddress($validated, $customer, $shippingId),
                'province_id' => $this->orderProvinceId($validated, $customer, $shippingId),
                'city_id' => $this->orderCityId($validated, $customer, $shippingId),

                'shipping_id' => $shippingId,
                'shipping_price' => (int) $this->resolveShippingPrice($shippingId),
                'discount_amount' => (int) ($validated['discount_amount'] ?? 0),
                'total_price' => (int) $validated['total_price'],
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
            'customer_id' => 'nullable|integer|exists:customers,id',
            'customer_name' => 'required|string|max:255',
            'customer_mobile' => 'required|string|max:20',
            'customer_address' => $isInPerson ? 'nullable|string|max:1000' : 'required|string|max:1000',
            'province_id' => $isInPerson ? 'nullable|integer' : 'required|integer',
            'city_id' => 'nullable|integer',

            'shipping_id' => 'required|integer|exists:shipping_methods,id',
            'shipping_price' => 'nullable|integer|min:0',

            'discount_amount' => 'nullable|integer|min:0',
            'total_price' => 'required|integer|min:0',

            'products' => 'required|array|min:1',
            'products.*.id' => 'required|integer|exists:products,id,is_sellable,1',
            'products.*.variety_id' => [
                'required',
                'integer',
                Rule::exists('product_variants', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'nullable|integer|min:0',
        ], [
            'customer_name.required' => 'نام مشتری الزامی است.',
            'customer_mobile.required' => 'شماره موبایل مشتری الزامی است.',
            'customer_address.required' => 'برای روش‌های ارسال غیرحضوری، آدرس الزامی است.',
            'province_id.required' => 'برای روش‌های ارسال غیرحضوری، استان الزامی است.',
            'products.required' => 'حداقل یک محصول باید ثبت شود.',
            'products.min' => 'حداقل یک محصول باید ثبت شود.',
        ]);

        foreach (($validated['products'] ?? []) as $index => $productRow) {
            $isValidVariant = ProductVariant::query()
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

        $this->validateDraftItemsBusinessRules($validated['products'] ?? []);

        return $validated;
    }

    private function validateDraftItemsBusinessRules(array $products): void
    {
        $variantIds = collect($products)->pluck('variety_id')->map(fn ($id) => (int) $id)->filter()->values();
        if ($variantIds->isEmpty()) {
            return;
        }

        $variants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->get(['id', 'product_id', 'sell_price', 'stock', 'reserved', 'is_active'])
            ->keyBy('id');

        $qtyByVariant = [];
        $seenProductVariant = [];

        foreach ($products as $index => $row) {
            $productId = (int) ($row['id'] ?? 0);
            $variantId = (int) ($row['variety_id'] ?? 0);
            $price = (int) ($row['price'] ?? 0);

            $variant = $variants->get($variantId);
            if (!$variant || (int) $variant->product_id !== $productId || !(bool) $variant->is_active) {
                throw ValidationException::withMessages([
                    "products.{$index}.variety_id" => 'تنوع انتخابی معتبر نیست.',
                ]);
            }

            $pairKey = $productId . ':' . $variantId;
            if (isset($seenProductVariant[$pairKey])) {
                throw ValidationException::withMessages([
                    "products.{$index}.variety_id" => 'هر تنوع باید فقط یک‌بار در هر محصول مادر ثبت شود.',
                ]);
            }
            $seenProductVariant[$pairKey] = true;

            $serverPrice = (int) ($variant->sell_price ?? 0);
            if ($price !== $serverPrice) {
                throw ValidationException::withMessages([
                    "products.{$index}.price" => "قیمت ارسال‌شده معتبر نیست. قیمت فعلی تنوع: {$serverPrice}",
                ]);
            }

            $qtyByVariant[$variantId] = ($qtyByVariant[$variantId] ?? 0) + (int) ($row['quantity'] ?? 0);
        }

        foreach ($qtyByVariant as $variantId => $requiredQty) {
            $variant = $variants->get((int) $variantId);
            $availableQty = max(0, (int) ($variant->stock ?? 0) - (int) ($variant->reserved ?? 0));

            if ($requiredQty > $availableQty) {
                throw ValidationException::withMessages([
                    'products' => "موجودی تنوع انتخابی کافی نیست. موجودی قابل فروش: {$availableQty} | درخواست: {$requiredQty}",
                ]);
            }
        }
    }

    private function validateWarehouseReviewPayload(Request $request, bool $forApprove = false): array
    {
        $validated = $request->validate([
            'warehouse_review_note' => $forApprove ? 'required|string|max:2000' : 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id,is_sellable,1',
            'items.*.variant_id' => ['required', 'integer', Rule::exists('product_variants', 'id')->where(fn ($q) => $q->where('is_active', true))],
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|integer|min:0',
        ], [
            'warehouse_review_note.required' => 'برای تایید و ارسال به مالی، دلیل/توضیح بازبینی انبار الزامی است.',
            'items.required' => 'حداقل یک آیتم در پیش‌فاکتور لازم است.',
            'items.min' => 'حداقل یک آیتم در پیش‌فاکتور لازم است.',
        ]);

        foreach (($validated['items'] ?? []) as $index => $row) {
            $isValidVariant = ProductVariant::query()
                ->whereKey((int) $row['variant_id'])
                ->where('product_id', (int) $row['product_id'])
                ->where('is_active', true)
                ->exists();

            if (!$isValidVariant) {
                throw ValidationException::withMessages([
                    "items.{$index}.variant_id" => 'تنوع انتخابی برای کالا معتبر نیست.',
                ]);
            }
        }

        return $validated;
    }

    private function replaceOrderItems(PreinvoiceOrder $order, array $items): void
    {
        $order->items()->delete();

        foreach ($items as $row) {
            $order->items()->create([
                'product_id' => (int) $row['product_id'],
                'variant_id' => (int) $row['variant_id'],
                'quantity' => (int) $row['quantity'],
                'price' => (int) $row['price'],
            ]);
        }
    }

    private function snapshotItems(PreinvoiceOrder $order): array
    {
        $order->loadMissing(['items.product:id,name', 'items.variant:id,variant_name']);

        return $order->items->map(fn ($item) => [
            'product_id' => (int) $item->product_id,
            'product_name' => $item->product?->name,
            'variant_id' => (int) $item->variant_id,
            'variant_name' => $item->variant?->variant_name,
            'quantity' => (int) $item->quantity,
            'price' => (int) $item->price,
        ])->values()->all();
    }

    private function calculateOrderTotal(PreinvoiceOrder $order): int
    {
        $subtotal = (int) $order->items()->selectRaw('COALESCE(SUM(quantity * price),0) as total')->value('total');

        return max($subtotal + (int) $order->shipping_price - (int) $order->discount_amount, 0);
    }

    private function assertOrderHasStock(PreinvoiceOrder $order): void
    {
        $order->loadMissing('items.product', 'items.variant');
        $centralWarehouseId = WarehouseStockService::centralWarehouseId();

        $requiredByVariant = $order->items
            ->groupBy('variant_id')
            ->map(fn ($rows) => (int) $rows->sum('quantity'));

        $variants = ProductVariant::query()
            ->whereIn('id', $requiredByVariant->keys())
            ->get(['id', 'variant_name', 'stock', 'reserved']);

        foreach ($requiredByVariant as $variantId => $requiredQty) {
            $variant = $variants->firstWhere('id', (int) $variantId);
            $availableQty = $variant ? max(0, ((int) $variant->stock - (int) $variant->reserved)) : 0;

            if ($availableQty < $requiredQty) {
                $name = $variant?->variant_name ?? ('#' . $variantId);
                throw ValidationException::withMessages([
                    'items' => "موجودی تنوع «{$name}» کافی نیست. موجودی قابل فروش: {$availableQty} | درخواست: {$requiredQty}",
                ]);
            }
        }

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
                    'items' => "موجودی انبار مرکزی برای محصول «{$productName}» کافی نیست. موجودی: {$availableQty} | درخواست: {$requiredQty}",
                ]);
            }
        }
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
                'quantity' => (int) $p['quantity'],
                'price' => (int) ($p['price'] ?? 0),
            ]);
        }
    }

    private function canHandleFinanceActions(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasAnyRole(['admin', 'finance']) || $user->can('finance.approve'));
    }

    private function canHandleWarehouseActions(): bool
    {
        return true;
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'warehouse', 'finance']) || $user->can('warehouse.approve')) {
            return true;
        }

        // در برخی محیط‌ها هنوز نقش/دسترسی برای کاربران تعریف نشده است.
        // برای جلوگیری از 403 روی مسیر جدید، کاربر احراز هویت‌شده‌ی بدون نقش/دسترسی را مجاز می‌کنیم.
        $hasNoRole = method_exists($user, 'roles') ? !$user->roles()->exists() : true;
        $hasNoPermission = method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->isEmpty() : true;

        return $hasNoRole && $hasNoPermission;
    }

    public function finance(string $uuid)
    {
        $order = PreinvoiceOrder::with(['items.product', 'items.variant', 'creator:id,name'])
            ->where('uuid', $uuid)
            ->firstOrFail();
        abort_if($order->status !== PreinvoiceOrder::STATUS_SUBMITTED_FINANCE, 403);

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
        abort_if($order->status !== PreinvoiceOrder::STATUS_SUBMITTED_FINANCE, 403);

        $validated = $request->validate([
            'payments' => 'nullable|array',
            'payments.*.method' => 'required_with:payments|in:cash,cheque',
            'payments.*.amount' => 'required_with:payments|integer|min:1',
            'payments.*.paid_at' => 'required_with:payments|date',
            'payments.*.note' => 'nullable|string|max:2000',
            'payments.*.bank_name' => 'required_if:payments.*.method,cash|nullable|string|max:255',
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
                    empty($paymentRow['amount']) ||
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
            } elseif (empty($paymentRow['bank_name'])) {
                throw ValidationException::withMessages([
                    "payments.{$index}.bank_name" => 'برای پرداخت نقدی، نام بانک الزامی است.',
                ]);
            }
        }

        $invoice = DB::transaction(function () use ($order, $validated) {
            $subtotal = 0;

            foreach ($order->items as $it) {
                $subtotal += ((int) $it->price) * ((int) $it->quantity);
            }

            $total = max($subtotal + (int) $order->shipping_price - (int) $order->discount_amount, 0);

            $centralWarehouseId = WarehouseStockService::centralWarehouseId();

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
                'uuid' => (string) Str::uuid(),
                'preinvoice_order_id' => $order->id,

                'customer_id' => $order->customer_id ?? null,
                'customer_name' => $order->customer_name,
                'customer_mobile' => $order->customer_mobile,
                'customer_address' => $order->customer_address,
                'province_id' => $order->province_id,
                'city_id' => $order->city_id,

                'shipping_id' => $order->shipping_id,
                'shipping_price' => (int) $order->shipping_price,
                'discount_amount' => (int) $order->discount_amount,
                'subtotal' => (int) $subtotal,
                'total' => (int) $total,
                'status' => Invoice::STATUS_PENDING_WAREHOUSE_APPROVAL,
            ]);

            foreach ($order->items as $it) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => (int) $it->product_id,
                    'variant_id' => (int) $it->variant_id,
                    'quantity' => (int) $it->quantity,
                    'price' => (int) $it->price,
                    'line_total' => (int) $it->price * (int) $it->quantity,
                ]);

                WarehouseStockService::change($centralWarehouseId, (int) $it->product_id, -((int) $it->quantity));

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

            foreach (($validated['payments'] ?? []) as $paymentRow) {
                $payload = $paymentRow;
                if (($payload['method'] ?? null) === 'cheque') {
                    $payload['cheque_amount'] = (int) ($payload['amount'] ?? 0);
                }

                $this->paymentService->registerForInvoice(
                    $invoice,
                    $payload,
                    $invoice->customer_id ? (int) $invoice->customer_id : null,
                    auth()->id()
                );
            }

            $order->update(['status' => PreinvoiceOrder::STATUS_FINANCE_APPROVED]);

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice->uuid)
            ->with('success', '✅ تایید مالی انجام شد و پیش‌فاکتور به فاکتور/حواله انبار تبدیل شد.');
    }
}
