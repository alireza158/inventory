@extends('layouts.app')

@section('content')
@php
    $titles = [
        'return-from-sale' => 'برگشت از فروش',
        'scrap' => 'انبار ضایعات',
        'personnel' => 'حواله پرسنل',
        'transfer' => 'حواله بین انباری',
    ];

    $toRial = fn($rial) => \App\Support\Currency::formatRial($rial);

    $isCustomerReturn = $voucherType === \App\Models\WarehouseTransfer::TYPE_CUSTOMER_RETURN;

    $pageItems = method_exists($vouchers, 'getCollection') ? $vouchers->getCollection() : collect($vouchers);
    $pageCount = $pageItems->count();
    $pageTotalAmount = (int) $pageItems->sum('total_amount');

    $returnerName = function ($voucher): string {
        $customerFullName = trim(implode(' ', array_filter([
            $voucher->customer?->first_name,
            $voucher->customer?->last_name,
        ])));

        return $customerFullName !== ''
            ? $customerFullName
            : ($voucher->beneficiary_name ?: ($voucher->customer?->display_name ?: '—'));
    };

    $variantLabel = function ($item): string {
        $variant = $item->variant;
        $parts = collect([
            $variant?->variant_name,
            $variant?->modelList?->model_name,
            $variant?->color?->name,
            $variant?->variety_name,
            $item->variant_name,
        ])->filter(fn ($value) => filled($value) && $value !== '—')->unique()->values();

        return $parts->isNotEmpty() ? $parts->implode(' / ') : '—';
    };

    $returnedItemsSummary = function ($voucher) use ($variantLabel): string {
        $items = $voucher->items->map(function ($item) use ($variantLabel) {
            $product = $item->product?->name ?? ('#' . $item->product_id);
            $variant = $variantLabel($item);
            $code = $item->variant?->variant_code ?: ($item->variant_code ?: null);
            $quantity = number_format((int) $item->quantity);

            return trim($product . ' / ' . $variant . ($code ? ' (' . $code . ')' : '') . ' × ' . $quantity);
        })->filter()->values();

        return $items->isNotEmpty() ? $items->implode('، ') : '—';
    };
@endphp

<style>
    :root{
        --brd:#e8edf3;
        --soft:#f8fafc;
        --soft2:#f3f6fb;
        --text:#0f172a;
        --muted:#64748b;
        --blue:#2563eb;
        --blue-soft:#eff6ff;
        --green-soft:#ecfdf5;
        --shadow:0 12px 28px rgba(15,23,42,.06);
    }

    .page-wrap{
        padding: 6px 0 24px;
    }

    .hero-box{
        border:1px solid var(--brd);
        border-radius:22px;
        background:linear-gradient(135deg,#ffffff,#f8fbff 55%,#eef6ff);
        box-shadow:var(--shadow);
        overflow:hidden;
    }

    .hero-title{
        font-size:28px;
        font-weight:900;
        color:var(--text);
        margin-bottom:6px;
    }

    .hero-sub{
        color:var(--muted);
        font-size:14px;
        line-height:1.9;
        margin-bottom:0;
    }

    .soft-chip{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:8px 12px;
        border-radius:999px;
        border:1px solid var(--brd);
        background:#fff;
        font-size:12px;
        font-weight:700;
        color:var(--text);
    }

    .stat-card{
        border:none;
        border-radius:18px;
        box-shadow:var(--shadow);
        height:100%;
    }

    .stat-card .card-body{
        padding:18px;
    }

    .stat-label{
        color:var(--muted);
        font-size:12px;
        margin-bottom:8px;
    }

    .stat-value{
        color:var(--text);
        font-size:26px;
        font-weight:900;
        line-height:1.2;
    }

    .filter-card,
    .table-card{
        border:none;
        border-radius:20px;
        box-shadow:var(--shadow);
        overflow:hidden;
    }

    .section-head{
        padding:14px 18px;
        border-bottom:1px solid var(--brd);
        background:linear-gradient(0deg,#fff,var(--soft2));
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
    }

    .section-title{
        font-size:15px;
        font-weight:900;
        margin:0;
        color:var(--text);
    }

    .section-sub{
        color:var(--muted);
        font-size:12px;
        margin:4px 0 0;
    }

    .table-clean{
        margin-bottom:0;
    }

    .table-clean thead th{
        white-space:nowrap;
        font-size:12px;
        color:var(--muted);
        font-weight:800;
        background:#fbfcfe;
        border-bottom-width:1px;
    }

    .table-clean tbody td{
        vertical-align:middle;
        font-size:13px;
    }

    .customer-box .name{
        font-weight:800;
        color:var(--text);
    }

    .customer-box .meta{
        font-size:12px;
        color:var(--muted);
        margin-top:2px;
    }

    .money-strong{
        font-weight:900;
        color:#0f172a;
        white-space:nowrap;
    }

    .code-pill{
        display:inline-flex;
        align-items:center;
        padding:6px 10px;
        border-radius:999px;
        background:#f8fafc;
        border:1px solid var(--brd);
        font-size:12px;
        font-weight:700;
    }

    .reason-pill{
        display:inline-flex;
        align-items:center;
        padding:6px 10px;
        border-radius:999px;
        background:var(--blue-soft);
        border:1px solid #dbeafe;
        color:#1d4ed8;
        font-size:12px;
        font-weight:700;
    }

    .action-group{
        display:flex;
        gap:8px;
        justify-content:flex-end;
        flex-wrap:wrap;
    }

    .empty-state{
        text-align:center;
        padding:56px 20px;
        color:var(--muted);
    }

    .mobile-cards{
        display:none;
    }

    .return-mobile-card{
        border:1px solid var(--brd);
        border-radius:18px;
        background:#fff;
        padding:14px;
        box-shadow:0 8px 20px rgba(15,23,42,.04);
        margin-bottom:12px;
    }

    .return-mobile-card .title{
        font-weight:900;
        color:var(--text);
        margin-bottom:10px;
    }

    .return-grid{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px;
    }

    .return-grid .item small{
        display:block;
        color:var(--muted);
        font-size:11px;
        margin-bottom:2px;
    }

    .return-grid .item div{
        font-size:13px;
        font-weight:700;
        color:var(--text);
    }

    @media (max-width: 991.98px){
        .desktop-table{
            display:none;
        }
        .mobile-cards{
            display:block;
        }
    }
</style>

<div class="container page-wrap">
    <div class="hero-box mb-4">
        <div class="p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <div class="hero-title">{{ $titles[$type] ?? 'حواله' }}</div>
                    <p class="hero-sub">
                        @if($isCustomerReturn)
                            در این بخش می‌توانی برگشت‌های ثبت‌شده از فروش را با فیلتر مشتری، علت برگشت و بازه تاریخی بررسی، ویرایش یا حذف کنی.
                        @else
                            لیست حواله‌های ثبت‌شده این بخش را ببین و آن‌ها را مدیریت کن.
                        @endif
                    </p>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="soft-chip">نوع: {{ $titles[$type] ?? 'حواله' }}</span>
                        <span class="soft-chip">تعداد در این صفحه: {{ number_format($pageCount) }}</span>
                        @if($isCustomerReturn)
                            <span class="soft-chip">جمع مبلغ این صفحه: {{ $toRial($pageTotalAmount) }}</span>
                        @endif
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-outline-secondary" href="{{ route('vouchers.index') }}">بازگشت</a>
                    @if($isCustomerReturn)
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#salesReturnExportModal">خروجی اکسل</button>
                    @endif
                    <a class="btn btn-primary" href="{{ route('vouchers.section.create', $type) }}">+ ثبت جدید</a>
                </div>
            </div>
        </div>
    </div>

    @if($voucherType === \App\Models\WarehouseTransfer::TYPE_CUSTOMER_RETURN)
        <div class="modal fade" id="salesReturnExportModal" tabindex="-1" aria-labelledby="salesReturnExportModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form method="GET" action="{{ route('vouchers.section.return-from-sale.export') }}" id="salesReturnExportForm">
                        <div class="modal-header">
                            <h5 class="modal-title" id="salesReturnExportModalLabel">خروجی اکسل برگشت از فروش</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="exportCustomerId">مشتری</label>
                                    <select name="customer_id" id="exportCustomerId" class="form-select" data-placeholder="جستجوی نام، موبایل یا کد مشتری"></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="exportReturnReason">علت برگشت</label>
                                    <select name="return_reason" id="exportReturnReason" class="form-select">
                                        <option value="">همه علت‌ها</option>
                                        @foreach($returnReasons as $reasonKey => $reasonTitle)
                                            <option value="{{ $reasonKey }}">{{ $reasonTitle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="exportDateFrom">تاریخ شروع</label>
                                    <input type="date" name="date_from" id="exportDateFrom" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="exportDateTo">تاریخ پایان</label>
                                    <input type="date" name="date_to" id="exportDateTo" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="exportCategoryId">دسته‌بندی</label>
                                    <select name="category_id" id="exportCategoryId" class="form-select">
                                        <option value="">همه دسته‌بندی‌ها</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="exportSubcategoryId">زیر‌دسته‌بندی</label>
                                    <select name="subcategory_id" id="exportSubcategoryId" class="form-select" disabled>
                                        <option value="">ابتدا دسته‌بندی را انتخاب کنید</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="exportProductId">کالا</label>
                                    <select name="product_id" id="exportProductId" class="form-select" disabled data-placeholder="ابتدا زیر‌دسته‌بندی و سپس کالا را جستجو کنید"></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="exportVariantId">تنوع کالا</label>
                                    <select name="variant_id" id="exportVariantId" class="form-select" disabled>
                                        <option value="">ابتدا کالا را انتخاب کنید</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
                            <button type="submit" class="btn btn-success">دریافت خروجی اکسل</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($voucherType === \App\Models\WarehouseTransfer::TYPE_CUSTOMER_RETURN)
        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>شماره</th>
                        <th>تاریخ</th>
                        <th>مبدا</th>
                        <th>مقصد</th>
                        <th>کاربر</th>
                        <th>نام و نام خانوادگی برگشت‌دهنده</th>
                        <th>موبایل برگشت‌دهنده</th>
                        <th>کالا / نوع دقیق برگشتی</th>
                        <th>مبلغ برگشتی مشتری</th>
                        <th>نوع برگشت</th>
                        <th>فاکتور مرجع</th>
                        <th>شماره سازه‌حساب</th>
                        <th>علت برگشت</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($vouchers as $voucher)
                        <tr>
                            <td>{{ $voucher->id }}</td>
                            <td>{{ $voucher->reference ?: ('TR-'.$voucher->id) }}</td>
                            <td>{{ $voucher->transferred_at?->format('Y/m/d H:i') }}</td>
                            <td>{{ $voucher->fromWarehouse?->name ?: '—' }}</td>
                            <td>{{ $voucher->toWarehouse?->name ?: '—' }}</td>
                            <td>{{ $voucher->user?->name ?: '—' }}</td>
                            <td>{{ $returnerName($voucher) }}</td>
                            <td>{{ $voucher->customer?->mobile ?: '—' }}</td>
                            <td class="small" style="min-width: 260px;">{{ $returnedItemsSummary($voucher) }}</td>
                            <td>{{ $toRial($voucher->total_amount) }}</td>
                            <td>{{ \App\Models\WarehouseTransfer::returnSourceLabel($voucher->return_type ?? null) }}</td>
                            <td>{{ $voucher->relatedInvoice?->uuid ?: '—' }}</td>
                            <td>{{ $voucher->external_invoice_number ?: '—' }}</td>
                            <td>{{ \App\Models\WarehouseTransfer::returnReasonOptions()[$voucher->return_reason] ?? '—' }}</td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('vouchers.edit', $voucher) }}">ویرایش</a>
                                <form method="POST" action="{{ route('vouchers.destroy', $voucher) }}" class="d-inline" onsubmit="return confirm('از حذف برگشت از فروش مطمئن هستید؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="text-center py-4 text-muted">موردی ثبت نشده است.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
            <div class="table-responsive">
                <table class="table table-clean table-hover mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>شماره</th>
                        <th>تاریخ</th>
                        <th>مبدا</th>
                        <th>مقصد</th>
                        <th>کاربر</th>
                        <th>فاکتور مرجع</th>
                        <th class="text-end">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($vouchers as $voucher)
                        <tr>
                            <td>{{ $voucher->id }}</td>
                            <td>{{ $voucher->reference ?: ('TR-'.$voucher->id) }}</td>
                            <td>{{ $voucher->transferred_at?->format('Y/m/d H:i') }}</td>
                            <td>{{ $voucher->fromWarehouse?->name ?: '—' }}</td>
                            <td>{{ $voucher->toWarehouse?->name ?: '—' }}</td>
                            <td>{{ $voucher->user?->name ?: '—' }}</td>
                            <td>{{ $voucher->relatedInvoice?->uuid ?: '—' }}</td>
                            <td>
                                <div class="action-group">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('vouchers.edit', $voucher) }}">ویرایش</a>
                                    <form method="POST" action="{{ route('vouchers.destroy', $voucher) }}" class="d-inline" onsubmit="return confirm('از حذف حواله مطمئن هستید؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">موردی ثبت نشده است.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
    @endif

    <div class="mt-3">
        {{ $vouchers->links() }}
    </div>
</div>

@push('scripts')
@if($voucherType === \App\Models\WarehouseTransfer::TYPE_CUSTOMER_RETURN)
<script>
(function () {
    const hasSelect2 = window.jQuery && jQuery.fn && jQuery.fn.select2;
    const modal = document.getElementById('salesReturnExportModal');
    if (!modal) return;

    const $customer = jQuery('#exportCustomerId');
    const $product = jQuery('#exportProductId');
    const subcategory = document.getElementById('exportSubcategoryId');
    const category = document.getElementById('exportCategoryId');
    const variant = document.getElementById('exportVariantId');

    function resetSelect(select, placeholder, disabled = true) {
        select.innerHTML = `<option value="">${placeholder}</option>`;
        select.disabled = disabled;
    }

    function resetProduct() {
        if (hasSelect2 && $product.data('select2')) {
            $product.val(null).trigger('change');
        }
        $product.empty().prop('disabled', true);
        resetSelect(variant, 'ابتدا کالا را انتخاب کنید', true);
    }

    if (hasSelect2) {
        $customer.select2({
            dropdownParent: jQuery(modal),
            width: '100%',
            allowClear: true,
            minimumInputLength: 2,
            placeholder: $customer.data('placeholder'),
            ajax: {
                url: '{{ route('vouchers.section.return-from-sale.ajax.customers') }}',
                dataType: 'json',
                delay: 300,
                data: params => ({ q: params.term || '' }),
                processResults: data => ({ results: data.results || [] })
            }
        });

        $product.select2({
            dropdownParent: jQuery(modal),
            width: '100%',
            allowClear: true,
            minimumInputLength: 2,
            placeholder: $product.data('placeholder'),
            ajax: {
                url: '{{ route('vouchers.section.return-from-sale.ajax.products') }}',
                dataType: 'json',
                delay: 300,
                data: params => ({ q: params.term || '', subcategory_id: subcategory.value || '' }),
                processResults: data => ({ results: data.results || [] })
            }
        });
    }

    category.addEventListener('change', async function () {
        resetSelect(subcategory, this.value ? 'در حال دریافت...' : 'ابتدا دسته‌بندی را انتخاب کنید', true);
        resetProduct();
        if (!this.value) return;
        const response = await fetch(`{{ route('vouchers.section.return-from-sale.ajax.subcategories') }}?category_id=${encodeURIComponent(this.value)}`, { headers: { 'Accept': 'application/json' } });
        const rows = await response.json();
        resetSelect(subcategory, 'همه زیر‌دسته‌بندی‌ها', false);
        rows.forEach(row => subcategory.add(new Option(row.name, row.id)));
    });

    subcategory.addEventListener('change', function () {
        resetProduct();
        if (this.value) $product.prop('disabled', false);
    });

    $product.on('change', async function () {
        resetSelect(variant, this.value ? 'در حال دریافت...' : 'ابتدا کالا را انتخاب کنید', true);
        if (!this.value) return;
        const url = `{{ url('/vouchers/section/return-from-sale/ajax/products') }}/${encodeURIComponent(this.value)}/variants`;
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const rows = await response.json();
        resetSelect(variant, 'همه تنوع‌ها', false);
        rows.forEach(row => variant.add(new Option(row.text, row.id)));
    });
})();
</script>
@endif
@endpush

@endsection
