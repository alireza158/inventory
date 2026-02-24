@extends('layouts.app')

@section('content')
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
                    <div class="small subtle-text mt-1">مدیریت موجودی، قیمت و مدل‌ها</div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-success" href="{{ route('purchases.create') }}">+ خرید کالا</a>

                    <button class="btn btn-soft d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#catOffcanvas">
                        دسته‌بندی‌ها
                    </button>

                    <a class="btn btn-primary" href="{{ route('products.create') }}">+ افزودن کالا</a>

                    <form method="POST" action="{{ route('products.sync.crm') }}">
                        @csrf
                        <button class="btn btn-outline-success">Sync از CRM</button>
                    </form>

                    <a class="btn btn-outline-secondary" href="{{ route('products.import.template') }}">دانلود نمونه</a>
                </div>
            </div>
        </div>

<<<<<<< HEAD
=======


>>>>>>> 1d3ec7e100dbe0795727bcfd57ebd1eb3115ca62
        {{-- Filter --}}
        <div class="soft-card filter-card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div class="fw-bold">فیلترها</div>
                    <div class="small subtle-text">برای نتیجه بهتر چند فیلتر را ترکیب کنید</div>
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
                                    <th class="w-1"></th>
                                    <th class="nowrap">کد ۴ رقمی</th>
                                    <th class="nowrap">بارکد ۱۱ رقمی</th>
                                    <th>اسم کالا</th>
                                    <th class="nowrap">موجودی</th>
                                    <th class="nowrap">قیمت فروش</th>
                                    <th class="text-end nowrap">عملیات</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($products as $p)
                                    @php
                                        $hasVariants = $p->variants && $p->variants->count() > 0;
                                        $collapseId = "variantsRow{$p->id}";

                                        // کد ۴ رقمی PPPP
                                        $short = $p->short_barcode;
                                        if (!$short && $p->code && strlen($p->code) >= 6) {
                                            $short = substr($p->code, 2, 4); // CCPPPP -> PPPP
                                        }

                                        // نمایش نمونه بارکد 11 رقمی از اولین تنوع (به عنوان نمونه)
                                        $sampleBarcode = null;
                                        if ($hasVariants) {
                                            $firstVar = $p->variants->sortBy('variant_code')->first();
                                            $sampleBarcode = $firstVar?->variant_code;
                                        }
                                    @endphp

                                    <tr>
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
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="pill pill-gray">{{ $sampleBarcode }}</span>
                                                    <span class="pill pill-gray">{{ $p->variants->count() }} تنوع</span>
                                                </div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="fw-bold">{{ $p->name }}</div>
                                            <div class="small subtle-text">دسته: {{ $p->category?->name ?: '—' }}</div>
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

                                    {{-- Variants Row (اختیاری) --}}
                                    @if($hasVariants)
                                        <tr>
                                            <td colspan="7" class="p-0">
                                                <div class="collapse" id="{{ $collapseId }}">
                                                    <div class="variants-wrap">
                                                        <div class="small subtle-text mb-2">
                                                            تنوع‌ها (برای انبار/اسکن): بارکد ۱۱ رقمی هر تنوع
                                                        </div>

                                                        <div class="table-responsive">
                                                            <table class="table table-sm mb-0">
                                                                <thead>
                                                                    <tr>
                                                                        <th>نام تنوع</th>
                                                                        <th class="nowrap">بارکد ۱۱</th>
                                                                        <th class="nowrap">موجودی</th>
                                                                        <th class="nowrap">فروش</th>
                                                                        <th class="nowrap">خرید</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($p->variants->sortBy('variant_code') as $v)
                                                                        <tr>
                                                                            <td class="fw-bold">{{ $v->variant_name }}</td>
                                                                            <td class="mono">{{ $v->variant_code }}</td>
                                                                            <td>
                                                                                @if((int)$v->stock === 0)
                                                                                    <span class="pill pill-danger">0</span>
                                                                                @else
                                                                                    <span class="pill pill-success">{{ $v->stock }}</span>
                                                                                @endif
                                                                            </td>
                                                                            <td>{{ number_format((int)$v->sell_price) }} تومان</td>
                                                                            <td>{{ $v->buy_price !== null ? number_format((int)$v->buy_price).' تومان' : '—' }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif

                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">هیچ کالایی ثبت نشده 📦</td>
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

  // +/- toggle
  document.querySelectorAll('.toggle-variants').forEach(btn => {
    const targetSel = btn.getAttribute('data-bs-target');
    const el = document.querySelector(targetSel);
    if (!el) return;

    const symbol = btn.querySelector('.variant-symbol');
    const setSymbol = () => symbol.textContent = el.classList.contains('show') ? '−' : '+';
    setSymbol();

    el.addEventListener('shown.bs.collapse', setSymbol);
    el.addEventListener('hidden.bs.collapse', setSymbol);
  });

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