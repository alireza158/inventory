@php
    $is = fn($name) => request()->routeIs($name) ? 'active' : '';

    // برای active شدن گروه پیش‌فاکتور وقتی داخل هرکدوم از route هاش هستی
    $preinvoiceOpen = request()->routeIs('preinvoice.*');
    $productsOpen = request()->routeIs('products.*');
    $categoriesOpen = request()->routeIs('categories.*');
    $peopleOpen = request()->routeIs('persons.*') || request()->routeIs('customers.*') || request()->routeIs('suppliers.*') || request()->routeIs('users.*');
    $modelListsOpen = request()->routeIs('model-lists.*');
@endphp

<div class="bg-white border-end p-3" style="width: 260px">
    <div class="mb-3">
        <div class="fw-bold">پنل انبار</div>
        <div class="text-muted small">مدیریت موجودی و گردش کالا</div>
    </div>

    <div class="list-group list-group-flush">
        <a class="list-group-item list-group-item-action {{ $is('dashboard') }}"
           href="{{ route('dashboard') }}">
            داشبورد
        </a>

        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $productsOpen ? 'active' : '' }}"
           data-bs-toggle="collapse"
           href="#productsMenu"
           role="button"
           aria-expanded="{{ $productsOpen ? 'true' : 'false' }}"
           aria-controls="productsMenu">
            <span>کالاها</span>
            <span class="small">▾</span>
        </a>

        <div class="collapse {{ $productsOpen ? 'show' : '' }}" id="productsMenu">
            <div class="list-group list-group-flush ms-2 mt-1">
                <a class="list-group-item list-group-item-action {{ $is('products.index') }}"
                   href="{{ route('products.index') }}">
                    کلیه کالاها
                </a>

                <a class="list-group-item list-group-item-action {{ $is('products.create') }}"
                   href="{{ route('products.create') }}">
                    ➕ افزودن کالا
                </a>
            </div>
        </div>

        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $categoriesOpen ? 'active' : '' }}"
           data-bs-toggle="collapse"
           href="#categoriesMenu"
           role="button"
           aria-expanded="{{ $categoriesOpen ? 'true' : 'false' }}"
           aria-controls="categoriesMenu">
            <span>دسته‌بندی‌ها</span>
            <span class="small">▾</span>
        </a>

        <div class="collapse {{ $categoriesOpen ? 'show' : '' }}" id="categoriesMenu">
            <div class="list-group list-group-flush ms-2 mt-1">
                <a class="list-group-item list-group-item-action {{ $is('categories.index') }}"
                   href="{{ route('categories.index') }}">
                    لیست دسته‌بندی‌ها
                </a>

                <a class="list-group-item list-group-item-action {{ $is('categories.create') }}"
                   href="{{ route('categories.create') }}">
                    ➕ افزودن دسته‌بندی
                </a>
            </div>
        </div>


        <a class="list-group-item list-group-item-action {{ $modelListsOpen ? 'active' : '' }}"
           href="{{ route('model-lists.index') }}">
            مدل لیست‌ها
        </a>

        <div class="mt-2">
            <div class="text-muted small mb-2">خرید کالا / حواله‌ها</div>

            <a class="list-group-item list-group-item-action {{ $peopleOpen ? 'active' : '' }}"
               href="{{ route('persons.index') }}">
                اشخاص
            </a>

            <a class="list-group-item list-group-item-action {{ $is('users.*') }}"
               href="{{ route('users.index') }}">
                کاربران
            </a>

            <a class="list-group-item list-group-item-action {{ $is('purchases.*') }}"
               href="{{ route('purchases.index') }}">
                خرید کالا
            </a>

            <a class="list-group-item list-group-item-action {{ $is('vouchers.*') }}"
               href="{{ route('vouchers.index') }}">
                حواله‌ها
            </a>

            <a class="list-group-item list-group-item-action {{ $is('warehouses.*') }}"
               href="{{ route('warehouses.index') }}">
                انبارها
            </a>
        </div>

        <a class="list-group-item list-group-item-action {{ $is('stocktake.index') }}"
           href="{{ route('stocktake.index') }}">
            انبارگردانی
        </a>

        {{-- =========================
             پیش‌فاکتور
        ========================= --}}
        <div class="mt-3">
            <div class="text-muted small mb-2">پیش‌فاکتور</div>

            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $preinvoiceOpen ? 'active' : '' }}"
               data-bs-toggle="collapse"
               href="#preinvoiceMenu"
               role="button"
               aria-expanded="{{ $preinvoiceOpen ? 'true' : 'false' }}"
               aria-controls="preinvoiceMenu">
                <span>پیش‌فاکتور</span>
                <span class="small">▾</span>
            </a>

            <div class="collapse {{ $preinvoiceOpen ? 'show' : '' }}" id="preinvoiceMenu">
                <div class="list-group list-group-flush ms-2 mt-1">
                    <a class="list-group-item list-group-item-action {{ $is('preinvoice.create') }}"
                       href="{{ route('preinvoice.create') }}">
                        ➕ ایجاد پیش‌فاکتور
                    </a>

                    <a class="list-group-item list-group-item-action {{ $is('preinvoice.draft.index') }}"
                       href="{{ route('preinvoice.draft.index') }}">
                        📝 پیش‌نویس‌ها
                    </a>

                    {{-- اگر داری: لیست پیش‌فاکتورهای نهایی --}}
                    {{-- <a class="list-group-item list-group-item-action {{ $is('preinvoice.index') }}"
                       href="{{ route('preinvoice.index') }}">
                        📄 پیش‌فاکتورهای ثبت‌شده
                    </a> --}}
                </div>



            </div>
         <a class="list-group-item list-group-item-action {{ $is('invoices.*') }}"
   href="{{ route('invoices.index') }}">
   فاکتورها
</a>

        <a class="list-group-item list-group-item-action {{ $is('activity-logs.index') }}"
           href="{{ route('activity-logs.index') }}">
            لاگ فعالیت‌ها
        </a>

        </div>

    </div>
</div>
