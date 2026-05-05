@extends('layouts.app')
@section('content')
@php
  $toman = fn($a) => number_format((int)$a).' تومان';
  $statusFa = fn($s) => match($s){
    'pending_warehouse_approval' => 'در انتظار تایید انبار',
    'collecting' => 'در حال جمع‌آوری',
    'checking_discrepancy' => 'چک کردن بار',
    'packing' => 'بسته‌بندی',
    'shipped' => 'ارسال شده',
    'not_shipped' => 'کنسل شده',
    default => $s,
  };
@endphp
<style>.card-soft{background:#fff;border:1px solid rgba(0,0,0,.07);border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.04)}</style>
<div class="container py-4">
  <div class="d-flex justify-content-between mb-3"><a href="{{ route('archive.index') }}" class="btn btn-outline-secondary">بازگشت</a><span class="badge bg-dark">{{ $statusFa($invoice->status) }}</span></div>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card-soft p-3 mb-3">
        <h5 class="fw-bold">🧾 جزئیات فاکتور {{ $invoice->uuid }}</h5>
        <div>مشتری: {{ $invoice->customer_name }} | موبایل: {{ $invoice->customer_mobile }}</div>
        <div>جمع جزء: {{ $toman($invoice->subtotal) }} | تخفیف: {{ $toman($invoice->discount_amount) }} | کل: <b>{{ $toman($invoice->total) }}</b></div>
      </div>
      <div class="card-soft overflow-hidden">
        <div class="p-3 border-bottom fw-bold">🛍️ اقلام فاکتور</div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>کالا</th><th>تنوع</th><th>تعداد</th><th>قیمت</th><th>جمع</th></tr></thead><tbody>
          @foreach($invoice->items as $it)
            <tr><td>{{ $it->product?->name }}</td><td>{{ $it->variant?->variant_name }}</td><td>{{ number_format($it->quantity) }}</td><td>{{ $toman($it->price) }}</td><td><b>{{ $toman($it->line_total) }}</b></td></tr>
          @endforeach
        </tbody></table></div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card-soft p-3 mb-3"><div class="fw-bold mb-2">💳 پرداخت‌ها</div><ul class="small mb-0">@forelse($invoice->payments as $p)<li>{{ $p->created_at }} | {{ $p->creator?->name ?? '---' }} | {{ $p->method }} | {{ $toman($p->amount) }}</li>@empty<li>پرداختی ثبت نشده</li>@endforelse</ul></div>
      <div class="card-soft p-3 mb-3"><div class="fw-bold mb-2">📝 یادداشت‌ها</div><ul class="small mb-0">@forelse($invoice->notes as $n)<li>{{ $n->created_at }} | {{ $n->user?->name ?? '---' }} | {{ $n->body }}</li>@empty<li>یادداشتی ثبت نشده</li>@endforelse</ul></div>
      <div class="card-soft p-3"><div class="fw-bold mb-2">🕓 لاگ کامل تغییرات کاربران</div><ul class="small mb-0">@forelse($invoice->histories as $h)<li>{{ $h->done_at ?? $h->created_at }} | {{ $h->actor?->name ?? '---' }} | {{ $h->action_type }} | {{ $h->field_name }} | {{ $h->old_value }} → {{ $h->new_value }} | {{ $h->description }}</li>@empty<li>لاگی وجود ندارد</li>@endforelse</ul></div>
    </div>
  </div>
</div>
@endsection
