@extends('layouts.app')

@php use Morilog\Jalali\Jalalian; @endphp

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">📦 پیش‌فاکتورهای در انتظار تایید انبار</h4>
    <a href="{{ route('preinvoice.create') }}" class="btn btn-primary">➕ ایجاد پیش‌فاکتور</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="card shadow-sm border-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>شماره</th>
            <th>تاریخ</th>
            <th>مشتری</th>
            <th>تعداد آیتم</th>
            <th>مبلغ کل</th>
            <th>ثبت‌کننده</th>
            <th>وضعیت</th>
            <th class="text-end">عملیات</th>
          </tr>
        </thead>
        <tbody>
          @forelse($orders as $order)
            <tr>
              <td>{{ $order->uuid }}</td>
              <td>{{ $order->created_at ? Jalalian::fromDateTime($order->created_at)->format('Y/m/d H:i') : '—' }}</td>
              <td>{{ $order->customer_name }}</td>
              <td>{{ number_format((int) $order->items_count) }}</td>
              <td>{{ number_format((int) $order->total_price) }}</td>
              <td>{{ $order->creator?->name ?? '—' }}</td>
              <td><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ $order->status_label }}</span></td>
              <td class="text-end"><a href="{{ route('preinvoice.warehouse.review', $order->uuid) }}" class="btn btn-sm btn-outline-primary">بررسی و تایید</a></td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center py-4">موردی برای بررسی انبار وجود ندارد.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">{{ $orders->links() }}</div>
</div>
@endsection
