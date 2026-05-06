@extends('layouts.app')

@section('content')
@php
$currentSort = $sort ?? 'id';
$currentDir = $dir ?? 'desc';

$toFa = fn ($value) => strtr((string) $value, [
'0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
'5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
]);

$sortLink = function (string $key) use ($currentSort, $currentDir) {
$nextDir = ($currentSort === $key && $currentDir === 'asc') ? 'desc' : 'asc';

return route('products.index', array_merge(
request()->query(),
['sort' => $key, 'dir' => $nextDir, 'page' => null]
));
};

$sortArrow = function (string $key) use ($currentSort, $currentDir) {
if ($currentSort !== $key) {
return '↕';
}

return $currentDir === 'asc' ? '↑' : '↓';
};
@endphp

<style>
    :root {
        --brand: #14B5CC;
        --brand-dark: #0C5367;
        --brand-deep: #083D50;
        --accent: #F1AB27;
        --purple: #5B43E8;
        --pink: #E6459A;

        --bg: #F5F7FB;
        --card: #FFFFFF;
        --soft: #F8FAFC;
        --border: #E7ECF3;
        --text: #142B38;
        --muted: #748292;

        --success: #16A34A;
        --danger: #DC2626;
        --warning: #F59E0B;

        --radius: 22px;
        --radius-sm: 14px;
        --shadow: 0 18px 48px rgba(15, 23, 42, .07);
        --shadow-sm: 0 8px 24px rgba(15, 23, 42, .045);
    }

    body {
        background:
            radial-gradient(circle at 12% 8%, rgba(91, 67, 232, .08), transparent 34%),
            radial-gradient(circle at 85% 20%, rgba(20, 181, 204, .10), transparent 36%),
            linear-gradient(180deg, #F7F8FC 0%, #EEF3F8 100%);
        color: var(--text);
        font-size: 14px;
    }

    .inventory-page {
        max-width: 1280px;
        margin: 0 auto;
        padding: 22px 12px 36px;
    }

    .inventory-hero {
        border-radius: 28px;
        background: linear-gradient(135deg, var(--purple) 0%, #7B39E8 42%, var(--pink) 100%);
        box-shadow: 0 22px 55px rgba(91, 67, 232, .20);
        padding: 28px 32px;
        color: #fff;
        margin-bottom: 22px;
        position: relative;
        overflow: hidden;
    }

    .inventory-hero::before {
        content: "";
        position: absolute;
        width: 260px;
        height: 260px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .12);
        left: -85px;
        top: -120px;
    }

    .inventory-hero::after {
        content: "";
        position: absolute;
        width: 160px;
        height: 160px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .10);
        right: 26%;
        bottom: -95px;
    }

    .hero-content {
        position: relative;
        z-index: 1;
    }

    .hero-title {
        margin: 0;
        font-size: 1.45rem;
        font-weight: 900;
        letter-spacing: -.4px;
    }

    .hero-subtitle {
        margin-top: 8px;
        color: rgba(255, 255, 255, .82);
        font-size: .86rem;
        font-weight: 600;
    }

    .hero-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .hero-btn {
        border: 0;
        border-radius: 14px;
        padding: 10px 16px;
        font-weight: 850;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
        white-space: nowrap;
    }

    .hero-btn:hover {
        transform: translateY(-1px);
        opacity: .96;
    }

    .hero-btn-primary {
        background: #fff;
        color: var(--purple);
        box-shadow: 0 14px 26px rgba(15, 23, 42, .14);
    }

    .hero-btn-success {
        background: rgba(255, 255, 255, .16);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, .24);
    }

    .layout-row {
        display: grid;
        grid-template-columns: 260px minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }

    .glass-card {
        background: rgba(255, 255, 255, .94);
        border: 1px solid rgba(231, 236, 243, .92);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        backdrop-filter: blur(10px);
    }

    .side-card {
        position: sticky;
        top: 88px;
        padding: 18px;
    }

    .side-title {
        font-weight: 900;
        color: var(--brand-deep);
        margin: 0;
    }

    .side-link {
        font-size: .78rem;
        font-weight: 800;
        color: var(--purple);
        text-decoration: none;
    }

    .side-link:hover {
        text-decoration: underline;
    }

    .cat-search {
        height: 42px;
        border-radius: 14px;
        border: 1px solid var(--border);
        background: #fff;
        font-size: .84rem;
    }

    .cat-tree-wrap {
        max-height: calc(100vh - 270px);
        overflow: auto;
        padding-left: 4px;
        padding-right: 2px;
    }

    .cat-tree-wrap::-webkit-scrollbar,
    .sheet-scroll::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .cat-tree-wrap::-webkit-scrollbar-thumb,
    .sheet-scroll::-webkit-scrollbar-thumb {
        background: #CDD6E3;
        border-radius: 99px;
    }

    .main-shell {
        min-width: 0;
    }

    .toolbar-card {
        padding: 18px;
        margin-bottom: 16px;
    }

    .toolbar-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }

    .section-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 900;
        color: var(--brand-deep);
    }

    .subtle-text {
        color: var(--muted);
        font-size: .8rem;
        line-height: 1.8;
    }

    .operation-strip {
        background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFF 100%);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 12px;
        display: grid;
        grid-template-columns: minmax(170px, 1fr) auto;
        gap: 10px;
        align-items: center;
        margin-bottom: 14px;
    }

    .selected-info {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
    }

    .selected-dot {
        width: 34px;
        height: 34px;
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(91, 67, 232, .12), rgba(20, 181, 204, .16));
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--purple);
        font-weight: 900;
        flex: 0 0 auto;
    }

    .selected-title {
        font-weight: 900;
        font-size: .86rem;
        color: var(--brand-deep);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .selected-hint {
        font-size: .74rem;
        color: var(--muted);
        margin-top: 2px;
    }

    .operation-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .variant-operation-select {
        width: 190px;
        height: 36px;
        border-radius: 12px;
        font-size: .78rem;
    }

    .btn-mini {
        border-radius: 12px;
        padding: 7px 11px;
        font-size: .78rem;
        font-weight: 850;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        white-space: nowrap;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: minmax(240px, 1.5fr) minmax(140px, .75fr) minmax(140px, .75fr) minmax(160px, .9fr) auto;
        gap: 10px;
        align-items: end;
    }

    .label-sm {
        font-size: .76rem;
        font-weight: 850;
        color: #526171;
        margin-bottom: 6px;
    }

    .form-control,
    .form-select {
        border-color: var(--border);
        border-radius: 13px;
        color: var(--text);
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--brand);
        box-shadow: 0 0 0 .18rem rgba(20, 181, 204, .13);
    }

    .input-group .form-control:first-child {
        border-top-right-radius: 13px;
        border-bottom-right-radius: 13px;
    }

    .input-group .form-control:last-child {
        border-top-left-radius: 13px;
        border-bottom-left-radius: 13px;
    }

    .product-card {
        overflow: hidden;
    }

    .product-card-head {
        padding: 17px 20px;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        background: linear-gradient(180deg, #FFFFFF, #FAFBFF);
    }

    .count-badge {
        background: rgba(91, 67, 232, .08);
        color: var(--purple);
        border: 1px solid rgba(91, 67, 232, .14);
        border-radius: 999px;
        padding: 6px 12px;
        font-size: .78rem;
        font-weight: 850;
    }

    .sheet-wrap {
        padding: 16px;
    }

    .sheet-scroll {
        overflow: auto;
        border: 1px solid var(--border);
        border-radius: 18px;
        background: #fff;
    }

    .sheet {
        margin: 0;
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        min-width: 930px;
    }

    .sheet thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #FBFCFF;
        color: #516070;
        font-size: .76rem;
        font-weight: 900;
        padding: 12px 10px;
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
        text-align: right;
    }

    .sheet tbody td {
        padding: 12px 10px;
        font-size: .84rem;
        vertical-align: middle;
        background: #fff;
        border-bottom: 1px solid #F1F4F8;
    }

    .sheet tbody tr:hover td {
        background: #FBFDFF;
    }

    .sheet tbody tr:last-child td {
        border-bottom: 0;
    }

    .w-1 {
        width: 1%;
        white-space: nowrap;
    }

    .nowrap {
        white-space: nowrap;
    }

    .mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        letter-spacing: .4px;
        direction: ltr;
    }

    .sortable-link {
        color: inherit;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .sortable-link:hover {
        color: var(--purple);
    }

    .sort-arrow {
        color: #99A3B0;
        font-size: .72rem;
    }

    .pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: .75rem;
        font-weight: 850;
        border: 1px solid var(--border);
        background: #fff;
        white-space: nowrap;
    }

    .pill-gray {
        background: #F8FAFC;
        color: #4B5563;
    }

    .pill-danger {
        background: rgba(220, 38, 38, .08);
        border-color: rgba(220, 38, 38, .18);
        color: #B91C1C;
    }

    .pill-success {
        background: rgba(22, 163, 74, .10);
        border-color: rgba(22, 163, 74, .18);
        color: #15803D;
    }

    .pill-purple {
        background: rgba(91, 67, 232, .08);
        border-color: rgba(91, 67, 232, .16);
        color: var(--purple);
    }

    .product-name-wrap {
        display: flex;
        flex-direction: column;
        gap: 7px;
        min-width: 240px;
    }

    .product-title-text {
        font-weight: 900;
        color: var(--brand-deep);
        line-height: 1.6;
    }

    .sellable-state {
        display: flex;
        align-items: center;
        gap: 7px;
        flex-wrap: wrap;
    }

    .sellable-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 4px 9px;
        font-size: .72rem;
        font-weight: 850;
        border: 1px solid transparent;
    }

    .sellable-badge.active {
        color: #166534;
        background: rgba(34, 197, 94, .14);
        border-color: rgba(22, 101, 52, .16);
    }

    .sellable-badge.inactive {
        color: #991B1B;
        background: rgba(239, 68, 68, .12);
        border-color: rgba(153, 27, 27, .16);
    }

    .sellable-action-link {
        font-size: .72rem;
        font-weight: 800;
        text-decoration: none;
    }

    .sellable-action-link:hover {
        text-decoration: underline;
    }

    .price-inline {
        white-space: nowrap;
        font-weight: 900;
        color: var(--brand-deep);
    }

    .buy-price-muted {
        color: #9CA3AF;
    }

    .toggle-variants {
        width: 32px;
        height: 32px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border);
        background: #fff;
        color: var(--purple);
        font-weight: 900;
        transition: all .15s ease;
    }

    .toggle-variants:hover {
        background: rgba(91, 67, 232, .08);
        border-color: rgba(91, 67, 232, .20);
    }

    .variant-inner {
        background: #FAFCFF;
        border-radius: 16px;
        border: 1px solid #EDF2F7;
        padding: 12px;
    }

    .variant-table {
        margin: 0;
        font-size: .8rem;
    }

    .variant-table th {
        color: #64748B;
        font-weight: 900;
        font-size: .74rem;
        white-space: nowrap;
    }

    .empty-row {
        color: var(--muted);
        padding: 52px 0 !important;
        font-weight: 800;
    }

    .pagination {
        margin-bottom: 0;
    }

    .modal-content {
        border: 0;
        border-radius: 22px;
        box-shadow: 0 28px 70px rgba(15, 23, 42, .18);
    }

    .modal-header {
        background: linear-gradient(135deg, var(--purple), var(--pink));
        color: #fff;
        border-bottom: 0;
        border-radius: 22px 22px 0 0;
    }

    .modal-header .btn-close {
        filter: invert(1);
        opacity: .9;
    }

    .modal-title {
        font-weight: 900;
    }

    .offcanvas {
        border-top-left-radius: 22px;
        border-bottom-left-radius: 22px;
    }

    @media (max-width: 1199.98px) {
        .layout-row {
            grid-template-columns: 230px minmax(0, 1fr);
        }

        .filter-grid {
            grid-template-columns: 1fr 1fr;
        }

        .filter-actions {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 991.98px) {
        .inventory-page {
            padding: 14px 10px 28px;
        }

        .layout-row {
            display: block;
        }

        .inventory-hero {
            padding: 22px 18px;
            border-radius: 22px;
        }

        .hero-title {
            font-size: 1.15rem;
        }

        .toolbar-card,
        .sheet-wrap {
            padding: 12px;
        }

        .operation-strip {
            grid-template-columns: 1fr;
        }

        .operation-actions {
            justify-content: flex-start;
        }

        .variant-operation-select {
            width: min(100%, 240px);
        }
    }

    @media (max-width: 575.98px) {
        body {
            font-size: 13px;
        }

        .inventory-hero {
            margin-bottom: 14px;
        }

        .hero-actions {
            width: 100%;
        }

        .hero-btn {
            width: 100%;
            justify-content: center;
        }

        .filter-grid {
            grid-template-columns: 1fr;
        }

        .operation-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            width: 100%;
        }

        .operation-actions .variant-operation-select {
            grid-column: 1 / -1;
            width: 100%;
        }

        .operation-actions .btn-mini {
            justify-content: center;
        }

        .product-card-head {
            padding: 14px;
        }

        .sheet-wrap {
            padding: 10px;
        }
    }
</style>

<div class="inventory-page">
    <div class="inventory-hero">
        <div class="hero-content d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="hero-title">📦 مدیریت کالاها و موجودی</h1>
                <div class="hero-subtitle">
                    نمایش کالاها، کنترل موجودی، قیمت‌ها، تنوع‌ها، کارتکس فروش و عملیات سریع
                </div>
            </div>

            <div class="hero-actions">
                <a class="hero-btn hero-btn-success" href="{{ route('purchases.create') }}">
                    ➕ خرید کالا
                </a>

                <a class="hero-btn hero-btn-primary" href="{{ route('products.create') }}">
                    افزودن کالا
                </a>

                <button class="hero-btn hero-btn-success d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#catOffcanvas">
                    دسته‌بندی‌ها
                </button>
            </div>
        </div>
    </div>

    <div class="layout-row">
        <aside class="d-none d-lg-block">
            <div class="glass-card side-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="side-title fs-6">دسته‌بندی‌ها</h2>
                    <a href="{{ route('products.index', request()->except(['category_id', 'page'])) }}" class="side-link">همه</a>
                </div>

                <input type="text" id="catSearch" class="form-control cat-search mb-3" placeholder="جستجو در دسته‌ها...">

                <div class="cat-tree-wrap" id="catTree">
                    @include('categories._tree', ['nodes' => $categoryTree])
                </div>
            </div>
        </aside>

        <main class="main-shell">
            <div id="productsAjaxArea">
                <div class="glass-card toolbar-card">
                    <div class="toolbar-top">
                        <div>
                            <h2 class="section-title">عملیات و فیلتر کالاها</h2>
                            <div class="subtle-text">برای عملیات سریع، فقط یک کالا را انتخاب کنید.</div>
                        </div>

                        <div class="count-badge">
                            {{ $toFa($products->total() ?? 0) }} کالا
                        </div>
                    </div>

                    <div class="operation-strip">
                        <div class="selected-info">
                            <div class="selected-dot" id="selectedCountBadge">۰</div>
                            <div class="min-w-0">
                                <div class="selected-title" id="selectedProductTitle">هیچ کالایی انتخاب نشده است</div>
                                <div class="selected-hint" id="variantHelpText">برای انتخاب تنوع، ابتدا فقط یک کالا را تیک بزنید.</div>
                            </div>
                        </div>

                        <div class="operation-actions">
                            <button class="btn btn-primary btn-mini" type="button" id="bulkEditBtn">
                                ویرایش
                            </button>

                            <button class="btn btn-outline-danger btn-mini" type="button" id="bulkDeleteBtn">
                                حذف
                            </button>

                            <select id="bulkVariantSelect" class="form-select form-select-sm variant-operation-select" disabled>
                                <option value="">تنوع محصول...</option>
                            </select>

                            <button class="btn btn-outline-primary btn-mini" type="button" id="bulkStockBtn">
                                موجودی انبار
                            </button>

                            <button class="btn btn-outline-secondary btn-mini" type="button" id="bulkSalesLedgerBtn">
                                کارتکس فروش
                            </button>

                            <button class="btn btn-outline-secondary btn-mini" type="button" id="bulkPurchaseLedgerBtn">
                                🧾 کارتکس خرید
                            </button>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('products.index') }}">
                        @if(request('category_id'))
                        <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                        @endif

                        <div class="filter-grid">
                            <div>
                                <label class="label-sm">جستجو</label>
                                <input
                                    name="q"
                                    class="form-control"
                                    value="{{ request('q') }}"
                                    placeholder="نام / کد ۴ رقمی / بارکد محصول...">
                            </div>

                            <div>
                                <label class="label-sm">وضعیت موجودی</label>
                                <select name="stock_status" class="form-select">
                                    <option value="" @selected(request('stock_status')==='' || is_null(request('stock_status')))>همه</option>
                                    <option value="out" @selected(request('stock_status')==='out' )>ناموجود</option>
                                </select>
                            </div>

                            <div>
                                <label class="label-sm">وضعیت فروش</label>
                                <select name="sellable_status" class="form-select">
                                    <option value="" @selected(request('sellable_status')==='' || is_null(request('sellable_status')))>همه</option>
                                    <option value="sellable" @selected(request('sellable_status')==='sellable' )>قابل فروش</option>
                                    <option value="unsellable" @selected(request('sellable_status')==='unsellable' )>غیرفعال فروش</option>
                                </select>
                            </div>

                            <div>
                                <label class="label-sm">بازه قیمت</label>
                                <div class="input-group">
                                    <input name="min_price" class="form-control money" value="{{ request('min_price') }}" placeholder="از">
                                    <input name="max_price" class="form-control money" value="{{ request('max_price') }}" placeholder="تا">
                                </div>
                            </div>

                            <div class="filter-actions d-flex gap-2">
                                <button class="btn btn-primary btn-mini px-3">اعمال</button>
                                <a class="btn btn-outline-secondary btn-mini px-3" href="{{ route('products.index') }}">پاک</a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="glass-card product-card">
                    <div class="product-card-head">
                        <div>
                            <h2 class="section-title">لیست کالاها</h2>
                            <div class="subtle-text">
                                نمایش {{ $toFa($products->firstItem() ?? 0) }} تا {{ $toFa($products->lastItem() ?? 0) }} از {{ $toFa($products->total() ?? 0) }} مورد
                            </div>
                        </div>

                        <span class="pill pill-purple">
                            آخرین بروزرسانی لیست
                        </span>
                    </div>

                    <div class="sheet-wrap">
                        <div class="sheet-scroll">
                            <table class="sheet">
                                <thead>
                                    <tr>
                                        <th class="w-1 text-center">
                                            <input type="checkbox" class="form-check-input" id="selectAllProducts" title="انتخاب همه">
                                        </th>
                                        <th class="w-1"></th>
                                        <th class="nowrap">
                                            <a href="{{ $sortLink('short_barcode') }}" class="sortable-link">
                                                کد کالا
                                                <span class="sort-arrow">{{ $sortArrow('short_barcode') }}</span>
                                            </a>
                                        </th>
                                        <th class="nowrap">
                                            <a href="{{ $sortLink('barcode') }}" class="sortable-link">
                                                بارکد
                                                <span class="sort-arrow">{{ $sortArrow('barcode') }}</span>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ $sortLink('name') }}" class="sortable-link">
                                                اسم کالا
                                                <span class="sort-arrow">{{ $sortArrow('name') }}</span>
                                            </a>
                                        </th>
                                        <th class="nowrap">
                                            <a href="{{ $sortLink('stock') }}" class="sortable-link">
                                                موجودی
                                                <span class="sort-arrow">{{ $sortArrow('stock') }}</span>
                                            </a>
                                        </th>
                                        <th class="nowrap">
                                            <a href="{{ $sortLink('variants_buy_price_min') }}" class="sortable-link">
                                                قیمت خرید
                                                <span class="sort-arrow">{{ $sortArrow('variants_buy_price_min') }}</span>
                                            </a>
                                        </th>
                                        <th class="nowrap">
                                            <a href="{{ $sortLink('price') }}" class="sortable-link">
                                                قیمت فروش
                                                <span class="sort-arrow">{{ $sortArrow('price') }}</span>
                                            </a>
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse($products as $p)
                                    @php
                                    $hasVariants = $p->variants && $p->variants->count() > 0;
                                    $collapseId = 'variantsRow' . $p->id;

                                    $short = $p->short_barcode;
                                    if (!$short && !empty($p->code) && strlen($p->code) >= 6) {
                                    $short = substr($p->code, 2, 4);
                                    }

                                    $sampleBarcode = null;
                                    if ($hasVariants) {
                                    $firstVar = $p->variants->sortBy('variant_code')->first();
                                    $sampleBarcode = $firstVar?->variant_code;
                                    }

                                    $variantsPayload = $p->variants
                                    ->sortBy('variant_code')
                                    ->values()
                                    ->map(function ($v) {
                                    return [
                                    'id' => (int) $v->id,
                                    'name' => $v->variant_name,
                                    'stock' => (int) $v->stock,
                                    'is_active' => (bool) $v->is_active,
                                    ];
                                    })
                                    ->all();

                                    $stockBreakdownPayload = $p->warehouseStocks
                                    ->map(function ($ws) {
                                    return [
                                    'warehouse' => $ws->warehouse?->name,
                                    'qty' => (int) $ws->quantity,
                                    ];
                                    })
                                    ->values()
                                    ->all();

                                    $buyPrice = $p->variants_min_buy_price;
                                    @endphp

                                    <tr>
                                        <td class="text-center">
                                            <input
                                                type="checkbox"
                                                class="form-check-input product-checkbox"
                                                value="{{ $p->id }}"
                                                data-edit-url="{{ route('products.edit', $p) }}"
                                                data-delete-url="{{ route('products.destroy', $p) }}"
                                                data-sales-ledger-url="{{ route('products.sales-ledger', $p) }}"
                                                data-purchase-ledger-url="{{ route('products.purchase-ledger', $p) }}"
                                                data-product-name="{{ $p->name }}"
                                                data-variants='@json($variantsPayload)'
                                                data-stock-breakdown='@json($stockBreakdownPayload)'>
                                        </td>

                                        <td class="w-1">
                                            @if($hasVariants)
                                            <button
                                                class="toggle-variants"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#{{ $collapseId }}"
                                                aria-expanded="false"
                                                aria-controls="{{ $collapseId }}"
                                                title="نمایش تنوع‌ها">
                                                <span class="variant-symbol">+</span>
                                            </button>
                                            @else
                                            <span class="text-muted">—</span>
                                            @endif
                                        </td>

                                        <td class="nowrap mono">
                                            <span class="pill pill-gray">{{ $short ?: '—' }}</span>
                                        </td>

                                        <td class="nowrap mono">
                                            @if($sampleBarcode)
                                            <span class="pill pill-gray">{{ $sampleBarcode }}</span>
                                            @else
                                            <span class="pill pill-gray">{{ $p->barcode ?: '—' }}</span>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="product-name-wrap">
                                                <div class="product-title-text">{{ $p->name }}</div>

                                                <div class="sellable-state">
                                                    @if($p->is_sellable ?? true)
                                                    <span class="sellable-badge active">قابل فروش</span>
                                                    <a href="{{ route('product-deactivation-documents.create') }}" class="sellable-action-link text-danger">
                                                        غیرفعال‌سازی
                                                    </a>
                                                    @else
                                                    <span class="sellable-badge inactive">غیرفعال فروش</span>
                                                    <a href="{{ route('product-deactivation-documents.index', ['product_name' => $p->name]) }}" class="sellable-action-link text-secondary">
                                                        سوابق
                                                    </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <td class="nowrap">
                                            <span class="pill {{ ((int) $p->stock) === 0 ? 'pill-danger' : 'pill-success' }}">
                                                {{ $toFa($p->stock ?? 0) }}
                                            </span>
                                        </td>

                                        <td class="nowrap">
                                            @if(!is_null($buyPrice))
                                            <span class="price-inline">{{ $toFa(number_format((int) $buyPrice) . ' تومان') }}</span>
                                            @else
                                            <span class="buy-price-muted">—</span>
                                            @endif
                                        </td>

                                        <td class="nowrap">
                                            <span class="price-inline">{{ $toFa(number_format((int) $p->price) . ' تومان') }}</span>
                                        </td>
                                    </tr>

                                    @if($hasVariants)
                                    <tr class="collapse" id="{{ $collapseId }}">
                                        <td></td>
                                        <td colspan="7">
                                            <div class="variant-inner">
                                                <div class="table-responsive">
                                                    <table class="table table-sm variant-table">
                                                        <thead>
                                                            <tr>
                                                                <th>نام تنوع</th>
                                                                <th class="nowrap">بارکد ۱۱</th>
                                                                <th class="nowrap">موجودی</th>
                                                                <th class="nowrap">فروش</th>
                                                                <th class="nowrap">خرید</th>
                                                                <th class="nowrap">وضعیت</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($p->variants->sortBy('variant_code') as $v)
                                                            <tr>
                                                                <td class="fw-bold">{{ $v->variant_name }}</td>
                                                                <td class="mono">{{ $v->variant_code }}</td>
                                                                <td>
                                                                    @if((int) $v->stock === 0)
                                                                    <span class="pill pill-danger">۰</span>
                                                                    @else
                                                                    <span class="pill pill-success">{{ $toFa($v->stock) }}</span>
                                                                    @endif
                                                                </td>
                                                                <td>{{ $toFa(number_format((int) $v->sell_price) . ' تومان') }}</td>
                                                                <td>{{ $v->buy_price !== null ? $toFa(number_format((int) $v->buy_price) . ' تومان') : '—' }}</td>
                                                                <td>
                                                                    <span class="badge {{ $v->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                                        {{ $v->is_active ? 'فعال' : 'غیرفعال' }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center empty-row">هیچ کالایی ثبت نشده 📦</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $products->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="catOffcanvas" aria-labelledby="catOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold" id="catOffcanvasLabel">دسته‌بندی‌ها</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-bold">انتخاب دسته‌بندی</div>
            <a href="{{ route('products.index', request()->except(['category_id', 'page'])) }}" class="side-link">همه</a>
        </div>

        <input type="text" id="catSearchMobile" class="form-control cat-search mb-3" placeholder="جستجو در دسته‌ها...">

        <div class="cat-tree-wrap" id="catTreeMobile">
            @include('categories._tree', ['nodes' => $categoryTree])
        </div>

        <div class="small subtle-text mt-3">
            افزودن دسته‌بندی از منوی «دسته‌بندی‌ها» در سایدبار انجام می‌شود.
        </div>
    </div>
</div>

<form id="bulkDeleteForm" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>

<div class="modal fade" id="stockBreakdownModal" tabindex="-1" aria-labelledby="stockBreakdownLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockBreakdownLabel">موجودی کالا به تفکیک انبار</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>

            <div class="modal-body">
                <div id="stockBreakdownProductName" class="fw-bold mb-3"></div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>انبار</th>
                                <th class="text-end">تعداد</th>
                            </tr>
                        </thead>
                        <tbody id="stockBreakdownBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('stockBreakdownModal');
        const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
        const stockNameEl = document.getElementById('stockBreakdownProductName');
        const stockBodyEl = document.getElementById('stockBreakdownBody');
        const deleteForm = document.getElementById('bulkDeleteForm');

        function faNumber(value) {
            return String(value ?? '').replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹' [d]);
        }

        function parseJsonDataset(raw, fallback = []) {
            if (!raw) return fallback;

            try {
                return JSON.parse(raw);
            } catch (e) {
                return fallback;
            }
        }

        function freshVariantSelect() {
            return document.getElementById('bulkVariantSelect');
        }

        function freshVariantHelp() {
            return document.getElementById('variantHelpText');
        }

        function freshSelectedTitle() {
            return document.getElementById('selectedProductTitle');
        }

        function freshSelectedBadge() {
            return document.getElementById('selectedCountBadge');
        }

        function bindCatSearch(inputId, treeId) {
            const input = document.getElementById(inputId);
            const tree = document.getElementById(treeId);
            if (!input || !tree) return;

            input.addEventListener('input', function() {
                const q = this.value.trim().toLowerCase();

                tree.querySelectorAll('a').forEach(a => {
                    const text = (a.textContent || '').trim().toLowerCase();
                    const li = a.closest('li');
                    if (!li) return;
                    li.style.display = (q === '' || text.includes(q)) ? '' : 'none';
                });
            });
        }

        async function loadProducts(url, pushState = true) {
            const area = document.getElementById('productsAjaxArea');
            if (!area) return;

            area.style.opacity = '0.55';

            try {
                const res = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const html = await res.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const incomingArea = doc.getElementById('productsAjaxArea');

                if (!incomingArea) {
                    window.location.href = url;
                    return;
                }

                area.innerHTML = incomingArea.innerHTML;

                if (pushState) {
                    history.pushState({}, '', url);
                }

                initAjaxBindings();
            } catch (e) {
                window.location.href = url;
            } finally {
                area.style.opacity = '1';
            }
        }

        function getSelectedProducts() {
            return Array.from(document.querySelectorAll('.product-checkbox')).filter(ch => ch.checked);
        }

        function getSingleSelected() {
            const selected = getSelectedProducts();

            if (selected.length !== 1) {
                alert('برای این عملیات باید دقیقا یک کالا انتخاب شود.');
                return null;
            }

            return selected[0];
        }

        function updateVariantSelectState() {
            const variantSelectEl = freshVariantSelect();
            const variantHelpTextEl = freshVariantHelp();
            const selectedTitleEl = freshSelectedTitle();
            const selectedBadgeEl = freshSelectedBadge();

            if (!variantSelectEl) return;

            const selected = getSelectedProducts();

            if (selectedBadgeEl) {
                selectedBadgeEl.textContent = faNumber(selected.length);
            }

            if (!selected.length) {
                if (selectedTitleEl) {
                    selectedTitleEl.textContent = 'هیچ کالایی انتخاب نشده است';
                }

                variantSelectEl.innerHTML = '<option value="">تنوع محصول...</option>';
                variantSelectEl.disabled = true;

                if (variantHelpTextEl) {
                    variantHelpTextEl.textContent = 'برای انتخاب تنوع، ابتدا فقط یک کالا را تیک بزنید.';
                }

                return;
            }

            if (selected.length > 1) {
                if (selectedTitleEl) {
                    selectedTitleEl.textContent = faNumber(selected.length) + ' کالا انتخاب شده است';
                }

                variantSelectEl.innerHTML = '<option value="">تنوع محصول...</option>';
                variantSelectEl.disabled = true;

                if (variantHelpTextEl) {
                    variantHelpTextEl.textContent = 'عملیات ویرایش، حذف، موجودی، کارتکس فروش و کارتکس خرید فقط برای یک کالا انجام می‌شود.';
                }

                return;
            }

            const item = selected[0];

            if (selectedTitleEl) {
                selectedTitleEl.textContent = item.dataset.productName || 'کالای انتخاب شده';
            }

            const variants = parseJsonDataset(item.dataset.variants, []);

            variantSelectEl.innerHTML = '<option value="">تنوع محصول...</option>';

            if (!variants.length) {
                variantSelectEl.innerHTML = '<option value="">این کالا تنوعی ندارد</option>';
                variantSelectEl.disabled = true;

                if (variantHelpTextEl) {
                    variantHelpTextEl.textContent = 'این کالا تنوع ثبت‌شده‌ای ندارد؛ عملیات روی کل کالا انجام می‌شود.';
                }

                return;
            }

            variantSelectEl.disabled = false;

            variants.forEach(variant => {
                const option = document.createElement('option');
                option.value = String(variant.id);
                option.textContent = `${variant.name ?? 'تنوع'} | موجودی: ${variant.stock ?? 0}`;
                variantSelectEl.appendChild(option);
            });

            if (variantHelpTextEl) {
                variantHelpTextEl.textContent = 'برای موجودی انبار، کارتکس فروش یا کارتکس خرید، در صورت نیاز تنوع را انتخاب کنید.';
            }
        }

        function initAjaxBindings() {
            document.querySelectorAll('.toggle-variants').forEach(btn => {
                const targetSel = btn.getAttribute('data-bs-target');
                const el = document.querySelector(targetSel);
                if (!el) return;

                const symbol = btn.querySelector('.variant-symbol');

                const setSymbol = () => {
                    symbol.textContent = el.classList.contains('show') ? '−' : '+';
                };

                setSymbol();
                el.addEventListener('shown.bs.collapse', setSymbol);
                el.addEventListener('hidden.bs.collapse', setSymbol);
            });

            const selectAll = document.getElementById('selectAllProducts');
            const productCheckboxes = Array.from(document.querySelectorAll('.product-checkbox'));

            selectAll?.addEventListener('change', function() {
                productCheckboxes.forEach(ch => ch.checked = this.checked);
                updateVariantSelectState();
            });

            productCheckboxes.forEach(ch => ch.addEventListener('change', updateVariantSelectState));

            const form = document.querySelector('#productsAjaxArea form[method="GET"]');

            form?.addEventListener('submit', function(e) {
                e.preventDefault();
                const params = new URLSearchParams(new FormData(this));
                loadProducts(`${this.action}?${params.toString()}`);
            });

            document.querySelectorAll('#productsAjaxArea a.sortable-link, #productsAjaxArea .pagination a, #catTree a, #catTreeMobile a').forEach(link => {
                if (link.dataset.ajaxBound === '1') return;

                link.dataset.ajaxBound = '1';

                link.addEventListener('click', function(e) {
                    if (!this.href || !this.href.includes('/products')) return;

                    e.preventDefault();
                    loadProducts(this.href);
                });
            });

            const clearBtn = document.querySelector('#productsAjaxArea a.btn-outline-secondary[href*="products"]');

            if (clearBtn && clearBtn.dataset.ajaxBound !== '1') {
                clearBtn.dataset.ajaxBound = '1';

                clearBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    loadProducts(this.href);
                });
            }

            document.getElementById('bulkEditBtn')?.addEventListener('click', function() {
                const selected = getSingleSelected();
                if (!selected) return;
                window.location.href = selected.dataset.editUrl;
            });

            document.getElementById('bulkDeleteBtn')?.addEventListener('click', function() {
                const selected = getSingleSelected();
                if (!selected) return;

                if (!confirm(`کالای «${selected.dataset.productName}» حذف شود؟`)) {
                    return;
                }

                deleteForm.setAttribute('action', selected.dataset.deleteUrl);
                deleteForm.submit();
            });

            document.getElementById('bulkStockBtn')?.addEventListener('click', function() {
                const selected = getSingleSelected();
                const variantSelectEl = freshVariantSelect();

                if (!selected || !modal || !stockBodyEl || !stockNameEl) return;

                const variants = parseJsonDataset(selected.dataset.variants, []);
                const selectedVariantId = variantSelectEl?.value ? Number(variantSelectEl.value) : null;
                const selectedVariant = selectedVariantId ? variants.find(v => Number(v.id) === selectedVariantId) : null;

                if (variants.length && !selectedVariant) {
                    alert('ابتدا تنوع محصول را انتخاب کنید.');
                    variantSelectEl?.focus();
                    return;
                }

                stockNameEl.textContent = selectedVariant ?
                    `${selected.dataset.productName} — ${selectedVariant.name ?? 'تنوع انتخابی'}` :
                    selected.dataset.productName;

                stockBodyEl.innerHTML = '';

                const breakdown = parseJsonDataset(selected.dataset.stockBreakdown, []);
                const variantTotal = Number(selectedVariant?.stock ?? 0);
                let limitedBreakdown = breakdown;

                if (selectedVariant) {
                    let remaining = variantTotal;

                    limitedBreakdown = breakdown
                        .filter(item => Number(item.qty ?? 0) > 0)
                        .map(item => {
                            if (remaining <= 0) return null;

                            const qty = Math.min(Number(item.qty ?? 0), remaining);
                            remaining -= qty;

                            return {
                                warehouse: item.warehouse,
                                qty: qty,
                            };
                        })
                        .filter(Boolean);
                }

                if (!limitedBreakdown.length) {
                    stockBodyEl.innerHTML = '<tr><td colspan="2" class="text-center text-muted">برای این کالا در انبارها موجودی ثبت نشده است.</td></tr>';
                } else {
                    limitedBreakdown.forEach(item => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `<td>${item.warehouse ?? '—'}</td><td class="text-end fw-bold">${item.qty ?? 0}</td>`;
                        stockBodyEl.appendChild(tr);
                    });

                    if (selectedVariant) {
                        const hintRow = document.createElement('tr');
                        hintRow.innerHTML = `<td colspan="2" class="small text-muted pt-2">جمع موجودی تنوع «${selectedVariant.name ?? 'انتخابی'}»: ${selectedVariant.stock ?? 0}</td>`;
                        stockBodyEl.appendChild(hintRow);
                    }
                }

                modal.show();
            });


            document.getElementById('bulkPurchaseLedgerBtn')?.addEventListener('click', function() {
                const selected = getSingleSelected();
                const variantSelectEl = freshVariantSelect();

                if (!selected) return;

                const baseUrl = selected.dataset.purchaseLedgerUrl;
                if (!baseUrl) return;

                const params = new URLSearchParams();
                const selectedVariantId = variantSelectEl?.value ? String(variantSelectEl.value) : '';

                if (selectedVariantId) {
                    params.set('variant_id', selectedVariantId);
                }

                window.location.href = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;
            });

            document.getElementById('bulkSalesLedgerBtn')?.addEventListener('click', function() {
                const selected = getSingleSelected();
                const variantSelectEl = freshVariantSelect();

                if (!selected) return;

                const baseUrl = selected.dataset.salesLedgerUrl;
                if (!baseUrl) return;

                const params = new URLSearchParams();
                const selectedVariantId = variantSelectEl?.value ? String(variantSelectEl.value) : '';

                if (selectedVariantId) {
                    params.set('variant_id', selectedVariantId);
                }

                window.location.href = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;
            });

            updateVariantSelectState();
        }

        bindCatSearch('catSearch', 'catTree');
        bindCatSearch('catSearchMobile', 'catTreeMobile');
        initAjaxBindings();

        window.addEventListener('popstate', function() {
            loadProducts(window.location.href, false);
        });
    });
</script>
@endsection