@extends('layouts.app')

@section('content')
@php
    $order = $order ?? null;
    $customersPageUrl = $customersPageUrl ?? url('/customers');

    $initRows = old('products');

    if (!$initRows && $order) {
        $initRows = $order->items->map(function ($it) {
            $product = $it->product ?? null;
            $variant = $it->variant ?? null;

            return [
                'id'           => (int) $it->product_id,
                'product_id'   => (int) $it->product_id,
                'product_name' => $product->title ?? $product->name ?? null,
                'product_code' => $product->code ?? $product->sku ?? null,

                'variety_id'   => (int) $it->variant_id,
                'variant_id'   => (int) $it->variant_id,
                'variant_name' => $variant->variant_name ?? null,

                'quantity'     => (int) $it->quantity,
                'price'        => (int) $it->price,
            ];
        })->values();
    }

    if (!$initRows) {
        $initRows = [];
    }

    $oldCustomerTitle = trim((string) old('customer_name'));
    $oldCustomerMobile = trim((string) old('customer_mobile'));
@endphp

<link rel="stylesheet" href="{{ asset('lib/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('lib/bootstrap.rtl.min.css') }}">
<script src="{{ asset('lib/jquery.min.js') }}"></script>
<script src="{{ asset('lib/select2.min.js') }}"></script>
<script src="{{ asset('lib/bootstrap.bundle.min.js') }}"></script>

<style>
    :root {
        --brand: #14B5CC;
        --brand-dark: #0f8fa1;
        --brand-soft: rgba(20, 181, 204, .08);
        --brand-border: rgba(20, 181, 204, .24);

        --bg: #f7f9fb;
        --card: #ffffff;
        --border: #e4e9ef;
        --text: #111827;
        --muted: #6b7280;
        --soft: #f9fafb;
        --danger: #dc2626;
        --success: #16a34a;
    }

    body {
        background: var(--bg);
        font-size: 14px;
        color: var(--text);
    }

    .page-shell {
        max-width: 960px;
    }

    .soft-card,
    .soft-card-lg {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 14px;
        box-shadow: none;
    }

    .soft-card-lg {
        border-color: var(--brand-border);
    }

    .compact-card {
        padding: 12px;
    }

    .product-focus {
        padding: 14px;
    }

    .page-title {
        font-size: 1.12rem;
        font-weight: 850;
        margin: 0;
    }

    .section-title {
        font-size: .98rem;
        font-weight: 850;
        margin: 0;
    }

    .hint {
        color: var(--muted);
        font-size: .8rem;
    }

    .label-sm {
        font-size: .78rem;
        font-weight: 750;
        color: #374151;
        margin-bottom: 5px;
    }

    .customer-box {
        background: var(--soft);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 10px 12px;
    }

    .customer-box.is-selected {
        background: var(--brand-soft);
        border-color: var(--brand-border);
    }

    .product-hero {
        background: #fff;
        border: 1px solid var(--brand-border);
        border-radius: 14px;
        padding: 12px;
    }

    .quick-code {
        height: 52px;
        font-size: 1.45rem;
        font-weight: 900;
        text-align: center;
        letter-spacing: 5px;
        direction: ltr;
        border-radius: 12px;
        border-color: var(--brand-border);
    }

    .quick-code:focus {
        border-color: var(--brand);
        box-shadow: 0 0 0 .2rem rgba(20, 181, 204, .12);
    }

    .quick-btn {
        height: 52px;
        border-radius: 12px;
        font-weight: 850;
        background: var(--brand);
        border-color: var(--brand);
    }

    .quick-btn:hover,
    .quick-btn:focus,
    .btn-primary:hover,
    .btn-primary:focus {
        background: var(--brand-dark);
        border-color: var(--brand-dark);
    }

    .btn-primary {
        background: var(--brand);
        border-color: var(--brand);
    }

    .btn-outline-primary {
        color: var(--brand-dark);
        border-color: var(--brand);
    }

    .btn-outline-primary:hover,
    .btn-outline-primary:focus {
        background: var(--brand);
        border-color: var(--brand);
        color: #fff;
    }

    .found-product {
        display: none;
        background: var(--brand-soft);
        border: 1px solid var(--brand-border);
        border-radius: 13px;
        padding: 11px;
    }

    .empty-state {
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        background: var(--soft);
        color: var(--muted);
        padding: 18px;
        text-align: center;
        font-weight: 750;
    }

    .badge-soft {
        display: inline-flex;
        align-items: center;
        background: #f3f6f8;
        color: #374151;
        border: 1px solid #e5eaf0;
        border-radius: 999px;
        padding: 4px 8px;
        font-size: .72rem;
        font-weight: 750;
    }

    .badge-brand {
        background: var(--brand-soft);
        color: var(--brand-dark);
        border-color: var(--brand-border);
    }

    .group-card {
        border: 1px solid var(--border);
        border-radius: 13px;
        background: #fff;
        overflow: hidden;
    }

    .group-main {
        display: grid;
        grid-template-columns: 1.5fr .7fr .7fr .9fr auto;
        gap: 8px;
        align-items: center;
        padding: 11px;
    }

    .group-title {
        font-weight: 850;
        color: var(--text);
    }

    .group-meta {
        color: var(--muted);
        font-size: .78rem;
        margin-top: 3px;
    }

    .stat-box {
        background: var(--soft);
        border: 1px solid #eef2f5;
        border-radius: 10px;
        padding: 7px 9px;
    }

    .stat-label {
        font-size: .7rem;
        color: var(--muted);
        font-weight: 750;
    }

    .stat-value {
        font-size: .88rem;
        font-weight: 850;
        margin-top: 2px;
    }

    .group-details {
        display: none;
        border-top: 1px solid #eef2f5;
        background: #fbfcfd;
        padding: 10px 12px;
    }

    .group-card.is-open .group-details {
        display: block;
    }

    .details-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }

    .detail-pill {
        border: 1px solid #e5eaf0;
        background: #fff;
        border-radius: 10px;
        padding: 8px;
        font-size: .77rem;
    }

    .final-card {
        padding: 14px;
        margin-bottom: 24px;
    }

    .final-grid {
        display: grid;
        grid-template-columns: 1.15fr .9fr .9fr 1fr auto;
        gap: 10px;
        align-items: end;
    }

    .total-view {
        font-weight: 900;
        color: var(--text);
        background: var(--soft) !important;
    }

    .discount-line {
        color: #0f5560;
        font-size: .78rem;
        margin-top: 6px;
    }

    .discount-control {
        display: grid;
        grid-template-columns: 86px 1fr;
        gap: 6px;
    }

    .discount-control select,
    .discount-control input {
        height: 36px;
    }

    .modal-dialog {
        margin: .5rem auto;
    }

    .modal-xl {
        max-width: 920px;
        width: calc(100vw - 16px);
    }

    .modal-content {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
    }

    .picker-head {
        background: #f8fafb;
        border-bottom: 1px solid var(--border);
    }

    .picker-toolbar {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) auto auto;
        gap: 8px;
        align-items: center;
    }

    .picker-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        margin-top: 10px;
    }

    .picker-stat {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 9px 10px;
    }

    .variant-list {
        max-height: 56vh;
        overflow-y: auto;
        overflow-x: hidden;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #fff;
        padding: 8px;
    }

    .variant-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        border: 1px solid #e7edf3;
        border-radius: 12px;
        padding: 10px;
        background: #fff;
        margin-bottom: 8px;
    }

    .variant-card:last-child {
        margin-bottom: 0;
    }

    .variant-card.row-selected {
        background: rgba(20, 181, 204, .06);
        border-color: var(--brand-border);
    }

    .variant-card.row-empty-stock {
        opacity: .55;
    }

    .variant-title {
        font-weight: 850;
        color: var(--text);
        line-height: 1.6;
        word-break: break-word;
    }

    .variant-subtitle {
        color: var(--muted);
        font-size: .75rem;
        margin-top: 2px;
        direction: ltr;
        text-align: right;
        word-break: break-word;
    }

    .variant-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    .qty-control {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        direction: ltr;
    }

    .qty-control button {
        width: 34px;
        height: 34px;
        border: 1px solid #cbd5e1;
        background: #fff;
        border-radius: 9px;
        font-weight: 900;
        line-height: 1;
    }

    .qty-control input {
        width: 58px;
        height: 34px;
        text-align: center;
        font-weight: 850;
        direction: ltr;
    }

    .quick-plus {
        width: auto !important;
        min-width: 38px;
        height: 30px !important;
        padding: 0 8px;
        font-size: .75rem;
        color: var(--brand-dark);
        border-color: var(--brand-border) !important;
        background: var(--brand-soft) !important;
    }

    .badge-stock {
        background: rgba(22, 163, 74, .08);
        color: #15803d;
        border-color: rgba(22, 163, 74, .18);
    }

    .badge-no-stock {
        background: rgba(220, 38, 38, .08);
        color: #dc2626;
        border-color: rgba(220, 38, 38, .18);
    }

    .modal-discount-box {
        margin-top: 10px;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 10px;
    }

    .select2-container .select2-selection--single {
        min-height: 38px !important;
        border-color: #dee2e6 !important;
        border-radius: .65rem !important;
        padding-top: 4px;
    }

    .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 28px !important;
        padding-right: 12px !important;
    }

    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }

    @media (max-width: 991.98px) {
        .page-shell {
            max-width: 100%;
        }

        .group-main,
        .final-grid,
        .picker-toolbar,
        .picker-stats {
            grid-template-columns: 1fr;
        }

        .details-grid {
            grid-template-columns: 1fr;
        }

        .quick-code,
        .quick-btn {
            height: 50px;
        }
    }

    @media (max-width: 575.98px) {
        body {
            font-size: 13px;
        }

        .container {
            padding-left: 10px;
            padding-right: 10px;
        }

        .page-title {
            font-size: 1rem;
        }

        .section-title {
            font-size: .92rem;
        }

        .compact-card,
        .product-focus,
        .final-card {
            padding: 10px;
            border-radius: 12px;
        }

        .modal-dialog {
            width: 100%;
            max-width: 100%;
            margin: 0;
        }

        .modal-content {
            min-height: 100vh;
            border-radius: 0;
        }

        .modal-body {
            padding: 10px;
        }

        .modal-header,
        .modal-footer {
            padding: 10px;
        }

        .variant-list {
            max-height: 58vh;
            padding: 6px;
        }

        .variant-card {
            grid-template-columns: 1fr;
            gap: 8px;
            padding: 9px;
        }

        .variant-title {
            font-size: .86rem;
        }

        .variant-subtitle {
            font-size: .72rem;
        }

        .variant-meta {
            gap: 5px;
        }

        .qty-control {
            width: 100%;
            justify-content: space-between;
        }

        .qty-control button {
            width: 38px;
            height: 38px;
        }

        .qty-control input {
            width: 64px;
            height: 38px;
        }

        .quick-plus {
            min-width: 42px;
            height: 34px !important;
        }

        .discount-control {
            grid-template-columns: 80px 1fr;
        }

        .final-grid .btn {
            width: 100%;
        }
    }
</style>

<div class="container page-shell py-3">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="page-title">🧾 ثبت پیش‌فاکتور</h1>
            <div class="hint mt-1">ثبت سریع کالا با کد مادر، انتخاب گروهی و تخفیف جمع‌وجور</div>
        </div>

        <a class="btn btn-sm btn-outline-secondary rounded-3" href="{{ route('preinvoice.warehouse.index') }}">
            صف تایید انبار
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 fw-bold py-2">✅ {{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 fw-bold py-2" style="white-space: pre-wrap">
            {!! session('error') !!}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4 py-2">
            <div class="fw-bold mb-1">⚠️ خطا:</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('preinvoice.draft.save') }}" method="POST" id="orderForm" autocomplete="off">
        @csrf

        <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id', '') }}">
        <input type="hidden" name="customer_name" id="customer_name" value="{{ old('customer_name') }}">
        <input type="hidden" name="customer_mobile" id="customer_mobile" value="{{ old('customer_mobile') }}">
        <input type="hidden" name="payment_status" value="pending">

        <div class="soft-card compact-card mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-5">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h2 class="section-title">👤 مشتری</h2>
                            <div class="hint">جستجو با نام یا موبایل</div>
                        </div>

                        <a href="{{ $customersPageUrl }}" class="btn btn-sm btn-outline-success rounded-3">➕ افزودن</a>
                    </div>

                    <select id="customer_search_select" class="form-select"></select>
                </div>

                <div class="col-lg-7">
                    <div id="customerSummaryBox" class="customer-box h-100 {{ old('customer_id') || $oldCustomerTitle ? 'is-selected' : '' }}">
                        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                            <div>
                                <div class="fw-bold" id="selectedCustomerTitle">
                                    @if($oldCustomerTitle)
                                        {{ $oldCustomerTitle }} @if($oldCustomerMobile) - {{ $oldCustomerMobile }} @endif
                                    @else
                                        هنوز مشتری انتخاب نشده است
                                    @endif
                                </div>
                                <div class="hint mt-1" id="customer_balance_hint"></div>
                            </div>

                            <button type="button" id="clearCustomerBtn" class="btn btn-sm btn-light border rounded-3">
                                تغییر
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="soft-card compact-card mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-4">
                    <h2 class="section-title mb-2">🚚 ارسال</h2>
                    <label class="label-sm">شیوه ارسال</label>
                    <select id="shipping_id" name="shipping_id" class="form-select form-select-sm" required>
                        <option value="">انتخاب روش ارسال...</option>
                    </select>
                    <input type="hidden" id="shipping_price" name="shipping_price" value="{{ old('shipping_price', 0) }}">
                </div>

                <div class="col-lg-4" id="provinceBox">
                    <label class="label-sm">استان</label>
                    <select id="province_id" name="province_id" class="form-select form-select-sm">
                        <option value=""></option>
                    </select>
                </div>

                <div class="col-lg-4" id="cityBox">
                    <label class="label-sm">شهر</label>
                    <select id="city_id" name="city_id" class="form-select form-select-sm">
                        <option value=""></option>
                    </select>
                </div>
            </div>

            <div class="mt-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="hint" id="shipping_mode_hint">روش ارسال را انتخاب کنید.</div>

                <button class="btn btn-sm btn-light border rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#addressCollapse">
                    آدرس / توضیحات
                </button>
            </div>

            <div id="addressCollapse" class="collapse mt-2 {{ old('customer_address') ? 'show' : '' }}">
                <textarea id="customer_address" name="customer_address" class="form-control form-control-sm" rows="2" placeholder="آدرس یا توضیحات ارسال...">{{ old('customer_address') }}</textarea>
            </div>
        </div>

        <div class="soft-card-lg product-focus mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="section-title">🧩 ثبت سریع کالا</h2>
                    <div class="hint mt-1">کد مادر را بزن، تنوع‌ها را انتخاب کن، برو محصول بعدی.</div>
                </div>

                <span class="badge-soft badge-brand">ثبت گروهی محصول مادر</span>
            </div>

            <div class="product-hero mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-lg-3">
                        <label class="label-sm">کد ۴ رقمی محصول مادر</label>
                        <input
                            type="text"
                            id="motherCodeInput"
                            class="form-control quick-code"
                            maxlength="4"
                            inputmode="numeric"
                            placeholder="4450"
                        >
                    </div>

                    <div class="col-lg-3">
                        <button type="button" id="findMotherBtn" class="btn btn-primary w-100 quick-btn">
                            نمایش محصول
                        </button>
                    </div>

                    <div class="col-lg-6">
                        <div id="motherSearchHint" class="customer-box h-100 d-flex align-items-center">
                            <div>
                                <div class="fw-bold">محصول مادر هنوز انتخاب نشده</div>
                                <div class="hint mt-1">بعد از وارد کردن کد، دکمه مشاهده و انتخاب فعال می‌شود.</div>
                            </div>
                        </div>

                        <div id="motherProductBox" class="found-product">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <div class="hint">محصول انتخاب‌شده</div>
                                    <div class="fw-bold" id="motherProductTitle">—</div>
                                    <div class="hint mt-1" id="motherProductCode">—</div>
                                </div>

                                <button type="button" id="openGroupPickerBtn" class="btn btn-outline-primary rounded-3 fw-bold">
                                    مشاهده و انتخاب
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <h2 class="section-title">سبد پیش‌فاکتور</h2>
                    <div class="hint" id="orderItemsCountHint">۰ گروه انتخاب شده</div>
                </div>

                <div id="groupSummaryList"></div>
                <div id="groupProductsInputs"></div>
            </div>
        </div>

        <div class="soft-card final-card">
            <input type="hidden" name="discount_amount" id="discount" value="{{ old('discount_amount', 0) }}">

            <div class="final-grid">
                <div>
                    <label class="label-sm">تخفیف کلی</label>
                    <div class="discount-control">
                        <select id="orderDiscountType" class="form-select form-select-sm">
                            <option value="amount">تومان</option>
                            <option value="percent">درصد</option>
                        </select>
                        <input
                            type="number"
                            id="orderDiscountValue"
                            class="form-control form-control-sm"
                            min="0"
                            step="0.01"
                            value="{{ old('discount_amount', 0) }}"
                            placeholder="مقدار تخفیف"
                        >
                    </div>
                    <div class="discount-line" id="orderDiscountPreview">تخفیف کلی: 0 تومان</div>
                </div>

                <div>
                    <label class="label-sm">هزینه ارسال</label>
                    <input
                        type="text"
                        id="shipping_price_view"
                        class="form-control form-control-sm bg-light"
                        readonly
                        value="0 تومان"
                    >
                </div>

                <div>
                    <label class="label-sm">مجموع تخفیف</label>
                    <input
                        type="text"
                        id="totalDiscountView"
                        class="form-control form-control-sm bg-light"
                        readonly
                        value="0 تومان"
                    >
                </div>

                <div>
                    <label class="label-sm">جمع کل</label>
                    <input
                        type="text"
                        name="total_price"
                        id="total_price"
                        class="form-control form-control-sm total-view"
                        readonly
                        value="0"
                    >
                </div>

                <div>
                    <button class="btn btn-primary px-4 py-2 rounded-3 fw-bold" id="submitOrderBtn">
                        ثبت پیش‌فاکتور
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="groupPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header picker-head">
                <div>
                    <h5 class="modal-title fw-bold" id="pickerModalTitle">انتخاب کالا</h5>
                    <div class="hint mt-1" id="pickerModalSubTitle">—</div>
                </div>

                <button type="button" class="btn-close m-0" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="picker-toolbar mb-2">
                    <input
                        type="text"
                        id="pickerSearchInput"
                        class="form-control"
                        placeholder="جستجو در تنوع یا پارت‌نامبر..."
                    >

                    <label class="btn btn-light border rounded-3 mb-0">
                        <input type="checkbox" id="onlyInStockFilter" class="form-check-input ms-1">
                        فقط موجودها
                    </label>

                    <label class="btn btn-light border rounded-3 mb-0">
                        <input type="checkbox" id="onlySelectedFilter" class="form-check-input ms-1">
                        فقط انتخاب‌شده‌ها
                    </label>
                </div>

                <div class="picker-stats mb-3">
                    <div class="picker-stat">
                        <div class="stat-label">ردیف انتخاب‌شده</div>
                        <div class="stat-value" id="modalSelectedRows">0</div>
                    </div>

                    <div class="picker-stat">
                        <div class="stat-label">جمع تعداد</div>
                        <div class="stat-value" id="modalTotalQty">0</div>
                    </div>

                    <div class="picker-stat">
                        <div class="stat-label">جمع بعد از تخفیف</div>
                        <div class="stat-value" id="modalTotalAmount">0 تومان</div>
                    </div>
                </div>

                <div id="pickerLoading" class="empty-state d-none">
                    در حال دریافت کالاها...
                </div>

                <div class="variant-list" id="pickerTableWrap">
                    <div id="groupPickerRows"></div>
                </div>

                <div class="modal-discount-box">
                    <label class="label-sm">تخفیف این محصول</label>
                    <div class="discount-control">
                        <select id="modalGroupDiscountType" class="form-select form-select-sm">
                            <option value="amount">تومان</option>
                            <option value="percent">درصد</option>
                        </select>
                        <input type="number" id="modalGroupDiscountValue" class="form-control form-control-sm" min="0" step="0.01" value="0" placeholder="مقدار تخفیف">
                    </div>
                    <div class="discount-line">
                        مبلغ تخفیف این محصول: <strong id="modalGroupDiscountPreview">0 تومان</strong>
                    </div>
                </div>
            </div>

            <div class="modal-footer picker-head">
                <button type="button" class="btn btn-light border rounded-3" id="clearPickerQtyBtn">
                    پاک کردن تعدادها
                </button>

                <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">
                    لغو
                </button>

                <button type="button" id="saveGroupSelectionBtn" class="btn btn-primary rounded-3 fw-bold px-4">
                    اتمام و افزودن
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const API = {
        products:  "{{ url('/preinvoice/api/products') }}",
        product:   "{{ url('/preinvoice/api/products') }}",
        area:      "{{ url('/preinvoice/api/area') }}",
        customers: "{{ url('/preinvoice/api/customers') }}",
        customer:  "{{ url('/preinvoice/api/customers') }}"
    };

    const INIT_ROWS = @json($initRows);
    const INITIAL_SHIPPINGS = @json($shippingMethods);
    const OLD_CUSTOMER_ID = @json(old('customer_id', ''));
    const OLD_PROVINCE_ID = @json(old('province_id', ''));
    const OLD_CITY_ID = @json(old('city_id', ''));
    const OLD_SHIPPING_ID = @json(old('shipping_id', ''));
</script>

<script>
    let shippings = INITIAL_SHIPPINGS || [];
    let areaProvinces = [];
    const productCache = new Map();

    let selectedMotherProduct = null;
    let activeProductId = null;
    let activeProduct = null;
    let activeModalItems = [];
    let modalQuantities = new Map();

    let modalGroupDiscountType = 'amount';
    let modalGroupDiscountValue = 0;

    let groupedSelections = {};

    function toEnglishDigits(str) {
        return String(str || '')
            .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
            .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
    }

    function toInt(val) {
        const s = toEnglishDigits(val)
            .replaceAll(',', '')
            .replaceAll('٬', '')
            .replaceAll('،', '')
            .replace(/[^\d.-]/g, '')
            .trim();

        const n = parseFloat(s);
        return Number.isFinite(n) ? Math.trunc(n) : 0;
    }

    function formatMoney(val) {
        return Number(val || 0).toLocaleString('fa-IR') + ' تومان';
    }

    function formatNum(val) {
        return Number(val || 0).toLocaleString('fa-IR');
    }

    function esc(val) {
        return String(val ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function normalize(val) {
        return String(val || '').trim();
    }

    function safeDiscountValue(type, value) {
        let n = Number(value || 0);
        if (!Number.isFinite(n)) n = 0;
        if (n < 0) n = 0;

        if (type === 'percent' && n > 100) n = 100;

        return n;
    }

    function calcDiscount(baseAmount, type, value) {
        const base = Math.max(0, Number(baseAmount || 0));
        const safeType = type === 'percent' ? 'percent' : 'amount';
        const safeValue = safeDiscountValue(safeType, value);

        if (safeType === 'percent') {
            return Math.min(base, Math.floor(base * safeValue / 100));
        }

        return Math.min(base, Math.floor(safeValue));
    }

    function customerFullName(c) {
        if (!c) return '';
        const full = `${c.first_name || ''} ${c.last_name || ''}`.trim();
        return full || normalize(c.customer_name || c.name);
    }

    function productTitle(product) {
        return normalize(product?.title || product?.name) || 'بدون نام';
    }

    function productCode(product) {
        return normalize(product?.code || product?.sku || product?.short_code);
    }

    function getProductVarieties(product) {
        if (!product) return [];
        if (Array.isArray(product.varieties)) return product.varieties;
        if (Array.isArray(product.variants)) return product.variants;
        return [];
    }

    function variantId(v) {
        return Number(v?.id || 0);
    }

    function variantModel(v) {
        return normalize(v?.model_list_name || v?.model_name || v?.model_list?.name) || '—';
    }

    function variantDesign(v) {
        return normalize(v?.design_name || v?.pattern_name || v?.variety_name || v?.type_name) || '—';
    }

    function variantName(v) {
        return normalize(v?.variant_name || v?.color_name || v?.color || v?.name) || '—';
    }

    function variantCode(v) {
        return normalize(v?.part_number || v?.barcode || v?.variant_code || v?.variety_code || v?.code) || '—';
    }

    function variantPrice(v, product = null) {
        return Number(v?.sell_price ?? v?.price ?? product?.sell_price ?? product?.price ?? 0);
    }

    function variantStock(v) {
        if (v?.sellable_stock !== undefined && v?.sellable_stock !== null) {
            return Number(v.sellable_stock) || 0;
        }

        const stock = Number(v?.stock ?? v?.quantity ?? 0) || 0;
        const reserved = Number(v?.reserved ?? 0) || 0;

        return Math.max(0, stock - reserved);
    }

    function groupRawSubtotal(group) {
        if (!group || !Array.isArray(group.items)) return 0;

        return group.items.reduce((sum, item) => {
            return sum + Number(item.quantity || 0) * Number(item.price || 0);
        }, 0);
    }

    function groupDiscountTotal(group) {
        return calcDiscount(
            groupRawSubtotal(group),
            group.discount_type || 'amount',
            group.discount_value || 0
        );
    }

    function groupFinalAmount(group) {
        return Math.max(0, groupRawSubtotal(group) - groupDiscountTotal(group));
    }

    async function getProductDetails(productId) {
        const id = String(productId || '');
        if (!id) return null;

        if (productCache.has(id)) return productCache.get(id);

        const res = await fetch(API.product + '/' + encodeURIComponent(id), {
            headers: { 'Accept': 'application/json' }
        });

        const json = await res.json();
        const product = json?.data?.product || null;

        if (product) productCache.set(id, product);

        return product;
    }

    function shippingById(id) {
        return shippings.find(s => Number(s.id) === Number(id)) || null;
    }

    function isInPersonShipping(ship) {
        const name = normalize(ship?.name);
        return name.includes('حضوری') || name.includes('مراجعه');
    }

    function initSelect2Basic(selectEl, placeholder) {
        if (!window.jQuery || !window.jQuery.fn?.select2 || !selectEl) return;

        const $el = $(selectEl);

        if ($el.hasClass('select2-hidden-accessible')) {
            $el.off('select2:select select2:clear');
            $el.select2('destroy');
        }

        $el.select2({
            width: '100%',
            dir: 'rtl',
            placeholder,
            allowClear: true
        });

        $el.on('select2:select select2:clear', function () {
            this.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    async function loadArea() {
        try {
            const res = await fetch(API.area, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            areaProvinces = data?.data?.provinces || [];
        } catch (e) {
            areaProvinces = [];
        }
    }

    function fillProvincesSelect() {
        const provinceSelect = document.getElementById('province_id');
        provinceSelect.innerHTML = '<option value=""></option>';

        areaProvinces.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = normalize(p.name);
            provinceSelect.appendChild(opt);
        });

        initSelect2Basic(provinceSelect, 'انتخاب استان...');
    }

    function fillCitiesByProvinceId(provinceId) {
        const citySelect = document.getElementById('city_id');
        citySelect.innerHTML = '<option value=""></option>';

        const province = areaProvinces.find(p => Number(p.id) === Number(provinceId));
        const cities = province?.cities || [];

        cities.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = normalize(c.name);
            citySelect.appendChild(opt);
        });

        citySelect.disabled = cities.length === 0;
        initSelect2Basic(citySelect, 'انتخاب شهر...');
    }

    function fillShippingSelect() {
        const shippingSelect = document.getElementById('shipping_id');
        shippingSelect.innerHTML = '<option value="">انتخاب روش ارسال...</option>';

        shippings.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name;
            shippingSelect.appendChild(opt);
        });
    }

    function updateShippingMode() {
        const shippingSelect = document.getElementById('shipping_id');
        const ship = shippingById(shippingSelect.value);
        const inPerson = isInPersonShipping(ship);
        const price = ship ? Number(ship.price || 0) : 0;

        document.getElementById('shipping_price').value = String(price);
        document.getElementById('shipping_price_view').value = formatMoney(price);

        const provinceBox = document.getElementById('provinceBox');
        const cityBox = document.getElementById('cityBox');
        const provinceEl = document.getElementById('province_id');
        const cityEl = document.getElementById('city_id');
        const addressEl = document.getElementById('customer_address');
        const hintEl = document.getElementById('shipping_mode_hint');

        if (inPerson) {
            provinceBox.style.display = 'none';
            cityBox.style.display = 'none';

            provinceEl.value = '';
            cityEl.value = '';
            addressEl.value = '';

            provinceEl.disabled = true;
            cityEl.disabled = true;

            hintEl.textContent = 'مراجعه حضوری انتخاب شده؛ آدرس لازم نیست.';
        } else {
            provinceBox.style.display = '';
            cityBox.style.display = '';

            provinceEl.disabled = false;
            cityEl.disabled = false;

            hintEl.textContent = price > 0
                ? 'هزینه ارسال: ' + formatMoney(price)
                : 'برای ارسال غیرحضوری، مقصد و آدرس را تکمیل کنید.';
        }

        updateTotal();
    }

    function applyCustomerToForm(c) {
        if (!c) return;

        const name = customerFullName(c);
        const mobile = normalize(c.mobile);

        document.getElementById('customer_id').value = c.id || '';
        document.getElementById('customer_name').value = name;
        document.getElementById('customer_mobile').value = mobile;
        document.getElementById('customer_address').value = c.address || '';

        document.getElementById('selectedCustomerTitle').textContent =
            name + (mobile ? ' - ' + mobile : '');

        document.getElementById('customer_balance_hint').textContent =
            'مانده حساب: ' + formatMoney(c.balance || 0);

        document.getElementById('customerSummaryBox').classList.add('is-selected');

        if (c.province_id) {
            document.getElementById('province_id').value = String(c.province_id);
            if (window.jQuery) $('#province_id').trigger('change.select2');
            fillCitiesByProvinceId(c.province_id);
        }

        if (c.city_id) {
            document.getElementById('city_id').value = String(c.city_id);
            if (window.jQuery) $('#city_id').trigger('change.select2');
        }
    }

    function clearCustomer() {
        document.getElementById('customer_id').value = '';
        document.getElementById('customer_name').value = '';
        document.getElementById('customer_mobile').value = '';
        document.getElementById('selectedCustomerTitle').textContent = 'هنوز مشتری انتخاب نشده است';
        document.getElementById('customer_balance_hint').textContent = '';
        document.getElementById('customerSummaryBox').classList.remove('is-selected');

        if (window.jQuery) {
            $('#customer_search_select').val(null).trigger('change');
        }
    }

    function preloadCustomerOption(selectEl, customer) {
        if (!selectEl || !customer || !window.jQuery) return;

        const text = customerFullName(customer) + (customer.mobile ? ' - ' + customer.mobile : '');
        const option = new Option(text, customer.id, true, true);

        selectEl.add(option);
        $(selectEl).trigger('change');
    }

    function initCustomerSearch() {
        const selectEl = document.getElementById('customer_search_select');

        if (!window.jQuery || !window.jQuery.fn?.select2) return;

        $(selectEl).select2({
            width: '100%',
            dir: 'rtl',
            placeholder: 'نام یا شماره موبایل مشتری را وارد کنید...',
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: API.customers,
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term || '' }),
                processResults: resp => {
                    const items = resp?.data?.customers || [];

                    return {
                        results: items.map(c => ({
                            id: c.id,
                            text: customerFullName(c) + ' - ' + (c.mobile || '')
                        }))
                    };
                }
            }
        });

        $(selectEl).on('select2:select', async function (e) {
            const id = e?.params?.data?.id;
            if (!id) return;

            try {
                const res = await fetch(API.customer + '/' + encodeURIComponent(id), {
                    headers: { 'Accept': 'application/json' }
                });

                const json = await res.json();
                const customer = json?.data?.customer || null;

                if (customer) applyCustomerToForm(customer);
            } catch (error) {}
        });

        $(selectEl).on('select2:clear', clearCustomer);
    }

    async function loadOldCustomer() {
        const cid = document.getElementById('customer_id').value || OLD_CUSTOMER_ID || '';
        if (!cid) return;

        try {
            const res = await fetch(API.customer + '/' + encodeURIComponent(cid), {
                headers: { 'Accept': 'application/json' }
            });

            const json = await res.json();
            const customer = json?.data?.customer || null;

            if (customer) {
                applyCustomerToForm(customer);
                preloadCustomerOption(document.getElementById('customer_search_select'), customer);
            }
        } catch (e) {}
    }

    async function findMotherProductByCode() {
        const input = document.getElementById('motherCodeInput');
        const code = toEnglishDigits(input.value).replace(/\D/g, '').slice(0, 4);
        input.value = code;

        if (code.length !== 4) {
            alert('کد مادر باید ۴ رقم باشد.');
            input.focus();
            return;
        }

        const btn = document.getElementById('findMotherBtn');
        const originalText = btn.textContent;

        btn.disabled = true;
        btn.textContent = 'در حال جستجو...';

        try {
            const res = await fetch(API.products + '?q=' + encodeURIComponent(code), {
                headers: { 'Accept': 'application/json' }
            });

            const json = await res.json();
            const rows = json?.data?.products?.data || [];

            selectedMotherProduct =
                rows.find(p => String(productCode(p)).trim() === code) ||
                rows.find(p => String(p.code || '').trim() === code) ||
                rows.find(p => String(p.sku || '').trim() === code) ||
                rows[0] ||
                null;

            if (!selectedMotherProduct) {
                document.getElementById('motherProductBox').style.display = 'none';
                document.getElementById('motherSearchHint').style.display = '';
                alert('محصول مادری با این کد پیدا نشد.');
                input.select();
                return;
            }

            document.getElementById('motherSearchHint').style.display = 'none';
            document.getElementById('motherProductBox').style.display = 'block';
            document.getElementById('motherProductTitle').textContent = productTitle(selectedMotherProduct);
            document.getElementById('motherProductCode').textContent = 'کد مادر: ' + (productCode(selectedMotherProduct) || code);

            setTimeout(() => document.getElementById('openGroupPickerBtn').focus(), 50);
        } catch (e) {
            alert('خطا در جستجوی محصول. دوباره تلاش کنید.');
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }

    async function openGroupPicker(productId = null) {
        const targetId = productId || selectedMotherProduct?.id;
        if (!targetId) return;

        const modalEl = document.getElementById('groupPickerModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        activeProductId = Number(targetId);
        activeProduct = groupedSelections[activeProductId]?.product || selectedMotherProduct || null;

        document.getElementById('pickerLoading').classList.remove('d-none');
        document.getElementById('pickerTableWrap').classList.add('d-none');
        document.getElementById('groupPickerRows').innerHTML = '';
        document.getElementById('pickerSearchInput').value = '';
        document.getElementById('onlyInStockFilter').checked = false;
        document.getElementById('onlySelectedFilter').checked = false;

        modal.show();

        try {
            const product = await getProductDetails(activeProductId);

            if (!product) {
                alert('اطلاعات محصول دریافت نشد.');
                modal.hide();
                return;
            }

            activeProduct = product;
            activeModalItems = getProductVarieties(product);
            modalQuantities = new Map();

            const oldItems = groupedSelections[activeProductId]?.items || [];
            oldItems.forEach(item => {
                modalQuantities.set(Number(item.variant_id), Number(item.quantity || 0));
            });

            modalGroupDiscountType = groupedSelections[activeProductId]?.discount_type || 'amount';
            modalGroupDiscountValue = Number(groupedSelections[activeProductId]?.discount_value || 0);

            document.getElementById('modalGroupDiscountType').value = modalGroupDiscountType;
            document.getElementById('modalGroupDiscountValue').value = modalGroupDiscountValue;

            document.getElementById('pickerModalTitle').textContent = productTitle(product);
            document.getElementById('pickerModalSubTitle').textContent =
                'کد مادر: ' + (productCode(product) || '—') + ' | تعداد ردیف‌ها: ' + formatNum(activeModalItems.length);

            renderPickerRows();
            updateModalSummary();

            document.getElementById('pickerLoading').classList.add('d-none');
            document.getElementById('pickerTableWrap').classList.remove('d-none');

            setTimeout(() => document.getElementById('pickerSearchInput').focus(), 200);
        } catch (e) {
            alert('خطا در باز کردن لیست کالاها.');
            modal.hide();
        }
    }

    function filteredModalItems() {
        const q = normalize(document.getElementById('pickerSearchInput').value).toLowerCase();
        const onlyStock = document.getElementById('onlyInStockFilter').checked;
        const onlySelected = document.getElementById('onlySelectedFilter').checked;

        return activeModalItems.filter(v => {
            const id = variantId(v);
            const qty = Number(modalQuantities.get(id) || 0);
            const stock = variantStock(v);

            if (onlyStock && stock <= 0) return false;
            if (onlySelected && qty <= 0) return false;

            if (!q) return true;

            const haystack = [
                variantModel(v),
                variantDesign(v),
                variantName(v),
                variantCode(v)
            ].join(' ').toLowerCase();

            return haystack.includes(q);
        });
    }

    function modalMaxQty(v) {
        const id = variantId(v);
        const currentQty = Number(modalQuantities.get(id) || 0);

        return Math.max(variantStock(v), currentQty);
    }

    function renderPickerRows() {
        const wrap = document.getElementById('groupPickerRows');
        const rows = filteredModalItems();

        if (!rows.length) {
            wrap.innerHTML = `
                <div class="empty-state">
                    موردی برای نمایش وجود ندارد.
                </div>
            `;
            return;
        }

        wrap.innerHTML = rows.map(v => {
            const id = variantId(v);
            const stock = variantStock(v);
            const max = modalMaxQty(v);
            const qty = Number(modalQuantities.get(id) || 0);
            const price = variantPrice(v, activeProduct);
            const selectedClass = qty > 0 ? 'row-selected' : '';
            const noStockClass = stock <= 0 && qty <= 0 ? 'row-empty-stock' : '';
            const disabled = stock <= 0 && qty <= 0 ? 'disabled' : '';

            const fullVariantTitle = [
                variantModel(v),
                variantDesign(v),
                variantName(v)
            ].filter(x => x && x !== '—').join(' / ') || '—';

            return `
                <div class="variant-card ${selectedClass} ${noStockClass}" data-row-variant="${id}">
                    <div>
                        <div class="variant-title">${esc(fullVariantTitle)}</div>
                        <div class="variant-subtitle">${esc(variantCode(v))}</div>

                        <div class="variant-meta">
                            <span class="badge-soft ${stock > 0 ? 'badge-stock' : 'badge-no-stock'}">
                                موجودی: ${stock > 0 ? formatNum(stock) : 'ناموجود'}
                            </span>
                            <span class="badge-soft">
                                قیمت: ${formatMoney(price)}
                            </span>
                        </div>
                    </div>

                    <div class="qty-control">
                        <button type="button" class="picker-minus" data-id="${id}" ${disabled}>−</button>
                        <input
                            type="number"
                            class="form-control form-control-sm picker-qty"
                            data-id="${id}"
                            data-price="${price}"
                            min="0"
                            max="${max}"
                            value="${qty}"
                            inputmode="numeric"
                            ${disabled}
                        >
                        <button type="button" class="picker-plus" data-id="${id}" data-step="1" ${disabled}>+</button>
                        <button type="button" class="quick-plus picker-plus" data-id="${id}" data-step="6" ${disabled}>+6</button>
                        <button type="button" class="quick-plus picker-plus" data-id="${id}" data-step="12" ${disabled}>+12</button>
                    </div>
                </div>
            `;
        }).join('');
    }

    function setModalQty(id, value) {
        id = Number(id);

        const item = activeModalItems.find(v => variantId(v) === id);
        if (!item) return;

        const max = modalMaxQty(item);
        let qty = parseInt(toEnglishDigits(value), 10);

        if (!Number.isFinite(qty)) qty = 0;
        if (qty < 0) qty = 0;
        if (qty > max) qty = max;

        modalQuantities.set(id, qty);

        const input = document.querySelector(`.picker-qty[data-id="${id}"]`);
        if (input) input.value = String(qty);

        const row = document.querySelector(`[data-row-variant="${id}"]`);
        if (row) row.classList.toggle('row-selected', qty > 0);

        updateModalSummary();
    }

    function changeModalQty(id, delta) {
        const current = Number(modalQuantities.get(Number(id)) || 0);
        setModalQty(id, current + Number(delta || 0));
    }

    function updateModalSummary() {
        let selectedRows = 0;
        let totalQty = 0;
        let totalAmount = 0;

        activeModalItems.forEach(v => {
            const id = variantId(v);
            const qty = Number(modalQuantities.get(id) || 0);
            const price = variantPrice(v, activeProduct);

            if (qty > 0) {
                selectedRows++;
                totalQty += qty;
                totalAmount += qty * price;
            }
        });

        modalGroupDiscountType = document.getElementById('modalGroupDiscountType')?.value || 'amount';
        modalGroupDiscountValue = safeDiscountValue(
            modalGroupDiscountType,
            document.getElementById('modalGroupDiscountValue')?.value || 0
        );

        const discount = calcDiscount(totalAmount, modalGroupDiscountType, modalGroupDiscountValue);

        document.getElementById('modalSelectedRows').textContent = formatNum(selectedRows);
        document.getElementById('modalTotalQty').textContent = formatNum(totalQty);
        document.getElementById('modalTotalAmount').textContent = formatMoney(Math.max(0, totalAmount - discount));

        const preview = document.getElementById('modalGroupDiscountPreview');
        if (preview) {
            preview.textContent = formatMoney(discount);
        }
    }

    function clearPickerQuantities() {
        if (!confirm('همه تعدادهای انتخاب‌شده پاک شود؟')) return;

        modalQuantities = new Map();
        renderPickerRows();
        updateModalSummary();
    }

    function saveGroupSelection() {
        if (!activeProductId || !activeProduct) return;

        const items = [];

        activeModalItems.forEach(v => {
            const id = variantId(v);
            const qty = Number(modalQuantities.get(id) || 0);

            if (qty > 0) {
                items.push({
                    variant_id: id,
                    quantity: qty,
                    price: variantPrice(v, activeProduct),
                    model: variantModel(v),
                    design: variantDesign(v),
                    variant: variantName(v),
                    code: variantCode(v),
                });
            }
        });

        if (!items.length) {
            alert('حداقل یک کالا را انتخاب کنید.');
            return;
        }

        const discountType = document.getElementById('modalGroupDiscountType')?.value || 'amount';
        const discountValue = safeDiscountValue(
            discountType,
            document.getElementById('modalGroupDiscountValue')?.value || 0
        );

        groupedSelections[activeProductId] = {
            product: {
                id: activeProductId,
                title: productTitle(activeProduct),
                code: productCode(activeProduct),
            },
            items,
            discount_type: discountType,
            discount_value: discountValue,
        };

        renderGroupSummary();
        updateTotal();

        bootstrap.Modal.getInstance(document.getElementById('groupPickerModal'))?.hide();

        document.getElementById('motherCodeInput').value = '';
        document.getElementById('motherProductBox').style.display = 'none';
        document.getElementById('motherSearchHint').style.display = '';
        selectedMotherProduct = null;

        setTimeout(() => document.getElementById('motherCodeInput').focus(), 100);
    }

    function deleteGroup(productId) {
        const group = groupedSelections[productId];
        if (!group) return;

        if (!confirm(`محصول «${group.product.title}» از سفارش حذف شود؟`)) return;

        delete groupedSelections[productId];
        renderGroupSummary();
        updateTotal();
    }

    function toggleGroupDetails(productId) {
        const card = document.querySelector(`[data-group-card="${productId}"]`);
        if (card) card.classList.toggle('is-open');
    }

    function renderGroupSummary() {
        const wrap = document.getElementById('groupSummaryList');
        const inputWrap = document.getElementById('groupProductsInputs');

        wrap.innerHTML = '';
        inputWrap.innerHTML = '';

        const groups = Object.values(groupedSelections);
        document.getElementById('orderItemsCountHint').textContent = formatNum(groups.length) + ' گروه انتخاب شده';

        if (!groups.length) {
            wrap.innerHTML = `
                <div class="empty-state">
                    هنوز کالایی به پیش‌فاکتور اضافه نشده است.
                </div>
            `;
            return;
        }

        let idx = 0;

        groups.forEach(group => {
            const productId = Number(group.product.id);
            const qty = group.items.reduce((s, it) => s + Number(it.quantity || 0), 0);
            const rowsCount = group.items.length;
            const subtotal = groupRawSubtotal(group);
            const discount = groupDiscountTotal(group);
            const finalAmount = groupFinalAmount(group);

            const details = group.items.map(it => `
                <div class="detail-pill">
                    <div class="fw-bold">${esc(it.model)} / ${esc(it.design)} / ${esc(it.variant)}</div>
                    <div class="text-muted mt-1">${esc(it.code)} | تعداد: ${formatNum(it.quantity)} | ${formatMoney(Number(it.quantity) * Number(it.price))}</div>
                </div>
            `).join('');

            wrap.insertAdjacentHTML('beforeend', `
                <div class="group-card mb-2" data-group-card="${productId}">
                    <div class="group-main">
                        <div>
                            <div class="group-title">${esc(group.product.title)}</div>
                            <div class="group-meta">
                                کد مادر: ${esc(group.product.code || '—')}
                                ${discount > 0 ? ' | تخفیف: ' + formatMoney(discount) : ''}
                            </div>
                        </div>

                        <div class="stat-box">
                            <div class="stat-label">ردیف</div>
                            <div class="stat-value">${formatNum(rowsCount)}</div>
                        </div>

                        <div class="stat-box">
                            <div class="stat-label">جمع تعداد</div>
                            <div class="stat-value">${formatNum(qty)}</div>
                        </div>

                        <div class="stat-box">
                            <div class="stat-label">مبلغ نهایی</div>
                            <div class="stat-value">${formatMoney(finalAmount)}</div>
                        </div>

                        <div class="d-flex gap-1 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-3" onclick="openGroupPicker(${productId})">ویرایش</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-3" onclick="toggleGroupDetails(${productId})">جزئیات</button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-3" onclick="deleteGroup(${productId})">حذف</button>
                        </div>
                    </div>

                    <div class="group-details">
                        <div class="mb-2 hint">
                            مبلغ خام: ${formatMoney(subtotal)}
                            ${discount > 0 ? ' | تخفیف محصول: ' + formatMoney(discount) : ''}
                        </div>
                        <div class="details-grid">
                            ${details}
                        </div>
                    </div>
                </div>
            `);

            group.items.forEach(item => {
                inputWrap.insertAdjacentHTML('beforeend', `
                    <input type="hidden" name="products[${idx}][id]" value="${productId}">
                    <input type="hidden" name="products[${idx}][variety_id]" value="${Number(item.variant_id)}">
                    <input type="hidden" name="products[${idx}][quantity]" value="${Number(item.quantity)}">
                    <input type="hidden" name="products[${idx}][price]" value="${Number(item.price)}">
                `);

                idx++;
            });
        });
    }

    function updateTotal() {
        const shipping = toInt(document.getElementById('shipping_price')?.value || 0);

        let subtotal = 0;
        let groupDiscounts = 0;

        Object.values(groupedSelections).forEach(group => {
            subtotal += groupRawSubtotal(group);
            groupDiscounts += groupDiscountTotal(group);
        });

        const afterGroupDiscount = Math.max(0, subtotal - groupDiscounts);

        const orderType = document.getElementById('orderDiscountType')?.value || 'amount';
        const orderValue = safeDiscountValue(orderType, document.getElementById('orderDiscountValue')?.value || 0);
        const orderDiscount = calcDiscount(afterGroupDiscount, orderType, orderValue);

        const totalDiscount = Math.min(subtotal, groupDiscounts + orderDiscount);
        const total = Math.max(0, subtotal + shipping - totalDiscount);

        document.getElementById('discount').value = String(totalDiscount);
        document.getElementById('totalDiscountView').value = formatMoney(totalDiscount);
        document.getElementById('total_price').value = formatMoney(total);

        const preview = document.getElementById('orderDiscountPreview');
        if (preview) {
            preview.textContent = 'تخفیف کلی: ' + formatMoney(orderDiscount);
        }
    }

    async function hydrateInitialGroups() {
        if (!INIT_ROWS || !INIT_ROWS.length) {
            renderGroupSummary();
            updateTotal();
            return;
        }

        const grouped = {};

        INIT_ROWS.forEach(row => {
            const productId = Number(row.id || row.product_id || 0);
            if (!productId) return;

            if (!grouped[productId]) grouped[productId] = [];
            grouped[productId].push(row);
        });

        for (const [productId, rows] of Object.entries(grouped)) {
            let product = null;

            try {
                product = await getProductDetails(productId);
            } catch (e) {}

            const varieties = getProductVarieties(product);

            const productName =
                productTitle(product) ||
                rows[0]?.product_name ||
                ('محصول #' + productId);

            const productShortCode =
                productCode(product) ||
                rows[0]?.product_code ||
                '';

            groupedSelections[Number(productId)] = {
                product: {
                    id: Number(productId),
                    title: productName,
                    code: productShortCode,
                },
                discount_type: 'amount',
                discount_value: 0,
                items: rows.map(row => {
                    const vid = Number(row.variety_id || row.variant_id || 0);
                    const v = varieties.find(item => variantId(item) === vid);

                    return {
                        variant_id: vid,
                        quantity: Number(row.quantity || 0),
                        price: Number(row.price || (v ? variantPrice(v, product) : 0)),
                        model: v ? variantModel(v) : '—',
                        design: v ? variantDesign(v) : '—',
                        variant: v ? variantName(v) : (row.variant_name || '—'),
                        code: v ? variantCode(v) : '—',
                    };
                }).filter(item => item.variant_id && item.quantity > 0)
            };
        }

        renderGroupSummary();
        updateTotal();
    }

    function submitGuard(e) {
        const customerName = normalize(document.getElementById('customer_name').value);
        const customerMobile = normalize(document.getElementById('customer_mobile').value);
        const productInputs = document.querySelectorAll('#groupProductsInputs input[name$="[quantity]"]');

        if (!customerName || !customerMobile) {
            e.preventDefault();
            alert('لطفا مشتری را انتخاب کنید.');
            return false;
        }

        if (!productInputs.length) {
            e.preventDefault();
            alert('حداقل یک کالا باید به سفارش اضافه شود.');
            return false;
        }

        const totalEl = document.getElementById('total_price');
        if (totalEl) totalEl.value = String(toInt(totalEl.value));

        const shipEl = document.getElementById('shipping_price');
        if (shipEl) shipEl.value = String(toInt(shipEl.value));

        const discEl = document.getElementById('discount');
        if (discEl) discEl.value = String(toInt(discEl.value));

        document.querySelectorAll('#groupProductsInputs input').forEach(input => {
            input.value = String(toInt(input.value));
        });

        const btn = document.getElementById('submitOrderBtn');
        btn.disabled = true;
        btn.textContent = 'در حال ثبت...';

        return true;
    }

    document.addEventListener('DOMContentLoaded', async function () {
        initSelect2Basic(document.getElementById('province_id'), 'انتخاب استان...');
        initSelect2Basic(document.getElementById('city_id'), 'انتخاب شهر...');

        await loadArea();
        fillProvincesSelect();

        if (OLD_PROVINCE_ID) {
            document.getElementById('province_id').value = String(OLD_PROVINCE_ID);
            if (window.jQuery) $('#province_id').trigger('change.select2');
            fillCitiesByProvinceId(OLD_PROVINCE_ID);
        }

        if (OLD_CITY_ID) {
            document.getElementById('city_id').value = String(OLD_CITY_ID);
            if (window.jQuery) $('#city_id').trigger('change.select2');
        }

        fillShippingSelect();

        if (OLD_SHIPPING_ID) {
            document.getElementById('shipping_id').value = String(OLD_SHIPPING_ID);
        }

        initCustomerSearch();
        await loadOldCustomer();

        document.getElementById('clearCustomerBtn')?.addEventListener('click', clearCustomer);

        document.getElementById('province_id')?.addEventListener('change', function () {
            fillCitiesByProvinceId(this.value);
        });

        document.getElementById('shipping_id')?.addEventListener('change', updateShippingMode);

        document.getElementById('orderDiscountType')?.addEventListener('change', updateTotal);
        document.getElementById('orderDiscountValue')?.addEventListener('input', updateTotal);

        document.getElementById('modalGroupDiscountType')?.addEventListener('change', updateModalSummary);
        document.getElementById('modalGroupDiscountValue')?.addEventListener('input', updateModalSummary);

        document.getElementById('motherCodeInput')?.addEventListener('input', function () {
            this.value = toEnglishDigits(this.value).replace(/\D/g, '').slice(0, 4);
        });

        document.getElementById('motherCodeInput')?.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                findMotherProductByCode();
            }
        });

        document.getElementById('findMotherBtn')?.addEventListener('click', findMotherProductByCode);
        document.getElementById('openGroupPickerBtn')?.addEventListener('click', () => openGroupPicker());

        document.getElementById('pickerSearchInput')?.addEventListener('input', renderPickerRows);
        document.getElementById('onlyInStockFilter')?.addEventListener('change', renderPickerRows);
        document.getElementById('onlySelectedFilter')?.addEventListener('change', renderPickerRows);
        document.getElementById('clearPickerQtyBtn')?.addEventListener('click', clearPickerQuantities);
        document.getElementById('saveGroupSelectionBtn')?.addEventListener('click', saveGroupSelection);

        document.getElementById('groupPickerRows')?.addEventListener('click', function (e) {
            const plus = e.target.closest('.picker-plus');
            const minus = e.target.closest('.picker-minus');

            if (plus) {
                changeModalQty(plus.dataset.id, Number(plus.dataset.step || 1));
            }

            if (minus) {
                changeModalQty(minus.dataset.id, -1);
            }
        });

        document.getElementById('groupPickerRows')?.addEventListener('input', function (e) {
            if (e.target.classList.contains('picker-qty')) {
                setModalQty(e.target.dataset.id, e.target.value);
            }
        });

        document.getElementById('orderForm')?.addEventListener('submit', submitGuard, { capture: true });

        await hydrateInitialGroups();

        updateShippingMode();

        setTimeout(() => document.getElementById('motherCodeInput')?.focus(), 200);
    });
</script>
@endsection