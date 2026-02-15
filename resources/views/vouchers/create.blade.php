@extends('layouts.app')

@php
    $isEdit = isset($voucher) && $voucher;
    $formAction = $isEdit ? route('vouchers.update', $voucher) : route('vouchers.store');
    $initialItems = old('items', $isEdit
        ? $voucher->items->map(fn($it) => [
            'product_id' => $it->product_id,
            'quantity' => $it->quantity,
            'unit_price' => $it->unit_price,
            'personnel_asset_code' => $it->personnel_asset_code,
        ])->values()->all()
        : []);
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="page-title mb-0">{{ $isEdit ? 'ویرایش حواله انبار' : 'ثبت حواله انبار' }}</h4>
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

                <div class="col-md-4">
                    <label class="form-label">شماره حواله (اختیاری)</label>
                    <input name="reference" class="form-control" value="{{ old('reference', $voucher->reference ?? null) }}" placeholder="مثلاً 123">
                </div>

                <div class="col-md-4">
                    <label class="form-label">توضیحات (اختیاری)</label>
                    <input name="note" class="form-control" value="{{ old('note', $voucher->note ?? null) }}">
                </div>

                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table align-middle" id="itemsTable">
                            <thead>
                                <tr>
                                    <th>محصول</th>
                                    <th>تعداد</th>
                                    <th>مبلغ واحد</th>
                                    <th class="asset-col" style="display:none;">کد ۴ رقمی اموال</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="addItemBtn">+ افزودن ردیف</button>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">{{ $isEdit ? 'ذخیره تغییرات' : 'ثبت حواله انبار' }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    const products = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku])->values());
    const initialItems = @json($initialItems);
    const tbody = document.querySelector('#itemsTable tbody');
    const addBtn = document.getElementById('addItemBtn');
    const toWarehouse = document.getElementById('toWarehouse');

    function rowTemplate(i, item = null){
        return `<tr>
            <td><select name="items[${i}][product_id]" class="form-select" required>
                <option value="">انتخاب...</option>
                ${products.map(p => `<option value="${p.id}" ${String(item?.product_id || '') === String(p.id) ? 'selected' : ''}>${p.name} (${p.sku ?? ''})</option>`).join('')}
            </select></td>
            <td><input name="items[${i}][quantity]" type="number" min="1" class="form-control" value="${item?.quantity || 1}" required></td>
            <td><input name="items[${i}][unit_price]" type="number" min="0" class="form-control" value="${item?.unit_price || 0}"></td>
            <td class="asset-col" style="display:none;"><input name="items[${i}][personnel_asset_code]" class="form-control" pattern="\\d{4}" maxlength="4" value="${item?.personnel_asset_code || ''}"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">حذف</button></td>
        </tr>`;
    }
    function addRow(item = null){
        tbody.insertAdjacentHTML('beforeend', rowTemplate(tbody.querySelectorAll('tr').length, item));
        toggleAsset();
    }
    function toggleAsset(){
        const selected = toWarehouse.selectedOptions[0];
        const isPersonnel = selected && selected.dataset.type === 'personnel';
        document.querySelectorAll('.asset-col').forEach(el => el.style.display = isPersonnel ? '' : 'none');
    }
    addBtn.addEventListener('click', () => addRow());
    toWarehouse.addEventListener('change', toggleAsset);
    if (initialItems.length) {
        initialItems.forEach(item => addRow(item));
    } else {
        addRow();
    }
</script>
@endsection
