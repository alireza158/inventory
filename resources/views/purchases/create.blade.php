@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="page-title mb-0">ثبت خرید جدید</h4>
    <a class="btn btn-outline-secondary" href="{{ route('purchases.index') }}">بازگشت</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('purchases.store') }}" id="purchaseForm">
            @csrf

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">تامین‌کننده</label>
                    <div class="d-flex gap-2">
                        <select class="form-select" name="supplier_id" required>
                            <option value="">انتخاب کنید...</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id')==$supplier->id)>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-dark">مدیریت تامین‌کننده‌ها</a>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">توضیحات (اختیاری)</label>
                    <input class="form-control" name="note" value="{{ old('note') }}">
                </div>
            </div>

            <div class="alert alert-info py-2 small">
                در هر ردیف می‌توانید ابتدا از کالاهای موجود انتخاب کنید یا نام/کد محصول جدید وارد کنید؛ سپس مدل (Variant) همان کالا را ثبت کنید.
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="itemsTable">
                    <thead>
                        <tr>
                            <th style="min-width:180px">انتخاب کالا (اختیاری)</th>
                            <th style="min-width:180px">اسم محصول</th>
                            <th style="min-width:140px">کد محصول</th>
                            <th style="min-width:180px">مدل محصول</th>
                            <th>تعداد</th>
                            <th>قیمت خرید</th>
                            <th>قیمت فروش</th>
                            <th>جمع</th>
                            <th>حذف</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <button type="button" class="btn btn-outline-primary" id="addRowBtn">+ افزودن ردیف مدل</button>

            <div class="d-flex justify-content-end mt-3">
                <div class="fw-bold fs-5">قیمت کل خرید: <span id="totalAmount">0</span> ریال</div>
            </div>

            <div class="mt-4">
                <button class="btn btn-primary">ثبت نهایی خرید</button>
            </div>
        </form>
    </div>
</div>

@php
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

<script>
(function () {
    const products = @json($productsPayload);

    const tbody = document.querySelector('#itemsTable tbody');
    const addBtn = document.getElementById('addRowBtn');
    const totalEl = document.getElementById('totalAmount');

    function productOptions() {
        return `<option value="">کالای جدید/بدون انتخاب</option>${products.map((p) =>
            `<option value="${p.id}">${p.name} (${p.code || '-'})</option>`
        ).join('')}`;
    }

    function rowTemplate(index) {
        return `
        <tr>
            <td>
                <select class="form-select product-select" name="items[${index}][product_id]">
                    ${productOptions()}
                </select>
            </td>
            <td><input class="form-control product-name" name="items[${index}][name]" required></td>
            <td><input class="form-control product-code" name="items[${index}][code]" required></td>
            <td>
                <input type="hidden" class="variant-id" name="items[${index}][variant_id]">
                <div class="d-flex gap-2">
                    <select class="form-select variant-select" style="max-width: 170px;"></select>
                    <input class="form-control variant-name" name="items[${index}][variant_name]" placeholder="نام مدل" required>
                </div>
            </td>
            <td><input type="number" min="1" class="form-control qty" name="items[${index}][quantity]" value="1" required></td>
            <td><input type="number" min="0" class="form-control buy" name="items[${index}][buy_price]" value="0" required></td>
            <td><input type="number" min="0" class="form-control sell" name="items[${index}][sell_price]" value="0" required></td>
            <td class="line-total">0</td>
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

    function fillVariants(tr, productId) {
        const variantSelect = tr.querySelector('.variant-select');
        variantSelect.innerHTML = '<option value="">مدل جدید</option>';
        tr.querySelector('.variant-id').value = '';

        if (!productId) return;

        const product = products.find((p) => String(p.id) === String(productId));
        if (!product) return;

        product.variants.forEach((v) => {
            variantSelect.insertAdjacentHTML('beforeend', `<option value="${v.id}" data-name="${v.name}" data-buy="${v.buy_price}" data-sell="${v.sell_price}">${v.name}</option>`);
        });
    }

    function addRow() {
        const index = tbody.querySelectorAll('tr').length;
        tbody.insertAdjacentHTML('beforeend', rowTemplate(index));
    }

    addBtn.addEventListener('click', addRow);

    tbody.addEventListener('change', (e) => {
        const tr = e.target.closest('tr');
        if (!tr) return;

        if (e.target.classList.contains('product-select')) {
            const productId = e.target.value;
            const product = products.find((p) => String(p.id) === String(productId));

            if (product) {
                tr.querySelector('.product-name').value = product.name || '';
                tr.querySelector('.product-code').value = product.code || '';
            }

            fillVariants(tr, productId);
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
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('tr').remove();
            recalc();
        }
    });

    addRow();
})();
</script>
@endsection
