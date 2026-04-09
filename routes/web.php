@php
    $currentRouteName = request()->route()?->getName() ?? '';

    $routeIs = static fn (...$patterns) => \Illuminate\Support\Str::is($patterns, $currentRouteName);
    $routeClass = static fn (...$patterns) => $routeIs(...$patterns) ? 'is-active' : '';

    $routeHref = static function (array $names, array $parameters = [], string $fallback = '#') {
        foreach ($names as $name) {
            if (\Illuminate\Support\Facades\Route::has($name)) {
                return route($name, $parameters);
            }
        }

        return $fallback;
    };

    $productsActive = $routeIs('products.*', 'product-deactivation-documents.*', 'categories.*', 'model-lists.*')
        && ! $routeIs('products.create', 'products.import.show', 'products.import');

    $warehouseActive = $routeIs(
        'purchases.*',
        'vouchers.*',
        'stocktake.*',
        'asset.*',
        'preinvoice.warehouse.*',
        'products.create',
        'products.import.show',
        'products.import'
    );

    $salesActive = $routeIs(
        'preinvoice.create',
        'customers.*',
        'persons.*'
    );

    $financeActive = $routeIs(
        'preinvoice.draft.*',
        'account-statements.*',
        'invoices.*'
    );

    $configActive = $routeIs(
        'shipping-methods.*',
        'users.*',
        'activity-logs.*'
    );

    $initialOpenSection = match (true) {
        $productsActive => 'products',
        $warehouseActive => 'warehouse',
        $salesActive => 'sales',
        $financeActive => 'finance',
        $configActive => 'config',
        default => '',
    };

    $vouchersRoute = $routeHref(['vouchers.index']);
@endphp

<style>
  .sidebar-backdrop{
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,.45);
    opacity: 0;
    pointer-events: none;
    transition: opacity .22s ease;
    z-index: 1190;
  }

  .app-sidebar{
    width: 280px;
    flex: 0 0 280px;
    background: #fff;
  }

  .sidebar-scroll{
    height: 100%;
    overflow-y: auto;
    overflow-x: hidden;
  }

  .sidebar-section-link,
  .sidebar-accordion-trigger{
    width: 100%;
    border: 0;
    background: transparent;
    text-align: right;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    cursor: pointer;
  }

  .sidebar-section-link{
    color: inherit;
  }

  .sidebar-accordion-trigger-icon{
    flex: 0 0 auto;
    width: 18px;
    height: 18px;
    opacity: .6;
    transform: rotate(0deg);
    transition: transform .22s ease, opacity .22s ease;
  }

  .sidebar-accordion-item.is-open .sidebar-accordion-trigger-icon{
    transform: rotate(-180deg);
    opacity: 1;
  }

  .sidebar-accordion-panel{
    overflow: hidden;
    max-height: 0;
    opacity: 0;
    transition: max-height .26s ease, opacity .18s ease;
  }

  .sidebar-accordion-item.is-open .sidebar-accordion-panel{
    opacity: 1;
  }

  @media (max-width: 991.98px){
    .app-sidebar{
      position: fixed;
      top: 0;
      right: 0;
      height: 100dvh;
      max-height: 100dvh;
      z-index: 1210;
      box-shadow: 0 16px 40px rgba(15,23,42,.22);
      transform: translateX(110%);
      transition: transform .24s ease;
      border-top-left-radius: 18px;
      border-bottom-left-radius: 18px;
      overflow: hidden;
    }

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
      display:flex;
      align-items:center;
      gap:10px;
      min-width:0;
    }

    .app-sidebar__mobile-head .brand img{
      width: 40px;
      height: 40px;
      object-fit: contain;
    }

    .app-sidebar__mobile-head .brand .t{
      font-weight: 800;
      font-size: 13px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .app-sidebar__mobile-head .close-btn{
      width: 40px;
      height: 40px;
      border-radius: 12px;
      border: 1px solid rgba(15,23,42,.12);
      background: #fff;
      display:flex;
      align-items:center;
      justify-content:center;
    }

    .app-sidebar__mobile-head .close-btn svg{
      width:18px;
      height:18px;
    }
  }

  body.sidebar-open .app-sidebar{
    transform: translateX(0);
  }

  body.sidebar-open .sidebar-backdrop{
    opacity: 1;
    pointer-events: auto;
  }

  body.sidebar-open{
    overflow: hidden;
    touch-action: none;
  }
</style>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-scroll">

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

        <div class="app-sidebar__brand d-none d-lg-flex">
            <img
                src="{{ asset('logo.png') }}"
                alt="{{ config('app.name') }}"
                style="height:56px;width:56px;object-fit:contain;"
            >
            <div class="app-sidebar__title">{{ config('app.name', 'سیستم انبار آریا جانبی') }}</div>
            <div class="app-sidebar__subtitle">مدیریت موجودی و گردش کالا</div>
        </div>

        <a
            class="sidebar-section-title sidebar-section-link {{ $routeClass('dashboard') }}"
            href="{{ $routeHref(['dashboard']) }}"
        >
            <span>داشبورد</span>
        </a>

        <div class="sidebar-accordion-item {{ $productsActive ? 'is-open' : '' }}" data-accordion-section="products">
            <button
                type="button"
                class="sidebar-section-title sidebar-accordion-trigger {{ $productsActive ? 'is-active' : '' }}"
                data-accordion-trigger
                aria-expanded="{{ $productsActive ? 'true' : 'false' }}"
            >
                <span>کالاها</span>
                <svg class="sidebar-accordion-trigger-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div class="sidebar-accordion-panel" data-accordion-panel>
                <div class="sidebar-submenu">
                    <a class="sidebar-sublink {{ $routeClass('products.index') }}" href="{{ $routeHref(['products.index']) }}">نمایش کالاها</a>
                    <a class="sidebar-sublink {{ $routeClass('categories.*') }}" href="{{ $routeHref(['categories.index']) }}">دسته‌بندی محصولات</a>
                    <a class="sidebar-sublink {{ $routeClass('model-lists.*') }}" href="{{ $routeHref(['model-lists.index']) }}">مدل لیست</a>
                    <a class="sidebar-sublink {{ $routeClass('product-deactivation-documents.*') }}" href="{{ $routeHref(['product-deactivation-documents.index']) }}">غیرفعال‌سازی کالا</a>
                </div>
            </div>
        </div>

        <div class="sidebar-accordion-item {{ $warehouseActive ? 'is-open' : '' }}" data-accordion-section="warehouse">
            <button
                type="button"
                class="sidebar-section-title sidebar-accordion-trigger {{ $warehouseActive ? 'is-active' : '' }}"
                data-accordion-trigger
                aria-expanded="{{ $warehouseActive ? 'true' : 'false' }}"
            >
                <span>انبارداری</span>
                <svg class="sidebar-accordion-trigger-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div class="sidebar-accordion-panel" data-accordion-panel>
                <div class="sidebar-submenu">
                    <a class="sidebar-sublink {{ $routeClass('products.create') }}" href="{{ $routeHref(['products.create']) }}">افزودن کالا</a>
                    <a class="sidebar-sublink {{ $routeClass('products.import.show', 'products.import') }}" href="{{ $routeHref(['products.import.show']) }}">تعریف کالا</a>
                    <a class="sidebar-sublink {{ $routeClass('preinvoice.warehouse.*') }}" href="{{ $routeHref(['preinvoice.warehouse.index']) }}">در انتظار تایید انبار</a>
                    <a class="sidebar-sublink {{ $routeClass('vouchers.*') }}" href="{{ $vouchersRoute }}">حواله‌های انبار</a>
                    <a class="sidebar-sublink {{ $routeClass('stocktake.*') }}" href="{{ $routeHref(['stocktake.index']) }}">انبارگردانی</a>
                    <a class="sidebar-sublink {{ $routeClass('asset.*') }}" href="{{ $routeHref(['asset.hub']) }}">امین اموال</a>
                </div>
            </div>
        </div>

        <div class="sidebar-accordion-item {{ $salesActive ? 'is-open' : '' }}" data-accordion-section="sales">
            <button
                type="button"
                class="sidebar-section-title sidebar-accordion-trigger {{ $salesActive ? 'is-active' : '' }}"
                data-accordion-trigger
                aria-expanded="{{ $salesActive ? 'true' : 'false' }}"
            >
                <span>بازرگانی و فروش</span>
                <svg class="sidebar-accordion-trigger-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div class="sidebar-accordion-panel" data-accordion-panel>
                <div class="sidebar-submenu">
                    <a class="sidebar-sublink {{ $routeClass('preinvoice.create') }}" href="{{ $routeHref(['preinvoice.create']) }}">ثبت پیش‌فاکتور</a>
                    <a class="sidebar-sublink {{ $routeClass('customers.*', 'persons.*') }}" href="{{ $routeHref(['customers.index']) }}">اشخاص و طرف‌حساب‌ها</a>
                </div>
            </div>
        </div>

        <div class="sidebar-accordion-item {{ $financeActive ? 'is-open' : '' }}" data-accordion-section="finance">
            <button
                type="button"
                class="sidebar-section-title sidebar-accordion-trigger {{ $financeActive ? 'is-active' : '' }}"
                data-accordion-trigger
                aria-expanded="{{ $financeActive ? 'true' : 'false' }}"
            >
                <span>مالی</span>
                <svg class="sidebar-accordion-trigger-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div class="sidebar-accordion-panel" data-accordion-panel>
                <div class="sidebar-submenu">
                    <a class="sidebar-sublink {{ $routeClass('preinvoice.draft.*') }}" href="{{ $routeHref(['preinvoice.draft.index']) }}">در انتظار تایید مالی</a>
                    <a class="sidebar-sublink {{ $routeClass('account-statements.*') }}" href="{{ $routeHref(['account-statements.index']) }}">گردش حساب اشخاص</a>
                    <a class="sidebar-sublink {{ $routeClass('invoices.*') }}" href="{{ $routeHref(['invoices.index']) }}">فاکتورها</a>
                </div>
            </div>
        </div>

        <div class="sidebar-accordion-item {{ $configActive ? 'is-open' : '' }}" data-accordion-section="config">
            <button
                type="button"
                class="sidebar-section-title sidebar-accordion-trigger {{ $configActive ? 'is-active' : '' }}"
                data-accordion-trigger
                aria-expanded="{{ $configActive ? 'true' : 'false' }}"
            >
                <span>پیکربندی</span>
                <svg class="sidebar-accordion-trigger-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div class="sidebar-accordion-panel" data-accordion-panel>
                <div class="sidebar-submenu">
                    <a class="sidebar-sublink {{ $routeClass('shipping-methods.*') }}" href="{{ $routeHref(['shipping-methods.index']) }}">روش‌های ارسال بار</a>
                    <a class="sidebar-sublink {{ $routeClass('users.*') }}" href="{{ $routeHref(['users.index']) }}">کاربران و پرسنل</a>
                    <a class="sidebar-sublink {{ $routeClass('activity-logs.*') }}" href="{{ $routeHref(['activity-logs.index']) }}">لاگ فعالیت کاربران</a>
                </div>
            </div>
        </div>

    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebarStorageKey = 'inventory.sidebar.open-section';
    const initialOpenSection = @json($initialOpenSection);

    const body = document.body;
    const btnOpen = document.getElementById('sidebarToggleBtn');
    const btnClose = document.getElementById('sidebarCloseBtn');
    const backdrop = document.getElementById('sidebarBackdrop');
    const sidebar = document.getElementById('appSidebar');

    if (!sidebar) return;

    const accordionItems = Array.from(sidebar.querySelectorAll('[data-accordion-section]'));

    function isMobile() {
        return window.matchMedia('(max-width: 991.98px)').matches;
    }

    function getPanel(item) {
        return item.querySelector('[data-accordion-panel]');
    }

    function getTrigger(item) {
        return item.querySelector('[data-accordion-trigger]');
    }

    function openSidebar() {
        if (isMobile()) {
            body.classList.add('sidebar-open');
        }
    }

    function closeSidebar() {
        body.classList.remove('sidebar-open');
    }

    function setPanelHeight(item, isOpen) {
        const panel = getPanel(item);
        const trigger = getTrigger(item);

        if (!panel || !trigger) return;

        item.classList.toggle('is-open', isOpen);
        trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        panel.style.maxHeight = isOpen ? panel.scrollHeight + 'px' : '0px';
    }

    function closeAllExcept(sectionId = '') {
        accordionItems.forEach(function (item) {
            const currentId = item.getAttribute('data-accordion-section') || '';
            setPanelHeight(item, currentId === sectionId);
        });
    }

    function rememberSection(sectionId = '') {
        try {
            if (sectionId) {
                localStorage.setItem(sidebarStorageKey, sectionId);
            } else {
                localStorage.removeItem(sidebarStorageKey);
            }
        } catch (e) {}
    }

    function getRememberedSection() {
        try {
            return localStorage.getItem(sidebarStorageKey) || '';
        } catch (e) {
            return '';
        }
    }

    function initAccordion() {
        const remembered = getRememberedSection();
        const sectionToOpen = initialOpenSection || remembered || '';
        closeAllExcept(sectionToOpen);
    }

    function refreshOpenPanels() {
        accordionItems.forEach(function (item) {
            if (item.classList.contains('is-open')) {
                const panel = getPanel(item);
                if (panel) {
                    panel.style.maxHeight = panel.scrollHeight + 'px';
                }
            }
        });
    }

    btnOpen?.addEventListener('click', openSidebar);
    btnClose?.addEventListener('click', closeSidebar);
    backdrop?.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });

    accordionItems.forEach(function (item) {
        const trigger = getTrigger(item);
        if (!trigger) return;

        trigger.addEventListener('click', function () {
            const sectionId = item.getAttribute('data-accordion-section') || '';
            const willOpen = !item.classList.contains('is-open');

            closeAllExcept(willOpen ? sectionId : '');
            rememberSection(willOpen ? sectionId : '');
        });
    });

    sidebar.addEventListener('click', function (e) {
        const link = e.target.closest('a');
        if (link && isMobile()) {
            setTimeout(closeSidebar, 80);
        }
    });

    window.addEventListener('resize', function () {
        if (!isMobile()) {
            closeSidebar();
        }
        refreshOpenPanels();
    });

    initAccordion();
    refreshOpenPanels();
});
</script>