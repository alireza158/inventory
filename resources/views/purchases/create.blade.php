@extends('layouts.app')

@section('content')
@php
    $isEdit = isset($purchase) && $purchase;
    $formAction = $isEdit ? route('purchases.update', $purchase) : route('purchases.store');
    $initialItems = old('items');

    if (!is_array($initialItems)) {
        $initialItems = $isEdit
            ? $purchase->items->map(fn ($it) => [
                'product_id' => $it->product_id,
                'variant_id' => $it->product_variant_id,
                'name' => $it->product_name,
                'code' => $it->product_code,
                'variant_name' => $it->variant_name,
                'quantity' => $it->quantity,
                'buy_price' => $it->buy_price,
                'sell_price' => $it->sell_price,
                'discount_type' => $it->discount_type,
                'discount_value' => $it->discount_value,
            ])->values()->all()
            : [];
    }

    $productsPayload = $products->map(function ($p) {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->code ?: $p->sku,
            'variants' => $p->variants->map(function ($v) {
                return [
                    'id' => $v->id,
                    'name' => $v->variant_name,
                    'buy_price' => (int) ($v->buy_price ?? 0),
                    'sell_price' => (int) ($v->sell_price ?? 0),
                ];
            })->values()->all(),
        ];
    })->values()->all();
@endphp

<style>
    /* ===== White Background + Bold Blue/Black Theme ===== */
    :root{
        --ink: #0b1220;         /* نزدیک به مشکی (خوانا) */
        --navy: #071a3a;        /* سرمه‌ای پررنگ */
        --blue: #0d6efd;        /* آبی Bootstrap */
        --blue2:#0a58ca;        /* آبی پررنگ‌تر */
        --soft: #f6f9ff;        /* پس زمینه خیلی روشن آبی */
        --soft2:#eef4ff;        /* روشن‌تر برای ردیف‌های زوج */
        --border: #dbe6ff;      /* بوردر آبی ملایم */
        --shadow: 0 10px 28px rgba(7, 26, 58, .10);
        --shadow2:0 6px 14px rgba(7, 26, 58, .08);
    }

    /* wrapper برای اینکه بک گراند کلی سفید بمونه ولی صفحه جون داشته باشه */
    .purchase-page-wrap{
        background: #fff;
        border-radius: 18px;
        padding: 14px;
    }

    /* نوار بالای صفحه (جون‌دار با آبی/مشکی) */
    .purchase-topbar{
        background: linear-gradient(90deg, var(--navy), var(--blue2));
        border-radius: 16px;
        padding: 12px 14px;
        box-shadow: var(--shadow2);
        color: #fff;
    }
    .purchase-topbar .page-title{
        color: #fff;
        margin: 0;
    }
    .purchase-topbar .btn{
        border-radius: 12px;
    }
    .purchase-topbar .btn-outline-light{
        border-color: rgba(255,255,255,.55);
        color: #fff;
    }
    .purchase-topbar .btn-outline-light:hover{
        background: rgba(255,255,255,.12);
        color: #fff;
    }

    /* کارت اصلی فرم */
    .purchase-form.card{
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 18px;
        box-shadow: var(--shadow);
        overflow: hidden;
    }
    .purchase-form .card-body{
        background: #fff;
        color: var(--ink);
    }

    .purchase-form .form-label{
        color: rgba(11,18,32,.75);
        font-size: .85rem;
        margin-bottom: .35rem;
    }

    /* Inputs */
    .purchase-form .form-control,
    .purchase-form .form-select {
        height: 40px;
        font-size: .9rem;
        border-radius: 12px;
        border: 1px solid rgba(11,18,32,.12);
        background: #fff;
        color: var(--ink);
    }
    .purchase-form .form-control::placeholder{
        color: rgba(11,18,32,.35);
    }
    .purchase-form .form-control:focus,
    .purchase-form .form-select:focus{
        outline: none;
        border-color: rgba(13,110,253,.65);
        box-shadow: 0 0 0 .25rem rgba(13,110,253,.18);
    }

    /* Alert */
    .purchase-form .alert{
        background: linear-gradient(180deg, rgba(13,110,253,.10), rgba(13,110,253,.06));
        border: 1px solid rgba(13,110,253,.22);
        color: rgba(11,18,32,.85);
        border-radius: 14px;
    }

    /* دکمه‌ها */
    .purchase-page-wrap .btn{
        border-radius: 12px;
    }
    .purchase-page-wrap .btn-outline-dark{
        border-color: rgba(11,18,32,.22);
        color: var(--ink);
    }
    .purchase-page-wrap .btn-outline-dark:hover{
        border-color: rgba(13,110,253,.45);
        background: rgba(13,110,253,.06);
        color: var(--ink);
    }
    .purchase-page-wrap .btn-outline-secondary{
        border-color: rgba(11,18,32,.18);
        color: rgba(11,18,32,.78);
    }
    .purchase-page-wrap .btn-outline-secondary:hover{
        border-color: rgba(13,110,253,.45);
        background: rgba(13,110,253,.06);
        color: var(--ink);
    }

    .purchase-page-wrap .btn-primary{
        background: linear-gradient(90deg, var(--blue2), var(--blue));
        border: none;
        box-shadow: 0 10px 20px rgba(13,110,253,.18);
    }

    /* کارت هر ردیف */
    .purchase-item {
        border: 1px solid rgba(13,110,253,.18);
        border-radius: 16px;
        padding: .9rem;
        background: #fff;
        box-shadow: var(--shadow2);
        position: relative;
        overflow: hidden;
    }

    /* نوار رنگی کنار هر ردیف */
    .purchase-item::before{
        content:"";
        position:absolute;
        inset:0 auto 0 0;
        width:7px;
        background: linear-gradient(180deg, var(--navy), var(--blue));
    }

    /* زوج‌ها کمی رنگی‌تر */
    .purchase-item.is-even{
        background: linear-gradient(180deg, var(--soft2), #fff);
        border-color: rgba(7,26,58,.10);
    }
    .purchase-item.is-even::before{
        background: linear-gradient(180deg, var(--blue2), #00a3ff);
    }

    .purchase-item + .purchase-item {
        margin-top: 1rem;
    }

    .purchase-item:hover{
        border-color: rgba(13,110,253,.35);
        box-shadow: 0 14px 26px rgba(7,26,58,.12);
        transform: translateY(-1px);
        transition: .18s ease;
    }

    /* هدر ردیف */
    .row-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:.6rem;
        padding: .5rem .65rem;
        margin: -0.15rem -0.15rem .8rem -0.15rem;
        border-radius: 14px;
        background: linear-gradient(90deg, rgba(7,26,58,.06), rgba(13,110,253,.06));
        border: 1px dashed rgba(13,110,253,.20);
    }

    .row-badge{
        display:inline-flex;
        align-items:center;
        gap:.35rem;
        font-weight: 900;
        font-size: .85rem;
        color: #fff;
        background: linear-gradient(90deg, var(--navy), var(--blue2));
        padding: .30rem .65rem;
        border-radius: 999px;
        white-space: nowrap;
        box-shadow: 0 8px 16px rgba(7,26,58,.12);
    }

    .row-meta{
        font-size: .78rem;
        color: rgba(11,18,32,.65);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 55%;
    }

    .purchase-item .label{
        font-size: .75rem;
        color: rgba(11,18,32,.65);
        margin-bottom: .25rem;
    }

    .purchase-item .line-total{
        font-weight: 900;
        font-size: .92rem;
        min-height: 40px;
        display:flex;
        align-items:center;
        justify-content:center;
        border-radius: 12px;
        background: linear-gradient(90deg, var(--navy), rgba(7,26,58,.90));
        color: #fff;
        border: 1px solid rgba(7,26,58,.12);
    }

    hr{
        border-top: 1px solid rgba(7,26,58,.10);
        opacity: 1;
    }

    /* option متن سیاه برای خوانایی */
    option{ color:#111; }

    .purchase-form input[type=number]::-webkit-outer-spin-button,
    .purchase-form input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .purchase-form input[type=number] {
        -moz-appearance: textfield;
        appearance: textfield;
    }
</style>

<div class="purchase-page-wrap">

    <div class="purchase-topbar d-flex justify-content-between align-items-center mb-3">
        <h4 class="page-title mb-0">{{ $isEdit ? 'ویرایش سند خرید' : 'ثبت خرید جدید' }}</h4>
        <a class="btn btn-sm btn-outline-light" href="{{ route('purchases.index') }}">بازگشت</a>
    </div>

    <div class="card purchase-form">
        <div class="card-body">
            <form method="POST" action="{{ $formAction }}" id="purchaseForm">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label">تامین‌کننده</label>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" name="supplier_id" required>
                                <option value="">انتخاب کنید...</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id', $purchase->supplier_id ?? null)==$supplier->id)>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                            <a href="{{ route('suppliers.index') }}" class="btn btn-sm btn-outline-dark">مدیریت تامین‌کننده‌ها</a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">توضیحات (اختیاری)</label>
                        <input class="form-control form-control-sm" name="note" value="{{ old('note', $purchase->note ?? '') }}">
                    </div>
                </div>

                <div class="alert py-2 small">
                    بک‌گراند سفید حفظ شد و داخل صفحه از آبی پررنگ + مشکی استفاده شد تا فرم جون‌دارتر و خواناتر باشد.
                    تخفیف جزئی روی هر آیتم و تخفیف کلی روی فاکتور پشتیبانی می‌شود.
                </div>

                <div id="itemsList"></div>

                <div class="d-flex gap-2 flex-wrap mt-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">+ افزودن ردیف جدید</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addVariantForSameProductBtn">+ افزودن مدل برای همین محصول</button>
                </div>

                <hr>

                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">تخفیف کلی فاکتور</label>
                        <select class="form-select form-select-sm" name="invoice_discount_type" id="invoiceDiscountType">
                            <option value="">بدون تخفیف</option>
                            <option value="amount" @selected(old('invoice_discount_type', $purchase->discount_type ?? '')==='amount')>مبلغی</option>
                            <option value="percent" @selected(old('invoice_discount_type', $purchase->discount_type ?? '')==='percent')>درصدی</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">مقدار</label>
                        <input type="number" min="0" class="form-control form-control-sm" id="invoiceDiscountValue" name="invoice_discount_value" value="{{ old('invoice_discount_value', $purchase->discount_value ?? 0) }}">
                    </div>
                    <div class="col-md-7 text-end">
                        <div class="small text-muted">جمع کل قبل تخفیف: <span id="subtotalAmount">0</span> ریال</div>
                        <div class="small text-muted">جمع تخفیف: <span id="totalDiscountAmount">0</span> ریال</div>
                        <div class="fw-bold fs-5" style="color: var(--ink);">قیمت کل خرید: <span id="totalAmount">0</span> ریال</div>
                    </div>
                </div>

                <div class="mt-4">
                    <button class="btn btn-sm btn-primary">{{ $isEdit ? 'ذخیره تغییرات سند خرید' : 'ثبت نهایی خرید' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const products = @json($productsPayload);
    const initialItems = @json($initialItems);

    const itemsList = document.getElementById('itemsList');
    const addBtn = document.getElementById('addRowBtn');
    const addSameProductBtn = document.getElementById('addVariantForSameProductBtn');

    const subtotalEl = document.getElementById('subtotalAmount');
    const totalDiscountEl = document.getElementById('totalDiscountAmount');
    const totalEl = document.getElementById('totalAmount');
    const invoiceDiscountTypeEl = document.getElementById('invoiceDiscountType');
    const invoiceDiscountValueEl = document.getElementById('invoiceDiscountValue');

    function productOptions(selected = '') {
        return `<option value="">کالای جدید/بدون انتخاب</option>${products.map((p) =>
            `<option value="${p.id}" ${String(selected)===String(p.id)?'selected':''}>${p.name} (${p.code || '-'})</option>`
        ).join('')}`;
    }

    function variantOptions(productId, selected = '') {
        const product = products.find((p) => String(p.id) === String(productId));
        if (!product) return '<option value="">مدل جدید</option>';

        return `<option value="">مدل جدید</option>${product.variants.map((v) =>
            `<option value="${v.id}" data-name="${v.name}" data-buy="${v.buy_price}" data-sell="${v.sell_price}" ${String(selected)===String(v.id)?'selected':''}>${v.name}</option>`
        ).join('')}`;
    }

    function calculateDiscount(base, type, value) {
        const v = Number(value || 0);
        if (!type || v <= 0 || base <= 0) return 0;
        if (type === 'percent') return Math.floor(base * Math.min(v, 100) / 100);
        return Math.min(v, base);
    }

    function itemTemplate(index, item = {}) {
        const productId = item.product_id || '';
        const variantId = item.variant_id || '';
        const rowDiscountType = item.discount_type || '';
        const rowDiscountValue = item.discount_value || 0;

        return `
        <div class="purchase-item ${index % 2 === 1 ? 'is-even' : ''}" data-row>
            <div class="row-head">
                <div class="row-badge">
                    <span>ردیف</span>
                    <span class="row-index">${(index + 1).toLocaleString('fa-IR')}</span>
                </div>
                <div class="row-meta">
                    <span class="meta-product">${(item.name ?? '').trim() || '—'}</span>
                    <span class="text-muted"> / </span>
                    <span class="meta-variant">${(item.variant_name ?? '').trim() || '—'}</span>
                </div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-md-3">
                    <div class="label">انتخاب کالا</div>
                    <select class="form-select form-select-sm product-select" name="items[${index}][product_id]">
                        ${productOptions(productId)}
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="label">اسم محصول</div>
                    <input class="form-control form-control-sm product-name" name="items[${index}][name]" value="${item.name ?? ''}" required>
                </div>
                <div class="col-md-2">
                    <div class="label">کد محصول</div>
                    <input class="form-control form-control-sm product-code" name="items[${index}][code]" value="${item.code ?? ''}" required>
                </div>
                <div class="col-md-5">
                    <div class="label">مدل محصول</div>
                    <input type="hidden" class="variant-id" name="items[${index}][variant_id]" value="${variantId}">
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm variant-select" style="max-width:180px;">
                            ${variantOptions(productId, variantId)}
                        </select>
                        <input class="form-control form-control-sm variant-name" name="items[${index}][variant_name]" value="${item.variant_name ?? ''}" placeholder="نام مدل" required>
                    </div>
                </div>
            </div>

            <div class="row g-2 align-items-end">
                <div class="col-md-1">
                    <div class="label">تعداد</div>
                    <input type="number" min="1" class="form-control form-control-sm qty" name="items[${index}][quantity]" value="${item.quantity ?? 1}" required>
                </div>
                <div class="col-md-2">
                    <div class="label">قیمت خرید</div>
                    <input type="number" min="0" class="form-control form-control-sm buy" name="items[${index}][buy_price]" value="${item.buy_price ?? 0}" required>
                </div>
                <div class="col-md-2">
                    <div class="label">قیمت فروش</div>
                    <input type="number" min="0" class="form-control form-control-sm sell" name="items[${index}][sell_price]" value="${item.sell_price ?? 0}" required>
                </div>
                <div class="col-md-2">
                    <div class="label">نوع تخفیف</div>
                    <select class="form-select form-select-sm row-discount-type" name="items[${index}][discount_type]">
                        <option value="">—</option>
                        <option value="amount" ${rowDiscountType==='amount'?'selected':''}>مبلغی</option>
                        <option value="percent" ${rowDiscountType==='percent'?'selected':''}>درصدی</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="label">مقدار تخفیف</div>
                    <input type="number" min="0" class="form-control form-control-sm discount-value" name="items[${index}][discount_value]" value="${rowDiscountValue}">
                </div>
                <div class="col-md-1">
                    <div class="label">جمع نهایی</div>
                    <div class="line-total">0</div>
                </div>
                <div class="col-md-2 text-end d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary duplicate-variant-row">+ مدل</button>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-row">حذف</button>
                </div>
            </div>
        </div>`;
    }

    function setName(el, newName){
        if (el && el.getAttribute('name')) el.setAttribute('name', newName);
    }

    function reindexRows() {
        const rows = Array.from(itemsList.querySelectorAll('[data-row]'));

        rows.forEach((row, idx) => {
            row.classList.toggle('is-even', idx % 2 === 1);

            const badge = row.querySelector('.row-index');
            if (badge) badge.textContent = (idx + 1).toLocaleString('fa-IR');

            const mp = row.querySelector('.meta-product');
            const mv = row.querySelector('.meta-variant');
            const pn = row.querySelector('.product-name')?.value || '—';
            const vn = row.querySelector('.variant-name')?.value || '—';
            if (mp) mp.textContent = pn.trim() || '—';
            if (mv) mv.textContent = vn.trim() || '—';

            setName(row.querySelector('.product-select'), `items[${idx}][product_id]`);
            setName(row.querySelector('.product-name'),   `items[${idx}][name]`);
            setName(row.querySelector('.product-code'),   `items[${idx}][code]`);
            setName(row.querySelector('.variant-id'),     `items[${idx}][variant_id]`);
            setName(row.querySelector('.variant-name'),   `items[${idx}][variant_name]`);
            setName(row.querySelector('.qty'),            `items[${idx}][quantity]`);
            setName(row.querySelector('.buy'),            `items[${idx}][buy_price]`);
            setName(row.querySelector('.sell'),           `items[${idx}][sell_price]`);
            setName(row.querySelector('.row-discount-type'), `items[${idx}][discount_type]`);
            setName(row.querySelector('.discount-value'),    `items[${idx}][discount_value]`);
        });
    }

    function recalc() {
        let subtotal = 0;
        let itemDiscountTotal = 0;

        itemsList.querySelectorAll('[data-row]').forEach((row) => {
            const qty = Number(row.querySelector('.qty')?.value || 0);
            const buy = Number(row.querySelector('.buy')?.value || 0);
            const lineSubtotal = qty * buy;

            const discountType = row.querySelector('.row-discount-type')?.value || '';
            const discountValue = Number(row.querySelector('.discount-value')?.value || 0);
            const rowDiscount = calculateDiscount(lineSubtotal, discountType, discountValue);

            const lineTotal = Math.max(0, lineSubtotal - rowDiscount);
            subtotal += lineSubtotal;
            itemDiscountTotal += rowDiscount;

            row.querySelector('.line-total').textContent = lineTotal.toLocaleString('fa-IR');
        });

        const baseAfterRows = Math.max(0, subtotal - itemDiscountTotal);
        const invoiceDiscount = calculateDiscount(
            baseAfterRows,
            invoiceDiscountTypeEl.value,
            Number(invoiceDiscountValueEl.value || 0)
        );

        const totalDiscount = itemDiscountTotal + invoiceDiscount;
        const total = Math.max(0, subtotal - totalDiscount);

        subtotalEl.textContent = subtotal.toLocaleString('fa-IR');
        totalDiscountEl.textContent = totalDiscount.toLocaleString('fa-IR');
        totalEl.textContent = total.toLocaleString('fa-IR');
    }

    function addItem(item = {}) {
        const index = itemsList.querySelectorAll('[data-row]').length;
        itemsList.insertAdjacentHTML('beforeend', itemTemplate(index, item));
        reindexRows();
        recalc();
    }

    function addVariantForSameProduct() {
        const rows = itemsList.querySelectorAll('[data-row]');
        if (rows.length === 0) return addItem();

        const last = rows[rows.length - 1];
        addItem({
            product_id: last.querySelector('.product-select')?.value || '',
            name: last.querySelector('.product-name')?.value || '',
            code: last.querySelector('.product-code')?.value || '',
            variant_id: '',
            variant_name: '',
            quantity: 1,
            buy_price: 0,
            sell_price: 0,
            discount_type: '',
            discount_value: 0,
        });
    }

    addBtn.addEventListener('click', () => addItem());
    addSameProductBtn.addEventListener('click', addVariantForSameProduct);

    itemsList.addEventListener('change', (e) => {
        const row = e.target.closest('[data-row]');
        if (!row) return;

        if (e.target.classList.contains('product-select')) {
            const productId = e.target.value;
            const product = products.find((p) => String(p.id) === String(productId));
            const variantSelect = row.querySelector('.variant-select');

            variantSelect.innerHTML = variantOptions(productId);
            row.querySelector('.variant-id').value = '';

            if (product) {
                row.querySelector('.product-name').value = product.name || '';
                row.querySelector('.product-code').value = product.code || '';
            }

            reindexRows();
        }

        if (e.target.classList.contains('variant-select')) {
            const opt = e.target.selectedOptions[0];
            const variantId = opt?.value || '';
            row.querySelector('.variant-id').value = variantId;

            if (variantId) {
                row.querySelector('.variant-name').value = opt.dataset.name || '';
                row.querySelector('.buy').value = opt.dataset.buy || 0;
                row.querySelector('.sell').value = opt.dataset.sell || 0;
            }

            reindexRows();
        }

        recalc();
    });

    itemsList.addEventListener('input', (e) => {
        if (
            e.target.classList.contains('qty') ||
            e.target.classList.contains('buy') ||
            e.target.classList.contains('sell') ||
            e.target.classList.contains('discount-value')
        ) {
            recalc();
        }

        if (e.target.classList.contains('variant-name')) {
            const row = e.target.closest('[data-row]');
            if (row) row.querySelector('.variant-id').value = '';
            reindexRows();
        }

        if (e.target.classList.contains('product-name')) {
            reindexRows();
        }
    });

    itemsList.addEventListener('click', (e) => {
        const row = e.target.closest('[data-row]');
        if (!row) return;

        if (e.target.classList.contains('duplicate-variant-row')) {
            addItem({
                product_id: row.querySelector('.product-select')?.value || '',
                name: row.querySelector('.product-name')?.value || '',
                code: row.querySelector('.product-code')?.value || '',
                variant_id: '',
                variant_name: '',
                quantity: 1,
                buy_price: 0,
                sell_price: 0,
                discount_type: '',
                discount_value: 0,
            });
            return;
        }

        if (e.target.classList.contains('remove-row')) {
            row.remove();
            reindexRows();
            recalc();
        }
    });

    invoiceDiscountTypeEl.addEventListener('change', recalc);
    invoiceDiscountValueEl.addEventListener('input', recalc);

    if (Array.isArray(initialItems) && initialItems.length > 0) {
        initialItems.forEach((item) => addItem(item));
    } else {
        addItem();
    }
})();
</script>
@endsection
