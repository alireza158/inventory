<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PreinvoiceOrder;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class SalesPrintDocumentService
{
    public function __construct(private readonly WarehouseMapService $warehouseMapService) {}

    public function invoiceData(Invoice $invoice, string $mode = 'warehouse'): array
    {
        $invoice->loadMissing(['items.product', 'items.variant.modelList', 'items.variant.color', 'payments', 'preinvoiceOrder', 'shippingMethod', 'customer']);

        $paid = $invoice->relationLoaded('payments') ? (int) $invoice->payments->sum('amount') : (int) $invoice->paid_amount;

        return [
            'documentType' => 'invoice',
            'title' => 'فاکتور فروش',
            'numberLabel' => 'شماره فاکتور',
            'number' => $invoice->uuid,
            'customerNumber' => $invoice->external_order_id ?: null,
            'registeredAt' => $invoice->preinvoiceOrder?->created_at ?? $invoice->created_at,
            'issuedAt' => $invoice->created_at,
            'status' => $invoice->status,
            'customer' => [
                'name' => $invoice->customer_name ?: $invoice->customer?->display_name,
                'mobile' => $invoice->customer_mobile ?: $invoice->customer?->mobile,
                'address' => $invoice->customer_address,
                'code' => $invoice->customer?->crm_customer_id ?: $invoice->customer_id,
            ],
            'shipping' => [
                'method' => $invoice->shippingMethod?->name ?? ($invoice->shipping_id ? 'روش ارسال #' . $invoice->shipping_id : null),
                'cost' => (int) $invoice->shipping_price,
                'description' => null,
            ],
            'items' => $this->items($invoice->items),
            'totals' => [
                'subtotal' => (int) ($invoice->subtotal ?: $invoice->items->sum(fn ($i) => (int) $i->quantity * (int) $i->price)),
                'discount' => (int) $invoice->discount_amount,
                'shipping' => (int) $invoice->shipping_price,
                'total' => (int) $invoice->total,
                'paid' => $paid,
                'remaining' => max((int) $invoice->total - $paid, 0),
            ],
            'company' => config('company'),
            'logo' => asset('logo.png'),
            'mode' => $mode === 'customer' ? 'customer' : 'warehouse',
            'backUrl' => route('invoices.show', $invoice->uuid),
        ];
    }

    public function preinvoiceData(PreinvoiceOrder $order, string $mode = 'warehouse'): array
    {
        $order->loadMissing(['items.product', 'items.variant.modelList', 'items.variant.color', 'shippingMethod', 'customer', 'invoice']);
        $itemsTotal = (int) $order->items->sum(fn ($i) => (int) $i->quantity * (int) $i->price);

        return [
            'documentType' => 'preinvoice',
            'title' => 'پیش‌فاکتور فروش',
            'numberLabel' => 'شماره پیش‌فاکتور',
            'number' => $order->uuid,
            'customerNumber' => $order->external_order_id ?: null,
            'registeredAt' => $order->created_at,
            'issuedAt' => null,
            'status' => $order->status_label,
            'customer' => [
                'name' => $order->customer_name,
                'mobile' => $order->customer_mobile,
                'address' => $order->customer_address,
                'code' => $order->customer?->crm_customer_id ?: $order->customer_id,
            ],
            'shipping' => [
                'method' => $order->shippingMethod?->name ?? ($order->shipping_id ? 'روش ارسال #' . $order->shipping_id : null),
                'cost' => (int) $order->shipping_price,
                'description' => $order->description,
            ],
            'items' => $this->items($order->items),
            'totals' => [
                'subtotal' => $itemsTotal,
                'discount' => (int) $order->discount_amount,
                'shipping' => (int) $order->shipping_price,
                'total' => (int) ($order->total_price ?: ($itemsTotal - (int) $order->discount_amount + (int) $order->shipping_price)),
                'paid' => null,
                'remaining' => null,
            ],
            'company' => config('company'),
            'logo' => asset('logo.png'),
            'mode' => $mode === 'customer' ? 'customer' : 'warehouse',
            'backUrl' => route('archive.preinvoices.show', $order->uuid),
        ];
    }

    private function items(EloquentCollection|Collection $items): Collection
    {
        $warehouseId = (int) (Warehouse::query()->where('type', 'central')->value('id') ?: Warehouse::query()->value('id'));

        return $items->values()->map(function ($item) use ($warehouseId) {
            $variant = $item->variant;
            $product = $item->product;

            return [
                'description' => $this->description($item),
                'inventoryCode' => $variant?->barcode ?: $variant?->sku ?: $variant?->variant_code ?: $variant?->variety_code ?: $product?->barcode ?: $product?->sku ?: $product?->code ?: '—',
                'warehouseMap' => $variant ? $this->warehouseMap((int) $variant->id, $warehouseId) : 'بدون نقشه',
                'quantity' => (int) $item->quantity,
                'lineTotal' => (int) ($item->line_total ?? ((int) $item->quantity * (int) $item->price)),
            ];
        });
    }

    private function description($item): string
    {
        $variant = $item->variant;
        $parts = collect([
            $item->product?->name,
            $variant?->modelList?->model_name,
            $variant?->variant_name,
            $variant?->variety_name,
            $variant?->color?->name,
        ])->filter(fn ($v) => filled($v))->unique()->values();

        return $parts->isNotEmpty() ? $parts->implode(' - ') : ('#' . $item->product_id);
    }

    private function warehouseMap(int $variantId, int $warehouseId): string
    {
        if ($warehouseId <= 0) {
            return 'بدون نقشه';
        }

        $locations = $this->warehouseMapService->locationsForVariant($variantId, $warehouseId)
            ->filter(fn ($stock) => (int) $stock->quantity > 0 && $stock->location);

        if ($locations->isEmpty()) {
            return 'بدون نقشه';
        }

        return $locations->map(fn ($stock) => $stock->location->code . ((int) $stock->quantity > 0 ? ' (' . number_format((int) $stock->quantity) . ')' : ''))
            ->implode(' / ');
    }
}
