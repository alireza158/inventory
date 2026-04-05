@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">📄 نمایش فقط‌خواندنی حواله فروش</h4>
    <div class="d-flex gap-2">
      <a href="{{ route('vouchers.sales.edit', $invoice->uuid) }}" class="btn btn-outline-primary">ویرایش</a>
      <a href="{{ route('vouchers.sales.index') }}" class="btn btn-outline-secondary">بازگشت</a>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body row g-2">
      <div class="col-md-4"><b>کد سند:</b> {{ $invoice->uuid }}</div>
      <div class="col-md-4"><b>مشتری:</b> {{ $invoice->customer_name }}</div>
      <div class="col-md-4"><b>وضعیت:</b> {{ $statusLabels[$invoice->status] ?? $invoice->status }}</div>
      <div class="col-md-4"><b>مبلغ کل:</b> {{ number_format((int)$invoice->total) }} تومان</div>
      <div class="col-md-8"><b>آدرس:</b> {{ $invoice->customer_address ?: '—' }}</div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white">اقلام حواله</div>
    <div class="table-responsive">
      <table class="table mb-0">
        <thead><tr><th>محصول</th><th>مدل</th><th>تعداد</th><th>قیمت</th><th>جمع</th></tr></thead>
        <tbody>
          @foreach($invoice->items as $it)
            <tr>
              <td>{{ $it->product?->name ?? '#'.$it->product_id }}</td>
              <td>{{ $it->variant?->variant_name ?? '—' }}</td>
              <td>{{ number_format((int)$it->quantity) }}</td>
              <td>{{ number_format((int)$it->price) }}</td>
              <td>{{ number_format((int)$it->line_total) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white">تاریخچه تغییرات</div>
    <div class="table-responsive">
      <table class="table mb-0">
        <thead><tr><th>عملیات</th><th>فیلد</th><th>قدیم</th><th>جدید</th><th>کاربر</th><th>زمان</th></tr></thead>
        <tbody>
          @forelse($invoice->histories as $h)
            <tr>
              <td>{{ $h->action_type }}</td>
              <td>{{ $h->field_name ?: '—' }}</td>
              <td>{{ $h->old_value ?: '—' }}</td>
              <td>{{ $h->new_value ?: '—' }}</td>
              <td>{{ $h->actor?->name ?: '—' }}</td>
              <td>{{ optional($h->done_at)->format('Y-m-d H:i') ?: '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted">تاریخچه‌ای ثبت نشده است.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
