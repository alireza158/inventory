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
                <label class="form-label">جستجو (نام / SKU / شماره حواله)</label>
                <input name="q" class="form-control" value="{{ request('q') }}" placeholder="مثلاً GR-1004 یا حواله 123">
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
                        <th>محصول</th>
                        <th>نوع</th>
                        <th>علت</th>
                        <th>تعداد</th>
                        <th>شماره حواله</th>
                        <th>کاربر</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($vouchers as $m)
                    <tr>
                        <td class="text-muted small">{{ $m->created_at->format('Y/m/d H:i') }}</td>
                        <td class="fw-semibold">
                            {{ $m->product?->name }}
                            <div class="text-muted small">{{ $m->product?->sku }}</div>
                        </td>
                        <td>
                            @if($m->type==='in')
                                <span class="badge text-bg-success">ورود</span>
                            @else
                                <span class="badge text-bg-danger">خروج</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $m->reason }}</td>
                        <td class="fw-bold">{{ $m->quantity }}</td>
                        <td class="text-muted">{{ $m->reference }}</td>
                        <td class="text-muted">{{ $m->user?->name }}</td>
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
