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
                'category_id' => $it->product?->category_id,
                'variant_id' => $it->product_variant_id,
                'name' => $it->product_name,
                'code' => $it->product_code,
                'variant_name' => $it->variant_name,
                'variant_code' => $it->variant?->variant_code,
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
            'code' => $p->code,
            'category_id' => $p->category_id,
            'variants' => $p->variants->map(function ($v) {
                return [
                    'id' => $v->id,
                    'name' => $v->variant_name,
                    'code' => $v->variant_code,
                    'buy_price' => (int) ($v->buy_price ?? 0),
                    'sell_price' => (int) ($v->sell_price ?? 0),
                ];
            })->values()->all(),
        ];
    })->values()->all();

    $categoriesPayload = ($categories ?? collect())->map(function ($category) {
        return [
            'id' => $category->id,
            'name' => $category->name,
        ];
    })->values()->all();
@endphp



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
                    <div class="col-md-5">
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
                            <a href="{{ route('persons.index') }}" class="btn btn-sm btn-outline-dark">مدیریت اشخاص</a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">انبار مقصد خرید</label>
                        <select class="form-select form-select-sm" name="warehouse_id" required>
                            <option value="">انتخاب انبار...</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $purchase->warehouse_id ?? null)==$warehouse->id)>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">توضیحات (اختیاری)</label>
                        <input class="form-control form-control-sm" name="note" value="{{ old('note', $purchase->note ?? '') }}">
                    </div>
                </div>



                <div id="itemsList" class="mt-2"></div>

                <div class="d-flex gap-2 flex-wrap mt-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">+ افزودن کالا از لیست محصولات</button>
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
                        <input type="text" inputmode="numeric" class="form-control form-control-sm formatted-number" id="invoiceDiscountValue" name="invoice_discount_value" value="{{ old('invoice_discount_value', $purchase->discount_value ?? 0) }}">
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
    const categories = @json($categoriesPayload);
    const initialItems = @json($initialItems);

    const purchaseForm = document.getElementById('purchaseForm');
    const itemsList = document.getElementById('itemsList');
    const addBtn = document.getElementById('addRowBtn');

    const subtotalEl = document.getElementById('subtotalAmount');
    const totalDiscountEl = document.getElementById('totalDiscountAmount');
    const totalEl = document.getElementById('totalAmount');
    const invoiceDiscountTypeEl = document.getElementById('invoiceDiscountType');
    const invoiceDiscountValueEl = document.getElementById('invoiceDiscountValue');

    function parseNumericInput(value) {
        const normalized = String(value || '')
            .replace(/[٠-٩]/g, d => String(d.charCodeAt(0) - 0x0660))
            .replace(/[۰-۹]/g, d => String(d.charCodeAt(0) - 0x06F0))
            .replace(/[^\d]/g, '');
        return Number(normalized || 0);
    }

    function formatNumericInput(value) {
        return parseNumericInput(value).toLocaleString('en-US');
    }

    function calculateDiscount(base, type, value) {
        const v = Number(value || 0);
        if (!type || v <= 0 || base <= 0) return 0;
        if (type === 'percent') return Math.floor(base * Math.min(v, 100) / 100);
        return Math.min(v, base);
    }

    function categoryOptions(selected = '') {
        return `<option value="">انتخاب دسته‌بندی</option>${categories.map((category) =>
            `<option value="${category.id}" ${String(selected)===String(category.id)?'selected':''}>${category.name}</option>`
        ).join('')}`;
    }

    function productOptionsByCategory(categoryId, selected = '') {
        if (!categoryId) return '<option value="">ابتدا دسته‌بندی را انتخاب کنید</option>';
        const filtered = products.filter((p) => String(p.category_id) === String(categoryId));
        if (filtered.length === 0) return '<option value="">کالایی در این دسته‌بندی نیست</option>';

        return `<option value="">انتخاب کالا</option>${filtered.map((p) =>
            `<option value="${p.id}" ${String(selected)===String(p.id)?'selected':''}>${p.name} (${p.code || '-'})</option>`
        ).join('')}`;
    }

    function extractModelGroup(name) {
        const cleaned = String(name || '').trim();
        if (!cleaned) return 'سایر';

        const byDash = cleaned.split(' - ');
        if (byDash.length > 1) return byDash[0].trim() || 'سایر';

        const m = cleaned.match(/^(.*?)\s+طرح\s+\d+/u);
        if (m && m[1]) return m[1].trim();

        return 'عمومی';
    }

    function groupedVariants(productId) {
        const product = products.find((p) => String(p.id) === String(productId));
        if (!product) return [];

        const map = new Map();
        product.variants.forEach((v) => {
            const key = extractModelGroup(v.name);
            if (!map.has(key)) map.set(key, []);
            map.get(key).push(v);
        });

        return Array.from(map.entries()).map(([label, variants]) => ({ label, variants }));
    }

    function findVariant(productId, variantId) {
        const product = products.find((p) => String(p.id) === String(productId));
        if (!product) return null;
        return product.variants.find((v) => String(v.id) === String(variantId)) || null;
    }

    function productGroupTemplate(groupId, data = {}) {
        const categoryId = data.category_id || '';
        const productId = data.product_id || '';

        return `
        <div class="purchase-product-group" data-group-id="${groupId}">
            <div class="group-head">
                <div class="group-title">
                    <span class="group-index-badge">1</span>
                    <span class="group-title-text">محصول 1</span>
                </div>
                <div class="group-actions d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-product-group">حذف محصول</button>
                </div>
            </div>

            <div class="row g-2 mb-2 group-product-fields">
                <div class="col-md-4">
                    <div class="label">۱) دسته‌بندی</div>
                    <select class="form-select form-select-sm group-category-select" required>
                        ${categoryOptions(categoryId)}
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="label">۲) کالا (از همان دسته‌بندی)</div>
                    <select class="form-select form-select-sm group-product-select" required>
                        ${productOptionsByCategory(categoryId, productId)}
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="label">کد محصول</div>
                    <input class="form-control form-control-sm group-product-code" value="${data.code ?? ''}" required readonly>
                </div>
            </div>

            <div class="border rounded p-2 mb-2 bg-light-subtle variant-picker" data-variant-picker>
                <div class="small fw-semibold mb-2">۳) انتخاب مدل لیست و طرح (ردیفی)</div>
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <div class="label">مدل لیست</div>
                        <select class="form-select form-select-sm model-select" data-model-select>
                            <option value="">ابتدا دسته‌بندی و کالا را انتخاب کنید</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <div class="label">طرح‌بندی</div>
                        <select class="form-select form-select-sm design-select" data-design-select disabled>
                            <option value="">ابتدا مدل لیست را انتخاب کنید</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="button" class="btn btn-sm btn-outline-primary add-selected-variant-btn" data-add-selected-variant disabled>
                            افزودن
                        </button>
                    </div>
                </div>
                <div class="small text-muted mt-2" data-variant-picker-help>ابتدا دسته‌بندی و کالا را انتخاب کنید.</div>
            </div>

            <div class="group-models" data-models></div>
        </div>`;
    }

    function modelRowTemplate(item = {}) {
        const rowDiscountType = item.discount_type || '';
        const rowDiscountValue = item.discount_value || 0;

        return `
        <div class="purchase-model-row" data-row>
            <div class="row g-2 align-items-end">
                <input type="hidden" class="product-id-input" name="items[][product_id]" value="${item.product_id || ''}">
                <input type="hidden" class="category-id-input" name="items[][category_id]" value="${item.category_id || ''}">
                <input type="hidden" class="product-name-input" name="items[][name]" value="${item.name || ''}">
                <input type="hidden" class="product-code-input" name="items[][code]" value="${item.code || ''}">

                <div class="col-md-3">
                    <div class="label">مدل / طرح</div>
                    <input type="hidden" class="variant-id" name="items[][variant_id]" value="${item.variant_id || ''}">
                    <input type="text" class="form-control form-control-sm variant-name-display" value="${item.variant_name || ''}" readonly>
                    <div class="small text-muted mt-1 variant-code-tooltip" title="کد ۱۱ رقمی تنوع">کد ۱۱ رقمی: ${item.variant_code || '—'}</div>
                </div>
                <div class="col-md-1">
                    <div class="label">تعداد</div>
                    <input type="number" min="1" class="form-control form-control-sm qty" name="items[][quantity]" value="${item.quantity ?? 1}" required>
                </div>
                <div class="col-md-2">
                    <div class="label">قیمت خرید</div>
                    <input type="text" inputmode="numeric" class="form-control form-control-sm buy formatted-number" name="items[][buy_price]" value="${item.buy_price ?? 0}" required>
                </div>
                <div class="col-md-2">
                    <div class="label">قیمت فروش</div>
                    <input type="text" inputmode="numeric" class="form-control form-control-sm sell formatted-number" name="items[][sell_price]" value="${item.sell_price ?? 0}" required>
                </div>
                <div class="col-md-1">
                    <div class="label">نوع تخفیف</div>
                    <select class="form-select form-select-sm row-discount-type" name="items[][discount_type]">
                        <option value="">—</option>
                        <option value="amount" ${rowDiscountType==='amount'?'selected':''}>مبلغی</option>
                        <option value="percent" ${rowDiscountType==='percent'?'selected':''}>درصدی</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <div class="label">مقدار تخفیف</div>
                    <input type="text" inputmode="numeric" class="form-control form-control-sm discount-value formatted-number" name="items[][discount_value]" value="${rowDiscountValue}">
                </div>
                <div class="col-md-1">
                    <div class="label">جمع نهایی</div>
                    <div class="line-total">0</div>
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-row">حذف</button>
                </div>
            </div>
        </div>`;
    }

    function setName(el, newName) {
        if (el && el.getAttribute('name')) el.setAttribute('name', newName);
    }

    function setRowVariant(row, productId, variantId, explicitName = '') {
        const variant = findVariant(productId, variantId);
        row.querySelector('.variant-id').value = variantId || '';
        row.querySelector('.variant-name-display').value = explicitName || variant?.name || '';
        const codeEl = row.querySelector('.variant-code-tooltip');
        if (codeEl) {
            const code = variant?.code || '';
            codeEl.textContent = `کد ۱۱ رقمی: ${code || '—'}`;
            codeEl.setAttribute('title', code ? `کد ۱۱ رقمی: ${code}` : 'کد ۱۱ رقمی ثبت نشده');
        }

        if (variant) {
            row.querySelector('.buy').value = formatNumericInput(variant.buy_price || 0);
            row.querySelector('.sell').value = formatNumericInput(variant.sell_price || 0);
        }
    }

    function syncGroupFieldsToRows(groupEl) {
        const productId = groupEl.querySelector('.group-product-select')?.value || '';
        const categoryId = groupEl.querySelector('.group-category-select')?.value || '';
        const product = products.find((p) => String(p.id) === String(productId));
        const name = product?.name || '';
        const code = product?.code || '';

        const codeEl = groupEl.querySelector('.group-product-code');
        if (codeEl) codeEl.value = code;

        groupEl.querySelectorAll('[data-row]').forEach((row) => {
            row.querySelector('.product-id-input').value = productId;
            row.querySelector('.category-id-input').value = categoryId;
            row.querySelector('.product-name-input').value = name;
            row.querySelector('.product-code-input').value = code;

            const variantId = row.querySelector('.variant-id').value || '';
            if (variantId) {
                const found = findVariant(productId, variantId);
                if (!found) {
                    row.querySelector('.variant-id').value = '';
                    row.querySelector('.variant-name-display').value = '';
                }
            }
        });
    }

    function designLabelForVariant(modelLabel, variantName) {
        const full = String(variantName || '').trim();
        const model = String(modelLabel || '').trim();
        if (!full) return 'نامشخص';
        if (!model) return full;

        if (full === model) return '— بدون طرح —';

        const escaped = model.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const matcher = new RegExp(`^${escaped}\\s*[-–—]?\\s*`, 'u');
        const trimmed = full.replace(matcher, '').trim();

        return trimmed || full;
    }

    function modelOptionsHtml(groups, selected = '') {
        if (!groups.length) return '<option value="">برای این کالا مدل/طرحی ثبت نشده است</option>';

        return `<option value="">انتخاب مدل لیست</option>${groups.map((g) =>
            `<option value="${g.label}" ${g.label===selected?'selected':''}>${g.label}</option>`
        ).join('')}`;
    }

    function designOptionsHtml(variants, modelLabel, selected = '') {
        if (!variants.length) return '<option value="">طرحی برای این مدل ثبت نشده است</option>';

        return `<option value="">انتخاب طرح‌بندی</option>${variants.map((v) => {
            const title = designLabelForVariant(modelLabel, v.name);
            return `<option value="${v.id}" ${String(v.id)===String(selected)?'selected':''}>${title}</option>`;
        }).join('')}`;
    }

    function renderVariantPicker(groupEl, selectedModel = '', selectedVariantId = '') {
        const productId = groupEl.querySelector('.group-product-select')?.value || '';
        const modelSelect = groupEl.querySelector('[data-model-select]');
        const designSelect = groupEl.querySelector('[data-design-select]');
        const helpEl = groupEl.querySelector('[data-variant-picker-help]');
        const addBtnEl = groupEl.querySelector('[data-add-selected-variant]');

        if (!productId) {
            modelSelect.innerHTML = '<option value="">ابتدا دسته‌بندی و کالا را انتخاب کنید</option>';
            designSelect.innerHTML = '<option value="">ابتدا مدل لیست را انتخاب کنید</option>';
            designSelect.disabled = true;
            addBtnEl.disabled = true;
            helpEl.textContent = 'ابتدا دسته‌بندی و کالا را انتخاب کنید.';
            return;
        }

        const groups = groupedVariants(productId);
        if (groups.length === 0) {
            modelSelect.innerHTML = '<option value="">برای این کالا مدل/طرحی ثبت نشده است</option>';
            designSelect.innerHTML = '<option value="">طرحی برای انتخاب وجود ندارد</option>';
            designSelect.disabled = true;
            addBtnEl.disabled = true;
            helpEl.textContent = 'برای این کالا مدل/طرحی ثبت نشده است.';
            return;
        }

        const currentModel = groups.find((g) => g.label === selectedModel)?.label || selectedModel;
        modelSelect.innerHTML = modelOptionsHtml(groups, currentModel);

        const targetGroup = groups.find((g) => g.label === currentModel);
        if (!targetGroup) {
            designSelect.innerHTML = '<option value="">ابتدا مدل لیست را انتخاب کنید</option>';
            designSelect.disabled = true;
            addBtnEl.disabled = true;
            helpEl.textContent = 'مدل لیست را انتخاب کنید تا طرح‌بندی‌های همان مدل نمایش داده شود.';
            return;
        }

        designSelect.innerHTML = designOptionsHtml(targetGroup.variants, targetGroup.label, selectedVariantId);
        designSelect.disabled = false;
        const hasSelectedVariant = !!(designSelect.value || selectedVariantId);
        addBtnEl.disabled = !hasSelectedVariant;
        helpEl.textContent = 'طرح‌بندی موردنظر را انتخاب کنید و روی «افزودن» بزنید تا ردیف خرید اضافه شود.';
    }

    function formatPriceInputs(scope = document) {
        scope.querySelectorAll('.formatted-number').forEach((el) => {
            el.value = formatNumericInput(el.value);
        });
    }

    function reindexGroups() {
        const groups = Array.from(itemsList.querySelectorAll('[data-group-id]'));
        groups.forEach((groupEl, idx) => {
            const humanIndex = (idx + 1).toLocaleString('fa-IR');
            const badge = groupEl.querySelector('.group-index-badge');
            const title = groupEl.querySelector('.group-title-text');
            if (badge) badge.textContent = humanIndex;
            if (title) title.textContent = `محصول ${humanIndex}`;
        });
    }

    function reindexRows() {
        const rows = Array.from(itemsList.querySelectorAll('[data-row]'));
        reindexGroups();

        rows.forEach((row, idx) => {
            setName(row.querySelector('.product-id-input'), `items[${idx}][product_id]`);
            setName(row.querySelector('.category-id-input'), `items[${idx}][category_id]`);
            setName(row.querySelector('.product-name-input'), `items[${idx}][name]`);
            setName(row.querySelector('.product-code-input'), `items[${idx}][code]`);
            setName(row.querySelector('.variant-id'), `items[${idx}][variant_id]`);
            setName(row.querySelector('.qty'), `items[${idx}][quantity]`);
            setName(row.querySelector('.buy'), `items[${idx}][buy_price]`);
            setName(row.querySelector('.sell'), `items[${idx}][sell_price]`);
            setName(row.querySelector('.row-discount-type'), `items[${idx}][discount_type]`);
            setName(row.querySelector('.discount-value'), `items[${idx}][discount_value]`);
        });
    }

    function recalc() {
        let subtotal = 0;
        let itemDiscountTotal = 0;

        itemsList.querySelectorAll('[data-row]').forEach((row) => {
            const qty = Number(row.querySelector('.qty')?.value || 0);
            const buy = parseNumericInput(row.querySelector('.buy')?.value || 0);
            const lineSubtotal = qty * buy;

            const discountType = row.querySelector('.row-discount-type')?.value || '';
            const discountValue = parseNumericInput(row.querySelector('.discount-value')?.value || 0);
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
            parseNumericInput(invoiceDiscountValueEl.value || 0)
        );

        const totalDiscount = itemDiscountTotal + invoiceDiscount;
        const total = Math.max(0, subtotal - totalDiscount);

        subtotalEl.textContent = subtotal.toLocaleString('fa-IR');
        totalDiscountEl.textContent = totalDiscount.toLocaleString('fa-IR');
        totalEl.textContent = total.toLocaleString('fa-IR');
    }

    function addProductGroup(data = {}, withInitialRows = true) {
        const groupId = `group-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
        itemsList.insertAdjacentHTML('beforeend', productGroupTemplate(groupId, data));
        const groupEl = itemsList.querySelector(`[data-group-id="${groupId}"]`);

        if (withInitialRows && data.variant_id) {
            addModelRow(groupEl, data);
        }

        syncGroupFieldsToRows(groupEl);
        renderVariantPicker(groupEl);
        reindexRows();
        recalc();
        return groupEl;
    }

    function addModelRow(groupEl, item = {}) {
        if (!groupEl) return;

        const productId = groupEl.querySelector('.group-product-select')?.value || item.product_id || '';
        const categoryId = groupEl.querySelector('.group-category-select')?.value || item.category_id || '';
        if (!productId || !item.variant_id) return;

        const product = products.find((p) => String(p.id) === String(productId));
        const modelsWrap = groupEl.querySelector('[data-models]');

        const rowData = {
            ...item,
            product_id: productId,
            category_id: categoryId,
            name: product?.name || item.name || '',
            code: product?.code || item.code || '',
        };

        modelsWrap.insertAdjacentHTML('beforeend', modelRowTemplate(rowData));
        const row = modelsWrap.querySelector('[data-row]:last-child');

        setRowVariant(row, productId, item.variant_id, item.variant_name || '');
        const codeEl = row.querySelector('.variant-code-tooltip');
        if (codeEl && item.variant_code) {
            codeEl.textContent = `کد ۱۱ رقمی: ${item.variant_code}`;
            codeEl.setAttribute('title', `کد ۱۱ رقمی: ${item.variant_code}`);
        }

        if (item.buy_price !== undefined && item.buy_price !== null) {
            row.querySelector('.buy').value = formatNumericInput(item.buy_price);
        }
        if (item.sell_price !== undefined && item.sell_price !== null) {
            row.querySelector('.sell').value = formatNumericInput(item.sell_price);
        }

        formatPriceInputs(row);
        syncGroupFieldsToRows(groupEl);
        reindexRows();
        recalc();
    }

    function buildGroupsFromInitialItems(items) {
        const groupsMap = new Map();

        items.forEach((item) => {
            const key = [item.product_id || '', item.category_id || ''].join('||');
            if (!groupsMap.has(key)) {
                groupsMap.set(key, {
                    product_id: item.product_id || '',
                    category_id: item.category_id || '',
                    name: item.name || '',
                    code: item.code || '',
                    rows: [],
                });
            }
            groupsMap.get(key).rows.push(item);
        });

        groupsMap.forEach((group) => {
            const groupEl = addProductGroup(group, false);
            group.rows.forEach((rowItem) => {
                addModelRow(groupEl, rowItem);
            });
        });
    }

    addBtn.addEventListener('click', () => addProductGroup({}, false));

    itemsList.addEventListener('change', (e) => {
        const groupEl = e.target.closest('[data-group-id]');
        if (!groupEl) return;

        if (e.target.classList.contains('group-category-select')) {
            const categoryId = e.target.value || '';
            const productSelect = groupEl.querySelector('.group-product-select');
            productSelect.innerHTML = productOptionsByCategory(categoryId, '');
            groupEl.querySelector('.group-product-code').value = '';
            groupEl.querySelector('[data-models]').innerHTML = '';
            syncGroupFieldsToRows(groupEl);
            renderVariantPicker(groupEl);
            reindexRows();
            recalc();
            return;
        }

        if (e.target.classList.contains('group-product-select')) {
            groupEl.querySelector('[data-models]').innerHTML = '';
            syncGroupFieldsToRows(groupEl);
            renderVariantPicker(groupEl);
            reindexRows();
            recalc();
            return;
        }

        if (e.target.classList.contains('model-select')) {
            renderVariantPicker(groupEl, e.target.value || '', '');
            return;
        }

        if (e.target.classList.contains('design-select')) {
            const addSelectedBtn = groupEl.querySelector('[data-add-selected-variant]');
            if (addSelectedBtn) addSelectedBtn.disabled = !e.target.value;
            return;
        }

        if (e.target.classList.contains('row-discount-type')) {
            recalc();
        }
    });

    itemsList.addEventListener('input', (e) => {
        if (
            e.target.classList.contains('qty') ||
            e.target.classList.contains('buy') ||
            e.target.classList.contains('sell') ||
            e.target.classList.contains('discount-value')
        ) {
            if (e.target.classList.contains('formatted-number')) {
                const atEnd = e.target.selectionStart === e.target.value.length;
                e.target.value = formatNumericInput(e.target.value);
                if (atEnd) e.target.setSelectionRange(e.target.value.length, e.target.value.length);
            }
            recalc();
        }
    });

    itemsList.addEventListener('click', (e) => {
        const groupEl = e.target.closest('[data-group-id]');

        if (e.target.classList.contains('add-selected-variant-btn')) {
            if (!groupEl) return;

            const modelSelect = groupEl.querySelector('[data-model-select]');
            const designSelect = groupEl.querySelector('[data-design-select]');
            const selectedModel = modelSelect?.value || '';
            const variantId = designSelect?.value || '';
            const productId = groupEl.querySelector('.group-product-select')?.value || '';
            if (!selectedModel || !variantId || !productId) return;

            const selectedGroup = groupedVariants(productId).find((g) => g.label === selectedModel);
            const variant = selectedGroup?.variants.find((v) => String(v.id) === String(variantId));
            if (!variant) return;

            addModelRow(groupEl, {
                variant_id: variant.id,
                variant_name: variant.name,
                variant_code: variant.code || '',
                quantity: 1,
                buy_price: parseNumericInput(variant.buy_price || 0),
                sell_price: parseNumericInput(variant.sell_price || 0),
                discount_type: '',
                discount_value: 0,
            });

            renderVariantPicker(groupEl, selectedModel, '');
            return;
        }

        if (e.target.classList.contains('remove-product-group')) {
            if (!groupEl) return;
            groupEl.remove();
            reindexRows();
            recalc();
            return;
        }

        if (e.target.classList.contains('remove-row')) {
            if (!groupEl) return;
            const row = e.target.closest('[data-row]');
            row.remove();

            reindexRows();
            recalc();
        }
    });

    invoiceDiscountTypeEl.addEventListener('change', recalc);
    invoiceDiscountValueEl.addEventListener('input', recalc);

    purchaseForm.addEventListener('submit', () => {
        purchaseForm.querySelectorAll('.formatted-number').forEach((el) => {
            el.value = parseNumericInput(el.value);
        });
    });

    if (Array.isArray(initialItems) && initialItems.length > 0) {
        buildGroupsFromInitialItems(initialItems);
    } else {
        addProductGroup({}, false);
    }

    formatPriceInputs(document);
})();
</script>
@endsection
