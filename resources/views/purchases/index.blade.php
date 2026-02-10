@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="page-title mb-0">خرید کالا</h4>
    <a class="btn btn-primary" href="{{ route('purchases.create') }}">+ ثبت خرید جدید</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>تاریخ</th>
                        <th>تامین‌کننده</th>
                        <th>مبلغ کل خرید</th>
                        <th>تعداد آیتم</th>
                        <th class="text-end">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($purchases as $purchase)
                    <tr>
                        <td>{{ $purchase->purchased_at?->format('Y/m/d H:i') }}</td>
                        <td>{{ $purchase->supplier?->name }}</td>
                        <td>{{ number_format($purchase->total_amount) }} ریال</td>
                        <td>{{ $purchase->items_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-outline-secondary">مشاهده</a>
                            <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-sm btn-outline-primary">ویرایش</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">هنوز خریدی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $purchases->links() }}</div>
    </div>
</div>
@endsection
