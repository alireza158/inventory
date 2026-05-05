@extends('layouts.app')
@section('content')
<div class="container py-4">
  <a href="{{ route('archive.index') }}" class="btn btn-outline-secondary mb-3">بازگشت</a>
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white fw-bold">جزئیات کامل فاکتور {{ $invoice->uuid }}</div>
    <div class="card-body">
      <div>مشتری: {{ $invoice->customer_name }} | موبایل: {{ $invoice->customer_mobile }} | وضعیت: {{ $invoice->status }}</div>
      <div>جمع جزء: {{ number_format((int)$invoice->subtotal) }} | تخفیف: {{ number_format((int)$invoice->discount_amount) }} | کل: {{ number_format((int)$invoice->total) }}</div>
      <hr>
      <h6>محصولات</h6>
      <table class="table table-sm">
        <thead><tr><th>کالا</th><th>تنوع</th><th>تعداد</th><th>قیمت</th><th>جمع</th></tr></thead>
        <tbody>
          @foreach($invoice->items as $it)
            <tr><td>{{ $it->product?->name }}</td><td>{{ $it->variant?->variant_name }}</td><td>{{ $it->quantity }}</td><td>{{ number_format((int)$it->price) }}</td><td>{{ number_format((int)$it->line_total) }}</td></tr>
          @endforeach
        </tbody>
      </table>
      <h6>پرداخت‌ها</h6><ul>@foreach($invoice->payments as $p)<li>{{ $p->created_at }} | {{ $p->creator?->name ?? '---' }} | {{ $p->method }} | {{ number_format((int)$p->amount) }}</li>@endforeach</ul>
      <h6>تغییر وضعیت‌ها</h6><ul>@foreach($invoice->histories as $h)<li>{{ $h->done_at ?? $h->created_at }} | {{ $h->actor?->name ?? '---' }} | {{ $h->old_value }} => {{ $h->new_value }}</li>@endforeach</ul>
    </div>
  </div>
</div>
@endsection
