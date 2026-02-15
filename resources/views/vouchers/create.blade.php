@extends('layouts.app')

@php
    $isEdit = isset($voucher) && $voucher;
    $formAction = $isEdit ? route('vouchers.update', $voucher) : route('vouchers.store');
    $initialItems = old('items', $isEdit
        ? $voucher->items->map(fn($it) => [
            'category_id' => $it->product?->category_id,
            'product_id' => $it->product_id,
            'quantity' => $it->quantity,
            'personnel_asset_code' => $it->personnel_asset_code,
        ])->values()->all()
        : []);
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="page-title mb-0">{{ $isEdit ? 'ویرایش سند حواله' : 'ثبت سند حواله' }}</h4>
    <a class="btn btn-outline-secondary" href="{{ route('vouchers.index') }}">بازگشت</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $formAction }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">انبار مبدا</label>
                    <select name="from_warehouse_id" class="form-select" required>
                        <option value="">انتخاب کنید...</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected(old('from_warehouse_id', $voucher->from_warehouse_id ?? null)==$warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">انبار مقصد</label>
                    <select name="to_warehouse_id" class="form-select" id="toWarehouse" required>
                        <option value="">انتخاب کنید...</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" data-type="{{ $warehouse->type }}" @selected(old('to_warehouse_id', $voucher->to_warehouse_id ?? null)==$warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">تاریخ حواله</label>
                    <input name="transferred_at" type="datetime-local" class="form-control" value="{{ old('transferred_at', optional($voucher->transferred_at ?? now())->format('Y-m-d\TH:i')) }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">شماره حواله (اختیاری)</label>
                    <input name="reference" class="form-control" value="{{ old('reference', $voucher->reference ?? null) }}" placeholder="مثلاً 123">
                </div>

                <div class="col-12">
                    <label class="form-label">توضیحات (اختیاری)</label>
                    <input name="note" class="form-control" value="{{ old('note', $voucher->note ?? null) }}">
                </div>

                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table align-middle" id="itemsTable">
                            <thead>
                                <tr>
                                    <th>دسته‌بندی</th>
                                    <th>کالا</th>
                                    <th>تعداد</th>
                                    <th class="asset-col" style="display:none;">کد اموال ۴ رقمی</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="addItemBtn">+ افزودن ردیف</button>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">{{ $isEdit ? 'ذخیره تغییرات سند' : 'ثبت سند حواله' }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    const categories = @json($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values());
    const products = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'category_id' => $p->category_id])->values());
    const initialItems = @json($initialItems);

    const tbody = document.querySelector('#itemsTable tbody');
    const addBtn = document.getElementById('addItemBtn');
    const toWarehouse = document.getElementById('toWarehouse');

    function productOptions(categoryId, selectedProductId) {
        const filtered = products.filter(p => String(p.category_id) === String(categoryId || ''));
        return [`<option value="">انتخاب...</option>`]
            .concat(filtered.map(p => `<option value="${p.id}" ${String(selectedProductId || '') === String(p.id) ? 'selected' : ''}>${p.name} (${p.sku ?? ''})</option>`))
            .join('');
    }

    function rowTemplate(i, item = null) {
        const selectedCategoryId = item?.category_id || '';
        const selectedProductId = item?.product_id || '';

        return `<tr>
            <td>
                <select name="items[${i}][category_id]" class="form-select category-select" data-row="${i}" required>
                    <option value="">انتخاب...</option>
                    ${categories.map(c => `<option value="${c.id}" ${String(selectedCategoryId) === String(c.id) ? 'selected' : ''}>${c.name}</option>`).join('')}
                </select>
            </td>
            <td>
                <select name="items[${i}][product_id]" class="form-select product-select" required>
                    ${productOptions(selectedCategoryId, selectedProductId)}
                </select>
            </td>
            <td><input name="items[${i}][quantity]" type="number" min="1" class="form-control" value="${item?.quantity || 1}" required></td>
            <td class="asset-col" style="display:none;"><input name="items[${i}][personnel_asset_code]" class="form-control" pattern="\\d{4}" maxlength="4" value="${item?.personnel_asset_code || ''}"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">حذف</button></td>
        </tr>`;
    }

    function bindCategoryEvents() {
        tbody.querySelectorAll('.category-select').forEach((select) => {
            if (select.dataset.bound === '1') return;
            select.dataset.bound = '1';
            select.addEventListener('change', (e) => {
                const tr = e.target.closest('tr');
                const productSelect = tr.querySelector('.product-select');
                productSelect.innerHTML = productOptions(e.target.value, null);
            });
        });
    }

    function addRow(item = null) {
        tbody.insertAdjacentHTML('beforeend', rowTemplate(tbody.querySelectorAll('tr').length, item));
        bindCategoryEvents();
        toggleAssetCode();
    }

    function toggleAssetCode() {
        const selected = toWarehouse.selectedOptions[0];
        const isPersonnelDestination = selected && selected.dataset.type === 'personnel';
        document.querySelectorAll('.asset-col').forEach(el => el.style.display = isPersonnelDestination ? '' : 'none');
    }

    addBtn.addEventListener('click', () => addRow());
    toWarehouse.addEventListener('change', toggleAssetCode);

    if (initialItems.length) {
        initialItems.forEach(item => addRow(item));
    } else {
        addRow();
    }
</script>
@endsection
