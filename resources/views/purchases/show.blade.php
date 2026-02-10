@extends('layouts.app')

@section('content')
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
        <div class="col-md-4"><strong>تاریخ:</strong> {{ $purchase->purchased_at?->format('Y/m/d H:i') }}</div>
        <div class="col-md-4"><strong>مبلغ کل:</strong> {{ number_format($purchase->total_amount) }} ریال</div>
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
                    <th>جمع</th>
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
                        <td>{{ number_format($item->buy_price) }}</td>
                        <td>{{ number_format($item->sell_price) }}</td>
                        <td>{{ number_format($item->line_total) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
