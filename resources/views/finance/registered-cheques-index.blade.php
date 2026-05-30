@extends('layouts.app')

@section('content')
<div class="container py-4" dir="rtl">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">چک‌های ثبت‌شده</h4>
    </div>

    <form method="GET" action="{{ route('finance.cheques.index') }}" class="card card-body mb-3 shadow-sm">
        <div class="row g-2">

            <div class="col-md-3">
                <label class="form-label">نام مشتری</label>
                <input
                    type="text"
                    class="form-control"
                    name="customer_name"
                    value="{{ request('customer_name') }}"
                    placeholder="نام مشتری"
                >
            </div>

            <div class="col-md-2">
                <label class="form-label">شماره چک</label>
                <input
                    type="text"
                    class="form-control"
                    name="cheque_number"
                    value="{{ request('cheque_number') }}"
                    placeholder="شماره چک"
                >
            </div>

            <div class="col-md-2">
                <label class="form-label">وضعیت صیادی</label>
                <select class="form-select" name="status">
                    <option value="">همه وضعیت‌ها</option>

                    <option value="registered" @selected(request('status') === 'registered')>
                        ثبت‌شده
                    </option>

                    <option value="unregistered" @selected(request('status') === 'unregistered')>
                        ثبت‌نشده
                    </option>

                    <option value="pending" @selected(request('status') === 'pending')>
                        در انتظار
                    </option>

                    <option value="cleared" @selected(request('status') === 'cleared')>
                        پاس‌شده
                    </option>

                    <option value="bounced" @selected(request('status') === 'bounced')>
                        برگشتی
                    </option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">از تاریخ دریافت</label>
                <input
                    type="date"
                    class="form-control"
                    name="received_from"
                    value="{{ request('received_from') }}"
                >
            </div>

            <div class="col-md-2">
                <label class="form-label">تا تاریخ دریافت</label>
                <input
                    type="date"
                    class="form-control"
                    name="received_to"
                    value="{{ request('received_to') }}"
                >
            </div>

            <div class="col-md-2">
                <label class="form-label">از تاریخ سررسید</label>
                <input
                    type="date"
                    class="form-control"
                    name="due_from"
                    value="{{ request('due_from') }}"
                >
            </div>

            <div class="col-md-2">
                <label class="form-label">تا تاریخ سررسید</label>
                <input
                    type="date"
                    class="form-control"
                    name="due_to"
                    value="{{ request('due_to') }}"
                >
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">
                    جستجو
                </button>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('finance.cheques.index') }}" class="btn btn-outline-secondary w-100">
                    حذف فیلترها
                </a>
            </div>

        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>تاریخ دریافت</th>
                        <th>تاریخ سررسید</th>
                        <th>مشتری</th>
                        <th>شماره چک</th>
                        <th>بانک</th>
                        <th>مبلغ</th>
                        <th>وضعیت صیادی</th>
                        <th>فاکتور</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($cheques as $cheque)
                        @php
                            $invoice = $cheque->payment?->invoice;

                            $statusLabels = [
                                'registered' => 'ثبت‌شده',
                                'unregistered' => 'ثبت‌نشده',
                                'pending' => 'در انتظار',
                                'cleared' => 'پاس‌شده',
                                'bounced' => 'برگشتی',
                            ];

                            $statusClass = match($cheque->status) {
                                'registered' => 'success',
                                'unregistered' => 'secondary',
                                'pending' => 'warning',
                                'cleared' => 'primary',
                                'bounced' => 'danger',
                                default => 'dark',
                            };
                        @endphp

                        <tr>
                            <td>
                                {{ optional($cheque->received_at)->format('Y-m-d') ?? '—' }}
                            </td>

                            <td>
                                {{ optional($cheque->due_date)->format('Y-m-d') ?? '—' }}
                            </td>

                            <td>
                                {{ $cheque->customer_name ?: ($invoice?->customer_name ?: '—') }}
                            </td>

                            <td>
                                {{ $cheque->cheque_number ?: '—' }}
                            </td>

                            <td>
                                {{ $cheque->bank_name ?: '—' }}
                            </td>

                            <td>
                                {{ number_format((int) ($cheque->amount ?: $cheque->payment?->amount)) }}
                                تومان
                            </td>

                            <td>
                                <span class="badge bg-{{ $statusClass }}">
                                    {{ $statusLabels[$cheque->status] ?? ($cheque->status ?: '—') }}
                                </span>
                            </td>

                            <td>
                                @if($invoice)
                                    <a href="{{ route('invoices.show', $invoice->uuid) }}">
                                        {{ $invoice->uuid }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                چکی یافت نشد.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $cheques->links() }}
    </div>

</div>
@endsection