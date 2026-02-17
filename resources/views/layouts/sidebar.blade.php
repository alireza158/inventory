@php
    $is = fn($name) => request()->routeIs($name) ? 'active' : '';

    // برای اینکه تیتر بخش وقتی داخلش هستی حالت فعال بگیره (اختیاری)
    $productsActive = request()->routeIs('products.*')
                    || request()->routeIs('categories.*')
                    || request()->routeIs('model-lists.*');

    $warehouseActive = request()->routeIs('purchases.*')
                    || request()->routeIs('vouchers.*')
                    || request()->routeIs('warehouses.*')
                    || request()->routeIs('stocktake.*')
                    || request()->routeIs('stocktake.index');

    $commerceActive  = request()->routeIs('persons.*')
                    || request()->routeIs('customers.*')
                    || request()->routeIs('suppliers.*')
                    || request()->routeIs('users.*');

    $invoiceActive   = request()->routeIs('preinvoice.*')
                    || request()->routeIs('invoices.*');
@endphp

<div class="app-sidebar">
    <div class="sidebar-scroll">

        {{-- Brand --}}
        <div class="app-sidebar__brand">
            <img src="{{ asset('logo.png') }}"
                 alt="{{ config('app.name') }}"
                 style="height:56px;width:56px;object-fit:contain;">
            <div class="app-sidebar__title">{{ config('app.name', 'سیستم انبار آریا جانبی') }}</div>
            <div class="app-sidebar__subtitle">مدیریت موجودی و گردش کالا</div>
        </div>

        {{-- 1) Dashboard --}}
        <a class="sidebar-link {{ $is('dashboard') }}"
           href="{{ route('dashboard') }}">
            <span class="title">داشبورد</span>
        </a>

        {{-- =======================
            2) Products (always open)
        ======================= --}}
        <div class="sidebar-section-title {{ $productsActive ? 'is-active' : '' }}">محصولات</div>

        <div class="sidebar-submenu">
            <a class="sidebar-sublink {{ $is('products.index') }}"
               href="{{ route('products.index') }}">
                کالاها
            </a>

            <a class="sidebar-sublink {{ $is('categories.index') }}"
               href="{{ route('categories.index') }}">
                دسته‌بندی
            </a>

            <a class="sidebar-sublink {{ $is('model-lists.index') }}"
               href="{{ route('model-lists.index') }}">
                مدل لیست
            </a>
        </div>

        {{-- =======================
            3) Warehouse (always open)
        ======================= --}}
        <div class="sidebar-section-title {{ $warehouseActive ? 'is-active' : '' }}">انبارداری</div>

        <div class="sidebar-submenu">
            <a class="sidebar-sublink {{ request()->routeIs('purchases.*') ? 'active' : '' }}"
               href="{{ route('purchases.index') }}">
                خرید کالا
            </a>

            <a class="sidebar-sublink {{ request()->routeIs('vouchers.*') ? 'active' : '' }}"
               href="{{ route('vouchers.index') }}">
                حواله
            </a>

            <a class="sidebar-sublink {{ request()->routeIs('warehouses.*') ? 'active' : '' }}"
               href="{{ route('warehouses.index') }}">
                انبارها
            </a>

            <a class="sidebar-sublink {{ request()->routeIs('stocktake.*') || request()->routeIs('stocktake.index') ? 'active' : '' }}"
               href="{{ route('stocktake.index') }}">
                انبارگردانی
            </a>
        </div>

        {{-- =======================
            4) Commerce (always open)
        ======================= --}}
        <div class="sidebar-section-title {{ $commerceActive ? 'is-active' : '' }}">بازرگانی</div>

        <div class="sidebar-submenu">
            <a class="sidebar-sublink {{ request()->routeIs('persons.*') ? 'active' : '' }}"
               href="{{ route('persons.index') }}">
                اشخاص
            </a>

            <a class="sidebar-sublink {{ request()->routeIs('users.*') ? 'active' : '' }}"
               href="{{ route('users.index') }}">
                کاربران
            </a>
        </div>

        {{-- =======================
            5) Invoice (always open)
        ======================= --}}
        <div class="sidebar-section-title {{ $invoiceActive ? 'is-active' : '' }}">فاکتور</div>

        <div class="sidebar-submenu">

            <a class="sidebar-sublink {{ request()->routeIs('preinvoice.create') ? 'active' : '' }}"
               href="{{ route('preinvoice.create') }}">
                ثبت پیش‌فاکتور
            </a>

            @if (Route::has('preinvoice.index'))
                <a class="sidebar-sublink {{ request()->routeIs('preinvoice.index') ? 'active' : '' }}"
                   href="{{ route('preinvoice.index') }}">
                    پیش‌فاکتورها
                </a>
            @endif

            <a class="sidebar-sublink {{ request()->routeIs('preinvoice.draft.index') ? 'active' : '' }}"
               href="{{ route('preinvoice.draft.index') }}">
                پیش‌نویس‌ها
            </a>

            @if (Route::has('invoices.index'))
                <a class="sidebar-sublink {{ request()->routeIs('invoices.*') ? 'active' : '' }}"
                   href="{{ route('invoices.index') }}">
                    فاکتورها
                </a>
            @endif
        </div>

        {{-- =======================
            Activity Logs
        ======================= --}}
        <div class="sidebar-group-label">سایر</div>

        <a class="sidebar-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}"
           href="{{ route('activity-logs.index') }}">
            <span class="title">لاگ فعالیت</span>
        </a>

    </div>
</div>
