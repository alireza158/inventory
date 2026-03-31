@extends('layouts.app')

@section('content')
@php
    $toToman = fn($rial) => number_format((int) floor(((int) $rial) / 10));

    $voucherTypeLabels = [
        'between_warehouses' => 'حواله بین انبار',
        'scrap' => 'حواله ضایعات',
        'customer_return' => 'حواله مرجوعی مشتری',
        'showroom' => 'حواله شوروم سالن',
        'personnel_asset' => 'حواله اموال پرسنل',
        'sale' => 'حواله فروش کالا',
    ];

    $statusFa = fn($s) => match($s){
      'warehouse_pending' => 'در انتظار تایید انبار',
      'warehouse_collecting' => 'در حال جمع‌آوری',
      'warehouse_checking' => 'چک کردن بار',
      'warehouse_packing' => 'بسته‌بندی بار',
      'warehouse_sent' => 'ارسال شد',
      'canceled' => 'کنسل شده',
      default => $s,
    };
@endphp

<div class="purchase-page-wrap">

    <div class="purchase-topbar d-flex justify-content-between align-items-center">
        <h4 class="page-title mb-0">حواله‌ها</h4>
        <a class="btn btn-light" href="{{ route('vouchers.create') }}">+ ثبت حواله</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card stat-card h-100">
                <div class="card-body d-flex flex-wrap gap-4 justify-content-between align-items-center">
                    <div>
                        <div class="label">جمع کل مبلغ حواله‌های داخلی تا الان</div>
                        <div class="value">{{ $toToman($totalAllAmount) }} تومان</div>
                    </div>
                    <div>
                        <div class="label">تعداد کل حواله‌های داخلی</div>
                        <div class="value">{{ number_format($totalAllCount) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" class="card filter-card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">از تاریخ</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">تا تاریخ</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">نوع حواله</label>
                    <select name="voucher_type" class="form-select">
                        <option value="all" @selected($voucherType === 'all')>همه</option>
                        <option value="between_warehouses" @selected($voucherType === 'between_warehouses')>بین انبار</option>
                        <option value="scrap" @selected($voucherType === 'scrap')>ضایعات</option>
                        <option value="customer_return" @selected($voucherType === 'customer_return')>مرجوعی مشتری</option>
                        <option value="showroom" @selected($voucherType === 'showroom')>شوروم سالن</option>
                        <option value="personnel_asset" @selected($voucherType === 'personnel_asset')>اموال پرسنل</option>
                        <option value="sale" @selected($voucherType === 'sale')>فروش کالا</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">شماره/کد</label>
                    <input name="voucher_no" class="form-control" value="{{ request('voucher_no') }}" placeholder="مثلاً TR-123 یا کد فاکتور">
                </div>
                <div class="col-md-2 d-flex gap-2 filter-actions">
                    <button class="btn btn-primary">جستجو</button>
                    <a href="{{ route('vouchers.index') }}" class="btn btn-outline-secondary">حذف فیلتر</a>
                </div>
            </div>
        </div>
    </form>

    @if($voucherType !== 'sale')
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h6 class="mb-3">حواله‌های داخلی (بین انبار / ضایعات / مرجوعی مشتری / شوروم / اموال پرسنل)</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>تاریخ ثبت</th>
                            <th>نوع</th>
                            <th>شماره حواله</th>
                            <th>از انبار</th>
                            <th>به انبار</th>
                            <th>مبلغ سند</th>
                            <th>کاربر ثبت‌کننده</th>
                            <th>ذی‌نفع/فاکتور مرجع</th>
                            <th class="text-end">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($vouchers as $voucher)
                        <tr>
                            <td class="fw-bold">{{ $loop->iteration + (($vouchers->currentPage() - 1) * $vouchers->perPage()) }}</td>
                            <td>{{ $voucher->transferred_at?->format('Y/m/d H:i') }}</td>
                            <td>{{ $voucherTypeLabels[$voucher->voucher_type ?? 'between_warehouses'] ?? ($voucher->voucher_type ?? '—') }}</td>
                            <td class="text-muted">{{ $voucher->reference ?: ('TR-'.$voucher->id) }}</td>
                            <td>{{ $voucher->fromWarehouse?->name }}</td>
                            <td>{{ $voucher->toWarehouse?->name }}</td>
                            <td class="amount-strong">{{ $toToman($voucher->total_amount) }} تومان</td>
                            <td class="text-muted">{{ $voucher->user?->name }}</td>
                            <td class="small">
                                @if($voucher->relatedInvoice)
                                    <div>فاکتور: {{ $voucher->relatedInvoice->uuid }}</div>
                                @endif
                                <div>{{ $voucher->beneficiary_name ?: ($voucher->customer?->first_name ? $voucher->customer->first_name . ' ' . $voucher->customer->last_name : '—') }}</div>
                            </td>
                            <td class="text-end action-btns">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('vouchers.edit', $voucher) }}">ویرایش</a>
                                <form method="POST" action="{{ route('vouchers.destroy', $voucher) }}" class="d-inline" onsubmit="return confirm('از حذف حواله مطمئن هستید؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">حواله‌ای با این فیلتر ثبت نشده است.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3">
                {{ $vouchers->appends(request()->except('vouchers_page'))->links() }}
            </div>
        </div>
    </div>
    @endif

    @if($voucherType === 'sale' || $voucherType === 'all')
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="mb-3">حواله فروش کالا (بر پایه فاکتور نهایی)</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>کد فاکتور</th>
                                <th>مشتری</th>
                                <th>محصولات سفارش</th>
                                <th>تاریخ ثبت سفارش</th>
                                <th>وضعیت سفارش</th>
                                <th class="text-end">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($salesInvoices as $inv)
                            <tr>
                                <td class="text-muted">{{ $inv->uuid }}</td>
                                <td>
                                    <div>{{ $inv->customer_name ?: '—' }}</div>
                                    <div class="small text-muted">{{ $inv->customer_mobile ?: '—' }}</div>
                                </td>
                                <td>
                                    <ul class="mb-0 ps-3 small">
                                        @foreach($inv->items as $it)
                                            <li>{{ $it->product?->name ?? ('#'.$it->product_id) }} × {{ number_format((int)$it->quantity) }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td>{{ $inv->created_at?->format('Y/m/d H:i') }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $statusFa($inv->status) }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                    <a class="btn btn-outline-primary" href="{{ route('invoices.show', $inv->uuid) }}">مشاهده/وضعیت</a>
                                    <a class="btn btn-outline-secondary" href="{{ route('invoices.edit', $inv->uuid) }}">ویرایش</a>
                                    <a class="btn btn-outline-dark" target="_blank" href="{{ route('invoices.print', $inv->uuid) }}">چاپ</a>
                                </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">حواله فروشی یافت نشد.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3">
                    {{ $salesInvoices->appends(request()->except('sales_page'))->links() }}
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
