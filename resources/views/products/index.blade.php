@extends('layouts.app')

@section('content')
<style>
    :root{
        --soft-bg:#f6f8fb;
        --soft-border:#e8edf3;
        --card-radius:16px;
    }

    .page-head{
        background: linear-gradient(180deg, rgba(13,110,253,.10), rgba(13,110,253,0));
        border: 1px solid var(--soft-border);
        border-radius: var(--card-radius);
        padding: 14px 16px;
    }
    .page-title{
        font-weight: 800;
        letter-spacing: -.3px;
        margin: 0;
    }
    .subtle-text{ color:#6b7280; }

    .soft-card{
        border: 1px solid var(--soft-border);
        border-radius: var(--card-radius);
        box-shadow: 0 10px 30px rgba(16,24,40,.06);
    }

    .sticky-panel{
        position: sticky;
        top: 90px;
    }

    .cat-card .form-control{
        border-radius: 12px;
    }

    .cat-tree-wrap{
        max-height: calc(100vh - 240px);
        overflow: auto;
        padding-right: 4px;
    }
    .cat-tree-wrap::-webkit-scrollbar{ width: 8px; }
    .cat-tree-wrap::-webkit-scrollbar-thumb{ background: #d8dee8; border-radius: 99px; }

    .filter-card .form-control,
    .filter-card .form-select{
        border-radius: 12px;
    }

    .table-shell{
        border-radius: var(--card-radius);
        overflow: hidden;
        border: 1px solid var(--soft-border);
    }

    .table-modern thead th{
        position: sticky;
        top: 0;
        z-index: 3;
        background: #fff;
        border-bottom: 1px solid var(--soft-border) !important;
        font-weight: 800;
        color:#111827;
        white-space: nowrap;
    }

    .table-modern td{
        border-top: 1px solid var(--soft-border);
        vertical-align: middle;
    }

    .table-modern tbody tr:hover{
        background: #fbfdff;
    }

    .pill{
        display:inline-flex;
        align-items:center;
        gap:6px;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid var(--soft-border);
        background: #fff;
        white-space: nowrap;
    }
    .pill-gray{ background:#f8fafc; }
    .pill-danger{ background: rgba(220,53,69,.08); border-color: rgba(220,53,69,.25); color:#b42318; }
    .pill-success{ background: rgba(25,135,84,.10); border-color: rgba(25,135,84,.25); color:#146c43; }

    .btn-soft{
        border-radius: 12px;
        border: 1px solid var(--soft-border);
        background: #fff;
    }
    .btn-soft:hover{
        background: #f8fafc;
    }

    .toggle-variants{
        width: 34px;
        height: 34px;
        border-radius: 12px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border:1px solid var(--soft-border);
        background:#fff;
    }
    .toggle-variants:hover{ background:#f8fafc; }

    .name-cell{
        min-width: 240px;
        max-width: 420px;
    }
    .name-cell .title{
        font-weight: 800;
        color:#111827;
        line-height: 1.2;
    }
    .name-cell .meta{
        font-size: 12px;
        color:#6b7280;
        margin-top: 4px;
    }

    .nowrap{ white-space: nowrap; }
    .w-1{ width:1%; }

    .variants-wrap{
        background: var(--soft-bg);
        border-top: 1px solid var(--soft-border);
        padding: 10px 10px 14px;
    }
    .variants-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        margin-bottom: 8px;
    }
    .variants-head .label{
        font-weight:800;
        color:#111827;
        display:flex;
        align-items:center;
        gap:8px;
    }

    .variants-table{
        background:#fff;
        border:1px solid var(--soft-border);
        border-radius: 14px;
        overflow:hidden;
    }
    .variants-table table thead th{
        background:#fff;
        border-bottom:1px solid var(--soft-border) !important;
        font-weight:800;
    }
    .variants-table table td{
        border-top:1px solid var(--soft-border);
    }

    /* Responsive: sidebar narrower on large screens */
    @media (min-width: 992px){
        .col-cat { flex: 0 0 auto; width: 24%; }
        .col-main{ flex: 0 0 auto; width: 76%; }
    }
    @media (min-width: 1200px){
        .col-cat { width: 20%; }  /* کمتر از قبل */
        .col-main{ width: 80%; }  /* فضای بیشتر برای کالاها */
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

                <div class="small subtle-text mt-3">
                    افزودن دسته‌بندی از منوی «دسته‌بندی‌ها» در سایدبار انجام می‌شود.
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
                    <div class="small subtle-text mt-1">
                        مدیریت موجودی، قیمت و مدل‌ها در یک نگاه
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-success" href="{{ route('purchases.create') }}">+ خرید کالا</a>

                    {{-- Mobile categories button --}}
                    <button class="btn btn-soft d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#catOffcanvas" aria-controls="catOffcanvas">
                        دسته‌بندی‌ها
                    </button>

                    <a class="btn btn-primary" href="{{ route('products.create') }}">+ افزودن محصول</a>

                    <form method="POST" action="{{ route('products.sync.crm') }}">
                        @csrf
                        <button class="btn btn-outline-success">Sync از CRM</button>
                    </form>

                    <a class="btn btn-outline-secondary" href="{{ route('products.import.template') }}">دانلود نمونه</a>
                </div>
            </div>
        </div>



        {{-- Filter --}}
        <div class="soft-card filter-card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div class="fw-bold">فیلترها</div>
                    <div class="small subtle-text">برای نتیجه بهتر، چند فیلتر را ترکیب کنید</div>
                </div>

                <form method="GET" action="{{ route('products.index') }}">
                    @if(request('category_id'))
                        <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                    @endif

                    <div class="row g-3 align-items-end">
                        <div class="col-lg-5">
                            <label class="form-label">جستجو (نام یا کد)</label>
                            <input name="q" class="form-control" value="{{ request('q') }}" placeholder="مثلاً گارد یا 10010001">
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label">وضعیت موجودی</label>
                            <select name="stock_status" class="form-select">
                                <option value="" @selected(request('stock_status')==='' || is_null(request('stock_status')))>همه</option>
                                <option value="out" @selected(request('stock_status')==='out')>ناموجود</option>
                            </select>
                        </div>

                        <div class="col-lg-4">
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

        {{-- Active Filters Bar --}}
        @php
            $hasFilters = request('q') || request('stock_status') || request('min_price') || request('max_price') || request('category_id');
        @endphp

        @if($hasFilters)
            <div class="soft-card mb-3" style="background:#fff;">
                <div class="card-body py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="small subtle-text">فیلتر فعال:</div>
                        @if(request('q')) <span class="pill pill-gray">جستجو: {{ request('q') }}</span> @endif
                        @if(request('category_id')) <span class="pill pill-gray">دسته انتخاب شده</span> @endif
                        @if(request('stock_status')==='out') <span class="pill pill-danger">ناموجود</span> @endif
                        @if(request('min_price')) <span class="pill pill-gray">از: {{ request('min_price') }}</span> @endif
                        @if(request('max_price')) <span class="pill pill-gray">تا: {{ request('max_price') }}</span> @endif
                    </div>
                    <a class="btn btn-sm btn-soft" href="{{ route('products.index') }}">حذف فیلترها</a>
                </div>
            </div>
        @endif

        {{-- Table --}}
        <div class="soft-card">
            <div class="card-body pb-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div class="small subtle-text">
                        نمایش {{ $products->firstItem() ?? 0 }} تا {{ $products->lastItem() ?? 0 }} از {{ $products->total() ?? 0 }} مورد
                    </div>
                </div>

                <div class="table-shell">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th class="w-1"></th>
                                    <th class="nowrap w-1">#</th>
                                    <th class="nowrap">کد</th>
                                    <th>نام</th>
                                                                        <th class="nowrap">دسته‌بندی</th>
                                    <th class="nowrap">موجودی</th>
                                    <th class="nowrap">قیمت</th>
                                    <th class="text-end nowrap">عملیات</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($products as $p)
                                    @php
                                        $hasVariants = $p->variants && $p->variants->count() > 0;
                                        $collapseId = "variantsRow{$p->id}";
                                    @endphp

                                    {{-- Product Row --}}
                                    <tr>
                                        <td class="w-1">
                                            @if($hasVariants)
                                                <button class="toggle-variants"
                                                        type="button"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#{{ $collapseId }}"
                                                        aria-expanded="false"
                                                        aria-controls="{{ $collapseId }}"
                                                        title="نمایش مدل‌ها">
                                                    <span class="variant-symbol">+</span>
                                                </button>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>

                                        <td class="nowrap w-1">{{ $p->id }}</td>

                                        <td class="nowrap"><span class="pill pill-gray">{{ $p->code ?: "—" }}</span></td>

                                        <td class="name-cell">
                                            <div class="title">{{ $p->name }}</div>
                                            <div class="meta">
                                                @if($p->category?->name)
                                                    <span class="subtle-text">دسته: {{ $p->category?->name }}</span>
                                                @else
                                                    <span class="subtle-text">بدون دسته‌بندی</span>
                                                @endif
                                            </div>
                                        </td>

                                        
                                        <td class="nowrap">
                                            {{ $p->category?->name ?: "—" }}
                                        </td>

                                        <td class="nowrap">
                                            @if((int)$p->stock === 0)
                                                <span class="pill pill-danger">0</span>
                                            @else
                                                <span class="pill pill-success">{{ $p->stock }}</span>
                                            @endif
                                        </td>

                                        <td class="nowrap">
                                            <span class="fw-bold">{{ number_format((int)$p->price) }}</span>
                                            <span class="subtle-text">تومان</span>
                                        </td>

                                        <td class="text-end nowrap">
                                            <a class="btn btn-sm btn-soft" href="{{ route('products.edit', $p) }}">ویرایش</a>

                                            <form class="d-inline" method="POST" action="{{ route('products.destroy', $p) }}" onsubmit="return confirm('حذف شود؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" style="border-radius:12px;">حذف</button>
                                            </form>
                                        </td>
                                    </tr>

                                    {{-- Variants Row --}}
                                    @if($hasVariants)
                                        <tr>
                                            <td colspan="8" class="p-0">
                                                <div class="collapse" id="{{ $collapseId }}">
                                                    <div class="variants-wrap">
                                                        <div class="variants-head">
                                                            <div class="label">
                                                                <span>مدل‌های این محصول</span>
                                                                <span class="pill pill-gray">{{ $p->variants->count() }} مدل</span>
                                                            </div>
                                                            <div class="small subtle-text">موجودی و قیمت هر مدل جداگانه ثبت شده است</div>
                                                        </div>

                                                        <div class="variants-table">
                                                            <div class="table-responsive">
                                                                <table class="table table-sm mb-0">
                                                                    <thead>
                                                                        <tr>
                                                                            <th class="nowrap">#</th>
                                                                            <th>نام مدل</th>
                                                                            <th class="nowrap">موجودی</th>
                                                                            <th class="nowrap">قیمت فروش</th>
                                                                            <th class="nowrap">قیمت خرید</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($p->variants as $i => $v)
                                                                            <tr>
                                                                                <td class="nowrap">{{ $i + 1 }}</td>
                                                                                <td class="fw-bold">{{ $v->variant_name }}</td>
                                                                                <td class="nowrap">
                                                                                    @if((int)$v->stock === 0)
                                                                                        <span class="pill pill-danger">0</span>
                                                                                    @else
                                                                                        <span class="pill pill-success">{{ $v->stock }}</span>
                                                                                    @endif
                                                                                </td>
                                                                                <td class="nowrap">
                                                                                    <span class="fw-bold">{{ number_format((int)$v->sell_price) }}</span>
                                                                                    <span class="subtle-text">تومان</span>
                                                                                </td>
                                                                                <td class="nowrap">
                                                                                    @if($v->buy_price !== null)
                                                                                        <span class="fw-bold">{{ number_format((int)$v->buy_price) }}</span>
                                                                                        <span class="subtle-text">تومان</span>
                                                                                    @else
                                                                                        <span class="subtle-text">—</span>
                                                                                    @endif
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif

                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            هیچ محصولی ثبت نشده 📦
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-3 pb-3">
                    {{ $products->links() }}
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Mobile Categories Offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="catOffcanvas" aria-labelledby="catOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="catOffcanvasLabel">دسته‌بندی‌ها</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-bold">انتخاب دسته‌بندی</div>
            <a href="{{ route('products.index', request()->except(['category_id', 'page'])) }}" class="small text-decoration-none">همه</a>
        </div>

        <input type="text" id="catSearchMobile" class="form-control form-control-sm mb-3" placeholder="جستجو در دسته‌ها...">

        <div class="cat-tree-wrap" id="catTreeMobile">
            @include('categories._tree', ['nodes' => $categoryTree])
        </div>

        <div class="small subtle-text mt-3">
            افزودن دسته‌بندی از منوی «دسته‌بندی‌ها» در سایدبار انجام می‌شود.
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

  // Collapse +/- button symbol handling (single place)
  function initVariantToggles(root=document){
    root.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
      const targetSel = btn.getAttribute('data-bs-target');
      const el = document.querySelector(targetSel);
      if (!el) return;

      const symbol = btn.querySelector('.variant-symbol') || btn;
      const setSymbol = () => symbol.textContent = el.classList.contains('show') ? '−' : '+';
      setSymbol();

      el.addEventListener('shown.bs.collapse', setSymbol);
      el.addEventListener('hidden.bs.collapse', setSymbol);
    });
  }
  initVariantToggles();

  // Category search helper
  function bindCatSearch(inputId, treeId){
    const input = document.getElementById(inputId);
    const tree = document.getElementById(treeId);
    if (!input || !tree) return;

    input.addEventListener('input', function () {
      const q = this.value.trim().toLowerCase();
      tree.querySelectorAll('a').forEach(a => {
        const text = (a.textContent || '').trim().toLowerCase();
        const li = a.closest('li');
        if (!li) return;
        li.style.display = (q === '' || text.includes(q)) ? '' : 'none';
      });
    });
  }

  bindCatSearch('catSearch', 'catTree');
  bindCatSearch('catSearchMobile', 'catTreeMobile');
});
</script>
@endsection
