@extends('layouts.app')

@section('content')
@php
    $order = $order ?? null;
    $customersPageUrl = $customersPageUrl ?? url('/customers');

    $initRows = old('products');
    if (!$initRows && $order) {
        $initRows = $order->items->map(function ($it) {
            return [
                'id'         => (int) $it->product_id,
                'variety_id' => (int) $it->variant_id,
                'quantity'   => (int) $it->quantity,
                'price'      => (int) $it->price,
            ];
        })->values();
    }
    if (!$initRows) $initRows = [];
@endphp

<link rel="stylesheet" href="{{ asset('lib/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('lib/bootstrap.rtl.min.css') }}">
<script src="{{ asset('lib/jquery.min.js') }}"></script>
<script src="{{ asset('lib/select2.min.js') }}"></script>
<script src="{{ asset('lib/bootstrap.bundle.min.js') }}"></script>

<style>
    body {
        background: linear-gradient(180deg, #f6f8fc 0%, #eef2f9 100%);
        font-size: 14px;
    }

    .page-shell {
        max-width: 1140px;
    }

    .card-soft {
        background: #fff;
        border: 1px solid rgba(13, 110, 253, .10);
        border-radius: 16px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
    }

    .section-title {
        font-weight: 800;
        letter-spacing: -.2px;
        font-size: 1rem;
    }

    .hint {
        color: #6c757d;
        font-size: .83rem;
    }

    .topbar {
        background: #fff;
        border: 1px solid rgba(13,110,253,.10);
        border-radius: 14px;
        padding: .9rem 1rem;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .05);
    }

    .sticky-submit {
        position: sticky;
        bottom: 10px;
        z-index: 12;
        background: rgba(246, 248, 252, .90);
        backdrop-filter: blur(5px);
        border-radius: 12px;
        padding: .45rem;
    }

    .summary-input {
        background-color: #f8f9fa !important;
        border-color: #e9ecef;
    }

    .mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        letter-spacing: 1px;
    }

    .fs-7 {
        font-size: .82rem;
    }

    .compact-label {
        font-size: .82rem;
        font-weight: 700;
        margin-bottom: .32rem;
    }

    .customer-box {
        border: 1px dashed rgba(13,110,253,.22);
        background: rgba(13,110,253,.03);
        border-radius: 12px;
        padding: 10px 12px;
    }

    .chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        border: 1px solid #e2e8f0;
        background: #fff;
        font-size: .8rem;
        white-space: nowrap;
    }

    .product-block {
        background: #fff;
        border: 1px solid #dbe3ef;
        border-radius: 14px;
        overflow: hidden;
        transition: all .2s ease;
        margin-bottom: 12px;
    }

    .product-header {
        padding: 10px 12px;
        background: linear-gradient(0deg, #fff, #f7f9ff);
        border-bottom: 1px solid #e8edf5;
    }

    .product-body {
        padding: 10px 12px;
    }

    .product-summary {
        display: none;
        padding: 10px 12px;
        background: #fafcff;
    }

    .product-block.is-collapsed .product-body {
        display: none;
    }

    .product-block.is-collapsed .product-summary {
        display: block;
    }

    .varieties-wrapper {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px;
    }

    .variety-row {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px;
    }

    .variety-row + .variety-row {
        margin-top: 8px;
    }

    .row-tools {
        border-top: 1px dashed #d9e2ef;
        margin-top: 10px;
        padding-top: 10px;
    }

    .icon-btn {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        border: 1px solid #dbe3ef;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        line-height: 1;
        cursor: pointer;
        transition: all .15s ease;
    }

    .icon-btn:hover {
        background: #f8fafc;
        transform: translateY(-1px);
    }

    .icon-btn.ok {
        color: #15803d;
        border-color: #cce9d5;
    }

    .icon-btn.danger {
        color: #dc2626;
        border-color: #f5c9c9;
    }

    .icon-btn.muted {
        color: #334155;
    }

    .block-subtotal-box {
        border: 1px dashed rgba(13,110,253,.18);
        background: rgba(13,110,253,.03);
        border-radius: 10px;
        padding: 8px 10px;
    }

    .summary-line {
        font-size: .88rem;
    }

    .select2-container .select2-selection--single {
        height: 38px !important;
        padding-top: 4px;
        border-color: #dee2e6 !important;
        border-radius: .5rem !important;
    }

    .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 28px !important;
        padding-right: 12px !important;
    }

    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }

    .qty-box {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .qty-btn {
        width: 36px;
        height: 36px;
        border: 1px solid #dbe3ef;
        background: #fff;
        border-radius: 10px;
        font-size: 18px;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .15s ease;
        flex: 0 0 36px;
        user-select: none;
    }

    .qty-btn:hover {
        background: #f8fafc;
        transform: translateY(-1px);
    }

    .qty-btn:disabled {
        opacity: .45;
        cursor: not-allowed;
        transform: none;
    }

    .qty-box .quantity-input {
        text-align: center;
        min-width: 0;
    }

    .qty-box .quantity-input::-webkit-outer-spin-button,
    .qty-box .quantity-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .qty-box .quantity-input[type=number] {
        -moz-appearance: textfield;
    }

    @media (max-width: 767.98px) {
        .qty-btn {
            width: 40px;
            height: 40px;
            flex-basis: 40px;
            font-size: 20px;
        }
    }
</style>

<div class="container page-shell py-3">

    <div class="topbar mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <div class="h5 mb-0 fw-bold">🧾 ثبت پیش‌فاکتور</div>
            <div class="hint">نسخه جمع‌وجور برای ثبت خریدهای سنگین</div>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('preinvoice.warehouse.index') }}">صف تایید انبار</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 fw-bold py-2">✅ {{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 fw-bold py-2" style="white-space: pre-wrap">
            {!! session('error') !!}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4 py-2">
            <div class="fw-bold mb-1">⚠️ خطا:</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('preinvoice.draft.save') }}" method="POST" id="orderForm">
        @csrf

        <div class="card-soft p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div>
                    <div class="section-title">👤 مشتری</div>
                    <div class="hint">جستجو با نام یا شماره موبایل، مستقیم داخل فرم</div>
                </div>
                <a href="{{ $customersPageUrl }}" class="btn btn-sm btn-outline-success">➕ افزودن مشتری</a>
            </div>

            <div class="row g-2">
                <div class="col-lg-6">
                    <label class="compact-label">جستجو و انتخاب مشتری</label>
                    <select id="customer_search_select" class="form-select"></select>
                    <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id', '') }}">
                </div>

                <div class="col-lg-6">
                    <div class="customer-box h-100 d-flex flex-column justify-content-center">
                        <div class="fw-bold" id="selectedCustomerTitle">هنوز مشتری انتخاب نشده است</div>
                        <div class="hint mt-1" id="customer_balance_hint"></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="compact-label">نام مشتری</label>
                    <input
                        type="text"
                        name="customer_name"
                        id="customer_name"
                        class="form-control form-control-sm"
                        value="{{ old('customer_name') }}"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="compact-label">شماره موبایل</label>
                    <input
                        type="text"
                        name="customer_mobile"
                        id="customer_mobile"
                        class="form-control form-control-sm"
                        value="{{ old('customer_mobile') }}"
                        required
                    >
                </div>
            </div>
        </div>

        <div class="card-soft p-3 mb-3">
            <div class="section-title mb-2">🚚 ارسال و مقصد</div>

            <div class="row g-2 align-items-end">
                <div class="col-lg-6">
                    <label class="compact-label">شیوه ارسال</label>
                    <select id="shipping_id" name="shipping_id" class="form-select form-select-sm" required>
                        <option value="">انتخاب روش ارسال...</option>
                    </select>
                    <div class="hint mt-1" id="shipping_label">هزینه ارسال</div>
                    <input type="hidden" id="shipping_price" name="shipping_price" value="{{ old('shipping_price', 0) }}">
                </div>

                <div class="col-lg-6">
                    <div class="customer-box">
                        <div class="fw-bold fs-7">وضعیت</div>
                        <div class="hint mt-1" id="shipping_mode_hint">با تغییر روش ارسال، جمع کل دوباره محاسبه می‌شود.</div>
                    </div>
                </div>
            </div>

            <div id="locationWrapper" class="row g-2 mt-1">
                <div class="col-md-6">
                    <label class="compact-label">استان</label>
                    <select id="province_id" name="province_id" class="form-select form-select-sm">
                        <option value=""></option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="compact-label">شهر</label>
                    <select id="city_id" name="city_id" class="form-select form-select-sm">
                        <option value=""></option>
                    </select>
                </div>
            </div>

            <div id="addressWrapper" class="mt-2">
                <label class="compact-label">آدرس</label>
                <textarea id="customer_address" name="customer_address" class="form-control form-control-sm" rows="2">{{ old('customer_address') }}</textarea>
            </div>
        </div>

        <div class="card-soft mb-3">
            <div class="p-3 border-bottom">
                <div class="section-title mb-1">🧩 ثبت کالا به‌صورت گروهی</div>
                <div class="hint">کد ۴ رقمی محصول مادر را وارد کنید و با «مشاهده و انتخاب» کالاهای نهایی را مشخص کنید.</div>
            </div>
            <div class="p-3 border-bottom">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="compact-label">کد ۴ رقمی مادر</label>
                        <input type="text" id="motherCodeInput" class="form-control form-control-sm" maxlength="4" placeholder="مثال: 0123">
                    </div>
                    <div class="col-md-3">
                        <button type="button" id="findMotherBtn" class="btn btn-primary btn-sm w-100">نمایش محصول مادر</button>
                    </div>
                </div>
                <div id="motherProductBox" class="mt-2 small text-muted"></div>
                <div class="mt-2 d-none" id="openGroupPickerWrap">
                    <button type="button" id="openGroupPickerBtn" class="btn btn-outline-primary btn-sm">مشاهده و انتخاب</button>
                </div>
            </div>
            <div class="p-3">
                <div class="section-title mb-2">خلاصه انتخاب‌های گروهی</div>
                <div id="groupSummaryList" class="d-flex flex-column gap-2"></div>
                <div id="groupProductsInputs"></div>
            </div>
        </div>

        <div class="accordion mb-3" id="legacyProductAccordion">
            <div class="accordion-item card-soft">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#legacyProductCollapse">
                        فرم قدیمی افزودن کالا (بدون حذف)
                    </button>
                </h2>
                <div id="legacyProductCollapse" class="accordion-collapse collapse" data-bs-parent="#legacyProductAccordion">
                    <div class="accordion-body">
                        <div class="p-3 border rounded-3">
                            <div id="productBlocksContainer"></div>
                            <button type="button" id="addProductBlockBtn" class="btn btn-outline-primary btn-sm w-100 py-2 mt-2">
                                ➕ افزودن محصول
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-soft p-3 mb-3">
            <div class="section-title mb-2">💳 جمع‌بندی</div>

            <div class="row g-2">
                <div class="col-md-4">
                    <label class="compact-label">تخفیف (تومان)</label>
                    <input
                        type="number"
                        name="discount_amount"
                        id="discount"
                        class="form-control form-control-sm summary-input"
                        value="{{ old('discount_amount', 0) }}"
                    >
                </div>

                <div class="col-md-4">
                    <label class="compact-label">هزینه ارسال</label>
                    <input
                        type="text"
                        id="shipping_price_view"
                        class="form-control form-control-sm summary-input"
                        readonly
                        value="0 تومان"
                    >
                </div>

                <div class="col-md-4">
                    <label class="compact-label">جمع کل (تومان)</label>
                    <input
                        type="text"
                        name="total_price"
                        id="total_price"
                        class="form-control form-control-sm fw-bold summary-input"
                        readonly
                    >
                </div>
            </div>

            <input type="hidden" name="payment_status" value="pending">
        </div>

        <div class="sticky-submit">
            <button class="btn btn-primary w-100 py-2 shadow-sm">✅ ثبت پیش‌فاکتور</button>
        </div>
    </form>
</div>

<div class="modal fade" id="groupPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">انتخاب کالاهای نهایی محصول مادر</h5>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="groupPickerMeta" class="small text-muted mb-2"></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                        <tr>
                            <th>مدل لیست</th><th>طرح</th><th>تنوع</th><th>پارت‌نامبر</th><th>موجودی</th><th>قیمت</th><th>تعداد</th>
                        </tr>
                        </thead>
                        <tbody id="groupPickerRows"></tbody>
                    </table>
                </div>
                <div id="groupLiveSummary" class="alert alert-light border small mb-0"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">بستن</button>
                <button type="button" id="saveGroupSelectionBtn" class="btn btn-primary btn-sm">اتمام و افزودن به سفارش</button>
            </div>
        </div>
    </div>
</div>

<script>
    const API = {
        products:  "{{ url('/preinvoice/api/products') }}",
        product:   "{{ url('/preinvoice/api/products') }}",
        area:      "{{ url('/preinvoice/api/area') }}",
        customers: "{{ url('/preinvoice/api/customers') }}",
        customer:  "{{ url('/preinvoice/api/customers') }}"
    };

    var INIT_ROWS = @json($initRows);
    var INITIAL_SHIPPINGS = @json($shippingMethods);
    var OLD_CUSTOMER_ID = @json(old('customer_id', ''));
    var OLD_PROVINCE_ID = @json(old('province_id', ''));
    var OLD_CITY_ID = @json(old('city_id', ''));
    var INIT_GROUP_ROWS = @json($initRows);
</script>

<script>
let selectedMotherProduct = null;
let groupedSelections = {};
let activeEditingProductId = null;

function renderGroupSummary() {
    const wrap = document.getElementById('groupSummaryList');
    const inputWrap = document.getElementById('groupProductsInputs');
    if (!wrap || !inputWrap) return;
    wrap.innerHTML = '';
    inputWrap.innerHTML = '';
    let idx = 0;
    Object.values(groupedSelections).forEach(group => {
        const qty = group.items.reduce((s, it) => s + Number(it.quantity || 0), 0);
        const amount = group.items.reduce((s, it) => s + (Number(it.quantity || 0) * Number(it.price || 0)), 0);
        const card = document.createElement('div');
        card.className = 'border rounded-3 p-2 d-flex justify-content-between align-items-center';
        card.innerHTML = '<div><strong>' + group.product.title + '</strong><div class="small text-muted">تعداد: ' + qty.toLocaleString('fa-IR') + ' | مبلغ: ' + amount.toLocaleString('fa-IR') + ' تومان</div></div><button type="button" class="btn btn-sm btn-outline-secondary">ویرایش</button>';
        card.querySelector('button').addEventListener('click', () => openGroupPicker(group.product.id));
        wrap.appendChild(card);
        group.items.forEach(item => {
            inputWrap.insertAdjacentHTML('beforeend', '<input type="hidden" name="products[' + idx + '][id]" value="' + group.product.id + '"><input type="hidden" name="products[' + idx + '][variety_id]" value="' + item.variant_id + '"><input type="hidden" name="products[' + idx + '][quantity]" value="' + item.quantity + '"><input type="hidden" name="products[' + idx + '][price]" value="' + item.price + '">');
            idx++;
        });
    });
}

async function findMotherProductByCode() {
    const code = String(document.getElementById('motherCodeInput').value || '').trim();
    if (!code) return;
    const res = await fetch(API.products + '?q=' + encodeURIComponent(code), { headers: { Accept: 'application/json' } });
    const json = await res.json();
    const rows = json?.data?.products?.data || [];
    selectedMotherProduct = rows[0] || null;
    const box = document.getElementById('motherProductBox');
    const pickerWrap = document.getElementById('openGroupPickerWrap');
    if (!selectedMotherProduct) {
        box.textContent = 'محصول مادری با این کد پیدا نشد.';
        pickerWrap.classList.add('d-none');
        return;
    }
    box.textContent = 'محصول مادر: ' + selectedMotherProduct.title + ' | کد: ' + (selectedMotherProduct.sku || '');
    pickerWrap.classList.remove('d-none');
}

async function openGroupPicker(productId = null) {
    const targetId = productId || selectedMotherProduct?.id;
    if (!targetId) return;
    const data = await getProductDetails(targetId);
    if (!data) return;
    activeEditingProductId = targetId;
    const current = groupedSelections[targetId]?.items || [];
    const byVariant = {};
    current.forEach(i => byVariant[i.variant_id] = Number(i.quantity || 0));
    document.getElementById('groupPickerMeta').textContent = data.title;
    const tbody = document.getElementById('groupPickerRows');
    tbody.innerHTML = '';
    (data.varieties || []).forEach(v => {
        tbody.insertAdjacentHTML('beforeend', '<tr><td>' + (v.model_list_name || '—') + '</td><td>' + (v.variety_name || '—') + '</td><td>' + (v.variant_name || '—') + '</td><td>' + (v.barcode || v.variety_code || '—') + '</td><td>' + Number(v.quantity || 0).toLocaleString('fa-IR') + '</td><td>' + Number(v.price || 0).toLocaleString('fa-IR') + '</td><td><input type="number" min="0" max="' + Number(v.quantity || 0) + '" data-variant="' + v.id + '" data-price="' + Number(v.price || 0) + '" class="form-control form-control-sm group-qty-input" value="' + (byVariant[v.id] || 0) + '"></td></tr>');
    });
    updateModalLiveSummary();
    new bootstrap.Modal(document.getElementById('groupPickerModal')).show();
}

function updateModalLiveSummary() {
    let qty = 0, amount = 0;
    document.querySelectorAll('.group-qty-input').forEach(i => {
        const q = Math.max(0, Number(i.value || 0));
        const p = Number(i.dataset.price || 0);
        qty += q; amount += (q * p);
    });
    document.getElementById('groupLiveSummary').textContent = 'خلاصه لحظه‌ای: ' + qty.toLocaleString('fa-IR') + ' عدد | ' + amount.toLocaleString('fa-IR') + ' تومان';
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('findMotherBtn')?.addEventListener('click', findMotherProductByCode);
    document.getElementById('openGroupPickerBtn')?.addEventListener('click', () => openGroupPicker());
    document.addEventListener('input', e => { if (e.target.classList.contains('group-qty-input')) updateModalLiveSummary(); });
    document.getElementById('saveGroupSelectionBtn')?.addEventListener('click', function () {
        if (!activeEditingProductId) return;
        const product = (selectedMotherProduct && Number(selectedMotherProduct.id) === Number(activeEditingProductId)) ? selectedMotherProduct : (groupedSelections[activeEditingProductId]?.product || null);
        const items = [];
        document.querySelectorAll('.group-qty-input').forEach(i => {
            const q = Number(i.value || 0);
            if (q > 0) items.push({ variant_id: Number(i.dataset.variant), quantity: q, price: Number(i.dataset.price) });
        });
        if (product && items.length > 0) groupedSelections[activeEditingProductId] = { product, items };
        renderGroupSummary();
        bootstrap.Modal.getInstance(document.getElementById('groupPickerModal'))?.hide();
        updateTotal();
    });
    INIT_GROUP_ROWS.forEach(r => {
        const key = Number(r.id);
        if (!groupedSelections[key]) groupedSelections[key] = { product: { id: r.id, title: r.name || ('محصول #' + r.id), sku: '' }, items: [] };
        groupedSelections[key].items.push({ variant_id: r.variety_id, quantity: Number(r.quantity || 0), price: Number(r.price || 0) });
    });
    renderGroupSummary();
});
</script>

<script>
let shippings = INITIAL_SHIPPINGS || [];
let areaProvinces = [];
let globalRowIndex = 0;
const productCache = new Map();

function createEl(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html.trim();
    return tmp.firstChild;
}

function formatPrice(val) {
    const n = Number(val || 0);
    return n.toLocaleString('fa-IR');
}

function normalizeText(val) {
    return String(val || '').trim();
}

function shippingById(id) {
    return shippings.find(function (x) {
        return Number(x.id) === Number(id);
    }) || null;
}

function isInPersonShipping(ship) {
    if (!ship || !ship.name) return false;
    const name = String(ship.name).trim();
    return name.indexOf('حضوری') !== -1 || name.indexOf('مراجعه') !== -1;
}

function initLocationSelect2(selectEl, placeholder) {
    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;
    const $el = $(selectEl);

    if ($el.hasClass('select2-hidden-accessible')) {
        $el.off('select2:select select2:clear');
        $el.select2('destroy');
    }

    $el.select2({
        width: '100%',
        dir: 'rtl',
        placeholder: placeholder,
        allowClear: true
    });

    $el.on('select2:select select2:clear', function () {
        this.dispatchEvent(new Event('change', { bubbles: true }));
    });
}

function setSelectDisabled(selectEl, disabled) {
    selectEl.disabled = disabled;
    if (window.jQuery && $(selectEl).hasClass('select2-hidden-accessible')) {
        $(selectEl).prop('disabled', disabled).trigger('change.select2');
    }
}

async function loadArea() {
    const res = await fetch(API.area, { headers: { 'Accept': 'application/json' } });
    const data = await res.json();
    areaProvinces = (data && data.data && data.data.provinces) ? data.data.provinces : [];
}

function fillProvincesSelect(provincesToShow) {
    const provinceSelect = document.getElementById('province_id');
    provinceSelect.innerHTML = '<option value=""></option>';

    (provincesToShow || []).forEach(function (p) {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = (p.name || '').trim();
        provinceSelect.appendChild(opt);
    });

    initLocationSelect2(provinceSelect, 'انتخاب استان...');
}

function fillCities(citiesToShow) {
    const citySelect = document.getElementById('city_id');
    citySelect.innerHTML = '<option value=""></option>';

    (citiesToShow || []).forEach(function (c) {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = (c.name || '').trim();
        citySelect.appendChild(opt);
    });

    setSelectDisabled(citySelect, (citiesToShow || []).length === 0);
    initLocationSelect2(citySelect, 'انتخاب شهر...');
}

function fillCitiesByProvinceId(provinceId) {
    const province = areaProvinces.find(function (p) {
        return Number(p.id) === Number(provinceId);
    });

    fillCities(province && province.cities ? province.cities : []);
}

function fillShippingSelect() {
    const shippingSelect = document.getElementById('shipping_id');
    shippingSelect.innerHTML = '<option value="">انتخاب روش ارسال...</option>';

    shippings.forEach(function (s) {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.name;
        shippingSelect.appendChild(opt);
    });
}

function toggleShippingFields() {
    const shippingSelect = document.getElementById('shipping_id');
    const ship = shippingById(shippingSelect.value);
    const inPerson = isInPersonShipping(ship);

    const locationWrapper = document.getElementById('locationWrapper');
    const addressWrapper = document.getElementById('addressWrapper');
    const provinceEl = document.getElementById('province_id');
    const addressEl = document.getElementById('customer_address');
    const hintEl = document.getElementById('shipping_mode_hint');

    if (inPerson) {
        locationWrapper.style.display = 'none';
        addressWrapper.style.display = 'none';
        provinceEl.required = false;
        addressEl.required = false;
        hintEl.textContent = 'برای مراجعه حضوری نیازی به ثبت آدرس، استان و شهر نیست.';
    } else {
        locationWrapper.style.display = '';
        addressWrapper.style.display = '';
        provinceEl.required = true;
        addressEl.required = true;
        hintEl.textContent = 'با تغییر روش ارسال، جمع کل دوباره محاسبه می‌شود.';
    }
}

function setCustomerLocation(provinceId, cityId) {
    const provinceSelect = document.getElementById('province_id');
    const citySelect = document.getElementById('city_id');

    if (provinceId) {
        provinceSelect.value = String(provinceId);
        if (window.jQuery) $(provinceSelect).trigger('change.select2');
        fillCitiesByProvinceId(provinceId);
    } else {
        provinceSelect.value = '';
        if (window.jQuery) $(provinceSelect).trigger('change.select2');
        fillCities([]);
    }

    if (cityId) {
        citySelect.value = String(cityId);
        if (window.jQuery) $(citySelect).trigger('change.select2');
    } else {
        citySelect.value = '';
        if (window.jQuery) $(citySelect).trigger('change.select2');
    }
}

function customerFullName(c) {
    if (!c) return '';
    const full = (((c.first_name || '').trim() + ' ' + (c.last_name || '').trim()).trim());
    if (full) return full;
    return (c.customer_name || c.name || '').trim();
}

function applyCustomerToForm(c) {
    if (!c) return;

    document.getElementById('customer_id').value = c.id || '';
    document.getElementById('customer_name').value = customerFullName(c);
    document.getElementById('customer_mobile').value = c.mobile || '';
    document.getElementById('customer_address').value = c.address || '';

    setCustomerLocation(c.province_id || '', c.city_id || '');

    document.getElementById('selectedCustomerTitle').textContent =
        customerFullName(c) + (c.mobile ? ' - ' + c.mobile : '');

    document.getElementById('customer_balance_hint').textContent =
        'مانده حساب: ' + Number(c.balance || 0).toLocaleString('fa-IR') + ' تومان';
}

function preloadCustomerOption(selectEl, customer) {
    if (!selectEl || !customer || !window.jQuery) return;

    const text = customerFullName(customer) + (customer.mobile ? ' - ' + customer.mobile : '');
    const exists = Array.from(selectEl.options).some(function (opt) {
        return Number(opt.value) === Number(customer.id);
    });

    if (!exists) {
        const option = new Option(text, customer.id, true, true);
        selectEl.add(option);
    }

    $(selectEl).val(String(customer.id)).trigger('change');
}

function initInlineCustomerSearch() {
    const selectEl = document.getElementById('customer_search_select');

    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;

    $(selectEl).select2({
        width: '100%',
        dir: 'rtl',
        placeholder: 'جستجو با نام یا شماره موبایل...',
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: API.customers,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term || '' };
            },
            processResults: function (resp) {
                const items = (resp && resp.data && resp.data.customers) ? resp.data.customers : [];
                return {
                    results: items.map(function (c) {
                        return {
                            id: c.id,
                            text: customerFullName(c) + ' - ' + (c.mobile || '')
                        };
                    })
                };
            }
        }
    });

    $(selectEl).on('select2:select', async function (e) {
        const customerId = e && e.params && e.params.data ? e.params.data.id : null;
        if (!customerId) return;

        try {
            const res = await fetch(API.customer + '/' + customerId, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            const customer = json && json.data ? json.data.customer : null;
            if (customer) applyCustomerToForm(customer);
        } catch (error) {}
    });

    $(selectEl).on('select2:clear', function () {
        document.getElementById('customer_id').value = '';
        document.getElementById('selectedCustomerTitle').textContent = 'هنوز مشتری انتخاب نشده است';
        document.getElementById('customer_balance_hint').textContent = '';
    });
}

async function loadOldCustomer() {
    const cid = document.getElementById('customer_id').value || OLD_CUSTOMER_ID || '';
    if (!cid) return;

    try {
        const res = await fetch(API.customer + '/' + cid, { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        const customer = json && json.data ? json.data.customer : null;

        if (customer) {
            applyCustomerToForm(customer);
            preloadCustomerOption(document.getElementById('customer_search_select'), customer);
        }
    } catch (e) {}
}

async function getProductDetails(productId) {
    const id = String(productId || '');
    if (productCache.has(id)) return productCache.get(id);

    const res = await fetch(API.product + '/' + id, { headers: { 'Accept': 'application/json' } });
    const data = await res.json();
    const product = data && data.data ? data.data.product : null;

    productCache.set(id, product);
    return product;
}

function productTitle(product) {
    return normalizeText(product && (product.title || product.name || ''));
}

function getProductVarieties(product) {
    if (!product) return [];
    if (Array.isArray(product.varieties)) return product.varieties;
    if (Array.isArray(product.variants)) return product.variants;
    return [];
}

function varietyModelLabel(v) {
    return normalizeText(v && v.model_list_name) || '—';
}

function varietyDesignLabel(v) {
    const designName = normalizeText(v && v.variety_name);
    const designCode = normalizeText(v && v.variety_code);

    if (designName && designCode) return designName + ' (' + designCode + ')';
    if (designName) return designName;
    if (designCode) return designCode;
    return normalizeText(v && v.variant_name) || '—';
}

function productStockValue(product) {
    if (!product) return 0;
    if (product.quantity !== undefined && product.quantity !== null) return Number(product.quantity) || 0;
    if (product.stock !== undefined && product.stock !== null) return Number(product.stock) || 0;
    return 0;
}

function varietyStockValue(v) {
    if (!v) return 0;
    if (v.quantity !== undefined && v.quantity !== null) return Number(v.quantity) || 0;
    if (v.stock !== undefined && v.stock !== null) return Number(v.stock) || 0;
    return 0;
}

function setStockUI(row, stockQty) {
    const qtyInput = row.querySelector('.quantity-input');
    const badge = row.querySelector('.stock-badge');

    const qty = Number.isFinite(Number(stockQty)) ? Number(stockQty) : 0;

    if (qty > 0) {
        qtyInput.disabled = false;
        qtyInput.min = '1';
        qtyInput.max = String(qty);

        const cur = parseInt(String(qtyInput.value || '').trim(), 10);
        if (Number.isFinite(cur)) {
            if (cur < 1) qtyInput.value = '1';
            if (cur > qty) qtyInput.value = String(qty);
        } else if (String(qtyInput.value || '').trim() !== '') {
            qtyInput.value = '1';
        }

        badge.className = 'badge bg-success stock-badge';
        badge.textContent = 'موجودی: ' + qty;
    } else {
        qtyInput.value = '0';
        qtyInput.min = '0';
        qtyInput.max = '0';
        qtyInput.disabled = true;

        badge.className = 'badge bg-danger stock-badge';
        badge.textContent = 'ناموجود';
    }

    refreshQtyButtons(row);
}

function clampQtyInput(input, allowEmpty = false) {
    const raw = String(input.value || '').trim();

    if (allowEmpty && raw === '') {
        return;
    }

    const min = parseInt(input.min || '0', 10) || 0;
    const max = parseInt(input.max || '0', 10) || 0;
    let val = parseInt(raw, 10);

    if (!Number.isFinite(val)) val = min;

    if (max > 0 && val > max) val = max;
    if (val < min) val = min;

    input.value = String(val);
}

function changeQty(input, delta) {
    if (!input || input.disabled) return;

    const min = parseInt(input.min || '0', 10) || 0;
    const max = parseInt(input.max || '0', 10) || 0;
    const raw = String(input.value || '').trim();
    let current = parseInt(raw, 10);

    if (!Number.isFinite(current)) {
        current = min > 0 ? min : 0;
    }

    current += delta;

    if (max > 0 && current > max) current = max;
    if (current < min) current = min;

    input.value = String(current);
    refreshQtyButtons(input.closest('.variety-row'));
    updateTotal();
}

function refreshQtyButtons(row) {
    if (!row) return;

    const input = row.querySelector('.quantity-input');
    const minusBtn = row.querySelector('.qty-minus');
    const plusBtn = row.querySelector('.qty-plus');

    if (!input || !minusBtn || !plusBtn) return;

    const min = parseInt(input.min || '0', 10) || 0;
    const max = parseInt(input.max || '0', 10) || 0;
    const raw = String(input.value || '').trim();
    const val = parseInt(raw, 10);

    if (input.disabled) {
        minusBtn.disabled = true;
        plusBtn.disabled = true;
        return;
    }

    minusBtn.disabled = Number.isFinite(val) ? val <= min : false;
    plusBtn.disabled = (max > 0 && Number.isFinite(val)) ? val >= max : false;
}

function updateBlockSummary(block) {
    const summaryTitle = block.querySelector('.summary-title');
    const summarySubtotal = block.querySelector('.summary-subtotal');
    const summaryRows = block.querySelector('.summary-rows');
    const bodySubtotal = block.querySelector('.block-subtotal');
    const productNameEl = block.querySelector('.product-name-label');

    let subtotal = 0;
    const rows = block.querySelectorAll('.variety-row');

    rows.forEach(function (row) {
        const price = parseFloat(row.querySelector('.price-raw') ? row.querySelector('.price-raw').value : 0) || 0;
        const rawQty = row.querySelector('.quantity-input') ? row.querySelector('.quantity-input').value : 0;
        const quantity = parseInt(String(rawQty || '').trim(), 10) || 0;
        subtotal += price * quantity;
    });

    const name = productNameEl.textContent || 'محصول انتخاب نشده';
    summaryTitle.textContent = name;
    summarySubtotal.textContent = formatPrice(subtotal) + ' تومان';
    summaryRows.textContent = rows.length + ' تنوع';
    bodySubtotal.textContent = formatPrice(subtotal) + ' تومان';
}

function updateTotal() {
    const discount = parseFloat(document.getElementById('discount') ? document.getElementById('discount').value : 0) || 0;
    const shipping = parseFloat(document.getElementById('shipping_price') ? document.getElementById('shipping_price').value : 0) || 0;

    let total = 0;

    document.querySelectorAll('.product-block').forEach(function (block) {
        updateBlockSummary(block);
    });

    document.querySelectorAll('.variety-row').forEach(function (row) {
        const price = parseFloat(row.querySelector('.price-raw') ? row.querySelector('.price-raw').value : 0) || 0;
        const rawQty = row.querySelector('.quantity-input') ? row.querySelector('.quantity-input').value : 0;
        const quantity = parseInt(String(rawQty || '').trim(), 10) || 0;
        total += price * quantity;
        refreshQtyButtons(row);
    });

    const finalTotal = Math.max(total + shipping - discount, 0);
    document.getElementById('total_price').value = finalTotal.toLocaleString('fa-IR');
}

function collapseBlock(block) {
    const productId = block.querySelector('.product-select').value || '';
    if (!productId) {
        alert('اول محصول را انتخاب کن.');
        return;
    }

    const hasValidRow = Array.from(block.querySelectorAll('.variety-row')).some(function (row) {
        const varietyEl = row.querySelector('.selected-variety-id');
        return !!(varietyEl && varietyEl.value);
    });

    if (!hasValidRow) {
        alert('حداقل یک تنوع معتبر برای این محصول انتخاب کن.');
        return;
    }

    updateBlockSummary(block);
    block.classList.add('is-collapsed');
}

function expandBlock(block) {
    block.classList.remove('is-collapsed');
}

function initProductSelect2(selectEl) {
    $(selectEl).select2({
        width: '100%',
        dir: 'rtl',
        placeholder: 'جستجوی نام یا کد 4 رقمی محصول...',
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: API.products,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term || '', page: params.page || 1 };
            },
            processResults: function (resp, params) {
                params.page = params.page || 1;

                const list = (resp && resp.data && resp.data.products && resp.data.products.data) ? resp.data.products.data : [];
                const more = !!(resp && resp.data && resp.data.products && resp.data.products.next_page_url);

                return {
                    results: list.map(function (p) {
                        const code = p.code || '';
                        const title = p.title || p.name || '';
                        return {
                            id: p.id,
                            text: '[کد: ' + code + '] ' + title
                        };
                    }),
                    pagination: { more: more }
                };
            }
        }
    });

    $(selectEl).on('select2:select select2:clear', function () {
        this.dispatchEvent(new Event('change', { bubbles: true }));
    });
}

function addProductBlock(prefill) {
    prefill = prefill || {};
    const container = document.getElementById('productBlocksContainer');
    const blockIndex = container.children.length + 1;

    const block = createEl(
        '<div class="product-block">' +
            '<div class="product-header">' +
                '<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">' +
                    '<div>' +
                        '<div class="fw-bold text-primary">محصول #' + blockIndex + '</div>' +
                        '<div class="hint">محصول را انتخاب کن و تنوع‌ها را وارد کن</div>' +
                    '</div>' +
                    '<div class="d-flex gap-2 flex-wrap">' +
                        '<span class="chip"><span class="text-muted">نام:</span><span class="fw-bold product-name-label">—</span></span>' +
                        '<span class="chip"><span class="text-muted">کد:</span><span class="fw-bold product-code-label">—</span></span>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            '<div class="product-body">' +
                '<div class="mb-2">' +
                    '<label class="compact-label">انتخاب کالا</label>' +
                    '<select class="form-select form-select-sm product-select" required></select>' +
                '</div>' +

                '<div class="varieties-wrapper">' +
                    '<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">' +
                        '<div class="fw-semibold fs-7 text-secondary">تنوع‌های این محصول</div>' +
                        '<button type="button" class="btn btn-outline-primary btn-sm add-variety-btn">➕ افزودن تنوع</button>' +
                    '</div>' +

                    '<div class="varieties-list-container"></div>' +

                    '<div class="row-tools d-flex justify-content-between align-items-center flex-wrap gap-2">' +
                        '<div class="block-subtotal-box d-flex justify-content-between align-items-center gap-3">' +
                            '<span class="fw-bold fs-7">جمع این محصول</span>' +
                            '<span class="fw-bold block-subtotal">0 تومان</span>' +
                        '</div>' +
                        '<div class="d-flex align-items-center gap-2">' +
                            '<button type="button" class="icon-btn ok collapse-block-btn" title="تکمیل محصول">✓</button>' +
                            '<button type="button" class="icon-btn danger remove-block-btn" title="حذف محصول">✕</button>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            '<div class="product-summary">' +
                '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">' +
                    '<div class="summary-line">' +
                        '<span class="fw-bold summary-title">—</span>' +
                        '<span class="text-muted mx-2">|</span>' +
                        '<span class="summary-rows text-muted">0 تنوع</span>' +
                    '</div>' +
                    '<div class="d-flex align-items-center gap-2 flex-wrap">' +
                        '<span class="chip"><span class="text-muted">جمع:</span><span class="fw-bold summary-subtotal">0 تومان</span></span>' +
                        '<button type="button" class="icon-btn muted edit-block-btn" title="باز کردن محصول">✎</button>' +
                        '<button type="button" class="icon-btn danger remove-block-btn" title="حذف محصول">✕</button>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>'
    );

    container.appendChild(block);

    const productSelect = block.querySelector('.product-select');
    initProductSelect2(productSelect);

    block.querySelectorAll('.remove-block-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            block.remove();
            updateTotal();
        });
    });

    block.querySelector('.collapse-block-btn').addEventListener('click', function () {
        collapseBlock(block);
    });

    block.querySelector('.edit-block-btn').addEventListener('click', function () {
        expandBlock(block);
    });

    block.querySelector('.add-variety-btn').addEventListener('click', function () {
        const pid = productSelect.value || '';
        if (!pid) {
            alert('اول محصول را انتخاب کن.');
            return;
        }
        addVarietyRow(block, pid, null);
    });

    productSelect.addEventListener('change', async function () {
        const pid = productSelect.value || '';
        const list = block.querySelector('.varieties-list-container');

        block.querySelector('.product-name-label').textContent = '—';
        block.querySelector('.product-code-label').textContent = '—';
        list.innerHTML = '';

        if (!pid) {
            updateTotal();
            return;
        }

        const product = await getProductDetails(pid);
        const name = productTitle(product) || '—';
        const code = product && product.code ? product.code : '—';

        block.querySelector('.product-name-label').textContent = name;
        block.querySelector('.product-code-label').textContent = String(code);

        expandBlock(block);
        addVarietyRow(block, pid, null);
        updateTotal();
    });

    if (prefill.product_id) {
        (async function () {
            const pid = String(prefill.product_id);
            const product = await getProductDetails(pid);
            const code = product && product.code ? product.code : '';
            const title = productTitle(product);
            const text = '[کد: ' + code + '] ' + title;

            const opt = new Option(text, pid, true, true);
            productSelect.appendChild(opt);
            $(productSelect).trigger('change');

            if (prefill.rows && prefill.rows.length) {
                const list = block.querySelector('.varieties-list-container');
                list.innerHTML = '';
                for (let i = 0; i < prefill.rows.length; i++) {
                    await addVarietyRow(block, pid, prefill.rows[i]);
                }
            }

            updateTotal();
        })();
    }

    return block;
}

async function addVarietyRow(block, productId, prefillRow) {
    const list = block.querySelector('.varieties-list-container');
    const idx = globalRowIndex++;

    const row = createEl(
        '<div class="variety-row row g-2 align-items-end">' +
            '<input type="hidden" name="products[' + idx + '][id]" class="hidden-product-id" value="' + String(productId) + '">' +
            '<input type="hidden" name="products[' + idx + '][variety_id]" class="selected-variety-id" value="">' +

            '<div class="col-md-3">' +
                '<label class="compact-label">مدل‌لیست</label>' +
                '<select class="form-select form-select-sm model-select" required>' +
                    '<option value="">در حال بارگذاری...</option>' +
                '</select>' +
                '<div class="mt-1 d-flex gap-2 flex-wrap">' +
                    '<span class="badge bg-light text-dark" style="border:1px solid #e2e8f0;">مدل‌لیست: <span class="selected-model-label">—</span></span>' +
                    '<span class="badge bg-light text-dark" style="border:1px solid #e2e8f0;">طرح‌بندی: <span class="selected-design-label">—</span></span>' +
                '</div>' +
            '</div>' +

            '<div class="col-md-3">' +
                '<label class="compact-label">طرح‌بندی</label>' +
                '<select class="form-select form-select-sm design-select" required disabled>' +
                    '<option value="">ابتدا مدل‌لیست را انتخاب کنید</option>' +
                '</select>' +
            '</div>' +

            '<div class="col-md-2">' +
                '<label class="compact-label">تعداد</label>' +
                '<div class="qty-box">' +
                    '<button type="button" class="qty-btn qty-plus" title="افزایش">+</button>' +
                    '<input type="number" name="products[' + idx + '][quantity]" class="form-control form-control-sm quantity-input" min="1" value="1" inputmode="numeric" required>' +
                    '<button type="button" class="qty-btn qty-minus" title="کاهش">−</button>' +
                '</div>' +
            '</div>' +

            '<div class="col-md-3">' +
                '<label class="compact-label">قیمت واحد</label>' +
                '<input type="text" class="form-control form-control-sm price-view" readonly>' +
                '<input type="hidden" name="products[' + idx + '][price]" class="price-raw" value="0">' +
                '<div class="mt-1 d-flex gap-2 flex-wrap align-items-center">' +
                    '<span class="badge bg-secondary stock-badge">—</span>' +
                    '<span class="badge bg-light text-dark mono code11-badge" style="border:1px solid #e2e8f0;">—</span>' +
                '</div>' +
            '</div>' +

            '<div class="col-md-1 text-center">' +
                '<button type="button" class="icon-btn danger remove-variety-btn mt-4 mt-md-0" title="حذف تنوع">✕</button>' +
            '</div>' +
        '</div>'
    );

    list.appendChild(row);

    const modelSelect = row.querySelector('.model-select');
    const designSelect = row.querySelector('.design-select');
    const varietyInput = row.querySelector('.selected-variety-id');
    const qtyInput = row.querySelector('.quantity-input');
    const priceRaw = row.querySelector('.price-raw');
    const priceView = row.querySelector('.price-view');
    const codeBadge = row.querySelector('.code11-badge');
    const modelLabelEl = row.querySelector('.selected-model-label');
    const designLabelEl = row.querySelector('.selected-design-label');
    const plusBtn = row.querySelector('.qty-plus');
    const minusBtn = row.querySelector('.qty-minus');

    row.querySelector('.remove-variety-btn').addEventListener('click', function () {
        if (list.children.length <= 1) {
            alert('حداقل یک تنوع باید برای این محصول وجود داشته باشد.');
            return;
        }
        row.remove();
        updateTotal();
    });

    plusBtn.addEventListener('click', function () {
        changeQty(qtyInput, 1);
    });

    minusBtn.addEventListener('click', function () {
        changeQty(qtyInput, -1);
    });

    qtyInput.addEventListener('input', function () {
        const raw = String(qtyInput.value || '');

        if (raw !== '' && !/^\d+$/.test(raw)) {
            qtyInput.value = raw.replace(/[^\d]/g, '');
        }

        refreshQtyButtons(row);
        updateTotal();
    });

    qtyInput.addEventListener('blur', function () {
        clampQtyInput(qtyInput, false);
        refreshQtyButtons(row);
        updateTotal();
    });

    qtyInput.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            changeQty(qtyInput, 1);
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            changeQty(qtyInput, -1);
        }
    });

    const product = await getProductDetails(productId);
    const varieties = getProductVarieties(product).filter(function (v) {
        return varietyStockValue(v) > 0;
    });

    modelSelect.innerHTML = '<option value="">انتخاب مدل‌لیست...</option>';
    designSelect.innerHTML = '<option value="">ابتدا مدل‌لیست را انتخاب کنید</option>';
    designSelect.disabled = true;

    if (!varieties.length) {
        modelSelect.innerHTML = '<option value="">مدل موجودی‌دار ندارد</option>';
        modelSelect.disabled = true;
        designSelect.innerHTML = '<option value="">طرح موجودی‌دار ندارد</option>';

        qtyInput.value = '0';
        qtyInput.disabled = true;
        priceRaw.value = '0';
        priceView.value = '';
        varietyInput.value = '';
        setStockUI(row, 0);
        codeBadge.textContent = '—';
        modelLabelEl.textContent = '—';
        designLabelEl.textContent = '—';
        updateTotal();
        return;
    }

    const modelGroups = new Map();
    varieties.forEach(function (v) {
        const key = varietyModelLabel(v);
        if (!modelGroups.has(key)) modelGroups.set(key, []);
        modelGroups.get(key).push(v);
    });

    Array.from(modelGroups.keys()).sort(function (a, b) {
        return a.localeCompare(b, 'fa');
    }).forEach(function (modelName) {
        const opt = document.createElement('option');
        opt.value = modelName;
        opt.textContent = modelName;
        modelSelect.appendChild(opt);
    });

    if (prefillRow) {
        if (prefillRow.quantity !== undefined && prefillRow.quantity !== null) {
            qtyInput.value = String(prefillRow.quantity);
        }
    }

    function applySelectedVariety(v) {
        if (!v) {
            varietyInput.value = '';
            priceRaw.value = '0';
            priceView.value = '';
            setStockUI(row, 0);
            codeBadge.textContent = '—';
            modelLabelEl.textContent = '—';
            designLabelEl.textContent = '—';
            refreshQtyButtons(row);
            updateTotal();
            return;
        }

        const price = Number(v.price || v.sell_price || product.price || 0);
        const stock = varietyStockValue(v);
        const code11 = (v.variant_code || v.code || v.barcode || '');

        varietyInput.value = String(v.id);
        priceRaw.value = String(price);
        priceView.value = formatPrice(price) + ' تومان';
        setStockUI(row, stock);
        codeBadge.textContent = code11 ? String(code11) : ('VID-' + String(v.id));
        modelLabelEl.textContent = varietyModelLabel(v);
        designLabelEl.textContent = varietyDesignLabel(v);

        clampQtyInput(qtyInput, false);

        if (prefillRow && prefillRow.price !== undefined && prefillRow.price !== null) {
            priceRaw.value = String(prefillRow.price);
            priceView.value = formatPrice(prefillRow.price) + ' تومان';
        }

        refreshQtyButtons(row);
        updateTotal();
    }

    function fillDesignsByModel(modelName) {
        const designs = modelGroups.get(modelName) || [];
        designSelect.innerHTML = '<option value="">انتخاب طرح‌بندی...</option>';
        designSelect.disabled = designs.length === 0;

        designs.forEach(function (v) {
            const opt = document.createElement('option');
            opt.value = String(v.id);
            opt.textContent = varietyDesignLabel(v);
            designSelect.appendChild(opt);
        });
    }

    modelSelect.addEventListener('change', function () {
        fillDesignsByModel(modelSelect.value);
        applySelectedVariety(null);
    });

    designSelect.addEventListener('change', function () {
        const vid = parseInt(designSelect.value || 0, 10);
        const selected = varieties.find(function (v) {
            return Number(v.id) === Number(vid);
        }) || null;
        applySelectedVariety(selected);
    });

    const prefillVariantId = parseInt(prefillRow && prefillRow.variety_id ? prefillRow.variety_id : 0, 10);
    const prefillVariant = prefillVariantId
        ? (varieties.find(function (v) { return Number(v.id) === Number(prefillVariantId); }) || null)
        : null;

    if (prefillVariant) {
        const prefillModel = varietyModelLabel(prefillVariant);
        modelSelect.value = prefillModel;
        fillDesignsByModel(prefillModel);
        designSelect.value = String(prefillVariant.id);
        applySelectedVariety(prefillVariant);
    } else {
        modelSelect.value = modelSelect.options.length > 1 ? modelSelect.options[1].value : '';
        if (modelSelect.value) {
            fillDesignsByModel(modelSelect.value);
            if (designSelect.options.length > 1) {
                designSelect.value = designSelect.options[1].value;
                const selected = varieties.find(function (v) {
                    return Number(v.id) === Number(designSelect.value);
                }) || null;
                applySelectedVariety(selected);
            } else {
                applySelectedVariety(null);
            }
        } else {
            applySelectedVariety(null);
        }
    }

    refreshQtyButtons(row);
}

function initFromOldOrEdit() {
    if (!INIT_ROWS || !INIT_ROWS.length) {
        addProductBlock({});
        return;
    }

    const grouped = {};

    INIT_ROWS.forEach(function (r) {
        const pid = parseInt(r.id || 0, 10);
        if (!pid) return;

        if (!grouped[pid]) grouped[pid] = [];
        grouped[pid].push({
            product_id: pid,
            variety_id: parseInt(r.variety_id || 0, 10),
            quantity: parseInt(r.quantity || 1, 10),
            price: parseInt(r.price || 0, 10)
        });
    });

    Object.keys(grouped).forEach(function (pidStr) {
        const pid = parseInt(pidStr, 10);
        addProductBlock({
            product_id: pid,
            rows: grouped[pid]
        });
    });

    updateTotal();
}

(function () {
    function toEnglishDigits(str) {
        return String(str || '')
            .replace(/[۰-۹]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); })
            .replace(/[٠-٩]/g, function (d) { return '٠١٢٣٤٥٦٧٨٩'.indexOf(d); });
    }

    function toInt(val) {
        const s = toEnglishDigits(val)
            .replaceAll(',', '')
            .replaceAll('٬', '')
            .replaceAll('،', '')
            .trim();

        const n = parseFloat(s);
        return Number.isFinite(n) ? Math.trunc(n) : 0;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('orderForm');
        if (!form) return;

        form.addEventListener('submit', function () {
            const totalEl = document.getElementById('total_price');
            if (totalEl) totalEl.value = String(toInt(totalEl.value));

            const shipEl = document.getElementById('shipping_price');
            if (shipEl) shipEl.value = String(toInt(shipEl.value));

            const discEl = document.getElementById('discount');
            if (discEl) discEl.value = String(toInt(discEl.value));

            document.querySelectorAll('.price-raw').forEach(function (el) {
                el.value = String(toInt(el.value));
            });

            document.querySelectorAll('.quantity-input').forEach(function (el) {
                const raw = String(el.value || '').trim();
                if (raw === '') {
                    const min = parseInt(el.min || '0', 10) || 0;
                    el.value = String(min > 0 ? min : 0);
                } else {
                    el.value = String(toInt(el.value));
                }
            });
        }, { capture: true });
    });
})();

document.addEventListener('DOMContentLoaded', async function () {
    const shippingSelect = document.getElementById('shipping_id');
    const provinceSelect = document.getElementById('province_id');

    initLocationSelect2(document.getElementById('province_id'), 'انتخاب استان...');
    initLocationSelect2(document.getElementById('city_id'), 'انتخاب شهر...');

    await loadArea();
    fillProvincesSelect(areaProvinces);

    if (OLD_PROVINCE_ID) {
        document.getElementById('province_id').value = String(OLD_PROVINCE_ID);
        fillCitiesByProvinceId(OLD_PROVINCE_ID);
    }
    if (OLD_CITY_ID) {
        document.getElementById('city_id').value = String(OLD_CITY_ID);
    }

    fillShippingSelect();
    initInlineCustomerSearch();
    await loadOldCustomer();

    provinceSelect.addEventListener('change', function () {
        fillCitiesByProvinceId(provinceSelect.value);
    });

    shippingSelect.addEventListener('change', function () {
        const sid = parseInt(shippingSelect.value || 0, 10);
        const ship = shippingById(sid) || null;
        const price = ship ? parseInt(ship.price || 0, 10) : 0;

        document.getElementById('shipping_price').value = String(price);
        document.getElementById('shipping_label').textContent = 'هزینه ارسال: ' + price.toLocaleString('fa-IR') + ' تومان';
        document.getElementById('shipping_price_view').value = price.toLocaleString('fa-IR') + ' تومان';

        toggleShippingFields();
        updateTotal();
    });

    document.getElementById('discount').addEventListener('input', updateTotal);

    document.getElementById('addProductBlockBtn').addEventListener('click', function () {
        addProductBlock({});
    });

    initFromOldOrEdit();

    const oldShippingId = @json(old('shipping_id', ''));
    if (oldShippingId) {
        shippingSelect.value = String(oldShippingId);
    }

shippingSelect.dispatchEvent(new Event('change'));
    updateTotal();
});
</script>
<script>
window.updateTotal = function () {
    const shipping = Number(document.getElementById('shipping_price')?.value || 0);
    const discount = Number(document.getElementById('discount')?.value || 0);
    let subtotal = 0;
    document.querySelectorAll('#groupProductsInputs input[name$=\"[quantity]\"]').forEach((qtyEl) => {
        const i = qtyEl.name.match(/products\[(\d+)]/);
        if (!i) return;
        const idx = i[1];
        const priceEl = document.querySelector('#groupProductsInputs input[name=\"products[' + idx + '][price]\"]');
        subtotal += (Number(qtyEl.value || 0) * Number(priceEl?.value || 0));
    });
    const total = Math.max(0, subtotal + shipping - discount);
    const totalEl = document.getElementById('total_price');
    if (totalEl) totalEl.value = String(total);
};
</script>
@endsection
