@extends('layouts.app')

@section('content')
@php
    $currentSort = $sort ?? 'id';
    $currentDir = $dir ?? 'desc';
    $toFa = fn ($value) => strtr((string) $value, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
    $sortLink = function (string $key) use ($currentSort, $currentDir) {
        $nextDir = ($currentSort === $key && $currentDir === 'asc') ? 'desc' : 'asc';
        return route('products.index', array_merge(request()->query(), ['sort' => $key, 'dir' => $nextDir, 'page' => null]));
    };
    $sortArrow = function (string $key) use ($currentSort, $currentDir) {
        if ($currentSort !== $key) return '↕';
        return $currentDir === 'asc' ? '↑' : '↓';
    };
@endphp
<style>
    :root{
        --soft-bg:#f6f8fb;
        --soft-border:#e8edf3;
        --card-radius:16px;
        --grid:#e6ebf2;
        --grid2:#f4f7fb;
    }

    .page-head{
        background: linear-gradient(180deg, rgba(13,110,253,.10), rgba(13,110,253,0));
        border: 1px solid var(--soft-border);
        border-radius: var(--card-radius);
        padding: 12px 14px;
    }
    .page-title{font-weight: 800;letter-spacing: -.3px;margin:0;}
    .subtle-text{ color:#6b7280; }

    .soft-card{
        border: 1px solid var(--soft-border);
        border-radius: var(--card-radius);
        box-shadow: 0 10px 30px rgba(16,24,40,.06);
        background:#fff;
    }

    .sticky-panel{ position: sticky; top: 90px; }
    .cat-card .form-control{ border-radius: 12px; }

    .cat-tree-wrap{
        max-height: calc(100vh - 240px);
        overflow: auto;
        padding-right: 4px;
    }
    .cat-tree-wrap::-webkit-scrollbar{ width: 8px; }
    .cat-tree-wrap::-webkit-scrollbar-thumb{ background: #d8dee8; border-radius: 99px; }

    .filter-card .form-control,
    .filter-card .form-select{ border-radius: 12px; }

    .btn-soft{
        border-radius: 12px;
        border: 1px solid var(--soft-border);
        background: #fff;
        padding: .35rem .55rem;
    }
    .btn-soft:hover{ background:#f8fafc; }

    .pill{
        display:inline-flex;
        align-items:center;
        gap:6px;
        border-radius: 999px;
        padding: 3px 9px;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid var(--soft-border);
        background: #fff;
        white-space: nowrap;
    }
    .pill-gray{ background:#f8fafc; }
    .pill-danger{ background: rgba(220,53,69,.08); border-color: rgba(220,53,69,.25); color:#b42318; }
    .pill-success{ background: rgba(25,135,84,.10); border-color: rgba(25,135,84,.25); color:#146c43; }

    .product-name-wrap{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:8px;
    }
    .status-dot{
        width:10px;
        height:10px;
        border-radius:50%;
        flex:0 0 10px;
        margin-top:4px;
        box-shadow:0 0 0 2px rgba(255,255,255,.9);
    }
    .status-dot.active{ background:#22c55e; }
    .status-dot.inactive{ background:#ef4444; }
    .sortable-link{
        color: inherit;
        text-decoration: none;
        display:inline-flex;
        align-items:center;
        gap:6px;
    }
    .sortable-link:hover{ color:#0d6efd; }
    .sort-arrow{
        font-size:11px;
        color:#6b7280;
    }
    .bulk-toolbar{
        border:1px solid var(--soft-border);
        border-radius:14px;
        padding:10px 12px;
        background:#f8fafc;
    }
    .buy-price-muted{ color:#9ca3af; }
    .price-inline{
        white-space: nowrap;
        font-weight: 700;
    }
    .variant-operation-select{
        min-width: 240px;
        border-radius: 10px;
    }
    .btn-stock-breakdown{
        border: 0;
        border-radius: 12px;
        background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);
        color: #fff;
        box-shadow: 0 8px 18px rgba(37,99,235,.25);
        transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease;
    }
    .btn-stock-breakdown:hover{
        color:#fff;
        transform: translateY(-1px);
        box-shadow: 0 12px 22px rgba(37,99,235,.32);
    }
    .btn-stock-breakdown:focus{ color:#fff; }
    .btn-stock-breakdown:disabled{
        opacity: .65;
        box-shadow: none;
        transform: none;
    }

    .mono{
        font-family: ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;
        letter-spacing: .5px;
    }

    /* اکسل/شیت */
    .sheet-wrap{
        border: 1px solid var(--grid);
        border-radius: var(--card-radius);
        overflow: hidden;
        background:#fff;
    }
    .sheet{
        margin:0;
        border-collapse: separate;
        border-spacing: 0;
        width:100%;
    }
    .sheet thead th{
        position: sticky;
        top: 0;
        z-index: 3;
        background: #fff;
        font-weight: 800;
        color:#111827;
        padding: .55rem .6rem;
        font-size: 13px;
        border-bottom: 1px solid var(--grid);
        border-left: 1px solid var(--grid);
        white-space: nowrap;
    }
    .sheet thead th:first-child{ border-right: 1px solid var(--grid); }
    .sheet td{
        padding: .55rem .6rem;
        font-size: 13px;
        border-bottom: 1px solid var(--grid2);
        border-left: 1px solid var(--grid);
        vertical-align: middle;
        background:#fff;
    }
    .sheet td:first-child{ border-right: 1px solid var(--grid); }
    .sheet tbody tr:hover td{ background:#fbfdff; }

    .toggle-variants{
        width: 30px;
        height: 30px;
        border-radius: 10px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border:1px solid var(--soft-border);
        background:#fff;
        font-weight:900;
    }
    .toggle-variants:hover{ background:#f8fafc; }

    /* Responsive columns */
    @media (min-width: 992px){
        .col-cat { flex: 0 0 auto; width: 24%; }
        .col-main{ flex: 0 0 auto; width: 76%; }
    }
    @media (min-width: 1200px){
        .col-cat { width: 20%; }
        .col-main{ width: 80%; }
    }
</style>

<div class="row g-3">

    {{-- Categories Sidebar (Desktop) --}}
    <div class="col-cat d-none d-lg-block">
        <div class="soft-card cat-card sticky-panel">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-bold">دسته‌بندی‌ها</div>
                    <a href="{{ route('products.index', request()->except(['category_id', 'page'])) }}" class="small text-decoration-none">همه</a>
                </div>

                <input type="text" id="catSearch" class="form-control form-control-sm mb-3" placeholder="جستجو در دسته‌ها...">

                <div class="cat-tree-wrap" id="catTree">
                    @include('categories._tree', ['nodes' => $categoryTree])
                </div>
            </div>
        </div>
    </div>

    {{-- Main --}}
    <div class="col-main">

        {{-- Header --}}
        <div class="page-head mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="page-title">کالاها</h4>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-success" href="{{ route('purchases.create') }}">+ خرید کالا</a>

                    <button class="btn btn-soft d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#catOffcanvas">
                        دسته‌بندی‌ها
                    </button>

                    <a class="btn btn-primary" href="{{ route('products.create') }}">+ افزودن کالا</a>
                </div>
            </div>
        </div>
        <div id="productsAjaxArea">
        <div class="soft-card filter-card mb-3">
            <div class="card-body">
                <div class="bulk-toolbar mb-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="fw-bold">عملیات روی کالا</div>
                        <div class="small subtle-text">ابتدا یک کالا را انتخاب کنید، سپس عملیات را اجرا کنید.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <button class="btn btn-primary btn-sm" type="button" id="bulkEditBtn">ویرایش</button>
                        <button class="btn btn-outline-danger btn-sm" type="button" id="bulkDeleteBtn">حذف</button>
                        <select id="bulkVariantSelect" class="form-select form-select-sm variant-operation-select" disabled>
                            <option value="">انتخاب تنوع محصول...</option>
                        </select>
                        <button class="btn btn-sm btn-stock-breakdown" type="button" id="bulkStockBtn">📦 موجودی انبار به تفکیک</button>
                    </div>
                    <div class="small subtle-text mt-2" id="variantHelpText">برای انتخاب تنوع، ابتدا فقط یک کالا را تیک بزنید.</div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div class="fw-bold">فیلترها</div>
                </div>

                <form method="GET" action="{{ route('products.index') }}">
                    @if(request('category_id'))
                        <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                    @endif

                    <div class="row g-3 align-items-end">
                        <div class="col-lg-5">
                            <label class="form-label">جستجو (نام / کد ۴ رقمی / کد محصول)</label>
                            <input name="q" class="form-control" value="{{ request('q') }}" placeholder="مثلاً 0490 یا گارد یونیک">
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label">وضعیت موجودی</label>
                            <select name="stock_status" class="form-select">
                                <option value="" @selected(request('stock_status')==='' || is_null(request('stock_status')))>همه</option>
                                <option value="out" @selected(request('stock_status')==='out')>ناموجود</option>
                            </select>
                        </div>

                        <div class="col-lg-2">
                            <label class="form-label">وضعیت فروش</label>
                            <select name="sellable_status" class="form-select">
                                <option value="" @selected(request('sellable_status')==='' || is_null(request('sellable_status')))>همه</option>
                                <option value="sellable" @selected(request('sellable_status')==='sellable')>قابل فروش</option>
                                <option value="unsellable" @selected(request('sellable_status')==='unsellable')>غیرقابل فروش</option>
                            </select>
                        </div>

                        <div class="col-lg-2">
                            <label class="form-label">بازه قیمت (تومان)</label>
                            <div class="input-group">
                                <input name="min_price" class="form-control money" value="{{ request('min_price') }}" placeholder="از">
                                <input name="max_price" class="form-control money" value="{{ request('max_price') }}" placeholder="تا">
                            </div>
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button class="btn btn-primary">اعمال فیلتر</button>
                            <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">پاک کردن</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Table --}}
        <div class="soft-card">
            <div class="card-body pb-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div class="small subtle-text">
                        نمایش {{ $products->firstItem() ?? 0 }} تا {{ $products->lastItem() ?? 0 }} از {{ $products->total() ?? 0 }} مورد
                    </div>
                </div>

                <div class="sheet-wrap">
                    <div class="table-responsive">
                        <table class="sheet">
                            <thead>
                                <tr>
                                    <th class="w-1 text-center">
                                        <input type="checkbox" class="form-check-input" id="selectAllProducts" title="انتخاب همه">
                                    </th>
                                    <th class="w-1"></th>
                                    <th class="nowrap">
                                        <a href="{{ $sortLink('short_barcode') }}" class="sortable-link">کد کالا <span class="sort-arrow">{{ $sortArrow('short_barcode') }}</span></a>
                                    </th>
                                    <th class="nowrap">
                                        <a href="{{ $sortLink('barcode') }}" class="sortable-link">بارکد کالا <span class="sort-arrow">{{ $sortArrow('barcode') }}</span></a>
                                    </th>
                                    <th>
                                        <a href="{{ $sortLink('name') }}" class="sortable-link">اسم کالا <span class="sort-arrow">{{ $sortArrow('name') }}</span></a>
                                    </th>
                                    <th class="nowrap">
                                        <a href="{{ $sortLink('stock') }}" class="sortable-link">موجودی <span class="sort-arrow">{{ $sortArrow('stock') }}</span></a>
                                    </th>
                                    <th class="nowrap">
                                        <a href="{{ $sortLink('variants_buy_price_min') }}" class="sortable-link">قیمت خرید <span class="sort-arrow">{{ $sortArrow('variants_buy_price_min') }}</span></a>
                                    </th>
                                    <th class="nowrap">
                                        <a href="{{ $sortLink('price') }}" class="sortable-link">قیمت فروش <span class="sort-arrow">{{ $sortArrow('price') }}</span></a>
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($products as $p)
                                    @php
                                        $hasVariants = $p->variants && $p->variants->count() > 0;
                                        $collapseId = "variantsRow{$p->id}";
                                        $short = $p->short_barcode;
                                        if (!$short && $p->code && strlen($p->code) >= 6) {
                                            $short = substr($p->code, 2, 4); // CCPPPP -> PPPP
                                        }
                                        $sampleBarcode = null;
                                        if ($hasVariants) {
                                            $firstVar = $p->variants->sortBy('variant_code')->first();
                                            $sampleBarcode = $firstVar?->variant_code;
                                        }
                                    @endphp

                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox"
                                                   class="form-check-input product-checkbox"
                                                   value="{{ $p->id }}">
                                        </td>

                                        <td class="w-1">
                                            @if($hasVariants)
                                                <button class="toggle-variants"
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
                                                <div class="fw-bold">{{ $p->name }}</div>
                                                <span class="status-dot {{ ($p->is_sellable ?? true) ? 'active' : 'inactive