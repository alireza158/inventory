@extends('layouts.app')

@php
    use Morilog\Jalali\Jalalian;

    $subtotal = $order->items->sum(fn ($it) => ((int) $it->price) * ((int) $it->quantity));
    $shipping = (int) $order->shipping_price;
    $discount = (int) $order->discount_amount;
    $grandTotal = max($subtotal + $shipping - $discount, 0);

    $customerName = $order->customer_name ?: '—';
    $customerMobile = $order->customer_mobile ?: 'شماره تماس ثبت نشده';
    $creatorName = $order->creator?->name ?? '—';
    $createdAtFa = $order->created_at
        ? Jalalian::fromDateTime($order->created_at)->format('Y/m/d H:i')
        : '—';

    $balanceClass = $customerBalanceStatus === 'بدهکار'
        ? 'text-danger'
        : ($customerBalanceStatus === 'بستانکار' ? 'text-success' : 'text-secondary');
@endphp

@section('content')
<style>
    :root{
        --brd:#e8edf3;
        --soft:#f8fafc;
        --soft2:#f4f7fb;
        --text:#0f172a;
        --muted:#64748b;
        --blue:#2563eb;
        --blue-soft:#eff6ff;
        --green:#15803d;
        --green-soft:#ecfdf5;
        --warn:#b45309;
        --warn-soft:#fff7ed;
        --danger:#dc2626;
        --danger-soft:#fef2f2;
        --shadow:0 10px 28px rgba(15,23,42,.06);
    }

    .finance-page{
        padding:12px 0 28px;
    }

    .hero-box{
        border:1px solid var(--brd);
        border-radius:22px;
        background:#fff;
        box-shadow:var(--shadow);
        padding:20px;
        margin-bottom:16px;
    }

    .hero-title{
        font-size:28px;
        font-weight:900;
        color:var(--text);
        margin-bottom:6px;
    }

    .hero-sub{
        margin:0;
        color:var(--muted);
        font-size:14px;
        line-height:1.9;
    }

    .top-actions{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
    }

    .info-grid{
        display:grid;
        grid-template-columns:repeat(5,minmax(0,1fr));
        gap:12px;
        margin-bottom:16px;
    }

    .info-card{
        border:1px solid var(--brd);
        border-radius:18px;
        background:#fff;
        box-shadow:var(--shadow);
        padding:14px;
        min-height:104px;
    }

    .info-label{
        font-size:12px;
        color:var(--muted);
        margin-bottom:8px;
    }

    .info-value{
        font-size:15px;
        font-weight:900;
        color:var(--text);
        line-height:1.8;
    }

    .status-chip{
        display:inline-flex;
        align-items:center;
        padding:6px 10px;
        border-radius:999px;
        font-size:12px;
        font-weight:800;
        border:1px solid #fde68a;
        background:#fffbeb;
        color:#92400e;
    }

    .card-soft{
        border:1px solid var(--brd);
        border-radius:22px;
        background:#fff;
        box-shadow:var(--shadow);
        overflow:hidden;
        margin-bottom:16px;
    }

    .card-head{
        padding:14px 16px;
        border-bottom:1px solid var(--brd);
        background:linear-gradient(0deg,#fff,var(--soft2));
    }

    .card-title{
        margin:0;
        font-size:15px;
        font-weight:900;
        color:var(--text);
    }

    .card-sub{
        margin:4px 0 0;
        font-size:12px;
        color:var(--muted);
    }

    .summary-stack{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:12px;
    }

    .summary-pill{
        border:1px solid var(--brd);
        border-radius:16px;
        background:#fff;
        padding:12px;
    }

    .summary-pill .label{
        font-size:12px;
        color:var(--muted);
        margin-bottom:6px;
    }

    .summary-pill .value{
        font-size:22px;
        font-weight:900;
        color:var(--text);
        line-height:1.2;
    }

    .summary-pill.primary{
        background:var(--blue-soft);
        border-color:#dbeafe;
    }

    .summary-pill.success{
        background:var(--green-soft);
        border-color:#bbf7d0;
    }

    .summary-pill.warn{
        background:var(--warn-soft);
        border-color:#fed7aa;
    }

    .method-switch{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:10px;
        margin-bottom:16px;
    }

    .method-btn{
        border:1px solid var(--brd);
        border-radius:14px;
        background:#fff;
        padding:12px;
        text-align:center;
        cursor:pointer;
        transition:.15s ease;
        font-weight:800;
        color:var(--text);
    }

    .method-btn.active{
        background:var(--blue-soft);
        border-color:#bfdbfe;
        color:var(--blue);
    }

    .payment-form-box{
        padding:16px;
    }

    .payment-list-wrap{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:16px;
    }

    .payments-list{
        display:flex;
        flex-direction:column;
        gap:10px;
    }

    .payment-card{
        border:1px solid var(--brd);
        border-radius:16px;
        background:#fff;
        padding:12px;
    }

    .payment-card.cash{
        border-color:#bfdbfe;
        background:#f8fbff;
    }

    .payment-card.cheque{
        border-color:#e9d5ff;
        background:#fcfaff;
    }

    .payment-head{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:10px;
        flex-wrap:wrap;
        margin-bottom:8px;
    }

    .payment-title{
        font-weight:900;
        color:var(--text);
        margin-bottom:4px;
    }

    .payment-sub{
        font-size:12px;
        color:var(--muted);
        line-height:1.8;
    }

    .payment-amount{
        font-size:18px;
        font-weight:900;
        color:var(--text);
        white-space:nowrap;
    }

    .empty-box{
        border:1px dashed #dbeafe;
        background:#f8fbff;
        color:var(--muted);
        border-radius:16px;
        padding:14px;
        font-size:13px;
        line-height:1.9;
    }

    .clean-table{
        margin-bottom:0;
    }

    .clean-table thead th{
        background:#fbfcfe;
        color:var(--muted);
        font-size:12px;
        font-weight:800;
        white-space:nowrap;
        border-bottom-width:1px;
    }

    .clean-table tbody td{
        vertical-align:middle;
        font-size:13px;
    }

    .money-strong{
        font-weight:900;
        color:var(--text);
        white-space:nowrap;
    }

    .totals-box{
        padding:16px;
        border-top:1px solid var(--brd);
    }

    .total-row{
        display:flex;
        justify-content:space-between;
        gap:10px;
        align-items:center;
        margin-bottom:10px;
        font-size:14px;
    }

    .total-row:last-child{
        margin-bottom:0;
    }

    .total-row strong{
        font-weight:900;
    }

    .final-row{
        margin-top:12px;
        padding-top:12px;
        border-top:1px solid var(--brd);
        font-size:18px;
    }

    .mini-note{
        border:1px dashed #dbeafe;
        background:#f8fbff;
        border-radius:14px;
        padding:10px 12px;
        color:#334155;
        font-size:12px;
        line-height:1.8;
    }

    .footer-actions{
        display:flex;
        justify-content:flex-end;
        gap:10px;
        flex-wrap:wrap;
        padding:14px 16px;
        border-top:1px solid var(--brd);
        background:#fff;
    }

    .datepicker-container,
    .datepicker-plot-area,
    .jalali-datepicker,
    .pwt-datepicker-container,
    .pwt-datepicker-input-element + .datepicker-plot-area {
        z-index: 999999 !important;
    }

    @media (max-width: 1199.98px){
        .info-grid{
            grid-template-columns:repeat(3,minmax(0,1fr));
        }
    }

    @media (max-width: 991.98px){
        .summary-stack{
            grid-template-columns:1fr;
        }

        .payment-list-wrap{
            grid-template-columns:1fr;
        }
    }

    @media (max-width: 767.98px){
        .info-grid{
            grid-template-columns:repeat(2,minmax(0,1fr));
        }
    }

    @media (max-width: 575.98px){
        .info-grid{
            grid-template-columns:1fr;
        }

        .method-switch{
            grid-template-columns:1fr;
        }
    }
</style>

<div class="container finance-page">
    <div class="hero-box">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="hero-title">مشاهده و تایید مالی پیش‌فاکتور</div>
                <p class="hero-sub">
                    در این مرحله اطلاعات مشتری، مبلغ نهایی و پرداخت‌های اولیه را بررسی می‌کنی. بعد از تایید، پیش‌فاکتور به فاکتور تبدیل می‌شود و وارد صف حواله فروش انبار می‌شود.
                </p>
            </div>

            <div class="top-actions">
                <a href="{{ route('preinvoice.draft.index') }}" class="btn btn-outline-secondary rounded-4 px-4">
                    بازگشت به صف مالی
                </a>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-2">خطاهای ثبت:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="info-grid">
        <div class="info-card">
            <div class="info-label">کد پیش‌فاکتور</div>
            <div class="info-value">{{ $order->uuid }}</div>
        </div>

        <div class="info-card">
            <div class="info-label">تاریخ ثبت</div>
            <div class="info-value">{{ $createdAtFa }}</div>
        </div>

        <div class="info-card">
            <div class="info-label">مشتری</div>
            <div class="info-value">{{ $customerName }}</div>
            <div class="small text-muted mt-1">{{ $customerMobile }}</div>
        </div>

        <div class="info-card">
            <div class="info-label">ثبت‌شده توسط</div>
            <div class="info-value">{{ $creatorName }}</div>
        </div>

        <div class="info-card">
            <div class="info-label">وضعیت حساب مشتری</div>
            <div class="info-value {{ $balanceClass }}">
                {{ $customerBalanceStatus }}
                @if($customerBalanceStatus !== 'تسویه')
                    {{ number_format($customerBalanceAmount) }} تومان
                @endif
            </div>
            <div class="mt-2">
                <span class="status-chip">در انتظار تایید مالی</span>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('preinvoice.draft.finalize', $order->uuid) }}" enctype="multipart/form-data" class="card-soft" id="financeFinalizeForm">
        @csrf

        <div class="payment-form-box">
            <div class="card-head rounded-4 mb-3">
                <h6 class="card-title">ثبت پرداخت‌های مشتری</h6>
                <p class="card-sub">پرداخت نقدی یا چکی را همین‌جا ثبت کن و بعد در لیست پایین ببین.</p>
            </div>

            <div class="summary-stack mb-3">
                <div class="summary-pill primary">
                    <div class="label">مبلغ نهایی فاکتور</div>
                    <div class="value" id="invoiceGrandTotal" data-total="{{ $grandTotal }}">{{ number_format($grandTotal) }}</div>
                    <div class="small text-muted mt-1">تومان</div>
                </div>

                <div class="summary-pill success">
                    <div class="label">جمع پرداخت‌های ثبت‌شده</div>
                    <div class="value" id="paymentsTotalBox">0</div>
                    <div class="small text-muted mt-1">تومان</div>
                </div>

                <div class="summary-pill warn">
                    <div class="label">باقی‌مانده فاکتور</div>
                    <div class="value" id="remainingTotalBox">{{ number_format($grandTotal) }}</div>
                    <div class="small text-muted mt-1" id="remainingStateText">نیاز به تسویه</div>
                </div>
            </div>

            <div class="method-switch">
                <button type="button" class="method-btn active" data-method-btn="cash">پرداخت نقدی</button>
                <button type="button" class="method-btn" data-method-btn="cheque">پرداخت چکی</button>
            </div>

            <input type="hidden" id="paymentTypeInput" value="cash">

            <div id="cashFields" class="card-soft mb-3">
                <div class="card-head">
                    <h6 class="card-title">اطلاعات پرداخت نقدی</h6>
                    <p class="card-sub">مبلغ، تاریخ پرداخت و توضیح را ثبت کن.</p>
                </div>

                <div class="p-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">مبلغ</label>
                            <input type="text" inputmode="numeric" id="cashAmountInput" class="form-control money" placeholder="مثلاً 5,000,000">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تاریخ پرداخت</label>
                            <input type="text" id="cashPaidAtInput" class="form-control js-jdp" data-jdp data-jdp-only-date autocomplete="off" dir="ltr" placeholder="1405/01/15">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">شناسه پرداخت</label>
                            <input type="text" id="cashIdentifierInput" class="form-control" placeholder="کارت به کارت / درگاه / POS">
                        </div>

                        <div class="col-12">
                            <label class="form-label">توضیحات (الزامی)</label>
                            <textarea id="cashNoteInput" class="form-control" rows="2" placeholder="مثلاً پرداخت کارت به کارت یا نقدی پای صندوق"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div id="chequeFields" class="card-soft mb-3 d-none">
                <div class="card-head">
                    <h6 class="card-title">اطلاعات پرداخت چکی</h6>
                    <p class="card-sub">مشخصات کامل چک را ثبت کن.</p>
                </div>

                <div class="p-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">شماره چک</label>
                            <input type="text" id="chequeNumberInput" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">مبلغ چک</label>
                            <input type="text" inputmode="numeric" id="chequeAmountInput" class="form-control money">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">وضعیت</label>
                            <select id="chequeStatusInput" class="form-select">
                                <option value="pending">در انتظار وصول</option>
                                <option value="cleared">وصول شده</option>
                                <option value="bounced">برگشتی</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تاریخ سررسید</label>
                            <input type="text" id="chequeDueDateInput" class="form-control js-jdp" data-jdp data-jdp-only-date autocomplete="off" dir="ltr" placeholder="1405/01/20">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تاریخ دریافت چک</label>
                            <input type="text" id="chequeReceivedAtInput" class="form-control js-jdp" data-jdp data-jdp-only-date autocomplete="off" dir="ltr" placeholder="1405/01/10">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">نام مشتری</label>
                            <input type="text" id="chequeCustomerNameInput" class="form-control" value="{{ $order->customer_name }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">شناسه / کد مشتری</label>
                            <input type="text" id="chequeCustomerCodeInput" class="form-control" value="{{ $order->customer_id }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">نام بانک</label>
                            <input type="text" id="chequeBankNameInput" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">نام شعبه</label>
                            <input type="text" id="chequeBranchNameInput" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">شماره حساب / شبا (اختیاری)</label>
                            <input type="text" id="chequeAccountNumberInput" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">صاحب حساب / صادرکننده چک</label>
                            <input type="text" id="chequeAccountHolderInput" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label">توضیحات (الزامی)</label>
                            <textarea id="chequeNoteInput" class="form-control" rows="2" placeholder="مثلاً چک شخصی یا چک ضمانتی"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div id="paymentInlineError" class="alert alert-danger d-none mb-3"></div>

            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-primary rounded-4 px-4" id="addPaymentInlineBtn">افزودن به لیست پرداخت‌ها</button>
            </div>
        </div>

        <div class="p-3 pt-0">
            <div class="payment-list-wrap">
                <div class="card-soft">
                    <div class="card-head">
                        <h6 class="card-title">لیست پرداخت‌های ثبت‌شده</h6>
                        <p class="card-sub">هر پرداختی که اضافه شود اینجا نمایش داده می‌شود.</p>
                    </div>

                    <div class="p-3">
                        <div id="paymentRows" class="payments-list"></div>

                        <div class="empty-box" id="paymentGuide">
                            هنوز پرداختی اضافه نشده است. از فرم بالای صفحه استفاده کن.
                        </div>
                    </div>
                </div>

                <div class="card-soft">
                    <div class="card-head">
                        <h6 class="card-title">اقلام پیش‌فاکتور و خلاصه مالی</h6>
                        <p class="card-sub">بررسی نهایی کالاها و مبلغ کل قبل از تایید مالی</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table clean-table align-middle">
                            <thead>
                                <tr>
                                    <th>محصول</th>
                                    <th>مدل</th>
                                    <th>تعداد</th>
                                    <th>مبلغ واحد</th>
                                    <th>جمع ردیف</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $it)
                                    <tr>
                                        <td>{{ $it->product?->name ?? ('#'.$it->product_id) }}</td>
                                        <td>{{ $it->variant?->variant_name ?? '—' }}</td>
                                        <td>{{ number_format((int) $it->quantity) }}</td>
                                        <td>{{ number_format((int) $it->price) }}</td>
                                        <td class="money-strong">{{ number_format(((int) $it->price) * ((int) $it->quantity)) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="totals-box">
                        <div class="total-row">
                            <span class="text-muted">جمع اقلام</span>
                            <strong>{{ number_format($subtotal) }} تومان</strong>
                        </div>

                        <div class="total-row">
                            <span class="text-muted">هزینه ارسال</span>
                            <strong>{{ number_format($shipping) }} تومان</strong>
                        </div>

                        <div class="total-row">
                            <span class="text-muted">تخفیف لحاظ شده</span>
                            <strong class="text-danger">- {{ number_format($discount) }} تومان</strong>
                        </div>

                        <div class="total-row final-row">
                            <span class="fw-semibold">مبلغ نهایی فاکتور</span>
                            <strong>{{ number_format($grandTotal) }} تومان</strong>
                        </div>

                        <div class="mini-note mt-3">
                            با تایید نهایی، فاکتور فروش ساخته می‌شود، موجودی از انبار مرکزی کسر می‌شود و در صورت ثبت پرداخت، اسناد مالی پرداخت هم ثبت خواهند شد.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-actions">
            <a href="{{ route('preinvoice.draft.edit', $order->uuid) }}" class="btn btn-outline-secondary rounded-4 px-4">
                ویرایش فاکتور
            </a>

            <button
                type="submit"
                class="btn btn-success rounded-4 px-4"
                id="financeFinalizeBtn"
                onclick="return confirm('تاییدیه نهایی مالی ثبت شود؟ با این کار، پیش‌فاکتور به فاکتور تبدیل می‌شود و در صف حواله فروش انبار قرار می‌گیرد.')"
            >
                تاییدیه نهایی پیش‌فاکتور از سمت مالی
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const rowsWrap = document.getElementById('paymentRows');
    const guide = document.getElementById('paymentGuide');
    const paymentTypeInput = document.getElementById('paymentTypeInput');
    const paymentInlineError = document.getElementById('paymentInlineError');
    const finalizeForm = document.getElementById('financeFinalizeForm');
    const finalizeBtn = document.getElementById('financeFinalizeBtn');
    const addPaymentInlineBtn = document.getElementById('addPaymentInlineBtn');
    const methodButtons = Array.from(document.querySelectorAll('[data-method-btn]'));
    const invoiceGrandTotal = Number(document.getElementById('invoiceGrandTotal')?.dataset.total || 0);
    const paymentsTotalBox = document.getElementById('paymentsTotalBox');
    const remainingTotalBox = document.getElementById('remainingTotalBox');
    const remainingStateText = document.getElementById('remainingStateText');

    if (!rowsWrap || !guide || !paymentTypeInput || !addPaymentInlineBtn) return;

    const payments = [];

    function normalizeAmount(value) {
        return (value || '').toString().replace(/[^\d]/g, '');
    }

    function formatMoney(value) {
        return Number(value || 0).toLocaleString('en-US');
    }

    function div(a, b) { return ~~(a / b); }
    function pad(v) { return String(v).padStart(2, '0'); }

    function jalaliToGregorian(jy, jm, jd) {
        jy += 1595;
        let days = -355668 + (365 * jy) + div(jy, 33) * 8 + div((jy % 33) + 3, 4) + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
        let gy = 400 * div(days, 146097);
        days %= 146097;

        if (days > 36524) {
            gy += 100 * div(--days, 36524);
            days %= 36524;
            if (days >= 365) days++;
        }

        gy += 4 * div(days, 1461);
        days %= 1461;

        if (days > 365) {
            gy += div(days - 1, 365);
            days = (days - 1) % 365;
        }

        let gd = days + 1;
        const sal_a = [0,31,((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? 29 : 28,31,30,31,30,31,31,30,31,30,31];
        let gm = 0;

        for (gm = 1; gm <= 12 && gd > sal_a[gm]; gm++) gd -= sal_a[gm];

        return `${gy}-${pad(gm)}-${pad(gd)}`;
    }

    function normalizeDate(value) {
        const raw = (value || '').trim();

        if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) return raw;

        const m = raw.match(/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})$/);
        if (!m) return '';

        return jalaliToGregorian(Number(m[1]), Number(m[2]), Number(m[3]));
    }

    function initDatepickersSafe() {
        if (typeof initJalaliDatepickers === 'function') {
            initJalaliDatepickers();
        }

        if (window.jalaliDatepicker && typeof window.jalaliDatepicker.startWatch === 'function') {
            window.jalaliDatepicker.startWatch({
                minDate: 'attr',
                maxDate: 'attr',
                time: false
            });
        }
    }

    function setMethod(type) {
        paymentTypeInput.value = type;

        document.getElementById('cashFields').classList.toggle('d-none', type !== 'cash');
        document.getElementById('chequeFields').classList.toggle('d-none', type !== 'cheque');

        methodButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.methodBtn === type);
        });
    }

    function buildHiddenInput(name, value) {
        const safe = String(value ?? '').replace(/"/g, '&quot;');
        return `<input type="hidden" name="${name}" value="${safe}">`;
    }

    function computePaymentAmount(payment) {
        return payment.method === 'cheque'
            ? Number(payment.cheque_amount || 0)
            : Number(payment.amount || 0);
    }

    function renderTotals() {
        const paymentsTotal = payments.reduce((sum, item) => sum + computePaymentAmount(item), 0);
        const remaining = invoiceGrandTotal - paymentsTotal;

        paymentsTotalBox.textContent = formatMoney(paymentsTotal);

        if (remaining > 0) {
            remainingTotalBox.textContent = formatMoney(remaining);
            remainingStateText.textContent = 'نیاز به تسویه';
        } else if (remaining === 0) {
            remainingTotalBox.textContent = '0';
            remainingStateText.textContent = 'تسویه کامل';
        } else {
            remainingTotalBox.textContent = formatMoney(Math.abs(remaining));
            remainingStateText.textContent = 'بیش‌پرداخت';
        }
    }

    function renderPaymentsList() {
        if (payments.length === 0) {
            rowsWrap.innerHTML = '';
            guide.classList.remove('d-none');
            renderTotals();
            return;
        }

        guide.classList.add('d-none');

        rowsWrap.innerHTML = payments.map((payment, idx) => {
            const amount = computePaymentAmount(payment);
            const isCheque = payment.method === 'cheque';
            const title = isCheque ? 'پرداخت چکی' : 'پرداخت نقدی';
            const subtitle = isCheque
                ? `شماره چک: ${payment.cheque_number || '—'} | تاریخ دریافت: ${payment.cheque_received_at || '—'}`
                : `تاریخ پرداخت: ${payment.paid_at || '—'} | شناسه: ${payment.payment_identifier || '—'}`;

            const hiddenInputs = Object.entries(payment)
                .map(([key, val]) => buildHiddenInput(`payments[${idx}][${key}]`, val))
                .join('');

            return `
                <div class="payment-card ${isCheque ? 'cheque' : 'cash'}">
                    <div class="payment-head">
                        <div>
                            <div class="payment-title">${title}</div>
                            <div class="payment-sub">${subtitle}</div>
                            <div class="payment-sub mt-1">${payment.note || '—'}</div>
                        </div>

                        <div class="text-end">
                            <div class="payment-amount">${formatMoney(amount)} تومان</div>
                            <button type="button" class="btn btn-sm btn-outline-danger mt-2 js-remove-payment" data-index="${idx}">
                                حذف
                            </button>
                        </div>
                    </div>

                    ${hiddenInputs}
                </div>
            `;
        }).join('');

        rowsWrap.querySelectorAll('.js-remove-payment').forEach(btn => {
            btn.addEventListener('click', function () {
                payments.splice(Number(this.dataset.index), 1);
                renderPaymentsList();
            });
        });

        renderTotals();
    }

    function clearCashFields() {
        document.getElementById('cashAmountInput').value = '';
        document.getElementById('cashPaidAtInput').value = '';
        document.getElementById('cashIdentifierInput').value = '';
        document.getElementById('cashNoteInput').value = '';
    }

    function clearChequeFields() {
        document.getElementById('chequeNumberInput').value = '';
        document.getElementById('chequeAmountInput').value = '';
        document.getElementById('chequeDueDateInput').value = '';
        document.getElementById('chequeReceivedAtInput').value = '';
        document.getElementById('chequeCustomerNameInput').value = @json($order->customer_name);
        document.getElementById('chequeCustomerCodeInput').value = @json((string) ($order->customer_id ?? ''));
        document.getElementById('chequeBankNameInput').value = '';
        document.getElementById('chequeBranchNameInput').value = '';
        document.getElementById('chequeAccountNumberInput').value = '';
        document.getElementById('chequeAccountHolderInput').value = '';
        document.getElementById('chequeStatusInput').value = 'pending';
        document.getElementById('chequeNoteInput').value = '';
    }

    function clearInlineError() {
        paymentInlineError.classList.add('d-none');
        paymentInlineError.textContent = '';
    }

    function readCashPayment() {
        const amount = normalizeAmount(document.getElementById('cashAmountInput').value);
        const paidAt = normalizeDate(document.getElementById('cashPaidAtInput').value);
        const note = (document.getElementById('cashNoteInput').value || '').trim();

        if (!amount || !paidAt || !note) {
            return { error: 'برای پرداخت نقدی، مبلغ، تاریخ پرداخت و توضیحات الزامی است.' };
        }

        return {
            method: 'cash',
            amount,
            paid_at: paidAt,
            payment_identifier: (document.getElementById('cashIdentifierInput').value || '').trim(),
            note,
        };
    }

    function readChequePayment() {
        const payload = {
            method: 'cheque',
            amount: normalizeAmount(document.getElementById('chequeAmountInput').value),
            paid_at: normalizeDate(document.getElementById('chequeReceivedAtInput').value),
            note: (document.getElementById('chequeNoteInput').value || '').trim(),

            cheque_number: (document.getElementById('chequeNumberInput').value || '').trim(),
            cheque_amount: normalizeAmount(document.getElementById('chequeAmountInput').value),
            cheque_due_date: normalizeDate(document.getElementById('chequeDueDateInput').value),
            cheque_received_at: normalizeDate(document.getElementById('chequeReceivedAtInput').value),
            cheque_customer_name: (document.getElementById('chequeCustomerNameInput').value || '').trim(),
            cheque_customer_code: (document.getElementById('chequeCustomerCodeInput').value || '').trim(),
            cheque_bank_name: (document.getElementById('chequeBankNameInput').value || '').trim(),
            cheque_branch_name: (document.getElementById('chequeBranchNameInput').value || '').trim(),
            cheque_account_number: (document.getElementById('chequeAccountNumberInput').value || '').trim(),
            cheque_account_holder: (document.getElementById('chequeAccountHolderInput').value || '').trim(),
            cheque_status: document.getElementById('chequeStatusInput').value || 'pending',
        };

        const requiredFields = [
            payload.cheque_number,
            payload.cheque_amount,
            payload.cheque_due_date,
            payload.cheque_received_at,
            payload.cheque_customer_name,
            payload.cheque_customer_code,
            payload.cheque_bank_name,
            payload.cheque_branch_name,
            payload.cheque_account_holder,
            payload.note,
        ];

        if (requiredFields.some(v => !v)) {
            return { error: 'برای ثبت چک، اطلاعات اصلی چک و توضیحات را کامل کن.' };
        }

        return payload;
    }

    methodButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            clearInlineError();
            setMethod(this.dataset.methodBtn);
        });
    });

    addPaymentInlineBtn.addEventListener('click', function () {
        clearInlineError();

        const payload = paymentTypeInput.value === 'cheque'
            ? readChequePayment()
            : readCashPayment();

        if (payload.error) {
            paymentInlineError.textContent = payload.error;
            paymentInlineError.classList.remove('d-none');
            return;
        }

        payments.push(payload);
        renderPaymentsList();

        if (paymentTypeInput.value === 'cheque') {
            clearChequeFields();
        } else {
            clearCashFields();
        }
    });

    document.querySelectorAll('.money').forEach(input => {
        input.addEventListener('input', function () {
            const raw = normalizeAmount(this.value);
            this.value = raw ? Number(raw).toLocaleString('en-US') : '';
        });
    });

    renderPaymentsList();
    initDatepickersSafe();

    if (finalizeForm) {
        finalizeForm.addEventListener('submit', function () {
            if (finalizeBtn) {
                finalizeBtn.disabled = true;
                finalizeBtn.textContent = 'در حال ثبت...';
            }
        });
    }
})();
</script>
@endsection