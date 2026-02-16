@extends('layouts.app')

@section('content')
@php
    $toToman = fn($rial) => number_format((int) floor(((int) $rial) / 10));
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
                        <div class="label">جمع کل مبلغ حواله‌ها تا الان</div>
                        <div class="value">{{ $toToman($totalAllAmount) }} تومان</div>
                    </div>
                    <div>
                        <div class="label">تعداد کل حواله‌ها</div>
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
                <div class="col-md-3">
                    <label class="form-label">شماره حواله</label>
                    <input name="voucher_no" class="form-control" value="{{ request('voucher_no') }}" placeholder="مثلاً 123 یا TR-123">
                </div>
                <div class="col-md-3 d-flex gap-2 filter-actions">
                    <button class="btn btn-primary">جستجو</button>
                    <a href="{{ route('vouchers.index') }}" class="btn btn-outline-secondary">حذف فیلتر</a>
                </div>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>تاریخ ثبت</th>
                            <th>شماره حواله</th>
                            <th>از انبار</th>
                            <th>به انبار</th>
                            <th>مبلغ سند</th>
                            <th>کاربر</th>
                            <th class="text-end">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($vouchers as $voucher)
                        <tr>
                            <td class="fw-bold">{{ $loop->iteration + (($vouchers->currentPage() - 1) * $vouchers->perPage()) }}</td>
                            <td>{{ $voucher->transferred_at?->format('Y/m/d H:i') }}</td>
                            <td class="text-muted">{{ $voucher->reference ?: ('TR-'.$voucher->id) }}</td>
                            <td>{{ $voucher->fromWarehouse?->name }}</td>
                            <td>{{ $voucher->toWarehouse?->name }}</td>
                            <td class="amount-strong">{{ $toToman($voucher->total_amount) }} تومان</td>
                            <td class="text-muted">{{ $voucher->user?->name }}</td>
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
                            <td colspan="8" class="text-center text-muted py-5">حواله‌ای با این فیلتر ثبت نشده است.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3">
                {{ $vouchers->appends(request()->except('page'))->links() }}
            </div>
        </div>
    </div>

</div>
@endsection
