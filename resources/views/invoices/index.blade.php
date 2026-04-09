@extends('layouts.app')

@section('content')
@php
  use Morilog\Jalali\Jalalian;

  $statusFa = fn($s) => match($s){
    'pending_warehouse_approval' => 'در انتظار تایید انبار',
    'collecting' => 'در حال جمع‌آوری',
    'checking_discrepancy' => 'چک کردن بار',
    'packing' => 'بسته‌بندی بار',
    'shipped' => 'ارسال شد',
    'not_shipped' => 'کنسل شده',
    default => $s,
  };
@endphp

<div class="container">

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <div class="h5 fw-bold mb-0">🧾 فاکتورها</div>
      <div class="text-muted small">لیست فاکتورهای ثبت نهایی</div>
    </div>

    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('vouchers.index', ['voucher_type' => 'sale']) }}">حواله فروش کالا</a>
      <form class="d-flex gap-2" method="GET" action="{{ route('invoices.index') }}">
      <input class="form-control" name="q" value="{{ $q }}" placeholder="جستجو کد/نام/موبایل">
      <button class="btn btn-primary">جستجو</button>
    </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="text-nowrap">کد</th>
            <th>مشتری</th>
            <th class="text-nowrap">موبایل</th>
            <th class="text-nowrap">وضعیت</th>
            <th class="text-nowrap">مبلغ</th>
            <th class="text-nowrap">مانده</th>
            <th class="text-nowrap">تاریخ</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($invoices as $inv)
            <tr>
              <td class="text-nowrap">{{ $inv->uuid }}</td>
              <td>{{ $inv->customer_name ?: '—' }}</td>
              <td class="text-nowrap">{{ $inv->customer_mobile ?: '—' }}</td>
              <td class="text-nowrap">{{ $statusFa($inv->status) }}</td>
              <td class="text-nowrap">{{ number_format($inv->total) }}</td>
              <td class="text-nowrap fw-bold {{ $inv->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">
                {{ number_format($inv->remaining_amount) }}
              </td>
              <td class="text-nowrap">
                {{ $inv->created_at ? Jalalian::fromDateTime($inv->created_at)->format('Y/m/d') : '—' }}
              </td>
              <td class="text-nowrap">
                <div class="d-flex gap-1">
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('invoices.show', $inv->uuid) }}">جزئیات</a>
                  <a class="btn btn-sm btn-outline-dark" href="{{ route('invoices.print', $inv->uuid) }}" target="_blank">چاپ فاکتور</a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center text-muted py-4">فاکتوری یافت نشد</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $invoices->links() }}
  </div>
</div>
@endsection
