@extends('layouts.app')

@section('title', 'پیش‌فاکتورهای من')

@section('content')
@php
  $toJalali = function ($date) {
      if (!$date) return '—';
      if (class_exists(\Hekmatinasser\Verta\Verta::class)) {
          return \Hekmatinasser\Verta\Verta::instance($date)->format('Y/m/d H:i');
      }
      return optional($date)->format('Y/m/d H:i') ?? '—';
  };
@endphp
<div class="container py-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h4 class="mb-0">پیش‌فاکتورهای من</h4>
    <a href="{{ route('preinvoice.create') }}" class="btn btn-primary">➕ ثبت پیش‌فاکتور جدید</a>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <form class="row g-2 mb-3" method="GET" action="{{ route('preinvoice.my.index') }}">
        <div class="col-md-4">
          <label class="form-label">وضعیت</label>
          <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">همه وضعیت‌ها</option>
            @foreach($statusLabels as $key => $label)
              <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
          <thead>
            <tr>
              <th>کد</th>
              <th>مشتری</th>
              <th>موبایل</th>
              <th>توضیحات</th>
              <th>تعداد اقلام</th>
              <th>مبلغ نهایی</th>
              <th>وضعیت</th>
              <th>تاریخ ثبت</th>
              <th class="text-end">عملیات</th>
            </tr>
          </thead>
          <tbody>
            @forelse($orders as $order)
              <tr>
                <td class="fw-semibold">{{ $order->uuid }}</td>
                <td>{{ $order->customer_name }}</td>
                <td>{{ $order->customer_mobile ?: '—' }}</td>
                <td class="text-muted small" style="min-width: 220px; max-width: 320px; white-space: normal;">
                  {{ $order->description ? \Illuminate\Support\Str::limit($order->description, 120) : '—' }}
                </td>
                <td>{{ number_format($order->items_count) }}</td>
                <td>{{ \App\Support\Currency::formatRial($order->total_price) }}</td>
                <td>{{ $statusLabels[$order->status] ?? $order->status }}</td>
                <td>{{ $toJalali($order->created_at) }}</td>
                <td class="text-end">
                  <div class="d-flex gap-1 justify-content-end">
                    <a href="{{ route('preinvoice.my.show', $order->uuid) }}" class="btn btn-sm btn-outline-primary">مشاهده کامل</a>
                    <a href="{{ route('preinvoice.my.show', $order->uuid) }}?print=1" target="_blank" class="btn btn-sm btn-outline-dark">پرینت</a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-4">پیش‌فاکتوری توسط شما ثبت نشده است.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if(method_exists($orders, 'links'))
      <div class="card-footer bg-white">
        {{ $orders->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
