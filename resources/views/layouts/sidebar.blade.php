@php
    $is = fn($name) => request()->routeIs($name) ? 'active' : '';

    // Group open states
    $dashboardActive = request()->routeIs('dashboard');

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

    $logsActive = request()->routeIs('activity-logs.*') || request()->routeIs('activity-logs.index');
@endphp

<div class="bg-white border-end p-3" style="width: 260px">
    {{-- Brand --}}
    <div class="mb-3 text-center">
        <img src="{{ asset('logo.png') }}"
             alt="{{ config('app.name') }}"
             class="mb-2"
             style="height: 56px; width: 56px; object-fit: contain;">
        <div class="fw-bold">{{ config('app.name', 'سیستم انبار آریا جانبی') }}</div>
        <div class="text-muted small">مدیریت موجودی و گردش کالا</div>
    </div>

    <div class="list-group list-group-flush">

        {{-- =======================
            1) Dashboard
        ======================= --}}
        <a class="list-group-item list-group-item-action {{ $is('dashboard') }}"
           href="{{ route('dashboard') }}">
            داشبورد
        </a>

        {{-- =======================
            2) Products
        ======================= --}}
        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $productsOpen ? 'active' : '' }}"
           data-bs-toggle="collapse"
           href="#menuProducts"
           role="button"
           aria-expanded="{{ $productsOpen ? 'true' : 'false' }}"
           aria-controls="menuProducts">
            <span>محصولات</span>
            <span class="small">▾</span>
        </a>

        <div class="collapse {{ $productsOpen ? 'show' : '' }}" id="menuProducts">
            <div class="list-group list-group-flush ms-2 mt-1">
                <a class="list-group-item list-group-item-action {{ $is('products.index') }}"
                   href="{{ route('products.index') }}">
                    کالاها
                </a>

                <a class="list-group-item list-group-item-action {{ $is('categories.index') }}"
                   href="{{ route('categories.index') }}">
                    دسته‌بندی
                </a>

                <a class="list-group-item list-group-item-action {{ $is('model-lists.index') }}"
                   href="{{ route('model-lists.index') }}">
                    مدل لیست
                </a>
            </div>
        </div>

        {{-- =======================
            3) Warehouse (Inventory)
        ======================= --}}
        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $warehouseOpen ? 'active' : '' }}"
           data-bs-toggle="collapse"
           href="#menuWarehouse"
           role="button"
           aria-expanded="{{ $warehouseOpen ? 'true' : 'false' }}"
           aria-controls="menuWarehouse">
            <span>انبارداری</span>
            <span class="small">▾</span>
        </a>

        <div class="collapse {{ $warehouseOpen ? 'show' : '' }}" id="menuWarehouse">
            <div class="list-group list-group-flush ms-2 mt-1">
                <a class="list-group-item list-group-item-action {{ request()->routeIs('purchases.*') ? 'active' : '' }}"
                   href="{{ route('purchases.index') }}">
                    خرید کالا
                </a>

                <a class="list-group-item list-group-item-action {{ request()->routeIs('vouchers.*') ? 'active' : '' }}"
                   href="{{ route('vouchers.index') }}">
                    حواله
                </a>

                <a class="list-group-item list-group-item-action {{ request()->routeIs('warehouses.*') ? 'active' : '' }}"
                   href="{{ route('warehouses.index') }}">
                    انبارها
                </a>

                <a class="list-group-item list-group-item-action {{ request()->routeIs('stocktake.*') || request()->routeIs('stocktake.index') ? 'active' : '' }}"
                   href="{{ route('stocktake.index') }}">
                    انبارگردانی
                </a>
            </div>
        </div>

        {{-- =======================
            4) Commerce
        ======================= --}}
        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $commerceOpen ? 'active' : '' }}"
           data-bs-toggle="collapse"
           href="#menuCommerce"
           role="button"
           aria-expanded="{{ $commerceOpen ? 'true' : 'false' }}"
           aria-controls="menuCommerce">
            <span>بازرگانی</span>
            <span class="small">▾</span>
        </a>

        <div class="collapse {{ $commerceOpen ? 'show' : '' }}" id="menuCommerce">
            <div class="list-group list-group-flush ms-2 mt-1">
                <a class="list-group-item list-group-item-action {{ request()->routeIs('persons.*') ? 'active' : '' }}"
                   href="{{ route('persons.index') }}">
                    اشخاص
                </a>

                <a class="list-group-item list-group-item-action {{ request()->routeIs('users.*') ? 'active' : '' }}"
                   href="{{ route('users.index') }}">
                    کاربران
                </a>
            </div>
        </div>

        {{-- =======================
            5) Invoice
        ======================= --}}
        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $invoiceOpen ? 'active' : '' }}"
           data-bs-toggle="collapse"
           href="#menuInvoices"
           role="button"
           aria-expanded="{{ $invoiceOpen ? 'true' : 'false' }}"
           aria-controls="menuInvoices">
            <span>فاکتور</span>
            <span class="small">▾</span>
        </a>

        <div class="collapse {{ $invoiceOpen ? 'show' : '' }}" id="menuInvoices">
            <div class="list-group list-group-flush ms-2 mt-1">

                <a class="list-group-item list-group-item-action {{ request()->routeIs('preinvoice.create') ? 'active' : '' }}"
                   href="{{ route('preinvoice.create') }}">
                    ثبت پیش‌فاکتور
                </a>

                {{-- لیست پیش‌فاکتور‌ها --}}
                @if (Route::has('preinvoice.index'))
                    <a class="list-group-item list-group-item-action {{ request()->routeIs('preinvoice.index') ? 'active' : '' }}"
                       href="{{ route('preinvoice.index') }}">
                        پیش‌فاکتورها
                    </a>
                @endif

                <a class="list-group-item list-group-item-action {{ request()->routeIs('preinvoice.draft.index') ? 'active' : '' }}"
                   href="{{ route('preinvoice.draft.index') }}">
                    پیش‌نویس‌ها
                </a>

                @if (Route::has('invoices.index'))
                    <a class="list-group-item list-group-item-action {{ request()->routeIs('invoices.*') ? 'active' : '' }}"
                       href="{{ route('invoices.index') }}">
                        فاکتورها
                    </a>
                @endif
            </div>
        </div>

        {{-- =======================
            Activity Logs (single)
        ======================= --}}
        <a class="list-group-item list-group-item-action {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}"
           href="{{ route('activity-logs.index') }}">
            لاگ فعالیت
        </a>

    </div>
</div>