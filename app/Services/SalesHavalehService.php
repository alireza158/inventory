<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PreinvoiceOrder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\DocumentCodeGenerator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class SalesHavalehService
{
    public function __construct(
        private readonly SalesHavalehStatusService $statusService,
        private readonly InventoryService $inventoryService,
        private readonly CustomerLedgerService $ledgerService,
        private readonly SalesHavalehHistoryService $historyService,
        private readonly CentralInventoryService $centralInventoryService,
        private readonly SalesDocumentAccessService $accessService,
    ) {}

    public function updateItems(Invoice $invoice, array $itemPayloads, ?int $userId = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $itemPayloads, $userId) {
            $user = auth()->user();
            if (! $this->accessService->canSellerEditInvoiceItems($invoice, $user)) {
                abort(403, 'فقط فروشنده ثبت‌کننده سند یا مدیر مجاز به ویرایش اقلام است.');
            }

            $invoice->loadMissing('items');
            $itemsById = $invoice->items->keyBy('id');

            $requestedIds = collect($itemPayloads)->pluck('id')->map(fn ($id) => (int) $id)->all();

            foreach ($invoice->items as $item) {
                if (!in_array((int) $item->id, $requestedIds, true)) {
                    $this->inventoryService->adjustCentralStock(
                        (int) $item->product_id,
                        (int) $item->quantity,
                        $invoice->uuid,
                        'برگشت موجودی بابت حذف آیتم حواله فروش'
                    );
                    $this->changeReservedOnly((int) $item->product_id, (int) $item->variant_id, -((int) $item->quantity));

                    $this->historyService->log($invoice, 'item_removed', 'items', (string) $item->id, null, 'حذف آیتم از حواله فروش', $userId);
                    $this->historyService->log($invoice, 'inventory_returned', 'product_id', (string) $item->product_id, (string) $item->quantity, 'برگشت موجودی به انبار مرکزی', $userId);
                    $item->delete();
                }
            }

            foreach ($itemPayloads as $row) {
                $itemId = (int) $row['id'];
                $newQty = (int) $row['quantity'];
                $newPrice = (int) $row['price'];

                $item = $itemsById->get($itemId);
                if (!$item) {
                    continue;
                }

                $oldQty = (int) $item->quantity;
                $delta = $newQty - $oldQty;

                if ($delta > 0) {
                    $this->centralInventoryService->assertVariantAvailable((int) $item->variant_id, $delta);
                    $this->inventoryService->adjustCentralStock((int) $item->product_id, -$delta, $invoice->uuid, 'کسر موجودی بابت افزایش تعداد آیتم حواله فروش');
                    $this->changeReservedOnly((int) $item->product_id, (int) $item->variant_id, $delta);
                    $this->historyService->log($invoice, 'item_quantity_increased', 'quantity', (string) $oldQty, (string) $newQty, 'افزایش تعداد آیتم', $userId);
                    $this->historyService->log($invoice, 'inventory_deducted', 'product_id', (string) $item->product_id, (string) $delta, 'کسر موجودی انبار مرکزی', $userId);
                } elseif ($delta < 0) {
                    $this->inventoryService->adjustCentralStock((int) $item->product_id, abs($delta), $invoice->uuid, 'برگشت موجودی بابت کاهش تعداد آیتم حواله فروش');
                    $this->changeReservedOnly((int) $item->product_id, (int) $item->variant_id, $delta);
                    $this->historyService->log($invoice, 'item_quantity_decreased', 'quantity', (string) $oldQty, (string) $newQty, 'کاهش تعداد آیتم', $userId);
                    $this->historyService->log($invoice, 'inventory_returned', 'product_id', (string) $item->product_id, (string) abs($delta), 'برگشت موجودی به انبار مرکزی', $userId);
                }

                $lineTotal = $newQty * $newPrice;
                $item->update([
                    'quantity' => $newQty,
                    'price' => $newPrice,
                    'line_total' => $lineTotal,
                ]);
            }

            $invoice->refresh()->load(['items', 'preinvoiceOrder']);
            $oldStatus = (string) $invoice->status;
            $subtotal = (int) $invoice->items->sum('line_total');
            $newTotal = max($subtotal + (int) $invoice->shipping_price - (int) $invoice->discount_amount, 0);
            $oldTotal = (int) $invoice->total;

            $invoice->update([
                'subtotal' => $subtotal,
                'total' => $newTotal,
                'status' => SalesHavalehStatusService::PENDING_WAREHOUSE_APPROVAL,
                'status_changed_at' => now(),
                'status_changed_by' => $userId,
                'items_updated_at' => now(),
                'items_updated_by' => $userId,
            ]);

            if ($invoice->preinvoiceOrder) {
                $invoice->preinvoiceOrder->update([
                    'status' => PreinvoiceOrder::STATUS_RESERVED_WAITING_WAREHOUSE,
                    'warehouse_review_note' => null,
                    'warehouse_reject_reason' => null,
                    'warehouse_reviewed_by' => null,
                    'warehouse_reviewed_at' => null,
                    'stock_frozen_until' => now(),
                    'stock_released_at' => null,
                    'items_updated_at' => now(),
                    'items_updated_by' => $userId,
                ]);
            }

            if ($oldTotal !== $newTotal) {
                $this->historyService->log($invoice, 'amount_recalculated', 'total', (string) $oldTotal, (string) $newTotal, 'بروزرسانی مبلغ کل حواله فروش', $userId);
            }

            $this->ledgerService->syncInvoiceDebit($invoice->fresh());

            $this->historyService->log($invoice, 'reapproval_required', 'status', $oldStatus, SalesHavalehStatusService::PENDING_WAREHOUSE_APPROVAL, 'اقلام سند تغییر کرد و برای بررسی مجدد به انبار و مالی ارسال شد.', $userId);

            return $invoice->fresh(['items.product', 'items.variant']);
        });
    }


    private function officialCodeForPreinvoiceConversion(PreinvoiceOrder $order): string
    {
        if (is_string($order->uuid) && preg_match('/^\d{5}$/', $order->uuid) === 1) {
            return $order->uuid;
        }

        $code = DocumentCodeGenerator::generateUnique5DigitCode(PreinvoiceOrder::class);
        $order->update(['uuid' => $code]);

        return $code;
    }

    private function changeReservedOnly(int $productId, int $variantId, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        $variant = ProductVariant::query()->whereKey($variantId)->lockForUpdate()->first();
        if ($variant) {
            $variant->reserved = max(0, (int) $variant->reserved + $delta);
            $variant->save();
        }

        $product = Product::query()->whereKey($productId)->lockForUpdate()->first();
        if ($product) {
            $product->reserved = max(0, (int) $product->reserved + $delta);
            $product->save();
        }
    }

    public function createFromFinancialRecord(int $preinvoiceOrderId, ?int $userId = null): Invoice
    {
        return DB::transaction(function () use ($preinvoiceOrderId, $userId) {
            $order = PreinvoiceOrder::query()->with('items')->lockForUpdate()->findOrFail($preinvoiceOrderId);

            if ($order->status !== 'finance_approved') {
                throw ValidationException::withMessages([
                    'financial' => 'فقط پیش‌فاکتور تایید مالی‌شده قابل تبدیل به حواله فروش است.',
                ]);
            }

            $existing = Invoice::query()->where('preinvoice_order_id', $order->id)->first();
            if ($existing) {
                return $existing;
            }

            $subtotal = (int) $order->items->sum(fn ($it) => ((int) $it->quantity * (int) $it->price));
            $total = max($subtotal + (int) $order->shipping_price - (int) $order->discount_amount, 0);

            $invoice = Invoice::query()->create([
                'uuid' => $this->officialCodeForPreinvoiceConversion($order),
                'preinvoice_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer_name,
                'customer_mobile' => $order->customer_mobile,
                'customer_address' => $order->customer_address,
                'province_id' => $order->province_id,
                'city_id' => $order->city_id,
                'shipping_id' => $order->shipping_id,
                'shipping_price' => (int) $order->shipping_price,
                'discount_amount' => (int) $order->discount_amount,
                'subtotal' => $subtotal,
                'total' => $total,
                'status' => SalesHavalehStatusService::PENDING_WAREHOUSE_APPROVAL,
                'status_changed_at' => now(),
                'status_changed_by' => $userId,
            ]);

            foreach ($order->items as $item) {
                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'product_id' => (int) $item->product_id,
                    'variant_id' => (int) $item->variant_id,
                    'quantity' => (int) $item->quantity,
                    'price' => (int) $item->price,
                    'line_total' => (int) $item->quantity * (int) $item->price,
                ]);

                $this->inventoryService->adjustCentralStock(
                    (int) $item->product_id,
                    -((int) $item->quantity),
                    $invoice->uuid,
                    'کسر موجودی بابت ایجاد حواله فروش از رکورد مالی'
                );
            }

            $this->ledgerService->syncInvoiceDebit($invoice);
            $this->historyService->log($invoice, 'created', null, null, null, 'ایجاد حواله فروش از رکورد مالی', $userId);

            return $invoice;
        });
    }

    public function changeStatus(Invoice $invoice, string $newStatus, ?string $note = null, ?int $userId = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $newStatus, $note, $userId) {
            $user = auth()->user();
            $this->statusService->assertValidTransition($invoice, $newStatus, $user);

            $oldStatus = (string) $invoice->status;
            $invoice->update([
                'status' => $newStatus,
                'status_changed_at' => now(),
                'status_changed_by' => $userId,
            ]);

            $this->historyService->log(
                $invoice,
                'status_changed',
                'status',
                $oldStatus,
                $newStatus,
                $note,
                $userId
            );

            return $invoice->fresh();
        });
    }

    public function cancelAndRestore(Invoice $invoice, ?string $note = null, ?int $userId = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $note, $userId) {
            $invoice = Invoice::query()->with('items')->lockForUpdate()->findOrFail($invoice->id);
            if ((string) $invoice->status === SalesHavalehStatusService::NOT_SHIPPED) {
                return $invoice;
            }

            foreach ($invoice->items as $item) {
                $variant = ProductVariant::query()->whereKey((int) $item->variant_id)->lockForUpdate()->first();
                if ($variant) {
                    $variant->stock = (int) $variant->stock + (int) $item->quantity;
                    $variant->save();
                }
                $this->inventoryService->adjustCentralStock(
                    (int) $item->product_id,
                    (int) $item->quantity,
                    $invoice->uuid,
                    'برگشت موجودی بابت کنسلی حواله فروش'
                );
            }

            $oldStatus = (string) $invoice->status;
            $invoice->update([
                'status' => SalesHavalehStatusService::NOT_SHIPPED,
                'status_changed_at' => now(),
                'status_changed_by' => $userId,
            ]);

            $this->historyService->log($invoice, 'cancelled', 'status', $oldStatus, SalesHavalehStatusService::NOT_SHIPPED, $note ?: 'کنسلی فاکتور و برگشت موجودی', $userId);

            return $invoice->fresh();
        });
    }
}
