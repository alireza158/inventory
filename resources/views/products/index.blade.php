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
        --brand: #0EA5B7;
        --brand-dark: #0F5965;
        --brand-deep: #12343D;

        --bg: #F4F6F8;
        --card: #FFFFFF;
        --soft: #F8FAFC;
        --border: #E2E8F0;
        --border-soft: #EEF2F7;

        --text: #1F2937;
        --muted: #6B7280;

        --success: #16A34A;
        --danger: #DC2626;

        --radius: 14px;
        --shadow: 0 8px 24px rgba(15, 23, 42, .05);
    }

    body {
        background: var(--bg);
        color: var(--text);
        font-size: 13.5px;
    }

    .inventory-page {
        width: 100%;
        max-width: 1680px;
        margin: 0 auto;
        padding: 18px 16px 34px;
    }

    .page-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }

    .inventory-header {
        padding: 18px 20px;
        margin-bottom: 14px;
        border-right: 5px solid var(--brand);
    }

    .inventory-header-inner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }

    .page-title {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 900;
        color: var(--brand-deep);
    }

    .page-subtitle {
        margin-top: 7px;
        color: var(--muted);
        font-size: .82rem;
        line-height: 1.8;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-soft,
    .btn-main {
        border-radius: 10px;
        padding: 8px 13px;
        font-size: .8rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-decoration: none;
        border: 1px solid transparent;
        white-space: nowrap;
        transition: all .15s ease;
        cursor: pointer;
    }

    .btn-main {
        background: var(--brand);
        color: #fff;
        border-color: var(--brand);
    }

    .btn-main:hover {
        background: var(--brand-dark);
        border-color: var(--brand-dark);
        color: #fff;
    }

    .btn-soft {
        background: #fff;
        color: var(--brand-deep);
        border-color: var(--border);
    }

    .btn-soft:hover {
        background: var(--soft);
        color: var(--brand-deep);
    }

    .toolbar-card {
        padding: 15px;
        margin-bottom: 14px;
    }

    .toolbar-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .section-title {
        margin: 0;
        font-size: .98rem;
        font-weight: 900;
        color: var(--brand-deep);
    }

    .subtle-text {
        color: var(--muted);
        font-size: .78rem;
        line-height: 1.8;
    }

    .count-badge {
        background: #ECFEFF;
        color: var(--brand-dark);
        border: 1px solid #BAE6FD;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: .78rem;
        font-weight: 850;
    }

    .operation-strip {
        background: var(--soft);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 10px;
        display: grid;
        grid-template-columns: minmax(250px, 1fr) auto;
        gap: 10px;
        align-items: center;
        margin-bottom: 13px;
    }

    .selected-info {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
    }

    .selected-dot {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: #DFF7FA;
        color: var(--brand-dark);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        flex: 0 0 auto;
    }

    .selected-title {
        font-weight: 900;
        font-size: .84rem;
        color: var(--brand-deep);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .selected-hint {
        color: var(--muted);
        font-size: .73rem;
        margin-top: 2px;
    }

    .operation-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        flex-wrap: wrap;
        gap: 7px;
    }

    .variant-operation-select {
        width: 185px;
        height: 35px;
        border-radius: 10px;
        font-size: .77rem;
    }

    .btn-mini {
        border-radius: 10px;
        padding: 7px 11px;
        font-size: .77rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        white-space: nowrap;
    }

    .inventory-page .btn-primary {
        background: var(--brand);
        border-color: var(--brand);
    }

    .inventory-page .btn-primary:hover {
        background: var(--brand-dark);
        border-color: var(--brand-dark);
    }

    .inventory-page .btn-outline-primary {
        color: var(--brand-dark);
        border-color: rgba(14, 165, 183, .45);
    }

    .inventory-page .btn-outline-primary:hover {
        color: #fff;
        background: var(--brand);
        border-color: var(--brand);
    }

    .filter-grid {
        display: grid;
        grid-template-columns: minmax(320px, 1.6fr) minmax(145px, .7fr) minmax(145px, .7fr) minmax(190px, .9fr) auto;
        gap: 9px;
        align-items: end;
    }

    .label-sm {
        font-size: .74rem;
        font-weight: 800;
        color: #526171;
        margin-bottom: 6px;
    }

    .form-control,
    .form-select {
        border-color: var(--border);
        border-radius: 10px;
        color: var(--text);
        font-size: .82rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--brand);
        box-shadow: 0 0 0 .14rem rgba(14, 165, 183, .12);
    }

    .product-card {
        overflow: hidden;
    }

    .product-card-head {
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        background: #fff;
    }

    .sheet-wrap {
        padding: 12px;
    }

    .sheet-scroll {
        width: 100%;
        overflow-x: auto;
        overflow-y: visible;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #fff;
    }

    .sheet {
        width: 100%;
        min-width: 1240px;
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
        table-layout: fixed;
    }

    .sheet col.col-check { width: 46px; }
    .sheet col.col-toggle { width: 46px; }
    .sheet col.col-code { width: 115px; }
    .sheet col.col-barcode { width: 165px; }
    .sheet col.col-stock { width: 110px; }
    .sheet col.col-buy { width: 165px; }
    .sheet col.col-sell { width: 165px; }

    .sheet thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #F8FAFC;
        color: #475569;
        font-size: .74rem;
        font-weight: 900;
        padding: 11px 10px;
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
        text-align: right;
    }

    .sheet tbody td {
        padding: 11px 10px;
        font-size: .82rem;
        vertical-align: middle;
        background: #fff;
        border-bottom: 1px solid var(--border-soft);
    }

    .sheet tbody tr:hover td {
        background: #FAFCFE;
    }

    .sheet tbody tr:last-child td {
        border-bottom: 0;
    }

    .nowrap {
        white-space: nowrap;
    }

    .mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        letter-spacing: .2px;
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
        color: var(--brand-dark);
    }

    .sort-arrow {
        color: #94A3B8;
        font-size: .72rem;
    }

    .product-name-wrap {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 0;
        width: 100%;
    }

    .product-title-text {
        font-weight: 850;
        color: var(--brand-deep);
        line-height: 1.8;
        white-space: normal;
        overflow: visible;
        text-overflow: unset;
        word-break: normal;
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
        font-size: .71rem;
        font-weight: 800;
        border: 1px solid transparent;
    }

    .sellable-badge.active {
        color: #166534;
        background: #ECFDF3;
        border-color: #BBF7D0;
    }

    .sellable-badge.inactive {
        color: #991B1B;
        background: #FEF2F2;
        border-color: #FECACA;
    }

    .pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: .74rem;
        font-weight: 800;
        border: 1px solid var(--border);
        background: #fff;
        white-space: nowrap;
        max-width: 100%;
    }

    .pill-gray {
        background: #F8FAFC;
        color: #475569;
    }

    .pill-danger {
        background: #FEF2F2;
        border-color: #FECACA;
        color: #B91C1C;
    }

    .pill-success {
        background: #F0FDF4;
        border-color: #BBF7D0;
        color: #15803D;
    }

    .pill-purple {
        background: #ECFEFF;
        border-color: #BAE6FD;
        color: var(--brand-dark);
    }

    .price-inline {
        white-space: nowrap;
        font-weight: 850;
        color: var(--brand-deep);
    }

    .buy-price-muted {
        color: #9CA3AF;
    }

    .toggle-variants {
        width: 30px;
        height: 30px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border);
        background: #fff;
        color: var(--brand-dark);
        font-weight: 900;
        transition: all .15s ease;
    }

    .toggle-variants:hover {
        background: #ECFEFF;
        border-color: #BAE6FD;
    }

    .variant-inner {
        background: #F8FAFC;
        border-radius: 12px;
        border: 1px solid var(--border);
        padding: 10px;
    }

    .variant-table {
        margin: 0;
        font-size: .79rem;
    }

    .variant-table th {
        color: #64748B;
        font-weight: 900;
        font-size: .73rem;
        white-space: nowrap;
    }

    .variant-table td {
        background: transparent !important;
    }

    .status-dot {
        width: 11px;
        height: 11px;
        border-radius: 999px;
        display: inline-block;
        vertical-align: middle;
    }

    .status-dot.active {
        background: #16A34A;
        box-shadow: 0 0 0 4px rgba(22, 163, 74, .14);
    }

    .status-dot.inactive {
        background: #DC2626;
        box-shadow: 0 0 0 4px rgba(220, 38, 38, .13);
    }

    .empty-row {
        color: var(--muted);
        padding: 44px 0 !important;
        font-weight: 800;
    }

    .pagination {
        margin-bottom: 0;
    }

    .offcanvas {
        border-top-left-radius: 18px;
        border-bottom-left-radius: 18px;
    }

    .offcanvas-header {
        border-bottom: 1px solid var(--border);
    }

    .cat-search {
        height: 40px;
        border-radius: 10px;
        border: 1px solid var(--border);
        background: #fff;
        font-size: .82rem;
    }

    .cat-tree-wrap {
        max-height: calc(100vh - 190px);
        overflow: auto;
        padding-left: 4px;
        padding-right: 2px;
    }

    .side-link {
        font-size: .78rem;
        font-weight: 800;
        color: var(--brand);
        text-decoration: none;
    }

    .modal-content {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 22px 55px rgba(15, 23, 42, .18);
    }

    .modal-header {
        background: var(--brand-deep);
        color: #fff;
        border-bottom: 0;
        border-radius: 18px 18px 0 0;
    }

    .modal-header .btn-close {
        filter: invert(1);
        opacity: .9;
    }

    .modal-title {
        font-weight: 900;
    }

    .min-w-0 {
        min-width: 0;
    }

    @media (max-width: 1199.98px) {
        .filter-grid {
            grid-template-columns: 1fr 1fr;
        }

        .filter-actions {
            grid-column: 1 / -1;
        }

        .operation-strip {
            grid-template-columns: 1fr;
        }

        .operation-actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 767.98px) {
        body {
            font-size: 13px;
            background: #F6F7F9;
        }

        .inventory-page {
            padding: 10px 8px 24px;
        }

        .page-card {
            border-radius: 13px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, .045);
        }

        .inventory-header {
            padding: 13px;
            margin-bottom: 10px;
            border-right: 4px solid var(--brand);
        }

        .inventory-header-inner {
            display: block;
        }

        .page-title {
            font-size: 1.02rem;
            line-height: 1.8;
        }

        .page-subtitle {
            font-size: .76rem;
            line-height: 1.9;
            margin-top: 3px;
        }

        .header-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 7px;
            width: 100%;
            margin-top: 12px;
        }

        .btn-soft,
        .btn-main {
            width: 100%;
            min-height: 39px;
            font-size: .78rem;
        }

        .toolbar-card {
            padding: 10px;
            margin-bottom: 10px;
        }

        .toolbar-top {
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .section-title {
            font-size: .9rem;
        }

        .subtle-text {
            font-size: .74rem;
        }

        .count-badge {
            padding: 5px 10px;
            font-size: .74rem;
        }

        .operation-strip {
            display: block;
            padding: 9px;
            border-radius: 11px;
            margin-bottom: 11px;
        }

        .selected-info {
            align-items: flex-start;
            margin-bottom: 9px;
        }

        .selected-title {
            font-size: .78rem;
            white-space: normal;
            line-height: 1.8;
        }

        .selected-hint {
            font-size: .7rem;
            line-height: 1.8;
        }

        .operation-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 7px;
            width: 100%;
        }

        .operation-actions .variant-operation-select {
            grid-column: 1 / -1;
            width: 100%;
            height: 38px;
        }

        .operation-actions .btn-mini {
            width: 100%;
            min-height: 38px;
            font-size: .74rem;
        }

        .filter-grid {
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .filter-actions {
            display: grid !important;
            grid-template-columns: 1fr 1fr;
            width: 100%;
        }

        .form-control,
        .form-select {
            height: 39px;
            font-size: .78rem;
            border-radius: 10px;
        }

        .product-card-head {
            padding: 12px;
            align-items: flex-start;
        }

        .product-card-head .pill {
            display: none;
        }

        .sheet-wrap {
            padding: 9px;
        }

        .sheet-scroll {
            overflow: visible;
            border: 0;
            background: transparent;
            border-radius: 0;
        }

        .sheet {
            display: block;
            width: 100%;
            min-width: 0;
        }

        .sheet colgroup,
        .sheet thead {
            display: none;
        }

        .sheet tbody {
            display: block;
            width: 100%;
        }

        .sheet tbody tr {
            display: block;
            width: 100%;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 13px;
            margin-bottom: 10px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, .04);
            overflow: hidden;
        }

        .sheet tbody td {
            display: grid;
            grid-template-columns: 88px minmax(0, 1fr);
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 9px 11px;
            border-bottom: 1px solid var(--border-soft);
            background: #fff;
            font-size: .78rem;
            text-align: right;
            white-space: normal;
        }

        .sheet tbody td::before {
            content: attr(data-label);
            color: var(--muted);
            font-size: .7rem;
            font-weight: 800;
        }

        .sheet tbody td.mobile-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            background: #F8FAFC;
        }

        .sheet tbody td.mobile-top::before {
            display: none;
        }

        .mobile-row-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mobile-row-title {
            color: var(--brand-deep);
            font-weight: 900;
            font-size: .78rem;
        }

        .product-title-text {
            font-size: .82rem;
            line-height: 1.9;
            font-weight: 900;
        }

        .pill {
            width: fit-content;
            max-width: 100%;
            font-size: .72rem;
            padding: 4px 9px;
        }

        .price-inline {
            font-size: .78rem;
            white-space: normal;
        }

        .sheet tbody tr.collapse {
            display: none;
        }

        .sheet tbody tr.collapse.show {
            display: block;
        }

        .sheet tbody tr.collapse td:first-child {
            display: none;
        }

        .sheet tbody tr.collapse td.variant-cell {
            display: block;
            padding: 9px;
            border-bottom: 0;
        }

        .sheet tbody tr.collapse td.variant-cell::before {
            display: none;
        }

        .variant-table thead {
            display: none;
        }

        .variant-table tbody,
        .variant-table tr {
            display: block;
        }

        .variant-table tr {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 7px;
            margin-bottom: 8px;
        }

        .variant-table td {
            display: flex !important;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 6px 4px !important;
            border-bottom: 1px solid var(--border-soft) !important;
            background: #fff !important;
            font-size: .74rem;
        }

        .variant-table td::before {
            content: attr(data-label);
            color: var(--muted);
            font-size: .68rem;
            font-weight: 800;
        }

        .variant-table td:last-child {
            border-bottom: 0 !important;
        }

        .offcanvas {
            width: min(88vw, 360px) !important;
        }
    }

    @media (max-width: 390px) {
        .sheet tbody td {
            grid-template-columns: 76px minmax(0, 1fr);
            padding: 8px 9px;
        }

        .operation-actions {
            grid-template-columns: 1fr;
        }

        .operation-actions .variant-operation-select {
            grid-column: auto;
        }

        .filter-actions {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="inventory-page">
    <div class="page-card inventory-header">
        <div class="inventory-header-inner">
            <div>
                <h1 class="page-title">مدیریت کالاها و موجودی</h1>
                <div class="page-subtitle">
                    مدیریت کالا، موجودی، قیمت خرید، قیمت فروش، تنوع‌ها و کارتکس‌های انبار
                </div>
            </div>

            <div class="header-actions">
                <button class="btn-soft" type="button" data-bs-toggle="offcanvas" data-bs-target="#catOffcanvas">
                    دسته‌بندی‌ها
                </button>

                <a class="btn-soft" href="{{ route('purchases.create') }}">
                    خرید کالا
                </a>

                <a class="btn-main" href="{{ route('products.create') }}">
                    افزودن کالا
                </a>
            </div>
        </div>
    </div>

    <div id="productsAjaxArea">
        <div class="page-card toolbar-card">
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

                    <button class="btn btn-outline-danger btn-mini" type="button" id="bulkDeactivateBtn">
                        غیرفعال‌سازی
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
                        کارتکس خرید
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
                            <option value="" @selected(request('stock_status') === '' || is_null(request('stock_status')))>همه</option>
                            <option value="out" @selected(request('stock_status') === 'out')>ناموجود</option>
                        </select>
                    </div>

                    <div>
                        <label class="label-sm">وضعیت فروش</label>
                        <select name="sellable_status" class="form-select">
                            <option value="" @selected(request('sellable_status') === '' || is_null(request('sellable_status')))>همه</option>
                            <option value="sellable" @selected(request('sellable_status') === 'sellable')>قابل فروش</option>
                            <option value="unsellable" @selected(request('sellable_status') === 'unsellable')>غیرفعال فروش</option>
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

        <div class="page-card product-card">
            <div class="product-card-head">
                <div>
                    <h2 class="section-title">لیست کالاها</h2>
                    <div class="subtle-text">
                        نمایش {{ $toFa($products->firstItem() ?? 0) }} تا {{ $toFa($products->lastItem() ?? 0) }} از {{ $toFa($products->total() ?? 0) }} مورد
                    </div>
                </div>

                <span class="pill pill-purple">
                    نمایش واکنش‌گرا
                </span>
            </div>

            <div class="sheet-wrap">
                <div class="sheet-scroll">
                    <table class="sheet">
                        <colgroup>
                            <col class="col-check">
                            <col class="col-toggle">
                            <col class="col-code">
                            <col class="col-barcode">
                            <col class="col-name">
                            <col class="col-stock">
                            <col class="col-buy">
                            <col class="col-sell">
                        </colgroup>

                        <thead>
                            <tr>
                                <th class="text-center">
                                    <input type="checkbox" class="form-check-input" id="selectAllProducts" title="انتخاب همه">
                                </th>

                                <th></th>

                                <th>
                                    <a href="{{ $sortLink('short_barcode') }}" class="sortable-link">
                                        کد کالا
                                        <span class="sort-arrow">{{ $sortArrow('short_barcode') }}</span>
                                    </a>
                                </th>

                                <th>
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

                                <th>
                                    <a href="{{ $sortLink('stock') }}" class="sortable-link">
                                        موجودی
                                        <span class="sort-arrow">{{ $sortArrow('stock') }}</span>
                                    </a>
                                </th>

                                <th>
                                    <a href="{{ $sortLink('variants_buy_price_min') }}" class="sortable-link">
                                        قیمت خرید
                                        <span class="sort-arrow">{{ $sortArrow('variants_buy_price_min') }}</span>
                                    </a>
                                </th>

                                <th>
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
                                            $variantBreakdown = $v->warehouseStocks
                                                ->groupBy('warehouse_id')
                                                ->map(function ($rows) {
                                                    $first = $rows->first();

                                                    return [
                                                        'warehouse' => $first?->warehouse?->name,
                                                        'qty' => (int) $rows->sum('quantity'),
                                                    ];
                                                })
                                                ->values()
                                                ->all();

                                            return [
                                                'id' => (int) $v->id,
                                                'name' => $v->variant_name,
                                                'stock' => (int) $v->stock,
                                                'is_active' => (bool) $v->is_active,
                                                'warehouse_breakdown' => $variantBreakdown,
                                            ];
                                        })
                                        ->all();

                                    $stockBreakdownPayload = $p->warehouseStocks
                                        ->groupBy('warehouse_id')
                                        ->map(function ($rows) {
                                            $first = $rows->first();

                                            $variantRows = $rows->whereNotNull('product_variant_id');
                                            $aggregateRows = $rows->whereNull('product_variant_id');

                                            $qty = $variantRows->isNotEmpty()
                                                ? (int) $variantRows->sum('quantity')
                                                : (int) $aggregateRows->sum('quantity');

                                            return [
                                                'warehouse' => $first?->warehouse?->name,
                                                'qty' => max(0, $qty),
                                            ];
                                        })
                                        ->filter(fn ($row) => (int) ($row['qty'] ?? 0) > 0)
                                        ->values()
                                        ->all();

                                    $buyPrice = $p->variants_min_buy_price;
                                    $isSellable = $p->is_sellable ?? true;
                                @endphp

                                <tr>
                                    <td class="text-center mobile-top">
                                        <div class="mobile-row-title d-md-none">
                                            کالا
                                        </div>

                                        <div class="mobile-row-actions">
                                            <input
                                                type="checkbox"
                                                class="form-check-input product-checkbox"
                                                value="{{ $p->id }}"
                                                data-edit-url="{{ route('products.edit', $p) }}"
                                                data-delete-url="{{ route('products.destroy', $p) }}"
                                                data-sales-ledger-url="{{ route('products.sales-ledger', $p) }}"
                                                data-purchase-ledger-url="{{ route('products.purchase-ledger', $p) }}"
                                                data-deactivate-url="{{ route('product-deactivation-documents.create', ['product_id' => $p->id]) }}"
                                                data-deactivation-history-url="{{ route('product-deactivation-documents.index', ['product_name' => $p->name]) }}"
                                                data-is-sellable="{{ $isSellable ? '1' : '0' }}"
                                                data-product-name="{{ $p->name }}"
                                                data-variants='@json($variantsPayload)'
                                                data-stock-breakdown='@json($stockBreakdownPayload)'>

                                            @if($hasVariants)
                                                <button
                                                    class="toggle-variants d-md-none"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#{{ $collapseId }}"
                                                    aria-expanded="false"
                                                    aria-controls="{{ $collapseId }}"
                                                    title="نمایش تنوع‌ها">
                                                    <span class="variant-symbol">+</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>

                                    <td data-label="تنوع">
                                        @if($hasVariants)
                                            <button
                                                class="toggle-variants d-none d-md-inline-flex"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#{{ $collapseId }}"
                                                aria-expanded="false"
                                                aria-controls="{{ $collapseId }}"
                                                title="نمایش تنوع‌ها">
                                                <span class="variant-symbol">+</span>
                                            </button>

                                            <span class="d-md-none pill pill-gray">دارای تنوع</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td class="nowrap mono" data-label="کد کالا">
                                        <span class="pill pill-gray">{{ $short ?: '—' }}</span>
                                    </td>

                                    <td class="nowrap mono" data-label="بارکد">
                                        @if($sampleBarcode)
                                            <span class="pill pill-gray">{{ $sampleBarcode }}</span>
                                        @else
                                            <span class="pill pill-gray">{{ $p->barcode ?: '—' }}</span>
                                        @endif
                                    </td>

                                    <td data-label="نام کالا">
                                        <div class="product-name-wrap">
                                            <div class="product-title-text">{{ $p->name }}</div>

                                            <div class="sellable-state">
                                                @if($isSellable)
                                                    <span class="sellable-badge active">قابل فروش</span>
                                                @else
                                                    <span class="sellable-badge inactive">غیرفعال فروش</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td class="nowrap" data-label="موجودی">
                                        <span class="pill {{ ((int) $p->stock) === 0 ? 'pill-danger' : 'pill-success' }}">
                                            {{ $toFa($p->stock ?? 0) }}
                                        </span>
                                    </td>

                                    <td class="nowrap" data-label="قیمت خرید">
                                        @if(!is_null($buyPrice))
                                            <span class="price-inline">{{ $toFa(number_format((int) $buyPrice) . ' تومان') }}</span>
                                        @else
                                            <span class="buy-price-muted">—</span>
                                        @endif
                                    </td>

                                    <td class="nowrap" data-label="قیمت فروش">
                                        <span class="price-inline">{{ $toFa(number_format((int) $p->price) . ' تومان') }}</span>
                                    </td>
                                </tr>

                                @if($hasVariants)
                                    <tr class="collapse" id="{{ $collapseId }}">
                                        <td></td>
                                        <td colspan="7" class="variant-cell">
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
                                                                    <td data-label="نام تنوع" class="fw-bold">{{ $v->variant_name }}</td>

                                                                    <td data-label="بارکد" class="mono">
                                                                        {{ $v->variant_code }}
                                                                    </td>

                                                                    <td data-label="موجودی">
                                                                        @if((int) $v->stock === 0)
                                                                            <span class="pill pill-danger">۰</span>
                                                                        @else
                                                                            <span class="pill pill-success">{{ $toFa($v->stock) }}</span>
                                                                        @endif
                                                                    </td>

                                                                    <td data-label="فروش">
                                                                        {{ $toFa(number_format((int) $v->sell_price) . ' تومان') }}
                                                                    </td>

                                                                    <td data-label="خرید">
                                                                        {{ $v->buy_price !== null ? $toFa(number_format((int) $v->buy_price) . ' تومان') : '—' }}
                                                                    </td>

                                                                    <td data-label="وضعیت">
                                                                        @if($v->is_active)
                                                                            <span class="status-dot active" title="فعال" aria-label="فعال"></span>
                                                                        @else
                                                                            <span class="status-dot inactive" title="غیرفعال" aria-label="غیرفعال"></span>
                                                                        @endif
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
                                    <td colspan="8" class="text-center empty-row">هیچ کالایی ثبت نشده است.</td>
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
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="catOffcanvas" aria-labelledby="catOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold" id="catOffcanvasLabel">دسته‌بندی‌ها</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-bold">انتخاب دسته‌بندی</div>
            <a href="{{ route('products.index', request()->except(['category_id', 'page'])) }}" class="side-link">همه کالاها</a>
        </div>

        <input type="text" id="catSearchMobile" class="form-control cat-search mb-3" placeholder="جستجو در دسته‌ها...">

        <div class="cat-tree-wrap" id="catTreeMobile">
            @include('categories._tree', ['nodes' => $categoryTree])
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
            return String(value ?? '').replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
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

        function freshDeactivateBtn() {
            return document.getElementById('bulkDeactivateBtn');
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

                const offcanvasEl = document.getElementById('catOffcanvas');
                const openedOffcanvas = offcanvasEl ? bootstrap.Offcanvas.getInstance(offcanvasEl) : null;

                if (openedOffcanvas) {
                    openedOffcanvas.hide();
                }
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
            const deactivateBtn = freshDeactivateBtn();

            if (!variantSelectEl) return;

            const selected = getSelectedProducts();

            if (selectedBadgeEl) {
                selectedBadgeEl.textContent = faNumber(selected.length);
            }

            if (deactivateBtn) {
                deactivateBtn.textContent = 'غیرفعال‌سازی';
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
                    variantHelpTextEl.textContent = 'عملیات ویرایش، حذف، غیرفعال‌سازی، موجودی و کارتکس فقط برای یک کالا انجام می‌شود.';
                }

                return;
            }

            const item = selected[0];

            if (selectedTitleEl) {
                selectedTitleEl.textContent = item.dataset.productName || 'کالای انتخاب شده';
            }

            if (deactivateBtn) {
                deactivateBtn.textContent = item.dataset.isSellable === '1' ? 'غیرفعال‌سازی' : 'سوابق غیرفعال‌سازی';
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

            document.querySelectorAll('#productsAjaxArea a.sortable-link, #productsAjaxArea .pagination a, #catTreeMobile a').forEach(link => {
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

            document.getElementById('bulkDeactivateBtn')?.addEventListener('click', function() {
                const selected = getSingleSelected();

                if (!selected) return;

                const isSellable = selected.dataset.isSellable === '1';

                if (isSellable) {
                    window.location.href = selected.dataset.deactivateUrl;
                    return;
                }

                window.location.href = selected.dataset.deactivationHistoryUrl;
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

                stockNameEl.textContent = selectedVariant
                    ? `${selected.dataset.productName} — ${selectedVariant.name ?? 'تنوع انتخابی'}`
                    : selected.dataset.productName;

                stockBodyEl.innerHTML = '';

                const breakdown = parseJsonDataset(selected.dataset.stockBreakdown, []);
                let limitedBreakdown = breakdown;

                if (selectedVariant) {
                    limitedBreakdown = Array.isArray(selectedVariant.warehouse_breakdown)
                        ? selectedVariant.warehouse_breakdown.filter(item => Number(item.qty ?? 0) > 0)
                        : [];
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

        bindCatSearch('catSearchMobile', 'catTreeMobile');
        initAjaxBindings();

        window.addEventListener('popstate', function() {
            loadProducts(window.location.href, false);
        });
    });
</script>
@endsection