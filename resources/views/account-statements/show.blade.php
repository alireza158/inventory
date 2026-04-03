@extends('layouts.app')

@php
    use Morilog\Jalali\Jalalian;
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">📑 گردش حساب {{ $customer->display_name ?: 'شخص' }}</h4>
        <div class="text-muted small">ریز بدهکاری‌ها و بستانکاری‌ها مشابه نرم‌افزار حسابداری</div>
    </div>
    <a href="{{ route('account-statements.index') }}" class="btn btn-outline-secondary">بازگشت به لیست اشخاص</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">جمع بدهکاری</div><div class="fs-5 text-danger fw-bold">{{ number_format($totalDebit) }} تومان</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">جمع بستانکاری</div><div class="fs-5 text-success fw-bold">{{ number_format($totalCredit) }} تومان</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">وضعیت نهایی حساب</div><div class="fs-5 fw-bold {{ $netBalance > 0 ? 'text-danger' : ($netBalance < 0 ? 'text-success' : 'text-muted') }}">{{ $netBalance > 0 ? 'بدهکار' : ($netBalance < 0 ? 'بستانکار' : 'تسویه') }} {{ $netBalance === 0 ? '' : number_format(abs($netBalance)).' تومان' }}</div></div></div></div>
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
                    <th class="text-end">مشاهده/ویرایش</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ledgers as $ledger)
                    @php
                        $invoice = $ledger->reference_type === \App\Models\Invoice::class ? ($invoices[$ledger->reference_id] ?? null) : null;
                        $payment = $ledger->reference_type === \App\Models\InvoicePayment::class ? ($payments[$ledger->reference_id] ?? null) : null;
                        $relatedInvoice = $invoice ?: (($payment && !empty($payment->invoice_id)) ? ($invoices[$payment->invoice_id] ?? null) : null);

                        $description = $ledger->note ?: '—';

                        if ($invoice) {
                            $description = "فاکتور #{$invoice->id} | مبلغ فاکتور ".number_format((int) $invoice->total)." تومان | این شخص بدهکار شد";
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
                        }
                    @endphp
                    <tr>
                        <td class="text-nowrap">{{ $ledger->created_at ? Jalalian::fromDateTime($ledger->created_at)->format('Y/m/d H:i') : '—' }}</td>
                        <td>{{ $description }}</td>
                        <td>{{ $ledger->type === 'debit' ? number_format((int) $ledger->amount) : '—' }}</td>
                        <td>{{ $ledger->type === 'credit' ? number_format((int) $ledger->amount) : '—' }}</td>
                        <td class="text-end">
                            @if($relatedInvoice)
                                <div class="d-flex gap-2 justify-content-end flex-wrap">
                                    <a href="{{ route('invoices.show', $relatedInvoice->uuid) }}" class="btn btn-sm btn-outline-primary">مشاهده فاکتور</a>
                                    <a href="{{ route('invoices.edit', $relatedInvoice->uuid) }}" class="btn btn-sm btn-primary">ویرایش فاکتور</a>
                                    <a href="{{ route('vouchers.sales.edit', $relatedInvoice->uuid) }}" class="btn btn-sm btn-outline-secondary">برگشت از فروش</a>
                                </div>
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
