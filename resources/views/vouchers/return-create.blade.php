@extends('layouts.app')

@section('content')
<style>
    :root{
        --brd:#e8edf3;
        --muted:#6b7280;
        --soft:#f8fafc;
        --soft2:#f3f6fb;
        --blue:#2563eb;
        --ok:#16a34a;
        --danger:#dc2626;
        --warn:#f59e0b;
    }

    .card-soft{
        border:1px solid var(--brd);
        border-radius:18px;
        background:#fff;
        box-shadow:0 10px 28px rgba(15,23,42,.04);
    }

    .section-head{
        padding:12px 14px;
        border-bottom:1px solid var(--brd);
        background:linear-gradient(0deg,#fff,var(--soft2));
        border-top-left-radius:18px;
        border-top-right-radius:18px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        flex-wrap:wrap;
    }

    .section-title{
        font-weight:800;
        font-size:14px;
    }

    .muted{
        color:var(--muted);
        font-size:12px;
    }

    .hint-box{
        border:1px dashed #dbeafe;
        background:#f8fbff;
        border-radius:14px;
        padding:12px;
    }

    .chip{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:6px 10px;
        border-radius:999px;
        border:1px solid var(--brd);
        background:var(--soft);
        font-size:12px;
    }

    .info-card{
        border:1px solid var(--brd);
        border-radius:14px;
        background:#fff;
        padding:12px;
    }

    .invoice-card{
        border:1px solid var(--brd);
        border-radius:14px;
        background:#fff;
        padding:12px;
        cursor:pointer;
        transition:.18s ease;
    }

    .invoice-card:hover{
        border-color:#bfdbfe;
        box-shadow:0 6px 18px rgba(37,99,235,.08);
        transform:translateY(-1px);
    }

    .invoice-card.active{
        border-color:#93c5fd;
        background:#eff6ff;
    }

    .line-table th{
        white-space:nowrap;
        font-size:13px;
    }

    .line-table td{
        vertical-align:middle;
    }

    .line-total-box{
        border:1px dashed #dbeafe;
        background:#f8fbff;
        border-radius:14px;
        padding:12px;
    }

    .sticky-submit{
        position:sticky;
        bottom:16px;
        z-index:5;
        background:rgba(255,255,255,.92);
        backdrop-filter:blur(6px);
        border:1px solid var(--brd);
        border-radius:16px;
        padding:12px;
    }

    .mono{
        font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;
        letter-spacing:.5px;
    }
</style>

<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">ثبت برگشت از فروش</h4>
        <a class="btn btn-outline-secondary" href="{{ route('vouchers.section.index', 'return-from-sale') }}">بازگشت</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-2">لطفاً خطاهای زیر را بررسی کن:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card-soft">
        <div class="section-head">
            <div>
                <div class="section-title">فرم ثبت برگشت از فروش</div>
                <div class="muted">مشتری را انتخاب کن، فاکتور همان مشتری را از پاپ‌آپ بردار، بعد کالاهای همان فاکتور را برای برگشت ثبت کن.</div>
            </div>
        </div>

        <div class="p-3">
            <form method="POST" action="{{ route('vouchers.section.store', 'return-from-sale') }}" id="returnForm">
                @csrf

                <input type="hidden" name="related_invoice_uuid" id="relatedInvoiceUuid" value="{{ old('related_invoice_uuid') }}">

                <div class="row g-3">
                    <div class="col-lg-4">
                        <label class="form-label">مشتری</label>
                        <select name="customer_id" id="customerSelect" class="form-select" required>
                            <option value="">انتخاب مشتری...</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                    {{ trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: ('مشتری #' . $customer->id) }}
                                    @if(!empty($customer->mobile)) | {{ $customer->mobile }} @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">فاکتور مشتری</label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary w-100" id="openInvoiceModalBtn" disabled>
                                انتخاب فاکتور
                            </button>
                        </div>
                        <div class="form-text">بعد از انتخاب مشتری، لیست فاکتورهای همان مشتری باز می‌شود.</div>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">انبار مقصد برگشت</label>
                        <select name="to_warehouse_id" id="warehouseSelect" class="form-select" required>
                            <option value="">انتخاب انبار...</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected(old('to_warehouse_id') == $warehouse->id)>
                                    {{ $warehouse->name }}
                                </option>
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
                        <div class="hint-box">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <div class="fw-bold mb-1">فاکتور انتخاب‌شده</div>
                                    <div class="muted" id="selectedInvoiceText">هنوز فاکتوری انتخاب نشده است.</div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="chip">شناسه: <span class="mono" id="selectedInvoiceUuid">—</span></span>
                                    <span class="chip">تاریخ: <span id="selectedInvoiceDate">—</span></span>
                                    <span class="chip">جمع فاکتور: <span id="selectedInvoiceTotal">0</span> تومان</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12" id="itemsSection" style="display:none;">
                        <div class="info-card">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div>
                                    <div class="fw-bold">کالاهای برگشتی</div>
                                    <div class="muted">فقط از کالاهای همان فاکتور می‌توانی انتخاب کنی.</div>
                                </div>

                                <button type="button" class="btn btn-outline-primary btn-sm" id="addItemBtn" disabled>
                                    + افزودن کالا
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle line-table" id="itemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:34%">کالا</th>
                                            <th style="width:20%">قیمت واحد</th>
                                            <th style="width:16%">باقی‌مانده مجاز</th>
                                            <th style="width:16%">تعداد برگشتی</th>
                                            <th style="width:18%">جمع ردیف</th>
                                            <th style="width:8%">عملیات</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>

                            <div class="line-total-box mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <div class="fw-bold">جمع مبلغ برگشت از فروش</div>
                                    <div class="muted">بر اساس قیمت و تعداد کالاهای انتخاب‌شده</div>
                                </div>
                                <div class="fs-5 fw-bold">
                                    <span id="returnGrandTotal">0</span> تومان
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">شماره حواله (اختیاری)</label>
                        <input name="reference" class="form-control" value="{{ old('reference') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">توضیحات (اختیاری)</label>
                        <input name="note" class="form-control" value="{{ old('note') }}">
                    </div>

                    <div class="col-12">
                        <div class="sticky-submit">
                            <button type="submit" class="btn btn-success w-100" id="submitBtn">ثبت برگشت از فروش</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- modal فاکتورها --}}
<div class="modal fade" id="invoicePickerModal" tabindex="-1" aria-labelledby="invoicePickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">انتخاب فاکتور مشتری</h5>
                <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="invoiceSearchInput" placeholder="جستجو در شناسه فاکتور یا تاریخ...">
                </div>

                <div id="invoiceListWrap" class="row g-3">
                    <div class="col-12">
                        <div class="text-muted">ابتدا مشتری را انتخاب کن.</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">بستن</button>
                <button type="button" class="btn btn-primary" id="confirmInvoiceBtn" disabled>انتخاب این فاکتور</button>
            </div>
        </div>
    </div>
</div>

<script>
const customerSelect = document.getElementById('customerSelect');
const warehouseSelect = document.getElementById('warehouseSelect');
const openInvoiceModalBtn = document.getElementById('openInvoiceModalBtn');
const addItemBtn = document.getElementById('addItemBtn');
const tbody = document.querySelector('#itemsTable tbody');
const returnForm = document.getElementById('returnForm');
const itemsSection = document.getElementById('itemsSection');

const relatedInvoiceUuid = document.getElementById('relatedInvoiceUuid');
const selectedInvoiceUuid = document.getElementById('selectedInvoiceUuid');
const selectedInvoiceDate = document.getElementById('selectedInvoiceDate');
const selectedInvoiceTotal = document.getElementById('selectedInvoiceTotal');
const selectedInvoiceText = document.getElementById('selectedInvoiceText');
const returnGrandTotal = document.getElementById('returnGrandTotal');

const invoiceListWrap = document.getElementById('invoiceListWrap');
const invoiceSearchInput = document.getElementById('invoiceSearchInput');
const confirmInvoiceBtn = document.getElementById('confirmInvoiceBtn');

const invoiceModalEl = document.getElementById('invoicePickerModal');
const invoiceModal = new bootstrap.Modal(invoiceModalEl);

let customerInvoices = [];
let selectedInvoice = null;
let invoiceProducts = [];

function toMoney(x) {
    return Number(x || 0).toLocaleString('fa-IR');
}

function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function resetInvoiceSelection() {
    selectedInvoice = null;
    invoiceProducts = [];
    relatedInvoiceUuid.value = '';
    selectedInvoiceUuid.textContent = '—';
    selectedInvoiceDate.textContent = '—';
    selectedInvoiceTotal.textContent = '0';
    selectedInvoiceText.textContent = 'هنوز فاکتوری انتخاب نشده است.';
    tbody.innerHTML = '';
    addItemBtn.disabled = true;
    itemsSection.style.display = 'none';
    updateGrandTotal();
}

function updateGrandTotal() {
    let total = 0;

    tbody.querySelectorAll('tr').forEach(tr => {
        const qty = Number(tr.querySelector('.qty-input')?.value || 0);
        const price = Number(tr.querySelector('.price-raw')?.value || 0);
        total += qty * price;
    });

    returnGrandTotal.textContent = toMoney(total);
}

function rowTemplate(index) {
    const options = invoiceProducts
        .filter(p => Number(p.remaining_qty || 0) > 0)
        .map(p => {
            const label = `${p.name} | قیمت: ${toMoney(p.price || 0)} | باقی‌مانده: ${p.remaining_qty}`;
            return `<option value="${p.product_id}" data-price="${p.price || 0}" data-max="${p.remaining_qty}">${escapeHtml(label)}</option>`;
        })
        .join('');

    return `
        <tr>
            <td>
                <select name="items[${index}][product_id]" class="form-select product-select" required>
                    <option value="">انتخاب کالا...</option>
                    ${options}
                </select>
            </td>

            <td>
                <input type="text" class="form-control price-view" readonly value="0">
                <input type="hidden" name="items[${index}][price]" class="price-raw" value="0">
            </td>

            <td>
                <input type="text" class="form-control max-view" readonly value="0">
            </td>

            <td>
                <input type="number" min="1" name="items[${index}][quantity]" class="form-control qty-input" required>
            </td>

            <td>
                <input type="text" class="form-control line-total-view" readonly value="0">
            </td>

            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn">حذف</button>
            </td>
        </tr>
    `;
}

function refreshRowIndexes() {
    tbody.querySelectorAll('tr').forEach((tr, index) => {
        const productSelect = tr.querySelector('.product-select');
        const qtyInput = tr.querySelector('.qty-input');
        const priceRaw = tr.querySelector('.price-raw');

        if (productSelect) productSelect.name = `items[${index}][product_id]`;
        if (qtyInput) qtyInput.name = `items[${index}][quantity]`;
        if (priceRaw) priceRaw.name = `items[${index}][price]`;
    });
}

function bindRow(tr) {
    const productSelect = tr.querySelector('.product-select');
    const qtyInput = tr.querySelector('.qty-input');
    const priceView = tr.querySelector('.price-view');
    const priceRaw = tr.querySelector('.price-raw');
    const maxView = tr.querySelector('.max-view');
    const lineTotalView = tr.querySelector('.line-total-view');

    function syncRow() {
        const selected = productSelect.selectedOptions[0];
        const price = Number(selected?.dataset.price || 0);
        const max = Number(selected?.dataset.max || 0);

        priceRaw.value = String(price);
        priceView.value = toMoney(price);
        maxView.value = toMoney(max);

        if (max > 0) {
            qtyInput.max = String(max);
            if (!qtyInput.value || Number(qtyInput.value) < 1) qtyInput.value = '1';
            if (Number(qtyInput.value) > max) qtyInput.value = String(max);
        } else {
            qtyInput.value = '';
            qtyInput.removeAttribute('max');
        }

        const lineTotal = (Number(qtyInput.value || 0) * price);
        lineTotalView.value = toMoney(lineTotal);

        updateGrandTotal();
    }

    productSelect.addEventListener('change', syncRow);

    qtyInput.addEventListener('input', function () {
        const max = Number(productSelect.selectedOptions[0]?.dataset.max || 0);
        if (max > 0 && Number(qtyInput.value || 0) > max) {
            qtyInput.value = String(max);
        }
        if (Number(qtyInput.value || 0) < 1 && qtyInput.value !== '') {
            qtyInput.value = '1';
        }
        syncRow();
    });

    tr.querySelector('.remove-row-btn').addEventListener('click', function () {
        tr.remove();
        refreshRowIndexes();
        updateGrandTotal();
    });

    syncRow();
}

function addRow(prefill = null) {
    const index = tbody.querySelectorAll('tr').length;
    tbody.insertAdjacentHTML('beforeend', rowTemplate(index));
    const tr = tbody.querySelector('tr:last-child');

    bindRow(tr);

    if (prefill) {
        const productSelect = tr.querySelector('.product-select');
        const qtyInput = tr.querySelector('.qty-input');

        productSelect.value = String(prefill.product_id || '');
        productSelect.dispatchEvent(new Event('change'));

        qtyInput.value = String(prefill.quantity || '');
        qtyInput.dispatchEvent(new Event('input'));
    }
}

function renderInvoiceCards(list) {
    invoiceListWrap.innerHTML = '';

    if (!list.length) {
        invoiceListWrap.innerHTML = `
            <div class="col-12">
                <div class="text-muted">فاکتوری برای این مشتری پیدا نشد.</div>
            </div>
        `;
        confirmInvoiceBtn.disabled = true;
        return;
    }

    list.forEach(inv => {
        const col = document.createElement('div');
        col.className = 'col-md-6';

        const isActive = selectedInvoice && selectedInvoice.uuid === inv.uuid;

        col.innerHTML = `
            <div class="invoice-card ${isActive ? 'active' : ''}" data-uuid="${escapeHtml(inv.uuid)}">
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <div class="fw-bold mono">${escapeHtml(inv.uuid)}</div>
                    <span class="badge text-bg-light">فاکتور فروش</span>
                </div>

                <div class="mt-3 d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <div>
                        <div class="muted">تاریخ فاکتور</div>
                        <div class="fw-bold">${escapeHtml(inv.invoice_date || inv.created_at || '—')}</div>
                    </div>

                    <div class="text-start">
                        <div class="muted">مبلغ کل</div>
                        <div class="fw-bold">${toMoney(inv.total || 0)} تومان</div>
                    </div>
                </div>
            </div>
        `;

        col.querySelector('.invoice-card').addEventListener('click', function () {
            selectedInvoice = inv;
            renderInvoiceCards(filteredInvoices(invoiceSearchInput.value || ''));
            confirmInvoiceBtn.disabled = false;
        });

        invoiceListWrap.appendChild(col);
    });
}

function filteredInvoices(keyword) {
    const q = String(keyword || '').trim().toLowerCase();
    if (!q) return customerInvoices;

    return customerInvoices.filter(inv => {
        return String(inv.uuid || '').toLowerCase().includes(q) ||
               String(inv.invoice_date || inv.created_at || '').toLowerCase().includes(q) ||
               String(inv.total || '').toLowerCase().includes(q);
    });
}

async function loadCustomerInvoices(customerId) {
    invoiceListWrap.innerHTML = `
        <div class="col-12"><div class="text-muted">در حال بارگذاری فاکتورها...</div></div>
    `;
    confirmInvoiceBtn.disabled = true;
    selectedInvoice = null;

    const res = await fetch(`{{ url('/vouchers/return/customers') }}/${customerId}/invoices`, {
        headers: { 'Accept': 'application/json' }
    });

    if (!res.ok) {
        invoiceListWrap.innerHTML = `
            <div class="col-12"><div class="text-danger">خطا در دریافت فاکتورها.</div></div>
        `;
        return;
    }

    customerInvoices = await res.json();
    renderInvoiceCards(customerInvoices);
}

async function loadInvoiceProducts(uuid) {
    const res = await fetch(`{{ url('/vouchers/invoice') }}/${uuid}/products`, {
        headers: { 'Accept': 'application/json' }
    });

    if (!res.ok) {
        invoiceProducts = [];
        addItemBtn.disabled = true;
        itemsSection.style.display = 'none';
        return;
    }

    const payload = await res.json();
    invoiceProducts = payload.products || [];
    tbody.innerHTML = '';
    addItemBtn.disabled = invoiceProducts.filter(p => Number(p.remaining_qty || 0) > 0).length === 0;
    itemsSection.style.display = 'block';
    updateGrandTotal();
}

customerSelect.addEventListener('change', function () {
    resetInvoiceSelection();
    openInvoiceModalBtn.disabled = !customerSelect.value;
});

openInvoiceModalBtn.addEventListener('click', async function () {
    if (!customerSelect.value) {
        alert('اول مشتری را انتخاب کن.');
        return;
    }

    invoiceSearchInput.value = '';
    await loadCustomerInvoices(customerSelect.value);
    invoiceModal.show();
});

invoiceSearchInput.addEventListener('input', function () {
    renderInvoiceCards(filteredInvoices(this.value || ''));
});

confirmInvoiceBtn.addEventListener('click', async function () {
    if (!selectedInvoice) {
        alert('یک فاکتور را انتخاب کن.');
        return;
    }

    relatedInvoiceUuid.value = selectedInvoice.uuid;
    selectedInvoiceUuid.textContent = selectedInvoice.uuid || '—';
    selectedInvoiceDate.textContent = selectedInvoice.invoice_date || selectedInvoice.created_at || '—';
    selectedInvoiceTotal.textContent = toMoney(selectedInvoice.total || 0);
    selectedInvoiceText.textContent = 'فاکتور انتخاب شد. حالا کالاهای برگشتی را ثبت کن.';

    invoiceModal.hide();
    await loadInvoiceProducts(selectedInvoice.uuid);
});

addItemBtn.addEventListener('click', function () {
    addRow();
});

returnForm.addEventListener('submit', function () {
    const btn = document.getElementById('submitBtn');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'در حال ثبت...';
    }
});
</script>
@endsection