@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">📒 گردش حساب اشخاص</h4>
        <div class="text-muted small">لیست کامل اشخاص (مشتریان) و وضعیت حساب هر شخص</div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form class="d-flex gap-2" method="GET" action="{{ route('account-statements.index') }}">
            <input class="form-control" name="q" value="{{ $q ?? '' }}" placeholder="جستجو نام/شماره تماس">
            <button class="btn btn-outline-secondary">جستجو</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>نام شخص</th>
                        <th>موبایل</th>
                        <th>بدهکاری</th>
                        <th>بستانکاری</th>
                        <th>وضعیت نهایی</th>
                        <th class="text-end">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        @php
                            $balance = (int) $customer->balance;
                            $statusLabel = $balance > 0 ? 'بدهکار' : ($balance < 0 ? 'بستانکار' : 'تسویه');
                            $statusClass = $balance > 0 ? 'text-danger' : ($balance < 0 ? 'text-success' : 'text-muted');
                        @endphp
                        <tr>
                            <td>{{ $customer->display_name ?: '-' }}</td>
                            <td>{{ $customer->mobile ?: '-' }}</td>
                            <td>{{ number_format((int) $customer->debt) }} تومان</td>
                            <td>{{ number_format((int) $customer->credit) }} تومان</td>
                            <td class="fw-semibold {{ $statusClass }}">{{ $statusLabel }} {{ $balance === 0 ? '' : number_format(abs($balance)).' تومان' }}</td>
                            <td class="text-end">
                                <a href="{{ route('account-statements.show', $customer->id) }}" class="btn btn-sm btn-primary">مشاهده گردش حساب</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">شخصی یافت نشد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $customers->links() }}</div>
    </div>
</div>
@endsection
