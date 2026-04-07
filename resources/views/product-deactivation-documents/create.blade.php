@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">ثبت جدید غیرفعال‌سازی</h4>
    <a href="{{ route('product-deactivation-documents.index') }}" class="btn btn-outline-secondary">بازگشت به لیست</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('product-deactivation-documents.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-bold">دلیل غیرفعال‌سازی</label>
                <textarea name="reason_text" class="form-control @error('reason_text') is-invalid @enderror" rows="3" placeholder="دلیل را به صورت کامل بنویسید...">{{ old('reason_text') }}</textarea>
                @error('reason_text')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">آیتم‌های سند</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">+ افزودن ردیف</button>
            </div>

            <div class="table-responsive border rounded">
                <table class="table table-sm align-middle mb-0" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 160px;">دسته‌بندی</th>
                            <th style="min-width: 180px;">زیر دسته‌بندی</th>
                            <th style="min-width: 220px;">کالا</th>
                            <th style="min-width: 220px;">تنوع کالا</th>
                            <th class="text-center" style="width: 80px;">حذف</th>
                        </tr>
                    </thead>
                    <tbody id="itemsTbody"></tbody>
                </table>
            </div>
            @error('items')
                <div class="text-danger small mt-2">{{ $message }}</div>
            @enderror

            <div class="mt-3">
                <button class="btn btn-danger">ثبت سند</button>
            </div>
        </form>
    </div>
</div>

<template id="rowTemplate">
    <tr>
        <td>
            <select class="form-select form-select-sm js-category">
                <option value="">انتخاب دسته‌بندی</option>
            </select>
            <input type="hidden" class="js-category-input" name="">
        </td>
        <td>
            <select class="form-select form-select-sm js-subcategory">
                <option value="">انتخاب زیر دسته‌بندی</option>
            </select>
            <input type="hidden" class="js-subcategory-input" name="">
        </td>
        <td>
            <select class="form-select form-select-sm js-product">
                <option value="">انتخاب کالا</option>
            </select>
            <input type="hidden" class="js-product-input" name="">
        </td>
        <td>
            <select class="form-select form-select-sm js-variant">
                <option value="">بدون تنوع (غیرفعال‌سازی کل کالا)</option>
            </select>
            <input type="hidden" class="js-variant-input" name="">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger js-remove-row">×</button>
        </td>
    </tr>
</template>

@php
    $productPayload = $products->map(function ($p) {
        return [
            'id' => (int) $p->id,
            'name' => (string) $p->name,
            'category_id' => (int) ($p->category?->id ?? 0),
            'parent_category_id' => (int) ($p->category?->parent_id ?? 0),
            'variants' => $p->variants->map(fn ($v) => ['id' => (int) $v->id, 'name' => (string) $v->variant_name])->values()->all(),
        ];
    })->values();
@endphp

<script>
    const categories = @json($categories->map(fn($c) => ['id' => (int) $c->id, 'name' => (string) $c->name])->values());
    const subcategories = @json($subcategories->map(fn($c) => ['id' => (int) $c->id, 'name' => (string) $c->name, 'parent_id' => (int) $c->parent_id])->values());
    const products = @json($productPayload);
    const oldItems = @json(old('items', [['category_id' => '', 'subcategory_id' => '', 'product_id' => '', 'variant_id' => '']]));

    const tbody = document.getElementById('itemsTbody');
    const template = document.getElementById('rowTemplate');
    const addRowBtn = document.getElementById('addRowBtn');

    function toOptions(items, placeholder, valueKey = 'id', textKey = 'name') {
        return `<option value="">${placeholder}</option>` + items.map(item => {
            return `<option value="${item[valueKey]}">${item[textKey]}</option>`;
        }).join('');
    }

    function updateInputNames(row, index) {
        row.querySelector('.js-category-input').name = `items[${index}][category_id]`;
        row.querySelector('.js-subcategory-input').name = `items[${index}][subcategory_id]`;
        row.querySelector('.js-product-input').name = `items[${index}][product_id]`;
        row.querySelector('.js-variant-input').name = `items[${index}][variant_id]`;
    }

    function bindRow(row, initial = {}) {
        const categoryEl = row.querySelector('.js-category');
        const subcategoryEl = row.querySelector('.js-subcategory');
        const productEl = row.querySelector('.js-product');
        const variantEl = row.querySelector('.js-variant');

        const categoryInput = row.querySelector('.js-category-input');
        const subcategoryInput = row.querySelector('.js-subcategory-input');
        const productInput = row.querySelector('.js-product-input');
        const variantInput = row.querySelector('.js-variant-input');

        categoryEl.innerHTML = toOptions(categories, 'انتخاب دسته‌بندی');

        function syncHidden() {
            categoryInput.value = categoryEl.value || '';
            subcategoryInput.value = subcategoryEl.value || '';
            productInput.value = productEl.value || '';
            variantInput.value = variantEl.value || '';
        }

        function renderSubcategories() {
            const categoryId = Number(categoryEl.value || 0);
            const items = subcategories.filter(s => Number(s.parent_id) === categoryId);
            subcategoryEl.innerHTML = toOptions(items, 'انتخاب زیر دسته‌بندی');
            if (!items.length) {
                subcategoryEl.value = '';
            }
            renderProducts();
        }

        function renderProducts() {
            const categoryId = Number(categoryEl.value || 0);
            const subcategoryId = Number(subcategoryEl.value || 0);

            const filteredProducts = products.filter(p => {
                if (!categoryId) return true;
                if (!subcategoryId) return Number(p.parent_category_id) === categoryId || Number(p.category_id) === categoryId;
                return Number(p.category_id) === subcategoryId;
            });

            productEl.innerHTML = toOptions(filteredProducts, 'انتخاب کالا');
            renderVariants();
        }

        function renderVariants() {
            const productId = Number(productEl.value || 0);
            const product = products.find(p => Number(p.id) === productId);
            const variantOptions = (product?.variants || []);
            variantEl.innerHTML = `<option value="">بدون تنوع (غیرفعال‌سازی کل کالا)</option>` +
                variantOptions.map(v => `<option value="${v.id}">${v.name}</option>`).join('');
            variantEl.disabled = variantOptions.length === 0;
            syncHidden();
        }

        categoryEl.addEventListener('change', () => {
            renderSubcategories();
            syncHidden();
        });
        subcategoryEl.addEventListener('change', () => {
            renderProducts();
            syncHidden();
        });
        productEl.addEventListener('change', () => {
            renderVariants();
            syncHidden();
        });
        variantEl.addEventListener('change', syncHidden);

        row.querySelector('.js-remove-row').addEventListener('click', () => {
            if (tbody.querySelectorAll('tr').length <= 1) return;
            row.remove();
            reindexRows();
        });

        if (initial.category_id) categoryEl.value = String(initial.category_id);
        renderSubcategories();
        if (initial.subcategory_id) subcategoryEl.value = String(initial.subcategory_id);
        renderProducts();
        if (initial.product_id) productEl.value = String(initial.product_id);
        renderVariants();
        if (initial.variant_id) variantEl.value = String(initial.variant_id);
        syncHidden();
    }

    function reindexRows() {
        tbody.querySelectorAll('tr').forEach((row, index) => updateInputNames(row, index));
    }

    function addRow(initial = {}) {
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('tr');
        tbody.appendChild(row);
        bindRow(row, initial);
        reindexRows();
    }

    oldItems.forEach(item => addRow(item || {}));

    addRowBtn.addEventListener('click', () => addRow({}));
</script>
@endsection
