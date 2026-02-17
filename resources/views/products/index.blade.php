@extends('layouts.app')

@section('content')


    <div class="row g-3">
        <div class="col-lg-3">
            <div class="card shadow-sm sticky-top" style="top: 90px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">انتخاب دسته‌بندی</h6>
                        <a href="{{ route('products.index', request()->except(['category_id', 'page'])) }}" class="small text-decoration-none">همه</a>
                    </div>

                    <input type="text" id="catSearch" class="form-control form-control-sm mb-3" placeholder="جستجو در دسته‌ها...">

                    <div id="catTree">
                        @include('categories._tree', ['nodes' => $categoryTree])
                    </div>

                    <div class="small text-muted mt-3">
                        افزودن دسته‌بندی از منوی «دسته‌بندی‌ها» در سایدبار انجام می‌شود.
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="col-lg-9">

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h4 class="page-title mb-0">کالاها</h4>

            <div class="d-flex gap-2 flex-wrap">
                <form method="POST" action="{{ route('products.sync.crm') }}">
                    @csrf
                    <button class="btn btn-outline-success">Sync از CRM</button>
                </form>

                <a class="btn btn-outline-secondary" href="{{ route('products.import.template') }}">دانلود نمونه</a>
            </div>
        </div>

        {{-- Filter --}}
        <form class="card filter-card mb-3" method="GET" action="{{ route('products.index') }}">
            <div class="card-body">
                {{-- اگر دسته از درخت انتخاب شده، این hidden باعث میشه با فیلترها از بین نره --}}
                @if(request('category_id'))
                    <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                @endif

                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">جستجو (نام، SKU یا بارکد)</label>
                        <input name="q" class="form-control" value="{{ request('q') }}" placeholder="مثلاً کابل، KB-1001 یا 123456789012">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">وضعیت موجودی</label>
                        <select name="stock_status" class="form-select">
                            <option value="" @selected(request('stock_status')==='' || is_null(request('stock_status')))>همه</option>
                            <option value="out" @selected(request('stock_status')==='out')>ناموجود</option>
                        </select>
                    </div>

                    <div class="col-md-4">
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
            </div>
        </form>

        {{-- Active Filters Bar --}}
        @php
            $hasFilters = request('q') || request('stock_status') || request('min_price') || request('max_price') || request('category_id');
        @endphp

        @if($hasFilters)
            <div class="alert alert-light border d-flex justify-content-between align-items-center mb-3">
                <div class="small text-muted d-flex flex-wrap gap-1">
                    <span>فیلتر فعال است:</span>
                    @if(request('q')) <span class="badge text-bg-secondary">جستجو: {{ request('q') }}</span> @endif
                    @if(request('category_id')) <span class="badge text-bg-secondary">دسته انتخاب شده</span> @endif
                    @if(request('stock_status')==='out') <span class="badge text-bg-danger">ناموجود</span> @endif
                    @if(request('min_price')) <span class="badge text-bg-secondary">از: {{ request('min_price') }}</span> @endif
                    @if(request('max_price')) <span class="badge text-bg-secondary">تا: {{ request('max_price') }}</span> @endif
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('products.index') }}">حذف فیلترها</a>
            </div>
        @endif

        {{-- Table --}}
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:50px;"></th>
                                <th>#</th>
                                <th>نام</th>
                                <th>SKU</th>
                                <th>بارکد</th>
                                <th>دسته‌بندی</th>
                                <th>موجودی</th>
                                <th>قیمت</th>
                                <th class="text-end">عملیات</th>
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
                                    <td>
                                        @if($hasVariants)
                                            <button class="btn btn-sm btn-outline-secondary"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#{{ $collapseId }}"
                                                    aria-expanded="false"
                                                    aria-controls="{{ $collapseId }}">
                                                +
                                            </button>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td>{{ $p->id }}</td>
                                    <td class="fw-semibold">{{ $p->name }}</td>
                                    <td><span class="badge text-bg-secondary">{{ $p->sku }}</span></td>
                                    <td><span class="badge text-bg-light border">{{ $p->barcode ?: "—" }}</span></td>
                                    <td>{{ $p->category?->name }}</td>

                                    <td>
                                        @if((int)$p->stock === 0)
                                            <span class="badge text-bg-danger">0</span>
                                        @else
                                            <span class="badge text-bg-secondary">{{ $p->stock }}</span>
                                        @endif
                                    </td>

                                    <td>{{ number_format((int)$p->price) }} تومان</td>

                                    <td class="text-end action-buttons">
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('products.edit', $p) }}">ویرایش</a>

                                        <form class="d-inline" method="POST" action="{{ route('products.destroy', $p) }}" onsubmit="return confirm('حذف شود؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">حذف</button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- Variants Row --}}
                               {{-- Variants Row --}}
@if($hasVariants)
<tr>
    <td colspan="9" class="bg-light p-0">
        <div class="collapse" id="{{ $collapseId }}">
            <div class="p-2">
                <div class="small text-muted mb-2">مدل‌های این محصول:</div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>نام مدل</th>
                                <th>موجودی</th>
                                <th>قیمت فروش</th>
                                <th>قیمت خرید</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($p->variants as $i => $v)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td class="fw-semibold">{{ $v->variant_name }}</td>
                                    <td>
                                        @if((int)$v->stock === 0)
                                            <span class="badge text-bg-danger">0</span>
                                        @else
                                            <span class="badge text-bg-secondary">{{ $v->stock }}</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format((int)$v->sell_price) }} تومان</td>
                                    <td>{{ $v->buy_price !== null ? number_format((int)$v->buy_price) . ' تومان' : '—' }}</td>
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
                                    <td colspan="9" class="text-center text-muted py-5">هیچ محصولی ثبت نشده 📦</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">{{ $products->links() }}</div>
            </div>
        </div>

        </div>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
        const targetSel = btn.getAttribute('data-bs-target');
        const el = document.querySelector(targetSel);
        if (!el) return;

        // initial state
        btn.textContent = el.classList.contains('show') ? '−' : '+';

        el.addEventListener('shown.bs.collapse', () => btn.textContent = '−');
        el.addEventListener('hidden.bs.collapse', () => btn.textContent = '+');
      });
    });
    </script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // جستجوی ساده داخل درخت دسته‌ها
  const input = document.getElementById('catSearch');
  const tree = document.getElementById('catTree');
  if (input && tree) {
    input.addEventListener('input', function () {
      const q = this.value.trim().toLowerCase();
      tree.querySelectorAll('a').forEach(a => {
        const text = a.textContent.trim().toLowerCase();
        const li = a.closest('li');
        if (!li) return;
        li.style.display = (q === '' || text.includes(q)) ? '' : 'none';
      });
    });
  }

  // بهتر کردن UI دکمه +/-
  document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
    const targetSel = btn.getAttribute('data-bs-target');
    const el = document.querySelector(targetSel);
    if (!el) return;

    el.addEventListener('shown.bs.collapse', () => btn.textContent = '−');
    el.addEventListener('hidden.bs.collapse', () => btn.textContent = '+');
  });
});
</script>
@endsection
