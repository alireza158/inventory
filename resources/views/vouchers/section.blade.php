@extends('layouts.app')

@section('content')
@php
    $titles = [
        'return-from-sale' => 'برگشت از فروش',
        'scrap' => 'انبار ضایعات',
        'personnel' => 'حواله پرسنل',
        'transfer' => 'حواله بین انباری',
    ];
@endphp
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">{{ $titles[$type] ?? 'حواله' }}</h4>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('vouchers.index') }}">بازگشت</a>
            <a class="btn btn-primary" href="{{ route('vouchers.section.create', $type) }}">+ ثبت جدید</a>
        </div>
    </div>

    @if($voucherType === \App\Models\WarehouseTransfer::TYPE_CUSTOMER_RETURN)
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">مشتری</label>
                        <select name="customer_id" class="form-select form-select-sm">
                            <option value="">همه مشتری‌ها</option>
                            @foreach($customers as $customer)
                                @php
                                    $customerTitle = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: ('مشتری #' . $customer->id);
                                @endphp
                                <option value="{{ $customer->id }}" @selected((int) $requestCustomerId === (int) $customer->id)>
                                    {{ $customerTitle }} | {{ $customer->mobile }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">علت برگشت</label>
                        <select name="return_reason" class="form-select form-select-sm">
                            <option value="">همه علت‌ها</option>
                            @foreach($returnReasons as $reasonKey => $reasonTitle)
                                <option value="{{ $reasonKey }}" @selected($returnReason === $reasonKey)>{{ $reasonTitle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">از تاریخ</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">تا تاریخ</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-sm btn-primary w-100">فیلتر</button>
                        <a href="{{ route('vouchers.section.index', $type) }}" class="btn btn-sm btn-outline-secondary w-100">حذف فیلتر</a>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>#</th>
                    <th>شماره</th>
                    <th>تاریخ</th>
                    <th>مبدا</th>
                    <th>مقصد</th>
                    <th>کاربر</th>
                    <th>فاکتور مرجع</th>
                    @if($voucherType === \App\Models\WarehouseTransfer::TYPE_CUSTOMER_RETURN)
                        <th>علت برگشت</th>
                    @endif
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($vouchers as $voucher)
                    <tr>
                        <td>{{ $voucher->id }}</td>
                        <td>{{ $voucher->reference ?: ('TR-'.$voucher->id) }}</td>
                        <td>{{ $voucher->transferred_at?->format('Y/m/d H:i') }}</td>
                        <td>{{ $voucher->fromWarehouse?->name ?: '—' }}</td>
                        <td>{{ $voucher->toWarehouse?->name ?: '—' }}</td>
                        <td>{{ $voucher->user?->name ?: '—' }}</td>
                        <td>{{ $voucher->relatedInvoice?->uuid ?: '—' }}</td>
                        @if($voucherType === \App\Models\WarehouseTransfer::TYPE_CUSTOMER_RETURN)
                            <td>{{ \App\Models\WarehouseTransfer::returnReasonOptions()[$voucher->return_reason] ?? '—' }}</td>
                        @endif
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('vouchers.edit', $voucher) }}">ویرایش</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $voucherType === \App\Models\WarehouseTransfer::TYPE_CUSTOMER_RETURN ? 9 : 8 }}" class="text-center py-4 text-muted">موردی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $vouchers->links() }}</div>
</div>
@endsection
