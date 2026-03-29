@php
    $currentRouteName = request()->route()?->getName() ?? '';

    $isRoute = static fn(string ...$patterns): bool => Str::is($patterns, $currentRouteName);
    $is = static fn(string ...$patterns): string => $isRoute(...$patterns) ? 'active' : '';

    $productsActive = $isRoute('products.*', 'categories.*', 'model-lists.*');

    $warehouseActive = $isRoute('purchases.*', 'vouchers.*', 'warehouses.*', 'stocktake.*', 'stocktake.index');

    $commerceActive  = $isRoute('persons.*', 'customers.*', 'suppliers.*', 'users.*');

    $invoiceActive   = $isRoute('preinvoice.*', 'invoices.*', 'shipping-methods.*');
@endphp

<style>

  /* ===== Backdrop ===== */
  .sidebar-backdrop{
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,.45);
    opacity: 0;
    pointer-events: none;
    transition: opacity .22s ease;
    z-index: 1190;
  }

  /* ===== Sidebar base ===== */
  .app-sidebar{
    width: 280px;
    flex: 0 0 280px;
  }

  /* ===== Mobile Offcanvas Behavior ===== */
  @media (max-width: 991.98px){
    .app-sidebar{
      position: fixed;
      top: 0;
      right: 0;
      height: 100dvh;
      max-height: 100dvh;
      z-index: 1210;
      background: #fff;
      box-shadow: 0 16px 40px rgba(15,23,42,.22);
      transform: translateX(110%);
      transition: transform .24s ease;
      border-top-left-radius: 18px;
      border-bottom-left-radius: 18px;
      overflow: hidden;
    }

    .app-sidebar .sidebar-scroll{
      height: 100%;
      overflow: auto;
      -webkit-overflow-scrolling: touch;
      padding-bottom: 18px;
    }

    /* header داخل سایدبار روی موبایل */
    .app-sidebar__mobile-head{
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 12px 14px;
      border-bottom: 1px solid rgba(15,23,42,.08);
      background: linear-gradient(0deg,#fff,#f6f8ff);
    }
    .app-sidebar__mobile-head .brand{
      display:flex; align-items:center; gap:10px; min-width:0;
    }
    .app-sidebar__mobile-head .brand img{
      width: 40px; height: 40px; object-fit: contain;
    }
    .app-sidebar__mobile-head .brand .t{
      font-weight: 800;
      font-size: 13px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .app-sidebar__mobile-head .close-btn{
      width: 40px; height: 40px;
      border-radius: 12px;
      border: 1px solid rgba(15,23,42,.12);
      background: #fff;
      display:flex; align-items:center; justify-content:center;
    }
    .app-sidebar__mobile-head .close-btn svg{ width:18px; height:18px; }
  }

  /* ===== Open state ===== */
  body.sidebar-open .app-sidebar{
    transform: translateX(0);
  }
  body.sidebar-open .sidebar-backdrop{
    opacity: 1;
    pointer-events: auto;
  }
  body.sidebar-open{
    overflow: hidden; /* قفل اسکرول */
    touch-action: none;
  }
</style>

{{-- بک‌دراپ --}}
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="app-sidebar" id="appSidebar">
    <div class="sidebar-scroll">

        {{-- Mobile Head (فقط موبایل) --}}
        <div class="app-sidebar__mobile-head d-lg-none">
          <div class="brand">
            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}">
            <div class="t">{{ config('app.name', 'سیستم انبار آریا جانبی') }}</div>
          </div>
          <button type="button" class="close-btn" id="sidebarCloseBtn" aria-label="بستن منو">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        {{-- Brand (desktop) --}}
        <div class="app-sidebar__brand d-none d-lg-flex">
            <img src="{{ asset('logo.png') }}"
                 alt="{{ config('app.name') }}"
                 style="height:56px;width:56px;object-fit:contain;">
            <div class="app-sidebar__title">{{ config('app.name', 'سیستم انبار آریا جانبی') }}</div>
            <div class="app-sidebar__subtitle">مدیریت موجودی و گردش کالا</div>
        </div>

        {{-- 1) Dashboard --}}
        <a class="sidebar-link {{ $is('dashboard') }}" href="{{ route('dashboard') }}">
            <span class="title">داشبورد</span>
        </a>

        {{-- Products --}}
        <div class="sidebar-section-title {{ $productsActive ? 'is-active' : '' }}">محصولات</div>
        <div class="sidebar-submenu">
            <a class="sidebar-sublink {{ $is('products.index') }}" href="{{ route('products.index') }}">کالاها</a>
            <a class="sidebar-sublink {{ $is('categories.index') }}" href="{{ route('categories.index') }}">دسته‌بندی</a>
            <a class="sidebar-sublink {{ $is('model-lists.index') }}" href="{{ route('model-lists.index') }}">مدل لیست</a>
        </div>

        {{-- Warehouse --}}
        <div class="sidebar-section-title {{ $warehouseActive ? 'is-active' : '' }}">انبارداری</div>
        <div class="sidebar-submenu">
            <a class="sidebar-sublink {{ $is('purchases.*') }}" href="{{ route('purchases.index') }}">خرید کالا</a>
            <a class="sidebar-sublink {{ $is('vouchers.*') }}" href="{{ route('vouchers.index') }}">حواله</a>
            <a class="sidebar-sublink {{ $is('warehouses.*') }}" href="{{ route('warehouses.index') }}">انبارها</a>
            <a class="sidebar-sublink {{ $is('stocktake.*', 'stocktake.index') }}" href="{{ route('stocktake.index') }}">انبارگردانی</a>
        </div>

        {{-- Commerce --}}
        <div class="sidebar-section-title {{ $commerceActive ? 'is-active' : '' }}">بازرگانی</div>
        <div class="sidebar-submenu">
            <a class="sidebar-sublink {{ $is('persons.*') }}" href="{{ route('persons.index') }}">اشخاص</a>
            <a class="sidebar-sublink {{ $is('users.*') }}" href="{{ route('users.index') }}">کاربران</a>
        </div>

        {{-- Invoice --}}
        <div class="sidebar-section-title {{ $invoiceActive ? 'is-active' : '' }}">فاکتور</div>
        <div class="sidebar-submenu">
            <a class="sidebar-sublink {{ $is('preinvoice.create') }}" href="{{ route('preinvoice.create') }}">ثبت پیش‌فاکتور</a>

            @if (Route::has('preinvoice.index'))
                <a class="sidebar-sublink {{ $is('preinvoice.index') }}" href="{{ route('preinvoice.index') }}">پیش‌فاکتورها</a>
            @endif

            <a class="sidebar-sublink {{ $is('preinvoice.draft.index') }}" href="{{ route('preinvoice.draft.index') }}">صف تایید مالی</a>
            <a class="sidebar-sublink {{ $is('shipping-methods.*') }}" href="{{ route('shipping-methods.index') }}">روش‌های ارسال</a>

            @if (Route::has('invoices.index'))
                <a class="sidebar-sublink {{ $is('invoices.*') }}" href="{{ route('invoices.index') }}">فاکتورها</a>
            @endif
        </div>


        {{-- Other --}}
        <div class="sidebar-group-label">سایر</div>
        <a class="sidebar-link {{ $is('activity-logs.*') }}" href="{{ route('activity-logs.index') }}">
            <span class="title">لاگ فعالیت</span>
        </a>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  var btnOpen = document.getElementById('sidebarToggleBtn');
  var btnClose = document.getElementById('sidebarCloseBtn');
  var backdrop = document.getElementById('sidebarBackdrop');
  var sidebar = document.getElementById('appSidebar');

  function isMobile(){
    return window.matchMedia('(max-width: 991.98px)').matches;
  }

  function openSidebar(){
    if(!isMobile()) return;
    document.body.classList.add('sidebar-open');
  }

  function closeSidebar(){
    document.body.classList.remove('sidebar-open');
  }

  btnOpen && btnOpen.addEventListener('click', openSidebar);
  btnClose && btnClose.addEventListener('click', closeSidebar);
  backdrop && backdrop.addEventListener('click', closeSidebar);

  // بستن با ESC
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeSidebar();
  });

  // کلیک روی لینک‌ها => بستن خودکار (موبایل)
  if(sidebar){
    sidebar.addEventListener('click', function(e){
      var a = e.target && e.target.closest ? e.target.closest('a') : null;
      if(a && isMobile()){
        // کمی تاخیر برای احساس بهتر کلیک
        setTimeout(closeSidebar, 60);
      }
    });
  }

  // اگر از موبایل رفت روی دسکتاپ، خودکار بسته شود
  window.addEventListener('resize', function(){
    if(!isMobile()) closeSidebar();
  });
});
</script>
