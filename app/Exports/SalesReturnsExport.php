<?php

namespace App\Exports;

use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SalesReturnsExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    public function __construct(private readonly array $filters = [])
    {
    }

    public function query(): Builder
    {
        return WarehouseTransferItem::query()
            ->select('warehouse_transfer_items.*')
            ->join('warehouse_transfers', 'warehouse_transfers.id', '=', 'warehouse_transfer_items.warehouse_transfer_id')
            ->with(['transfer.customer', 'transfer.user', 'product.category', 'variant.modelList', 'variant.color'])
            ->where('warehouse_transfers.voucher_type', WarehouseTransfer::TYPE_CUSTOMER_RETURN)
            ->when(($this->filters['product_id'] ?? 0) > 0, fn ($q) => $q->where('warehouse_transfer_items.product_id', (int) $this->filters['product_id']))
            ->when(($this->filters['variant_id'] ?? 0) > 0, fn ($q) => $q->where('warehouse_transfer_items.product_variant_id', (int) $this->filters['variant_id']))
            ->when(($this->filters['customer_id'] ?? 0) > 0, fn ($q) => $q->where('warehouse_transfers.customer_id', (int) $this->filters['customer_id']))
            ->when(($this->filters['date_from'] ?? '') !== '', fn ($q) => $q->whereDate('warehouse_transfers.transferred_at', '>=', $this->filters['date_from']))
            ->when(($this->filters['date_to'] ?? '') !== '', fn ($q) => $q->whereDate('warehouse_transfers.transferred_at', '<=', $this->filters['date_to']))
            ->orderBy('warehouse_transfers.transferred_at')
            ->orderBy('warehouse_transfers.id')
            ->orderBy('warehouse_transfer_items.id');
    }

    public function headings(): array
    {
        return [
            'شماره سند برگشت از فروش', 'تاریخ سند', 'نام مشتری', 'کد کالا', 'نام کالا', 'دسته‌بندی کالا',
            'تنوع کالا / Variant', 'تعداد برگشتی', 'علت برگشت', 'توضیحات', 'کاربر ثبت‌کننده', 'تاریخ ثبت', 'تاریخ ویرایش',
        ];
    }

    public function map($item): array
    {
        $transfer = $item->transfer;
        $customerName = trim(($transfer?->customer?->first_name ?? '') . ' ' . ($transfer?->customer?->last_name ?? ''));
        $variantParts = array_filter([
            $item->variant?->modelList?->model_name,
            $item->variant?->color?->name,
            $item->variant?->variant_name ?: $item->variant_name,
            $item->variant?->variant_code ?: $item->variant_code,
        ]);

        return [
            $transfer?->reference ?: ('TR-' . $transfer?->id),
            $transfer?->transferred_at?->format('Y/m/d H:i'),
            $customerName !== '' ? $customerName : ($transfer?->beneficiary_name ?: '—'),
            $item->product?->code ?: $item->product?->sku,
            $item->product?->name,
            $item->product?->category?->name,
            implode(' / ', array_unique($variantParts)) ?: '—',
            (int) $item->quantity,
            WarehouseTransfer::returnReasonOptions()[$transfer?->return_reason] ?? '—',
            $transfer?->note,
            $transfer?->user?->name,
            $transfer?->created_at?->format('Y/m/d H:i'),
            $transfer?->updated_at && !$transfer->updated_at->equalTo($transfer->created_at) ? $transfer->updated_at->format('Y/m/d H:i') : '',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setRightToLeft(true);
                $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A1:M1')->getFont()->setBold(true);
                $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
                $sheet->freezePane('A2');
            },
        ];
    }
}
