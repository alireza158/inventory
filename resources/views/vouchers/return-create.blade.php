@extends('layouts.app')

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">ثبت برگشت از فروش</h4>
        <a class="btn btn-outline-secondary" href="{{ route('vouchers.section.index', 'return-from-sale') }}">بازگشت</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('vouchers.section.store', 'return-from-sale') }}" id="returnForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">مشتری</label>
                        <select name="customer_id" id="customerSelect" class="form-select" required>
                            <option value="">انتخاب مشتری...</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: ('مشتری #' . $customer->id) }} | {{ $customer->mobile }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">فاکتور مشتری</label>
                        <select name="related_invoice_uuid" id="invoiceSelect" class="form-select" required>
                            <option value="">ابتدا مشتری را انتخاب کنید...</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">انبار مبدا برگشت</label>
                        <select name="to_warehouse_id" class="form-select" required>
                            <option value="">انتخاب انبار...</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">علت برگشت از فروش</label>
                        <select name="return_reason" class="form-select" required>
                            <option value="">انتخاب علت...</option>
                            @foreach($returnReasons as $reasonKey => $reasonTitle)
                                <option value="{{ $reasonKey }}" @selected(old('return_reason') === $reasonKey)>{{ $reasonTitle }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-striped" id="itemsTable">
                                <thead>
                                <tr>
                                    <th>محصول (جستجو با نام/کد)</th>
                                    <th>تنوع/طرح</th>
                                    <th>موجودی تنوع</th>
                                    <th>حداکثر مجاز مرجوعی</th>
                                    <th>تعداد برگشتی</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addItemBtn" disabled>+ افزودن کالا</button>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">شماره حواله (اختیاری)</label>
                        <input name="reference" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">توضیحات (اختیاری)</label>
                        <input name="note" class="form-control">
                    </div>

                    <div class="col-12">
                        <button class="btn btn-success">ثبت برگشت از فروش</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const customerSelect = document.getElementById('customerSelect');
const invoiceSelect = document.getElementById('invoiceSelect');
const addItemBtn = document.getElementById('addItemBtn');
const tbody = document.querySelector('#itemsTable tbody');
const returnForm = document.getElementById('returnForm');

let invoiceVariants = [];

function productOptions(selected='') {
    const uniq = new Map();
    invoiceVariants.forEach(v => {
        const k = String(v.product_id);
        if (!uniq.has(k)) uniq.set(k, v);
    });
    return '<option value="">انتخاب محصول...</option>' + Array.from(uniq.values()).map(p =>
        `<option value="${p.product_id}" ${String(selected)===String(p.product_id)?'selected':''}>${p.name} ${p.product_code ? '('+p.product_code+')' : ''}</option>`
    ).join('');
}

function variantOptions(productId, selected='') {
    if (!productId) return '<option value="">ابتدا محصول را انتخاب کنید...</option>';
    const rows = invoiceVariants.filter(v => String(v.product_id)===String(productId) && Number(v.remaining_qty||0) > 0);
    if (!rows.length) return '<option value="">تنوع مجازی باقی نمانده</option>';
    return '<option value="">انتخاب تنوع...</option>' + rows.map(v =>
        `<option value="${v.variant_id}" data-max="${v.remaining_qty}" data-stock="${v.variant_stock || 0}" ${String(selected)===String(v.variant_id)?'selected':''}>${v.variant_name || 'بدون نام'} ${v.variant_code ? '['+v.variant_code+']' : ''}</option>`
    ).join('');
}

function itemRow(i){
    return `<tr>
        <td><select name="items[${i}][product_id]" class="form-select product-select" required>${productOptions()}</select></td>
        <td>
          <select name="items[${i}][variant_id]" class="form-select variant-select" required>
            <option value="">ابتدا محصول را انتخاب کنید...</option>
          </select>
        </td>
        <td><span class="badge text-bg-light stock-badge">—</span></td>
        <td><span class="badge text-bg-light max-badge">—</span></td>
        <td><input type="number" min="1" name="items[${i}][quantity]" class="form-control qty" required></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">حذف</button></td>
      </tr>`;
}

function bindRow(tr){
    const productSelect = tr.querySelector('.product-select');
    const variantSelect = tr.querySelector('.variant-select');
    const qtyInput = tr.querySelector('.qty');
    const stockBadge = tr.querySelector('.stock-badge');
    const maxBadge = tr.querySelector('.max-badge');

    const syncMax = () => {
        const opt = variantSelect.selectedOptions[0];
        const max = Number(opt?.dataset.max || 0);
        const stock = Number(opt?.dataset.stock || 0);
        stockBadge.textContent = stock > 0 ? stock.toLocaleString('fa-IR') : '۰';
        if (max > 0) {
            qtyInput.max = String(max);
            if (Number(qtyInput.value || 0) > max) qtyInput.value = String(max);
            maxBadge.textContent = max.toLocaleString('fa-IR');
        } else {
            qtyInput.removeAttribute('max');
            maxBadge.textContent = '—';
        }
    };

    productSelect.addEventListener('change', () => {
        variantSelect.innerHTML = variantOptions(productSelect.value, '');
        qtyInput.value = '';
        syncMax();
    });
    variantSelect.addEventListener('change', syncMax);
    qtyInput.addEventListener('input', syncMax);
}

async function loadCustomerInvoices(customerId){
    invoiceSelect.innerHTML = '<option value="">در حال بارگذاری...</option>';
    const res = await fetch(`{{ url('/vouchers/return/customers') }}/${customerId}/invoices`);
    if (!res.ok) return;
    const invoices = await res.json();
    invoiceSelect.innerHTML = '<option value="">انتخاب فاکتور...</option>' + invoices.map(inv => `<option value="${inv.uuid}">${inv.uuid}</option>`).join('');
}

async function loadInvoiceProducts(uuid){
    const res = await fetch(`{{ url('/vouchers/invoice') }}/${uuid}/products`);
    if (!res.ok) return;
    const payload = await res.json();
    invoiceVariants = payload.products || [];
    addItemBtn.disabled = invoiceVariants.length === 0;
    tbody.innerHTML = '';
}

customerSelect.addEventListener('change', () => {
    tbody.innerHTML = '';
    addItemBtn.disabled = true;
    if (!customerSelect.value) {
        invoiceSelect.innerHTML = '<option value="">ابتدا مشتری را انتخاب کنید...</option>';
        return;
    }
    loadCustomerInvoices(customerSelect.value);
});

invoiceSelect.addEventListener('change', () => {
    tbody.innerHTML = '';
    if (!invoiceSelect.value) {
        addItemBtn.disabled = true;
        return;
    }
    loadInvoiceProducts(invoiceSelect.value);
});

addItemBtn.addEventListener('click', () => {
    tbody.insertAdjacentHTML('beforeend', itemRow(tbody.querySelectorAll('tr').length));
    bindRow(tbody.querySelector('tr:last-child'));
});

returnForm.addEventListener('submit', () => {
    const btn = returnForm.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'در حال ثبت...';
    }
});
</script>
@endsection
