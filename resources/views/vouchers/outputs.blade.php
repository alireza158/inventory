@extends('layouts.app')

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">خروجی‌های انبار</h4>
        <a class="btn btn-outline-secondary" href="{{ route('vouchers.index') }}">بازگشت</a>
    </div>

    <form class="mb-3" method="GET">
        <div class="input-group">
            <input class="form-control" name="q" value="{{ $q }}" placeholder="جستجو نام کالا">
            <button class="btn btn-primary">جستجو</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>کالا</th>
                    <th>تعداد خروج</th>
                    <th>علت</th>
                    <th>مرجع</th>
                    <th>کاربر</th>
                    <th>تاریخ</th>
                </tr>
                </thead>
                <tbody>
                @forelse($outputs as $m)
                    <tr>
                        <td>{{ $m->product?->name ?: '—' }}</td>
                        <td>{{ number_format((int)$m->quantity) }}</td>
                        <td>{{ $m->reason }}</td>
                        <td>{{ $m->reference ?: '—' }}</td>
                        <td>{{ $m->user?->name ?: '—' }}</td>
                        <td>{{ $m->created_at?->format('Y/m/d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">خروجی‌ای ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $outputs->links() }}</div>
</div>
@endsection
