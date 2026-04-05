@extends('layouts.app')

@php
    use Morilog\Jalali\Jalalian;
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">📑 گردش حساب {{ $customer->display_name ?: 'شخص' }}</h4>
        <div class="text-muted small">جزئیات کامل تراکنش‌ها، فاکتورها و اسناد مالی مشتری</div>
    </div>
    <a href="{{ route('account-statements.index') }}" class="btn btn-outline-secondary">بازگشت به لیست اشخاص</a>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <h6 class="fw-bold mb-2">اطلاعات مشتری</h6>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge text-bg-light border px-3 py-2">👤 {{ $customer->display_name ?: '—' }}</span>
                    <span class="badge text-bg-light border px-3 py-2">📱 {{ $customer->mobile ?: '—' }}</span>
                    <span class="badge text-bg-light border px-3 py-2 text-wrap">📍 {{ $customer->address ?: 'آدرس ثبت نشده' }}</span>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="rounded-3 p-3 border {{ $netBalance > 0 ? 'border-danger-subtle bg-danger-subtle' : ($netBalance < 0 ? 'border-success-subtle bg-success-subtle' : 'border-secondary-subtle bg-light') }}">
                    <div class="text-muted small mb-1">وضعیت نهایی حساب</div>
                    <div class="fs-5 fw-bold {{ $netBalance > 0 ? 'text-danger' : ($netBalance < 0 ? 'text-success' : 'text-muted') }}">
                        {{ $netBalance > 0 ? 'بدهکار' : ($netBalance < 0 ? 'بستانکار' : 'تسویه') }}
                        {{ $netBalance === 0 ? '' : number_format(abs($netBalance)).' تومان' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">لیست گردش‌ها</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>تاریخ</th>
                    <th>شرح</th>
                    <th>بدهکار</th>
                    <th>بستانکار</th>
                    <th class="text-end">مشاهده</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ledgers as $ledger)
                    @php
                        $invoice = $ledger->reference_type === \App\Models\Invoice::class ? ($invoices[$ledger->reference_id] ?? null) : null;
                        $payment = $ledger->reference_type === \App\Models\InvoicePayment::class ? ($payments[$ledger->reference_id] ?? null) : null;
                        $transfer = $ledger->reference_type === \App\Models\WarehouseTransfer::class ? ($transfers[$ledger->reference_id] ?? null) : null;
                        $description = $ledger->note ?: '—';
                        $viewUrl = null;

                        if ($invoice) {
                            $description = "فاکتور #{$invoice->id} | مبلغ فاکتور ".number_format((int) $invoice->total)." تومان | این شخص بدهکار شد";
                            $viewUrl = route('account-statements.documents.invoices.show', $invoice->uuid);
                        }

                        if ($payment) {
                            if ($payment->method === 'cheque') {
                                $cheque = $payment->cheque;
                                $chNumber = $cheque?->cheque_number ?: '—';
                                $description = "پرداخت چکی شماره {$chNumber} | مبلغ ".number_format((int) $payment->amount)." تومان | این شخص بستانکار شد";
                            } else {
                                $pid = $payment->payment_identifier ?: '—';
                                $description = "پرداخت نقدی | مبلغ ".number_format((int) $payment->amount)." تومان | شناسه {$pid} | این شخص بستانکار شد";
                            }

                            $viewUrl = route('account-statements.documents.payments.show', $payment->id);
                        }

                        if ($transfer) {
                            $transferRef = $transfer->reference ?: ('TR-' . $transfer->id);
                            $transferTypeLabel = \App\Models\WarehouseTransfer::typeOptions()[$transfer->voucher_type] ?? $transfer->voucher_type;
                            $description = "سند {$transferRef} | نوع: {$transferTypeLabel} | مبلغ " . number_format((int) $ledger->amount) . " تومان";
                            if ($transfer->voucher_type === \App\Models\WarehouseTransfer::TYPE_CUSTOMER_RETURN) {
                                $viewUrl = route('account-statements.documents.returns.show', $transfer->id);
                            }
                        }
                    @endphp
                    <tr>
                        <td class="text-nowrap">{{ $ledger->created_at ? Jalalian::fromDateTime($ledger->created_at)->format('Y/m/d H:i') : '—' }}</td>
                        <td>{{ $description }}</td>
                        <td>{{ $ledger->type === 'debit' ? number_format((int) $ledger->amount) : '—' }}</td>
                        <td>{{ $ledger->type === 'credit' ? number_format((int) $ledger->amount) : '—' }}</td>
                        <td class="text-end">
                            @if($viewUrl)
                                <a href="{{ $viewUrl }}" class="btn btn-sm btn-primary">مشاهده</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">گردشی برای این شخص ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer bg-white">{{ $ledgers->links() }}</div>
</div>
@endsection
