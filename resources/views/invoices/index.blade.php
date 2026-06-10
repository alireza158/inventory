@extends('layouts.app')

@section('title', 'لیست فاکتورها')

@section('content')
@php
  use Morilog\Jalali\Jalalian;

  $statusFa = fn($s) => match($s){
    'pending_warehouse_approval' => 'در انتظار تایید انبار',
    'collecting' => 'در حال جمع‌آوری',
    'checking_discrepancy' => 'چک کردن بار',
    'final_check' => 'کنترل نهایی',
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

  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <div class="h5 fw-bold mb-0">🧾 لیست فاکتورها</div>
      <div class="text-muted small">مرجع اصلی همه فاکتورهای ثبت نهایی؛ فاکتورهای قدیمی هم در همین صفحه نمایش داده می‌شوند.</div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-outline-secondary" href="{{ route('vouchers.index', ['voucher_type' => 'sale']) }}">حواله فروش کالا</a>
      <form class="d-flex gap-2" method="GET" action="{{ route('invoices.index') }}">
        @foreach(($filters ?? []) as $key => $value)
          @if($key !== 'date')
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
          @endif
        @endforeach
        <input type="text" class="form-control" name="export_date" value="{{ $reportDateInput ?? '' }}" placeholder="تاریخ خروجی مثل 1403/03/21">
        <button class="btn btn-outline-success" type="submit" name="export" value="daily_csv">خروجی روزانه CSV</button>
      </form>
    </div>
  </div>

  <div class="card mb-3 border-0 shadow-sm">
    <div class="card-body">
      <form class="row g-2 align-items-end" method="GET" action="{{ route('invoices.index') }}">
        <div class="col-md-3">
          <label class="form-label">تاریخ شمسی ثبت فاکتور</label>
          <input type="text" class="form-control" name="date" value="{{ $filters['date'] ?? '' }}" dir="ltr" placeholder="1403/03/21 یا ۱۴۰۳/۰۳/۲۱">
        </div>
        <div class="col-md-3">
          <label class="form-label">شماره فاکتور</label>
          <input class="form-control" name="invoice_number" value="{{ $filters['invoice_number'] ?? $q ?? '' }}" placeholder="جستجوی بخشی از شماره">
        </div>
        <div class="col-md-3">
          <label class="form-label">کد شخص / مشتری</label>
          <input class="form-control" name="customer_code" value="{{ $filters['customer_code'] ?? '' }}" placeholder="کد داخلی یا کد CRM مشتری">
        </div>
        <div class="col-md-3">
          <label class="form-label">نام شخص / مشتری</label>
          <input class="form-control" name="customer_name" value="{{ $filters['customer_name'] ?? '' }}" placeholder="جستجوی بخشی از نام فارسی">
        </div>
        <div class="col-12 d-flex gap-2 justify-content-end">
          <a class="btn btn-outline-secondary" href="{{ route('invoices.index') }}">پاک‌کردن فیلترها</a>
          <button class="btn btn-primary">جستجو</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="text-nowrap">شماره فاکتور</th>
            <th>مشتری</th>
            <th class="text-nowrap">کد مشتری</th>
            <th class="text-nowrap">موبایل</th>
            <th class="text-nowrap">وضعیت</th>
            <th class="text-nowrap">وضعیت پرداخت</th>
            <th class="text-nowrap">مبلغ</th>
            <th class="text-nowrap">مانده</th>
            <th class="text-nowrap">تاریخ ثبت</th>
            <th class="text-nowrap">ثبت‌کننده</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($invoices as $inv)
            @php
              $paid = (int) ($inv->paid_total ?? 0);
              $remaining = max((int) $inv->total - $paid, 0);
              $payStatus = $remaining <= 0 ? 'تسویه شده' : ($paid > 0 ? 'پرداخت ناقص' : 'پرداخت نشده');
              $payStatusClass = $remaining <= 0 ? 'bg-success' : ($paid > 0 ? 'bg-warning text-dark' : 'bg-danger');
              $customerCode = $inv->customer?->crm_customer_id ?: $inv->customer_id;
            @endphp
            <tr>
              <td class="text-nowrap fw-semibold">{{ $inv->uuid }}</td>
              <td>{{ $inv->customer_name ?: $inv->customer?->display_name ?: '—' }}</td>
              <td class="text-nowrap">{{ $customerCode ?: '—' }}</td>
              <td class="text-nowrap">{{ $inv->customer_mobile ?: $inv->customer?->mobile ?: '—' }}</td>
              <td class="text-nowrap">{{ $statusFa($inv->status) }}</td>
              <td class="text-nowrap"><span class="badge {{ $payStatusClass }}">{{ $payStatus }}</span></td>
              <td class="text-nowrap">{{ number_format($inv->total) }}</td>
              <td class="text-nowrap fw-bold {{ $remaining > 0 ? 'text-danger' : 'text-success' }}">
                {{ number_format($remaining) }}
              </td>
              <td class="text-nowrap">
                {{ $inv->created_at ? Jalalian::fromDateTime($inv->created_at)->format('Y/m/d') : '—' }}
              </td>
              <td class="text-nowrap">{{ $inv->preinvoiceOrder?->creator?->name ?? '—' }}</td>
              <td class="text-nowrap">
                <div class="d-flex gap-1">
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('invoices.show', $inv->uuid) }}">جزئیات</a>
                  <a class="btn btn-sm btn-outline-dark" href="{{ route('invoices.print', $inv->uuid) }}" target="_blank">چاپ فاکتور</a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="11" class="text-center text-muted py-4">هیچ فاکتوری با فیلترهای انتخاب‌شده یافت نشد.</td>
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
