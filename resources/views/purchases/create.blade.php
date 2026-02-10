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
    .purchase-form-compact .form-control,
    .purchase-form-compact .form-select {
        height: 34px;
        padding: .25rem .5rem;
        font-size: .9rem;
    }

    .purchase-form-compact .table > :not(caption) > * > * {
        padding: .35rem .4rem;
        vertical-align: middle;
        font-size: .88rem;
    }

    .purchase-form-compact .btn {
        padding: .28rem .6rem;
        font-size: .85rem;
    }

    .purchase-form-compact .qty,
    .purchase-form-compact .buy,
    .purchase-form-compact .sell {
        max-width: 80px;
        text-align: center;
    }

    .purchase-form-compact .line-total {
        min-width: 72px;
        font-weight: 600;
    }

    .purchase-form-compact input[type=number]::-webkit-outer-spin-button,
    .purchase-form-compact input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .purchase-form-compact input[type=number] {
        -moz-appearance: textfield;
        appearance: textfield;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="page-title mb-0">{{ $isEdit ? 'ویرایش سند خرید' : 'ثبت خرید جدید' }}</h4>
    <a class="btn btn-sm btn-outline-secondary" href="{{ route('purchases.index') }}">بازگشت</a>
</div>

<div class="card purchase-form-compact">
    <div class="card-body">
        <form method="POST" action="{{ $formAction }}" id="purchaseForm">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="row g-2 mb-2">
                <div class="col-md-6">
                    <label class="form-label">تامین‌کننده</label>
                    <div class="d-flex gap-1">
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

            <div class="alert alert-info py-2 small">
                برای یک محصول می‌توانید چند «مدل» مختلف ثبت کنید (مثلاً یک محصول: گارد یویک، و چند مدل زیرمجموعه). برای این کار همان محصول را انتخاب کنید و با دکمه «افزودن مدل برای همین محصول» مدل‌های بعدی را اضافه کنید.
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0" id="itemsTable">
                    <thead>
                        <tr>
                            <th style="min-width:180px">انتخاب کالا (اختیاری)</th>
                            <th style="min-width:180px">اسم محصول</th>
                            <th style="min-width:140px">کد محصول</th>
                            <th style="min-width:220px">مدل محصول</th>
                            <th>تعداد</th>
                            <th>قیمت خرید</th>
                            <th>قیمت فروش</th>
                            <th>جمع</th>
                            <th>مدل بیشتر</th>
                            <th>حذف</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">+ افزودن ردیف جدید</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="addVariantForSameProductBtn">+ افزودن مدل برای همین محصول</button>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <div class="fw-bold fs-5">قیمت کل خرید: <span id="totalAmount">0</span> ریال</div>
            </div>

            <div class="mt-4">
                <button class="btn btn-sm btn-primary">{{ $isEdit ? 'ذخیره تغییرات سند خرید' : 'ثبت نهایی خرید' }}</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const products = @json($productsPayload);
    const initialItems = @json($initialItems);

    const tbody = document.querySelector('#itemsTable tbody');
    const addBtn = document.getElementById('addRowBtn');
    const addSameProductBtn = document.getElementById('addVariantForSameProductBtn');
    const totalEl = document.getElementById('totalAmount');

    function productOptions(selected = '') {
        return `<option value="">کالای جدید/بدون انتخاب</option>${products.map((p) =>
            `<option value="${p.id}" ${String(selected)===String(p.id)?'selected':''}>${p.name} (${p.code || '-'})</option>`
        ).join('')}`;
    }

    function variantOptions(productId, selected = '') {
        const product = products.find((p) => String(p.id) === String(productId));
        if (!product) {
            return '<option value="">مدل جدید</option>';
        }

        return `<option value="">مدل جدید</option>${product.variants.map((v) =>
            `<option value="${v.id}" data-name="${v.name}" data-buy="${v.buy_price}" data-sell="${v.sell_price}" ${String(selected)===String(v.id)?'selected':''}>${v.name}</option>`
        ).join('')}`;
    }

    function rowTemplate(index, item = {}) {
        const productId = item.product_id || '';
        const variantId = item.variant_id || '';

        return `
        <tr>
            <td>
                <select class="form-select form-select-sm product-select" name="items[${index}][product_id]">
                    ${productOptions(productId)}
                </select>
            </td>
            <td><input class="form-control form-control-sm product-name" name="items[${index}][name]" value="${item.name ?? ''}" required></td>
            <td><input class="form-control form-control-sm product-code" name="items[${index}][code]" value="${item.code ?? ''}" required></td>
            <td>
                <input type="hidden" class="variant-id" name="items[${index}][variant_id]" value="${variantId}">
                <div class="d-flex gap-1">
                    <select class="form-select form-select-sm variant-select" style="max-width: 170px;">
                        ${variantOptions(productId, variantId)}
                    </select>
                    <input class="form-control form-control-sm variant-name" name="items[${index}][variant_name]" value="${item.variant_name ?? ''}" placeholder="نام مدل" required>
                </div>
            </td>
            <td><input type="number" min="1" class="form-control form-control-sm qty" name="items[${index}][quantity]" value="${item.quantity ?? 1}" required></td>
            <td><input type="number" min="0" class="form-control form-control-sm buy" name="items[${index}][buy_price]" value="${item.buy_price ?? 0}" required></td>
            <td><input type="number" min="0" class="form-control form-control-sm sell" name="items[${index}][sell_price]" value="${item.sell_price ?? 0}" required></td>
            <td class="line-total">0</td>
            <td><button type="button" class="btn btn-sm btn-outline-secondary duplicate-variant-row">+ مدل</button></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">حذف</button></td>
        </tr>`;
    }

    function recalc() {
        let total = 0;
        tbody.querySelectorAll('tr').forEach((tr) => {
            const qty = Number(tr.querySelector('.qty')?.value || 0);
            const buy = Number(tr.querySelector('.buy')?.value || 0);
            const line = qty * buy;
            total += line;
            tr.querySelector('.line-total').textContent = line.toLocaleString('fa-IR');
        });
        totalEl.textContent = total.toLocaleString('fa-IR');
    }

    function addRow(item = {}) {
        const index = tbody.querySelectorAll('tr').length;
        tbody.insertAdjacentHTML('beforeend', rowTemplate(index, item));
        recalc();
    }

    function addVariantForSameProduct() {
        const rows = tbody.querySelectorAll('tr');
        if (rows.length === 0) {
            addRow();
            return;
        }

        const last = rows[rows.length - 1];
        const cloneItem = {
            product_id: last.querySelector('.product-select')?.value || '',
            name: last.querySelector('.product-name')?.value || '',
            code: last.querySelector('.product-code')?.value || '',
            variant_id: '',
            variant_name: '',
            quantity: 1,
            buy_price: 0,
            sell_price: 0,
        };

        addRow(cloneItem);
    }

    addBtn.addEventListener('click', () => addRow());
    addSameProductBtn.addEventListener('click', addVariantForSameProduct);

    tbody.addEventListener('change', (e) => {
        const tr = e.target.closest('tr');
        if (!tr) return;

        if (e.target.classList.contains('product-select')) {
            const productId = e.target.value;
            const product = products.find((p) => String(p.id) === String(productId));
            const variantSelect = tr.querySelector('.variant-select');

            variantSelect.innerHTML = variantOptions(productId);
            tr.querySelector('.variant-id').value = '';

            if (product) {
                tr.querySelector('.product-name').value = product.name || '';
                tr.querySelector('.product-code').value = product.code || '';
            }
        }

        if (e.target.classList.contains('variant-select')) {
            const opt = e.target.selectedOptions[0];
            const variantId = opt?.value || '';

            tr.querySelector('.variant-id').value = variantId;

            if (variantId) {
                tr.querySelector('.variant-name').value = opt.dataset.name || '';
                tr.querySelector('.buy').value = opt.dataset.buy || 0;
                tr.querySelector('.sell').value = opt.dataset.sell || 0;
                recalc();
            }
        }
    });

    tbody.addEventListener('input', (e) => {
        if (e.target.classList.contains('qty') || e.target.classList.contains('buy')) {
            recalc();
        }

        if (e.target.classList.contains('variant-name')) {
            const tr = e.target.closest('tr');
            tr.querySelector('.variant-id').value = '';
        }
    });

    tbody.addEventListener('click', (e) => {
        if (e.target.classList.contains('duplicate-variant-row')) {
            const tr = e.target.closest('tr');
            const cloneItem = {
                product_id: tr.querySelector('.product-select')?.value || '',
                name: tr.querySelector('.product-name')?.value || '',
                code: tr.querySelector('.product-code')?.value || '',
                variant_id: '',
                variant_name: '',
                quantity: 1,
                buy_price: 0,
                sell_price: 0,
            };

            addRow(cloneItem);
            return;
        }

        if (e.target.classList.contains('remove-row')) {
            e.target.closest('tr').remove();
            recalc();
        }
    });

    if (Array.isArray(initialItems) && initialItems.length > 0) {
        initialItems.forEach((item) => addRow(item));
    } else {
        addRow();
    }
})();
</script>
@endsection
