@extends('layouts.app')

@section('content')
@php
    $toToman = fn($rial) => number_format((int) floor(((int) $rial) / 10));
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="page-title mb-0">مشاهده سند خرید #{{ $purchase->id }}</h4>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="{{ route('purchases.edit', $purchase) }}">ویرایش سند</a>
        <a class="btn btn-outline-secondary" href="{{ route('purchases.index') }}">بازگشت</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body row g-3">
        <div class="col-md-4"><strong>تامین‌کننده:</strong> {{ $purchase->supplier?->name }}</div>
        <div class="col-md-4"><strong>شماره تماس:</strong> {{ $purchase->supplier?->phone ?: '-' }}</div>
        <div class="col-md-4"><strong>تاریخ:</strong> {{ $purchase->purchased_at?->format('Y/m/d H:i') }}</div>
        <div class="col-md-8"><strong>آدرس تامین‌کننده:</strong> {{ $purchase->supplier?->address ?: '-' }}</div>
        <div class="col-md-4"><strong>مبلغ کل:</strong> {{ $toToman($purchase->total_amount) }} تومان</div>
        <div class="col-12"><strong>توضیحات:</strong> {{ $purchase->note ?: '-' }}</div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                <tr>
                    <th>محصول</th>
                    <th>مدل</th>
                    <th>تعداد</th>
                    <th>قیمت خرید</th>
                    <th>قیمت فروش</th>
                    <th>تخفیف</th>
                    <th>جمع نهایی</th>
                </tr>
                </thead>
                <tbody>
                @foreach($purchase->items as $item)
                    <tr>
                        <td>
                            {{ $item->product_name }}
                            <div class="small text-muted">{{ $item->product_code }}</div>
                        </td>
                        <td>{{ $item->variant_name ?: ($item->variant?->variant_name ?? '-') }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $toToman($item->buy_price) }} تومان</td>
                        <td>{{ $toToman($item->sell_price) }} تومان</td>
                        <td>
                            @if($item->discount_type === 'percent')
                                {{ $item->discount_value }}٪
                            @elseif($item->discount_type === 'amount')
                                {{ $toToman($item->discount_value) }} تومان
                            @else
                                -
                            @endif
                            <div class="small text-muted">{{ $toToman($item->discount_amount ?? 0) }} تومان</div>
                        </td>
                        <td>{{ $toToman($item->line_total) }} تومان</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="row mt-3">
            <div class="col-md-4 ms-auto">
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between"><span>جمع قبل تخفیف</span><strong>{{ $toToman($purchase->subtotal_amount ?? 0) }} تومان</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>تخفیف کل</span><strong>{{ $toToman($purchase->total_discount ?? 0) }} تومان</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>قابل پرداخت</span><strong>{{ $toToman($purchase->total_amount) }} تومان</strong></li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
