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

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

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

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0">➕ ثبت پرداخت جدید</h6>
            <span class="text-muted small">ثبت سریع پرداخت نقدی/چکی برای این مشتری</span>
        </div>
        <form method="POST" action="{{ route('account-statements.payments.store', $customer->id) }}" enctype="multipart/form-data" id="accountStatementPaymentForm">
            @csrf
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">نوع پرداخت</label>
                    <select name="method" id="as_payment_method" class="form-select" required>
                        <option value="cash">نقدی</option>
                        <option value="cheque">چکی</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">فاکتور مرتبط</label>
                    <select name="invoice_id" class="form-select" required>
                        <option value="">انتخاب فاکتور</option>
                        @foreach($customerInvoices as $invoiceOption)
                            <option value="{{ $invoiceOption->id }}">
                                {{ $invoiceOption->uuid }} | {{ number_format((int) $invoiceOption->total) }} تومان
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">مبلغ</label>
                    <input type="number" min="1" step="1" class="form-control" name="amount" placeholder="مبلغ پرداخت" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">تاریخ پرداخت</label>
                    <input type="date" class="form-control" name="paid_at" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">اسم بانک (فقط نقدی)</label>
                    <input type="text" class="form-control" name="bank_name" placeholder="مثال: ملی">
                </div>
                <div class="col-md-4">
                    <label class="form-label">رسید پرداخت</label>
                    <input type="file" class="form-control" name="receipt_image" accept="image/*">
                </div>

                <div class="col-12 as-cheque-fields d-none">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">شماره چک</label>
                            <input type="text" class="form-control" name="cheque_number">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">نام بانک</label>
                            <input type="text" class="form-control" name="cheque_bank_name">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">نام شعبه</label>
                            <input type="text" class="form-control" name="cheque_branch_name">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">تاریخ سررسید</label>
                            <input type="date" class="form-control" name="cheque_due_date">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">تاریخ دریافت چک</label>
                            <input type="date" class="form-control" name="cheque_received_at">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">نام مشتری</label>
                            <input type="text" class="form-control" name="cheque_customer_name" value="{{ $customer->display_name }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">کد/شناسه مشتری</label>
                            <input type="text" class="form-control" name="cheque_customer_code">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">شماره حساب/شبا</label>
                            <input type="text" class="form-control" name="cheque_account_number">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">صاحب حساب</label>
                            <input type="text" class="form-control" name="cheque_account_holder">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">وضعیت چک</label>
                            <select name="cheque_status" class="form-select">
                                <option value="pending">در انتظار وصول</option>
                                <option value="cleared">وصول شده</option>
                                <option value="bounced">برگشتی</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تصویر چک</label>
                            <input type="file" class="form-control" name="cheque_image" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">یادداشت</label>
                    <textarea name="note" class="form-control" rows="2" placeholder="اختیاری"></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-success">ثبت پرداخت</button>
                </div>
            </div>
        </form>
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
                            $viewUrl = route('vouchers.sales.show', $invoice->uuid);
                        }

                        if ($payment) {
                            $creatorName = $payment->creator?->name ?: 'نامشخص';
                            $invoiceUuid = $payment->invoice?->uuid ?: ($invoices[$payment->invoice_id]->uuid ?? null);
                            if ($payment->method === 'cheque') {
                                $cheque = $payment->cheque;
                                $chNumber = $cheque?->cheque_number ?: '—';
                                $description = "پرداخت چکی شماره {$chNumber} | مبلغ ".number_format((int) $payment->amount)." تومان | ثبت‌کننده: {$creatorName}";
                            } else {
                                $bankName = $payment->bank_name ?: '—';
                                $description = "پرداخت نقدی | مبلغ ".number_format((int) $payment->amount)." تومان | بانک {$bankName} | ثبت‌کننده: {$creatorName}";
                            }

                            if ($invoiceUuid) {
                                $description .= " | فاکتور {$invoiceUuid}";
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

<script>
    (function () {
        const methodSelect = document.getElementById('as_payment_method');
        if (!methodSelect) return;
        const chequeBlocks = document.querySelectorAll('.as-cheque-fields');
        const toggle = () => {
            const isCheque = methodSelect.value === 'cheque';
            chequeBlocks.forEach((item) => item.classList.toggle('d-none', !isCheque));
        };
        methodSelect.addEventListener('change', toggle);
        toggle();
    })();
</script>
@endsection
