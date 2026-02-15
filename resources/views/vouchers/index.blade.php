@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="page-title mb-0">حواله‌ها</h4>
    <a class="btn btn-primary" href="{{ route('vouchers.create') }}">+ ثبت حواله</a>
</div>

<form class="card filter-card mb-3" method="GET" action="{{ route('vouchers.index') }}">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">جستجو (شماره حواله / نام انبار مبدا / نام انبار مقصد)</label>
                <input name="q" class="form-control" value="{{ request('q') }}" placeholder="مثلاً حواله 123 یا انبار مرکزی">
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button class="btn btn-primary">اعمال</button>
                <a class="btn btn-outline-secondary" href="{{ route('vouchers.index') }}">پاک کردن</a>
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
                        <th>زمان</th>
                        <th>شماره حواله</th>
                        <th>از انبار</th>
                        <th>به انبار</th>
                        <th>مبلغ سند</th>
                        <th>کاربر</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($vouchers as $m)
                    <tr>
                        <td class="text-muted small">{{ $m->transferred_at?->format('Y/m/d H:i') }}</td>
                        <td class="text-muted">{{ $m->reference ?: ('TR-'.$m->id) }}</td>
                        <td class="fw-semibold">{{ $m->fromWarehouse?->name }}</td>
                        <td class="fw-semibold">{{ $m->toWarehouse?->name }}</td>
                        <td class="fw-bold">{{ number_format($m->total_amount) }}</td>
                        <td class="text-muted">{{ $m->user?->name }}</td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('vouchers.edit', $m) }}">ویرایش</a>
                            <form method="POST" action="{{ route('vouchers.destroy', $m) }}" class="d-inline" onsubmit="return confirm('از حذف حواله مطمئن هستید؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">حذف</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">حواله‌ای ثبت نشده.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $vouchers->links() }}</div>
    </div>
</div>
@endsection
