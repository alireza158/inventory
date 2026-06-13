@extends('layouts.app')

@section('content')
@php
    $filters = $filters ?? [];
@endphp
<style>
    .product-export-page{direction:rtl;max-width:1500px;margin:0 auto;padding:24px 16px;background:#f6f8fb;min-height:calc(100vh - 80px)}
    .export-hero{background:linear-gradient(135deg,#0f766e,#0ea5e9);color:#fff;border-radius:22px;padding:24px;box-shadow:0 20px 50px rgba(15,118,110,.18)}
    .export-card{background:#fff;border:1px solid #e5e7eb;border-radius:18px;box-shadow:0 12px 35px rgba(15,23,42,.06)}
    .export-card .form-label{font-weight:800;color:#334155;font-size:.85rem}.btn-export{border-radius:12px;font-weight:900}.table-wrap{overflow-x:auto}.product-thumb{width:56px;height:56px;object-fit:cover;border-radius:14px;border:1px solid #e5e7eb;background:#f8fafc}.status-badge{border-radius:999px;padding:7px 12px;font-weight:900}.loading-mask{display:none;position:absolute;inset:0;background:rgba(255,255,255,.72);backdrop-filter:blur(2px);z-index:3;align-items:center;justify-content:center}.is-loading .loading-mask{display:flex}.empty-state{text-align:center;padding:48px 12px;color:#64748b}.code-ltr{direction:ltr;unicode-bidi:plaintext;display:inline-block}
</style>

<div class="product-export-page">
    <div class="export-hero mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="h3 fw-black mb-2">خروجی محصولات</h1>
            <div class="opacity-75">فیلتر محصولات بر اساس دسته‌بندی، انبار و وضعیت موجودی و دریافت خروجی Excel، PDF یا CSV.</div>
        </div>
        <div class="badge bg-white text-dark p-3 rounded-pill">آستانه کم‌موجودی: {{ \App\Services\ProductExportService::LOW_STOCK_THRESHOLD }} عدد</div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="export-card p-4 mb-4">
        <form id="productExportForm" class="row g-3" method="GET" action="{{ route('admin.product-exports.index') }}">
            <div class="col-md-3">
                <label class="form-label">دسته‌بندی محصول</label>
                <select name="category_id" class="form-select">
                    <option value="">همه دسته‌بندی‌ها</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">انبار</label>
                <select name="warehouse_id" class="form-select">
                    <option value="">همه انبارها</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(($filters['warehouse_id'] ?? '') == $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">وضعیت موجودی</label>
                <select name="stock_status" class="form-select">
                    <option value="all" @selected(($filters['stock_status'] ?? 'all') === 'all')>همه محصولات</option>
                    <option value="in_stock" @selected(($filters['stock_status'] ?? '') === 'in_stock')>فقط کالاهای موجود</option>
                    <option value="out_of_stock" @selected(($filters['stock_status'] ?? '') === 'out_of_stock')>فقط کالاهای ناموجود</option>
                    <option value="low_stock" @selected(($filters['stock_status'] ?? '') === 'low_stock')>کالاهای کم‌موجودی</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">جستجو</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="نام، کد، SKU یا بارکد">
            </div>
            <div class="col-12 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <button type="submit" class="btn btn-primary btn-export px-4">نمایش محصولات</button>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" data-format="xlsx" class="btn btn-success btn-export">دریافت خروجی Excel</button>
                    <button type="button" data-format="pdf" class="btn btn-danger btn-export">دریافت خروجی PDF</button>
                    <button type="button" data-format="csv" class="btn btn-secondary btn-export">دریافت خروجی CSV</button>
                </div>
            </div>
        </form>
    </div>

    <div id="productExportResult" class="export-card position-relative">
        <div class="loading-mask"><div class="spinner-border text-primary" role="status"></div><span class="me-3 fw-bold">در حال دریافت اطلاعات...</span></div>
        @include('product-exports.partials.table', ['rows' => $rows])
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('productExportForm');
        const result = document.getElementById('productExportResult');
        const buildParams = () => new URLSearchParams(new FormData(form));

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            result.classList.add('is-loading');
            try {
                const response = await fetch(`{{ route('admin.product-exports.data') }}?${buildParams().toString()}`, {
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                });
                if (!response.ok) throw new Error('خطا در دریافت اطلاعات');
                result.innerHTML = '<div class="loading-mask"><div class="spinner-border text-primary" role="status"></div><span class="me-3 fw-bold">در حال دریافت اطلاعات...</span></div>' + await response.text();
            } catch (error) {
                alert('دریافت اطلاعات با خطا روبه‌رو شد. لطفاً دوباره تلاش کنید.');
            } finally {
                result.classList.remove('is-loading');
            }
        });

        document.querySelectorAll('[data-format]').forEach(button => {
            button.addEventListener('click', () => {
                const params = buildParams();
                params.set('format', button.dataset.format);
                window.location.href = `{{ route('admin.product-exports.export') }}?${params.toString()}`;
            });
        });
    });
</script>
@endsection
