@extends('layouts.app')

@php
    use Morilog\Jalali\Jalalian;
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">↩️ نمایش فاکتور برگشت از فروش</h4>
        <div class="text-muted small">این صفحه فقط برای مشاهده است و امکان ویرایش ندارد.</div>
    </div>
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">بازگشت</a>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><strong>شماره سند:</strong> {{ $voucher->reference ?: ('TR-' . $voucher->id) }}</div>
            <div class="col-md-4"><strong>تاریخ:</strong> {{ $voucher->transferred_at ? Jalalian::fromDateTime($voucher->transferred_at)->format('Y/m/d H:i') : '—' }}</div>
            <div class="col-md-4"><strong>مبلغ کل:</strong> {{ number_format((int) $voucher->total_amount) }} تومان</div>
            <div class="col-md-4"><strong>مشتری:</strong> {{ $voucher->customer?->display_name ?: ($voucher->beneficiary_name ?: '—') }}</div>
            <div class="col-md-4"><strong>فاکتور مرجع:</strong> {{ $voucher->relatedInvoice?->uuid ?: '—' }}</div>
            <div class="col-md-4"><strong>علت برگشت:</strong> {{ \App\Models\WarehouseTransfer::returnReasonOptions()[$voucher->return_reason] ?? '—' }}</div>
            <div class="col-md-6"><strong>انبار مبدا:</strong> {{ $voucher->fromWarehouse?->name ?: '—' }}</div>
            <div class="col-md-6"><strong>انبار مقصد:</strong> {{ $voucher->toWarehouse?->name ?: '—' }}</div>
            <div class="col-md-12"><strong>توضیحات:</strong> {{ $voucher->note ?: '—' }}</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">اقلام برگشتی</div>
    <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>کالا</th>
                    <th>تنوع</th>
                    <th>تعداد برگشتی</th>
                    <th>قیمت واحد</th>
                    <th>جمع</th>
                </tr>
            </thead>
            <tbody>
            @forelse($voucher->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->product?->name ?? ('#' . $item->product_id) }}</td>
                    <td>{{ $item->variant?->variant_name ?: ($item->variant_name ?: '—') }}</td>
                    <td>{{ number_format((int) $item->quantity) }}</td>
                    <td>{{ number_format((int) $item->unit_price) }}</td>
                    <td>{{ number_format((int) $item->line_total) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">آیتمی ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
