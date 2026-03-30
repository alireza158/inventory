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

                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-striped" id="itemsTable">
                                <thead>
                                <tr>
                                    <th>کالا (از همان فاکتور)</th>
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

let invoiceProducts = [];

function itemRow(i){
    return `<tr>
        <td>
          <select name="items[${i}][product_id]" class="form-select" required>
            <option value="">انتخاب کالا...</option>
            ${invoiceProducts.map(p => `<option value="${p.product_id}">${p.name} (تعداد فاکتور: ${p.qty})</option>`).join('')}
          </select>
        </td>
        <td><input type="number" min="1" name="items[${i}][quantity]" class="form-control" required></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">حذف</button></td>
      </tr>`;
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
    invoiceProducts = payload.products || [];
    addItemBtn.disabled = invoiceProducts.length === 0;
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
});
</script>
@endsection
