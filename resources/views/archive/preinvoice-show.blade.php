@extends('layouts.app')
@section('content')
<div class="container py-4">
  <a href="{{ route('archive.index') }}" class="btn btn-outline-secondary mb-3">بازگشت</a>
  <div class="card shadow-sm border-0">
    <div class="card-header bg-white fw-bold">جزئیات کامل پیش‌فاکتور {{ $order->uuid }}</div>
    <div class="card-body">
      <div>مشتری: {{ $order->customer_name }} | موبایل: {{ $order->customer_mobile }} | وضعیت: {{ $order->status_label }}</div>
      <div>ثبت‌کننده: {{ $order->creator?->name ?? '---' }} | بازبین انبار: {{ $order->warehouseReviewer?->name ?? '---' }}</div>
      <hr>
      <h6>محصولات</h6>
      <table class="table table-sm">
        <thead><tr><th>کالا</th><th>تنوع</th><th>تعداد</th><th>قیمت</th><th>جمع</th></tr></thead>
        <tbody>
          @foreach($order->items as $it)
            <tr><td>{{ $it->product?->name }}</td><td>{{ $it->variant?->variant_name }}</td><td>{{ $it->quantity }}</td><td>{{ number_format((int)$it->price) }}</td><td>{{ number_format((int)$it->quantity * (int)$it->price) }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
