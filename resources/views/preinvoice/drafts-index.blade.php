@extends('layouts.app')

@php
  use Morilog\Jalali\Jalalian;
@endphp

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">💰 صف مالی پیش‌فاکتورها</h4>
    <a href="{{ route('preinvoice.create') }}" class="btn btn-primary">➕ ایجاد پیش‌فاکتور</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
      <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
        <div>
          <h6 class="mb-1">پیش‌فاکتورهای در انتظار تایید مالی</h6>
          <small class="text-muted">در این بخش تیم مالی می‌تواند پیش‌فاکتورها را بررسی و مشاهده کند.</small>
        </div>
        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
          {{ number_format($orders->total()) }} مورد در انتظار بررسی
        </span>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr class="table-light">
            <th>#</th>
            <th>کد</th>
            <th>مشتری</th>
            <th>ثبت‌کننده</th>
            <th>موبایل</th>
            <th class="text-nowrap">جمع کل (تومان)</th>
            <th class="text-nowrap">تاریخ ثبت</th>
            <th class="text-end"></th>
          </tr>
        </thead>
        <tbody>
          @forelse($orders as $o)
            <tr>
              <td>{{ $o->id }}</td>
              <td>{{ $o->uuid }}</td>
              <td>{{ $o->customer_name }}</td>
              <td>{{ $o->creator?->name ?? '—' }}</td>
              <td>{{ $o->customer_mobile }}</td>
              <td>{{ number_format((int)$o->total_price) }}</td>
              <td>{{ $o->created_at ? Jalalian::fromDateTime($o->created_at)->format('Y/m/d H:i') : '—' }}</td>
              <td class="text-end">
                <div class="d-flex gap-2 justify-content-end">
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('preinvoice.draft.edit', $o->uuid) }}">ویرایش</a>
                  <a class="btn btn-sm btn-success" href="{{ route('preinvoice.draft.finance', $o->uuid) }}">مشاهده</a>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center py-4">موردی نیست</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $orders->links() }}
  </div>
</div>
@endsection
