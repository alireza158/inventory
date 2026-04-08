@extends('layouts.app')

@section('content')
@php
  $activeReasons = $tab === 'outgoing' ? $outgoingReasons : $incomingReasons;
  $activeSummary = $tab === 'outgoing' ? $outgoingSummary : $incomingSummary;
  $list = $tab === 'outgoing' ? $outgoingVouchers : $incomingVouchers;
@endphp

<div class="container py-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h4 class="mb-0">حواله‌های انبار</h4>
    <a class="btn btn-primary" href="{{ route('vouchers.create') }}">+ ثبت حواله</a>
  </div>

  <ul class="nav nav-tabs mb-3">
    <li class="nav-item">
      <a class="nav-link {{ $tab === 'outgoing' ? 'active' : '' }}" href="{{ route('vouchers.index', array_merge(request()->query(), ['tab' => 'outgoing'])) }}">حواله‌های خروجی</a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $tab === 'incoming' ? 'active' : '' }}" href="{{ route('vouchers.index', array_merge(request()->query(), ['tab' => 'incoming'])) }}">حواله‌های ورودی</a>
    </li>
  </ul>

  <form method="GET" class="card shadow-sm border-0 mb-3">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="form-label">از تاریخ</label>
          <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">تا تاریخ</label>
          <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">علت حواله</label>
          <select name="reason" class="form-select">
            <option value="">همه</option>
            @foreach($activeReasons as $key => $label)
              <option value="{{ $key }}" @selected($filters['reason'] === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">انبار مرتبط</label>
          <select name="warehouse_id" class="form-select">
            <option value="0">همه</option>
            @foreach($warehouses as $warehouse)
              <option value="{{ $warehouse->id }}" @selected($filters['warehouse_id'] === (int) $warehouse->id)>{{ $warehouse->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">شماره حواله</label>
          <input type="text" class="form-control" name="voucher_no" value="{{ $filters['voucher_no'] }}" placeholder="TR-...">
        </div>
        <div class="col-md-2">
          <label class="form-label">جستجوی کالا</label>
          <input type="text" class="form-control" name="product_q" value="{{ $filters['product_q'] }}" placeholder="نام کالا">
        </div>
      </div>
      <div class="mt-2 d-flex gap-2">
        <button class="btn btn-primary">اعمال فیلتر</button>
        <a class="btn btn-outline-secondary" href="{{ route('vouchers.index', ['tab' => $tab]) }}">حذف فیلتر</a>
      </div>
    </div>
  </form>

  <div class="row g-2 mb-3">
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">تعداد حواله‌ها</small><div class="fs-5 fw-bold">{{ number_format($activeSummary['count']) }}</div></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">تعداد کل ردیف‌ها</small><div class="fs-5 fw-bold">{{ number_format($activeSummary['items']) }}</div></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">جمع کل تعداد اقلام</small><div class="fs-5 fw-bold">{{ number_format($activeSummary['qty']) }}</div></div></div></div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
        <tr>
          <th>شماره حواله</th>
          <th>تاریخ</th>
          <th>علت</th>
          <th>{{ $tab === 'outgoing' ? 'انبار مبدا' : 'انبار مقصد' }}</th>
          <th>{{ $tab === 'outgoing' ? 'مقصد/تحویل‌گیرنده' : 'مبدا/فرستنده' }}</th>
          <th>تعداد ردیف</th>
          <th>جمع تعداد</th>
          <th>ثبت‌کننده</th>
          <th class="text-end">عملیات</th>
        </tr>
        </thead>
        <tbody>
        @forelse($list as $voucher)
          <tr>
            <td>{{ $voucher->reference ?: ('TR-' . $voucher->id) }}</td>
            <td>{{ optional($voucher->transferred_at)->format('Y/m/d H:i') }}</td>
            <td>{{ $activeReasons[$voucher->voucher_type] ?? \App\Models\WarehouseTransfer::typeOptions()[$voucher->voucher_type] ?? $voucher->voucher_type }}</td>
            <td>{{ $tab === 'outgoing' ? ($voucher->fromWarehouse?->name ?? '—') : ($voucher->toWarehouse?->name ?? '—') }}</td>
            <td>
              @if($tab === 'outgoing')
                {{ $voucher->beneficiary_name ?: ($voucher->toWarehouse?->name ?? '—') }}
              @else
                {{ $voucher->fromWarehouse?->name ?? (($voucher->customer?->first_name || $voucher->customer?->last_name) ? trim(($voucher->customer?->first_name ?? '') . ' ' . ($voucher->customer?->last_name ?? '')) : '—') }}
              @endif
            </td>
            <td>{{ number_format((int) ($voucher->items_count ?? 0)) }}</td>
            <td>{{ number_format((int) ($voucher->total_quantity ?? 0)) }}</td>
            <td>{{ $voucher->user?->name ?? '—' }}</td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="{{ route('vouchers.show', $voucher) }}">مشاهده جزئیات</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="9" class="text-center py-4 text-muted">رکوردی یافت نشد.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $list->appends(request()->query())->links() }}
  </div>
</div>
@endsection
