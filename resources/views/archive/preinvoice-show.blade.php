@extends('layouts.app')
@section('content')
@php $toman = fn($a) => number_format((int)$a).' تومان'; @endphp
<style>.card-soft{background:#fff;border:1px solid rgba(0,0,0,.07);border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.04)}</style>
<div class="container py-4">
  <a href="{{ route('archive.index') }}" class="btn btn-outline-secondary mb-3">بازگشت</a>
  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card-soft p-3 mb-3">
        <h5 class="fw-bold">📄 جزئیات پیش‌فاکتور {{ $order->uuid }}</h5>
        <div>مشتری: {{ $order->customer_name }} | موبایل: {{ $order->customer_mobile }} | وضعیت: {{ $order->status_label }}</div>
        <div>ثبت‌کننده: {{ $order->creator?->name ?? '---' }} | بازبین: {{ $order->warehouseReviewer?->name ?? '---' }}</div>
      </div>
      <div class="card-soft overflow-hidden">
        <div class="p-3 border-bottom fw-bold">🛍️ اقلام پیش‌فاکتور</div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>کالا</th><th>تنوع</th><th>تعداد</th><th>قیمت</th><th>جمع</th></tr></thead><tbody>
          @foreach($order->items as $it)
            <tr><td>{{ $it->product?->name }}</td><td>{{ $it->variant?->variant_name }}</td><td>{{ number_format($it->quantity) }}</td><td>{{ $toman($it->price) }}</td><td><b>{{ $toman($it->quantity * $it->price) }}</b></td></tr>
          @endforeach
        </tbody></table></div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card-soft p-3">
        <div class="fw-bold mb-2">🕓 لاگ کامل تغییرات کاربران</div>
        <ul class="small mb-0">
          @forelse($order->reviews as $r)
            <li>{{ $r->created_at }} | {{ $r->user?->name ?? '---' }} | {{ $r->action }} | {{ $r->reason }}</li>
          @empty
            <li>لاگی ثبت نشده</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection
