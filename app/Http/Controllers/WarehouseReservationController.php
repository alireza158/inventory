<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InvoiceItem;
use App\Models\PreinvoiceDraftReservation;
use App\Models\PreinvoiceOrder;
use App\Models\PreinvoiceOrderItem;
use App\Models\User;
use App\Models\WarehouseTransferItem;
use App\Services\InventoryReservationReleaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class WarehouseReservationController extends Controller
{
    private const ACTIVE_PREINVOICE_STATUSES = [
        PreinvoiceOrder::STATUS_RESERVED_WAITING_WAREHOUSE,
        PreinvoiceOrder::STATUS_WAREHOUSE_REVIEWING,
        PreinvoiceOrder::STATUS_WAREHOUSE_APPROVED_WAITING_FINANCE,
        PreinvoiceOrder::STATUS_FINANCE_REVIEWING,
        PreinvoiceOrder::STATUS_RETURNED_TO_WAREHOUSE,
    ];

    public function index(Request $request)
    {
        $filters = $request->only(['q', 'user_id', 'customer_id', 'type', 'document_status', 'older_than_20', 'releasable_only', 'date_from', 'date_to']);
        $rows = collect()
            ->merge($this->draftRows($filters))
            ->merge($this->preinvoiceRows($filters))
            ->merge($this->invoiceRows($filters))
            ->merge($this->transferRows($filters))
            ->filter(fn (array $row) => $this->passesFilters($row, $filters))
            ->sortByDesc('created_at_ts')
            ->values();

        $stats = [
            'total_active' => $rows->count(),
            'draft_active' => $rows->where('type', 'draft')->count(),
            'draft_over_20h' => $rows->where('type', 'draft')->where('age_hours', '>', 20)->count(),
            'preinvoice_active' => $rows->where('type', 'preinvoice')->count(),
            'suspicious_releasable' => $rows->where('releasable', true)->where('age_hours', '>', 20)->count(),
        ];

        return view('warehouse-reservations.index', [
            'rows' => $rows,
            'stats' => $stats,
            'filters' => $filters,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'customers' => Customer::query()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(500)
                ->get(['id', 'first_name', 'last_name', 'mobile']),
            'statusLabels' => PreinvoiceOrder::statusLabels(),
        ]);
    }

    public function releaseDraftReservation(Request $request, PreinvoiceDraftReservation $reservation, InventoryReservationReleaseService $service)
    {
        $data = $request->validate([
            'release_reason' => ['required', 'string', Rule::in(['کاربر پیش‌فاکتور را ثبت نکرده', 'رزرو اشتباه ایجاد شده', 'مشتری منصرف شده', 'اصلاح موجودی', 'سایر'])],
            'release_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->releaseDraftReservation($reservation, $request->user(), $data['release_reason'], $data['release_note'] ?? null);

        return back()->with('success', 'رزرو موقت با موفقیت آزاد شد.');
    }


    private function passesFilters(array $row, array $filters): bool
    {
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '' && ! str_contains($row['product'].' '.$row['variant'].' '.$row['sku'], $q)) {
            return false;
        }
        if (($filters['user_id'] ?? '') !== '' && (string) ($row['user_id'] ?? '') !== (string) $filters['user_id']) {
            return false;
        }
        if (($filters['customer_id'] ?? '') !== '' && (string) ($row['customer_id'] ?? '') !== (string) $filters['customer_id']) {
            return false;
        }
        if (($filters['type'] ?? '') !== '' && $row['type'] !== $filters['type']) {
            return false;
        }
        if (($filters['document_status'] ?? '') !== '' && $row['document_status'] !== $filters['document_status']) {
            return false;
        }
        if (($filters['date_from'] ?? '') !== '' && $row['created_at']->toDateString() < $filters['date_from']) {
            return false;
        }
        if (($filters['date_to'] ?? '') !== '' && $row['created_at']->toDateString() > $filters['date_to']) {
            return false;
        }
        if (! empty($filters['older_than_20']) && $row['age_hours'] <= 20) {
            return false;
        }
        if (! empty($filters['releasable_only']) && ! $row['releasable']) {
            return false;
        }
        return true;
    }

    private function draftRows(array $filters): Collection
    {
        return PreinvoiceDraftReservation::query()
            ->with(['product', 'variant', 'user'])
            ->whereNull('converted_at')->whereNull('preinvoice_order_id')->whereNull('released_at')
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->get()->map(fn ($r) => $this->row('draft', $r, $r->product, $r->variant, $r->quantity, $r->created_at, $r->user, null, 'رزرو موقت فرم پیش‌فاکتور', 'فعال', null, null, true, $r));
    }

    private function preinvoiceRows(array $filters): Collection
    {
        return PreinvoiceOrderItem::query()->with(['product', 'variant', 'order.creator', 'order.customer'])
            ->whereHas('order', fn ($q) => $q->whereIn('status', self::ACTIVE_PREINVOICE_STATUSES)->whereNull('stock_released_at'))
            ->get()->map(function ($item) {
                $order = $item->order;
                return $this->row('preinvoice', $item, $item->product, $item->variant, $item->quantity, $order?->created_at ?? $item->created_at, $order?->creator, $order?->customer, 'رزرو پیش‌فاکتور ثبت‌شده', PreinvoiceOrder::statusLabels()[$order?->status] ?? $order?->status, $order?->uuid, $order ? route('archive.preinvoices.show', $order->uuid) : null, false, null);
            });
    }

    private function invoiceRows(array $filters): Collection
    {
        return InvoiceItem::query()->with(['product', 'variant', 'invoice.customer', 'invoice.preinvoiceOrder.creator'])
            ->get()->map(function ($item) {
                $invoice = $item->invoice;
                return $this->row('invoice', $item, $item->product, $item->variant, $item->quantity, $invoice?->created_at ?? $item->created_at, $invoice?->preinvoiceOrder?->creator, $invoice?->customer, 'فاکتور', $invoice?->status, $invoice?->uuid, $invoice ? route('archive.invoices.show', $invoice->uuid) : null, false, null);
            });
    }

    private function transferRows(array $filters): Collection
    {
        return WarehouseTransferItem::query()->with(['product', 'variant', 'transfer.user', 'transfer.customer'])
            ->whereHas('transfer', fn ($q) => $q->where('voucher_type', 'sale'))
            ->get()->map(function ($item) {
                $transfer = $item->transfer;
                return $this->row('transfer', $item, $item->product, $item->variant, $item->quantity, $transfer?->transferred_at ?? $transfer?->created_at ?? $item->created_at, $transfer?->user, $transfer?->customer, 'حواله فروش', $transfer?->reference, $transfer?->reference ?: $transfer?->id, $transfer ? route('vouchers.show', $transfer->id) : null, false, null);
            });
    }

    private function row(string $type, $source, $product, $variant, int $quantity, $createdAt, $user, $customer, string $typeLabel, ?string $status, ?string $documentNo, ?string $documentUrl, bool $releasable, ?PreinvoiceDraftReservation $reservation): array
    {
        $createdAt = $createdAt ?: now();
        $age = $createdAt->diffInHours(now());
        return [
            'type' => $type, 'source_id' => $source->id, 'product' => $product?->name ?? '—',
            'variant' => $variant?->variant_name ?? $variant?->variety_name ?? '—', 'sku' => $variant?->variant_code ?? $variant?->variety_code ?? $product?->sku ?? $product?->code ?? '—',
            'quantity' => $quantity, 'type_label' => $typeLabel, 'age_hours' => $age,
            'user_id' => $user?->id, 'customer_id' => $customer?->id, 'user' => $user?->name ?? '—', 'customer' => $customer ? ($customer->display_name ?: ($customer->mobile ?: 'نامشخص')) : 'نامشخص',
            'document_no' => $documentNo ?: '—', 'document_status' => $status ?: '—', 'created_at' => $createdAt,
            'created_at_ts' => $createdAt->timestamp, 'alert' => $age > 20 ? 'red' : ($age >= 6 ? 'yellow' : 'normal'),
            'releasable' => $releasable, 'document_url' => $documentUrl, 'reservation' => $reservation,
        ];
    }
}
