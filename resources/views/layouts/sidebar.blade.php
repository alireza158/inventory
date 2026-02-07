@php
    $is = fn($name) => request()->routeIs($name) ? 'active' : '';
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

        <a class="list-group-item list-group-item-action {{ $is('products.*') }}"
           href="{{ route('products.index') }}">
            کالاها
        </a>

        <a class="list-group-item list-group-item-action {{ $is('vouchers.*') }}"
           href="{{ route('vouchers.index') }}">
            خرید کالا / حواله‌ها
        </a>

        <a class="list-group-item list-group-item-action {{ $is('movements.index') }}"
           href="{{ route('movements.index') }}">
            گردش انبار
        </a>

        <a class="list-group-item list-group-item-action {{ $is('stocktake.index') }}"
           href="{{ route('stocktake.index') }}">
            انبارگردانی
        </a>
    </div>
</div>
