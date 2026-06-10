<?php

namespace App\Exports;

use App\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchasesExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function query(): Builder
    {
        return PurchaseItem::query()
            ->select('purchase_items.*')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->with(['purchase.supplier', 'purchase.user'])
            ->orderBy('purchases.purchased_at')
            ->orderBy('purchases.id')
            ->orderBy('purchase_items.id');
    }

    public function headings(): array
    {
        return [
            'شناسه خرید',
            'تاریخ خرید',
            'تامین‌کننده',
            'شماره تماس تامین‌کننده',
            'توضیحات خرید',
            'ثبت‌کننده',
            'جمع قبل تخفیف سند (ریال)',
            'نوع تخفیف سند',
            'مقدار تخفیف سند',
            'مبلغ تخفیف سند (ریال)',
            'مبلغ نهایی سند (ریال)',
            'نام کالا',
            'کد کالا',
            'مدل',
            'تعداد',
            'قیمت خرید واحد (ریال)',
            'قیمت فروش واحد (ریال)',
            'جمع ردیف قبل تخفیف (ریال)',
            'نوع تخفیف ردیف',
            'مقدار تخفیف ردیف',
            'مبلغ تخفیف ردیف (ریال)',
            'جمع نهایی ردیف (ریال)',
        ];
    }

    public function map($item): array
    {
        $purchase = $item->purchase;

        return [
            $purchase?->id,
            $purchase?->purchased_at?->format('Y/m/d H:i'),
            $purchase?->supplier?->name,
            $purchase?->supplier?->phone,
            $purchase?->note,
            $purchase?->user?->name,
            $purchase?->subtotal_amount ?? 0,
            $this->discountTypeLabel($purchase?->discount_type),
            $purchase?->discount_value ?? 0,
            $purchase?->total_discount ?? 0,
            $purchase?->total_amount ?? 0,
            $item->product_name,
            $item->product_code,
            $item->variant_name,
            $item->quantity,
            $item->buy_price,
            $item->sell_price,
            $item->line_subtotal,
            $this->discountTypeLabel($item->discount_type),
            $item->discount_value,
            $item->discount_amount,
            $item->line_total,
        ];
    }

    private function discountTypeLabel(?string $type): string
    {
        return match ($type) {
            'amount' => 'مبلغی',
            'percent' => 'درصدی',
            default => '',
        };
    }
}
