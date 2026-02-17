@php
    $is = fn($name) => request()->routeIs($name) ? 'active' : '';

    // Group open states
    $productsOpen = request()->routeIs('products.*')
                || request()->routeIs('categories.*')
                || request()->routeIs('model-lists.*');

    $warehouseOpen = request()->routeIs('purchases.*')
                || request()->routeIs('vouchers.*')
                || request()->routeIs('warehouses.*')
                || request()->routeIs('stocktake.*')
                || request()->routeIs('stocktake.index');

    $commerceOpen = request()->routeIs('persons.*')
                || request()->routeIs('customers.*')
                || request()->routeIs('suppliers.*')
                || request()->routeIs('users.*');

    $invoiceOpen = request()->routeIs('preinvoice.*')
              || request()->routeIs('invoices.*');
@endphp

<div class="app-sidebar p-3">
    <div class="sidebar-scroll">

        {{-- Brand --}}
        <div class="app-sidebar__brand">
            <img src="{{ asset('logo.png') }}"
                 alt="{{ config('app.name') }}"
                 style="height:56px;width:56px;object-fit:contain;">
            <div class="app-sidebar__title">{{ config('app.name', 'سیستم انبار آریا جانبی') }}</div>
            <div class="app-sidebar__subtitle">مدیریت موجودی و گردش کالا</div>
        </div>

        {{-- =======================
            1) Dashboard (single)
        ======================= --}}
        <a class="sidebar-link {{ $is('dashboard') }}"
           href="{{ route('dashboard') }}">
            <span>داشبورد</span>
        </a>

        {{-- =======================
            2) Products
        ======================= --}}
        <a class="sidebar-link {{ $productsOpen ? 'active' : '' }}"
           data-bs-toggle="collapse"
           href="#menuProducts"
           role="button"
           aria-expanded="{{ $productsOpen ? 'true' : 'false' }}"
           aria-controls="menuProducts">
            <span>محصولات</span>
            <span class="chev">▾</span>
        </a>

        <div class="collapse {{ $productsOpen ? 'show' : '' }}" id="menuProducts">
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
        </div>

        {{-- =======================
            3) Warehouse (Inventory)
        ======================= --}}
        <a class="sidebar-link {{ $warehouseOpen ? 'active' : '' }}"
           data-bs-toggle="collapse"
           href="#menuWarehouse"
           role="button"
           aria-expanded="{{ $warehouseOpen ? 'true' : 'false' }}"
           aria-controls="menuWarehouse">
            <span>انبارداری</span>
            <span class="chev">▾</span>
        </a>

        <div class="collapse {{ $warehouseOpen ? 'show' : '' }}" id="menuWarehouse">
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
        </div>

        {{-- =======================
            4) Commerce
        ======================= --}}
        <a class="sidebar-link {{ $commerceOpen ? 'active' : '' }}"
           data-bs-toggle="collapse"
           href="#menuCommerce"
           role="button"
           aria-expanded="{{ $commerceOpen ? 'true' : 'false' }}"
           aria-controls="menuCommerce">
            <span>بازرگانی</span>
            <span class="chev">▾</span>
        </a>

        <div class="collapse {{ $commerceOpen ? 'show' : '' }}" id="menuCommerce">
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
        </div>

        {{-- =======================
            5) Invoice
        ======================= --}}
        <a class="sidebar-link {{ $invoiceOpen ? 'active' : '' }}"
           data-bs-toggle="collapse"
           href="#menuInvoices"
           role="button"
           aria-expanded="{{ $invoiceOpen ? 'true' : 'false' }}"
           aria-controls="menuInvoices">
            <span>فاکتور</span>
            <span class="chev">▾</span>
        </a>

        <div class="collapse {{ $invoiceOpen ? 'show' : '' }}" id="menuInvoices">
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
        </div>

        {{-- =======================
            Activity Logs (single)
        ======================= --}}
        <div class="sidebar-group-label">سایر</div>

        <a class="sidebar-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}"
           href="{{ route('activity-logs.index') }}">
            <span>لاگ فعالیت</span>
        </a>

    </div>
</div>
