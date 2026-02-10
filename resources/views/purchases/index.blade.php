@extends('layouts.app')

@section('content')
@php
    $toToman = fn($rial) => number_format((int) floor(((int) $rial) / 10));
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="page-title mb-0">خرید کالا</h4>
    <a class="btn btn-primary" href="{{ route('purchases.create') }}">+ ثبت خرید جدید</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">مبلغ کل خریدها تا الان</div>
                <div class="fs-5 fw-bold">{{ $toToman($totalAllAmount) }} تومان</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">مبلغ کل نتیجه فیلتر</div>
                <div class="fs-5 fw-bold">{{ $toToman($totalFilteredAmount) }} تومان</div>
            </div>
        </div>
    </div>
</div>

<form method="GET" class="card mb-3">
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
                <label class="form-label">تامین‌کننده</label>
                <select name="supplier_id" class="form-select">
                    <option value="">همه</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">حداقل مبلغ فاکتور (تومان)</label>
                <input type="number" min="0" name="min_total" class="form-control" value="{{ request('min_total') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">حداکثر مبلغ فاکتور (تومان)</label>
                <input type="number" min="0" name="max_total" class="form-control" value="{{ request('max_total') }}">
            </div>
            <div class="col-md-9 d-flex gap-2">
                <button class="btn btn-primary">جستجو</button>
                <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">حذف فیلتر</a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>تاریخ</th>
                        <th>تامین‌کننده</th>
                        <th>تخفیف سند</th>
                        <th>مبلغ کل فاکتور</th>
                        <th>تعداد آیتم</th>
                        <th class="text-end">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($purchases as $purchase)
                    <tr>
                        <td>{{ $loop->iteration + (($purchases->currentPage() - 1) * $purchases->perPage()) }}</td>
                        <td>{{ $purchase->purchased_at?->format('Y/m/d H:i') }}</td>
                        <td>{{ $purchase->supplier?->name }}</td>
                        <td>{{ $toToman($purchase->total_discount ?? 0) }} تومان</td>
                        <td class="fw-semibold">{{ $toToman($purchase->total_amount) }} تومان</td>
                        <td>{{ $purchase->items_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-outline-secondary">مشاهده</a>
                            <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-sm btn-outline-primary">ویرایش</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">هیچ سند خریدی با این فیلتر ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $purchases->links() }}</div>
    </div>
</div>
@endsection
