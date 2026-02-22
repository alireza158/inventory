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
                            <a href="{{ route('persons.index') }}" class="btn btn-sm btn-outline-dark">مدیریت اشخاص</a>
                        </div>
                    </div>
                    <div class="col-md-6">
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
            .replace(/[\u0660-\u0669]/g, d => String(d.charCodeAt(0) - 0x0660))
            .replace(/[\u06F0-\u06F9]/g, d => String(d.charCodeAt(0) - 0x06F0))
            .replace(/[^\d]/g, '');
        return Number(normalized || 0);
    }

    function formatNumericInput(value) {
        return parseNumericInput(value).toLocaleString('en-US');
    }


    function productOptions(selected = '') {
        return `<option value="">انتخاب کالا</option>${products.map((p) =>
            `<option value="${p.id}" ${String(selected)===String(p.id)?'selected':''}>${p.name} (${p.code || '-'})</option>`
        ).join('')}`;
    }

    function variantOptions(productId, selected = '') {
        const product = products.find((p) => String(p.id) === String(productId));
        if (!product) return '<option value="">ابتدا کالا را انتخاب کنید</option>';

        return `<option value="">انتخاب مدل</option>${product.variants.map((v) =>
            `<option value="${v.id}" data-name="${v.name}" data-buy="${v.buy_price}" data-sell="${v.sell_price}" ${String(selected)===String(v.id)?'selected':''}>${v.name}</option>`
        ).join('')}`;
    }

    function categoryOptions(selected = '') {
        return `<option value="">انتخاب دسته‌بندی</option>${categories.map((category) =>
            `<option value="${category.id}" ${String(selected)===String(category.id)?'selected':''}>${category.name}</option>`
        ).join('')}`;
    }



    function calculateDiscount(base, type, value) {
        const v = Number(value || 0);
        if (!type || v <= 0 || base <= 0) return 0;
        if (type === 'percent') return Math.floor(base * Math.min(v, 100) / 100);
        return Math.min(v, base);
    }

    function productGroupTemplate(groupId, data = {}) {
        const productId = data.product_id || '';
        const categoryId = data.category_id || '';
        return `
        <div class="purchase-product-group" data-group-id="${groupId}">
            <div class="group-head">
                <div class="group-title">
                    <span class="group-index-badge">1</span>
                    <span class="group-title-text">محصول 1</span>
                </div>
                <div class="group-actions d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary add-model-row">+ افزودن مدل لیست</button>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-product-group">حذف محصول</button>
                </div>
            </div>

            <div class="row g-2 mb-2 group-product-fields">
                <div class="col-md-4">
                    <div class="label">انتخاب کالا</div>
                    <select class="form-select form-select-sm group-product-select" required>
                        ${productOptions(productId)}
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="label">دسته‌بندی کالا</div>
                    <select class="form-select form-select-sm group-category-select" required disabled>
                        ${categoryOptions(categoryId)}
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="label">کد محصول</div>
                    <input class="form-control form-control-sm group-product-code" value="${data.code ?? ''}" required readonly>
                </div>
            </div>

            <div class="group-models" data-models></div>
        </div>`;
    }

    function modelRowTemplate(item = {}) {
        const productId = item.product_id || '';
        const variantId = item.variant_id || '';
        const rowDiscountType = item.discount_type || '';
        const rowDiscountValue = item.discount_value || 0;

        return `
        <div class="purchase-model-row" data-row>
            <div class="row g-2 align-items-end">
                <input type="hidden" class="product-id-input" name="items[][product_id]" value="${productId}">
                <input type="hidden" class="category-id-input" name="items[][category_id]" value="${item.category_id ?? ''}">
                <input type="hidden" class="product-name-input" name="items[][name]" value="${item.name ?? ''}">
                <input type="hidden" class="product-code-input" name="items[][code]" value="${item.code ?? ''}">

                <div class="col-md-3">
                    <div class="label">مدل لیست</div>
                    <input type="hidden" class="variant-id" name="items[][variant_id]" value="${variantId}">
                    <div>
                        <select class="form-select form-select-sm variant-select" required>
                            ${variantOptions(productId, variantId)}
                        </select>
                    </div>
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
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-row">حذف</button>
                </div>
            </div>
        </div>`;
    }

    function setName(el, newName) {
        if (el && el.getAttribute('name')) el.setAttribute('name', newName);
    }

    function syncGroupFieldsToRows(groupEl) {
        const productId = groupEl.querySelector('.group-product-select')?.value || '';
        const categoryId = groupEl.querySelector('.group-category-select')?.value || '';
        const name = products.find((p) => String(p.id) === String(productId))?.name || '';
        const code = groupEl.querySelector('.group-product-code')?.value || '';

        groupEl.querySelectorAll('[data-row]').forEach((row) => {
            row.querySelector('.product-id-input').value = productId;
            row.querySelector('.category-id-input').value = categoryId;
            row.querySelector('.product-name-input').value = name;
            row.querySelector('.product-code-input').value = code;

            const variantSelect = row.querySelector('.variant-select');
            const currentVariantId = row.querySelector('.variant-id').value || '';
            variantSelect.innerHTML = variantOptions(productId, currentVariantId);

        });
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

    function addProductGroup(data = {}, withInitialModel = true) {
        const groupId = `group-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
        itemsList.insertAdjacentHTML('beforeend', productGroupTemplate(groupId, data));
        const groupEl = itemsList.querySelector(`[data-group-id="${groupId}"]`);

        if (withInitialModel) {
            addModelRow(groupEl, data);
        }

        syncGroupFieldsToRows(groupEl);
        reindexRows();
        recalc();
        return groupEl;
    }

    function addModelRow(groupEl, item = {}) {
        if (!groupEl) return;
        const modelsWrap = groupEl.querySelector('[data-models]');
        modelsWrap.insertAdjacentHTML('beforeend', modelRowTemplate(item));
        formatPriceInputs(modelsWrap);
        syncGroupFieldsToRows(groupEl);
        reindexRows();
        recalc();
    }

    function buildGroupsFromInitialItems(items) {
        const groupsMap = new Map();

        items.forEach((item) => {
            const key = [item.product_id || '', item.name || '', item.code || ''].join('||');
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
            group.rows.forEach((rowItem) => addModelRow(groupEl, rowItem));
        });
    }

    addBtn.addEventListener('click', () => addProductGroup({}, true));

    itemsList.addEventListener('change', (e) => {
        const groupEl = e.target.closest('[data-group-id]');
        if (!groupEl) return;

        if (
            e.target.classList.contains('group-product-select') ||
            e.target.classList.contains('group-category-select') ||
            e.target.classList.contains('group-product-code')
        ) {
            if (e.target.classList.contains('group-product-select')) {
                const productId = e.target.value;
                const product = products.find((p) => String(p.id) === String(productId));
                if (product) {
                    groupEl.querySelector('.group-product-code').value = product.code || '';
                    const categorySelect = groupEl.querySelector('.group-category-select');
                    if (categorySelect && product.category_id) {
                        categorySelect.value = String(product.category_id);
                    }
                } else {
                    groupEl.querySelector('.group-product-code').value = '';
                    const categorySelect = groupEl.querySelector('.group-category-select');
                    if (categorySelect) {
                        categorySelect.value = '';
                    }
                }
            }

            syncGroupFieldsToRows(groupEl);
            reindexRows();
            recalc();
            return;
        }

        if (e.target.classList.contains('variant-select')) {
            const row = e.target.closest('[data-row]');
            const opt = e.target.selectedOptions[0];
            const variantId = opt?.value || '';
            row.querySelector('.variant-id').value = variantId;

            if (variantId) {
                row.querySelector('.buy').value = formatNumericInput(opt.dataset.buy || 0);
                row.querySelector('.sell').value = formatNumericInput(opt.dataset.sell || 0);
            }

            recalc();
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

        if (e.target.classList.contains('group-product-code')) {
            const groupEl = e.target.closest('[data-group-id]');
            if (groupEl) {
                syncGroupFieldsToRows(groupEl);
                reindexRows();
            }
        }
    });

    itemsList.addEventListener('click', (e) => {
        const groupEl = e.target.closest('[data-group-id]');
        if (!groupEl) return;

        if (e.target.classList.contains('add-model-row')) {
            const lastRow = groupEl.querySelector('[data-row]:last-child');
            const copied = lastRow ? {
                quantity: 1,
                buy_price: 0,
                sell_price: 0,
                discount_type: '',
                discount_value: 0,
            } : {};
            addModelRow(groupEl, copied);
            return;
        }

        if (e.target.classList.contains('remove-product-group')) {
            groupEl.remove();
            reindexRows();
            recalc();
            return;
        }

        if (e.target.classList.contains('remove-row')) {
            const row = e.target.closest('[data-row]');
            row.remove();

            if (!groupEl.querySelector('[data-row]')) {
                groupEl.remove();
            }

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
        addProductGroup({}, true);
    }

    formatPriceInputs(document);
})();
</script>
@endsection
