@extends('layouts.app')

@php
<<<<<<< HEAD
    $categoriesJson = ($categories ?? collect())->map(fn($c) => [
        'id' => (int) $c->id,
        'name' => (string) $c->name,
        'parent_id' => $c->parent_id ? (int) $c->parent_id : null,
    ])->values();

    $productsJson = ($products ?? collect())->map(fn($p) => [
        'id' => (int) $p->id,
        'name' => (string) $p->name,
        'sku' => (string) ($p->sku ?? ''),
        'category_id' => (int) $p->category_id,
    ])->values();

    $warehousesCollection = collect($warehouses ?? []);

    $scrapWarehouse =
        isset($scrapWarehouse) && $scrapWarehouse
            ? $scrapWarehouse
            : (
                isset($scrapWarehouseId) && $scrapWarehouseId
                    ? $warehousesCollection->firstWhere('id', $scrapWarehouseId)
                    : $warehousesCollection->first(function ($w) {
                        return str_contains((string) ($w->name ?? ''), 'ضایعات');
                    })
            );

    $scrapWarehouseId = $scrapWarehouse->id ?? ($scrapWarehouseId ?? null);
    $scrapWarehouseName = $scrapWarehouse->name ?? ($scrapWarehouseName ?? 'انبار ضایعات');

    $sourceWarehouses = $warehousesCollection->filter(function ($w) use ($scrapWarehouseId) {
        return (int) $w->id !== (int) $scrapWarehouseId;
    })->values();

    $oldItems = old('items', []);
    if (empty($oldItems)) {
        $oldItems = [[
            'root_id' => '',
            'category_id' => '',
            'product_id' => '',
            'quantity' => 1,
        ]];
    }
=======
$productsJson = $products->map(fn($p) => ['id'=>$p->id,'name'=>$p->name,'code'=>$p->code ?? $p->sku,'category_id'=>$p->category_id])->values();
$variantsJson = $variants->map(fn($v) => ['id'=>$v->id,'product_id'=>$v->product_id,'name'=>$v->variant_name,'code'=>$v->variant_code,'stock'=>(int)$v->stock])->values();
>>>>>>> a33829fcf03c65d3f859dc8aec4a0150336cd741
@endphp

@section('content')
<style>
    :root{
        --brd:#e8edf3;
        --soft:#f8fafc;
        --soft2:#f3f6fb;
        --text:#0f172a;
        --muted:#64748b;
        --blue:#2563eb;
        --blue-soft:#eff6ff;
        --danger:#dc2626;
        --danger-soft:#fef2f2;
        --ok:#16a34a;
        --ok-soft:#ecfdf5;
        --shadow:0 14px 34px rgba(15,23,42,.06);
    }

    .page-wrap{
        padding: 8px 0 24px;
    }

    .hero-box{
        border:1px solid var(--brd);
        border-radius:24px;
        background:linear-gradient(135deg,#ffffff,#f8fbff 55%,#eef6ff);
        box-shadow:var(--shadow);
        overflow:hidden;
        margin-bottom:18px;
    }

    .hero-title{
        font-size:28px;
        font-weight:900;
        color:var(--text);
        margin-bottom:6px;
    }

    .hero-sub{
        color:var(--muted);
        font-size:14px;
        line-height:1.9;
        margin-bottom:0;
        max-width:780px;
    }

    .soft-chip{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:8px 12px;
        border-radius:999px;
        border:1px solid var(--brd);
        background:#fff;
        font-size:12px;
        font-weight:700;
        color:var(--text);
    }

    .form-card{
        border:none;
        border-radius:22px;
        box-shadow:var(--shadow);
        overflow:hidden;
        background:#fff;
    }

    .section-head{
        padding:14px 18px;
        border-bottom:1px solid var(--brd);
        background:linear-gradient(0deg,#fff,var(--soft2));
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
    }

    .section-title{
        font-size:15px;
        font-weight:900;
        color:var(--text);
        margin:0;
    }

    .section-sub{
        color:var(--muted);
        font-size:12px;
        margin:4px 0 0;
    }

    .info-box{
        border:1px dashed #dbeafe;
        background:#f8fbff;
        border-radius:16px;
        padding:14px;
    }

    .mini-stat{
        border:1px solid var(--brd);
        border-radius:16px;
        padding:14px;
        background:#fff;
        height:100%;
    }

    .mini-stat .label{
        font-size:12px;
        color:var(--muted);
        margin-bottom:8px;
    }

    .mini-stat .value{
        font-size:24px;
        font-weight:900;
        color:var(--text);
        line-height:1.2;
    }

    .items-shell{
        border:1px solid var(--brd);
        border-radius:18px;
        overflow:hidden;
        background:#fff;
    }

    .items-head{
        padding:12px 14px;
        border-bottom:1px solid var(--brd);
        background:#fbfcfe;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
    }

    .item-row{
        border-bottom:1px solid #eef2f7;
        padding:14px;
        background:#fff;
    }

    .item-row:last-child{
        border-bottom:none;
    }

    .item-badge{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-width:36px;
        height:36px;
        border-radius:999px;
        background:var(--blue-soft);
        color:var(--blue);
        font-weight:900;
        font-size:13px;
        border:1px solid #dbeafe;
    }

    .qty-box{
        display:flex;
        align-items:center;
        gap:8px;
    }

    .qty-btn{
        width:38px;
        height:38px;
        border:none;
        border-radius:12px;
        background:#f1f5f9;
        color:#0f172a;
        font-size:20px;
        line-height:1;
        font-weight:900;
        cursor:pointer;
        transition:.15s ease;
    }

    .qty-btn:hover{
        background:#e2e8f0;
    }

    .qty-input{
        text-align:center;
        font-weight:800;
    }

    .remove-btn{
        border-radius:12px;
    }

    .sticky-actions{
        position:sticky;
        bottom:16px;
        z-index:5;
        background:rgba(255,255,255,.92);
        backdrop-filter:blur(6px);
        border:1px solid var(--brd);
        border-radius:16px;
        padding:12px;
    }

    .empty-note{
        border:1px dashed #dbeafe;
        border-radius:14px;
        padding:12px;
        color:var(--muted);
        background:#f8fbff;
        font-size:13px;
    }

    .form-label.small-label{
        font-size:12px;
        color:var(--muted);
        font-weight:800;
        margin-bottom:6px;
    }
</style>

<div class="container page-wrap">
    <div class="hero-box">
        <div class="p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <div class="hero-title">ثبت حواله ضایعات</div>
                    <p class="hero-sub">
                        در این فرم فقط انبار مبدا را انتخاب می‌کنی، مقصد به‌صورت ثابت انبار ضایعات است.
                        کالاها از لیست کالاهای سیستم خوانده می‌شوند و برای هر ردیف می‌توانی سردسته، دسته‌بندی، کالا و تعداد را مشخص کنی.
                    </p>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="soft-chip">مبدا انتخابی</span>
                        <span class="soft-chip">مقصد ثابت: {{ $scrapWarehouseName }}</span>
                        <span class="soft-chip">ثبت چند ردیف کالا</span>
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-outline-secondary" href="{{ route('vouchers.section.index', 'scrap') }}">بازگشت</a>
                </div>
            </div>
        </div>
    </div>

<<<<<<< HEAD
    <div class="card form-card">
        <div class="section-head">
            <div>
                <h6 class="section-title">فرم ثبت حواله ضایعات</h6>
                <div class="section-sub">کالاها از انبار مبدا کم می‌شوند و به انبار ضایعات منتقل خواهند شد.</div>
=======
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('vouchers.section.store', 'scrap') }}">
            @csrf
            <input type="hidden" name="voucher_type" value="scrap">
            <input type="hidden" name="from_warehouse_id" value="{{ $centralWarehouseId }}">
            <input type="hidden" name="to_warehouse_id" value="{{ $centralWarehouseId }}">

            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">انبار مبدا</label><input class="form-control" value="انبار مرکزی" readonly></div>
                <div class="col-md-6"><label class="form-label">شماره حواله (اختیاری)</label><input name="reference" class="form-control" value="{{ old('reference') }}"></div>
                <div class="col-12"><label class="form-label">توضیحات (اختیاری)</label><input name="note" class="form-control" value="{{ old('note') }}"></div>

                <div class="col-12">
                    <table class="table" id="itemsTable">
                        <thead><tr><th>محصول (جستجو با نام/کد)</th><th>تنوع/طرح</th><th>موجودی تنوع</th><th>تعداد</th><th></th></tr></thead>
                        <tbody></tbody>
                    </table>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="addItemBtn">+ افزودن ردیف</button>
                </div>
                <div class="col-12"><button class="btn btn-primary">ثبت حواله ضایعات</button></div>
>>>>>>> a33829fcf03c65d3f859dc8aec4a0150336cd741
            </div>
        </div>

        <div class="card-body p-3 p-lg-4">
            @if(!$scrapWarehouseId)
                <div class="alert alert-danger">
                    انبار ضایعات پیدا نشد. یک انبار با عنوان ضایعات تعریف کن یا شناسه آن را از کنترلر به ویو ارسال کن.
                </div>
            @endif

            <form method="POST" action="{{ route('vouchers.section.store', 'scrap') }}" id="scrapForm">
                @csrf

                <input type="hidden" name="voucher_type" value="scrap">
                <input type="hidden" name="to_warehouse_id" value="{{ $scrapWarehouseId }}">

                <div class="row g-3 mb-4">
                    <div class="col-lg-4">
                        <label class="form-label">انبار مبدا</label>
                        <select name="from_warehouse_id" class="form-select" required>
                            <option value="">انتخاب انبار مبدا...</option>
                            @foreach($sourceWarehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected((int) old('from_warehouse_id', '') === (int) $warehouse->id)>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">انبار مقصد</label>
                        <input class="form-control" value="{{ $scrapWarehouseName }}" readonly>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">شماره حواله (اختیاری)</label>
                        <input name="reference" class="form-control" value="{{ old('reference') }}" placeholder="مثلاً SCR-1405-001">
                    </div>

                    <div class="col-12">
                        <label class="form-label">توضیحات (اختیاری)</label>
                        <input name="note" class="form-control" value="{{ old('note') }}" placeholder="مثلاً آسیب‌دیدگی در حمل / خرابی / شکستگی">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="mini-stat">
                            <div class="label">تعداد ردیف‌ها</div>
                            <div class="value" id="rowsCount">0</div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mini-stat">
                            <div class="label">جمع کل تعداد</div>
                            <div class="value" id="qtySum">0</div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="info-box h-100 d-flex align-items-center">
                            <div>
                                <div class="fw-bold mb-1">نکته</div>
                                <div class="text-muted small">
                                    اول سردسته را انتخاب کن، بعد دسته‌بندی و بعد کالا را انتخاب کن. هر ردیف هم جداگانه قابل حذف است.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="items-shell">
                    <div class="items-head">
                        <div>
                            <div class="fw-bold">ردیف‌های حواله ضایعات</div>
                            <div class="text-muted small">برای هر کالا یک ردیف جدا ثبت کن.</div>
                        </div>

                        <button type="button" class="btn btn-outline-primary btn-sm" id="addItemBtn">+ افزودن ردیف</button>
                    </div>

                    <div id="itemsWrap"></div>
                </div>

                <div class="mt-3 empty-note" id="emptyRowsNote" style="display:none;">
                    هنوز هیچ ردیفی اضافه نشده است. از دکمه «افزودن ردیف» استفاده کن.
                </div>

                <div class="sticky-actions mt-4">
                    <button class="btn btn-primary w-100" type="submit" id="submitBtn" @disabled(!$scrapWarehouseId)>
                        ثبت حواله ضایعات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
<<<<<<< HEAD
const categories = @json($categoriesJson);
const products = @json($productsJson);
const oldItems = @json($oldItems);

const itemsWrap = document.getElementById('itemsWrap');
const addBtn = document.getElementById('addItemBtn');
const rowsCountEl = document.getElementById('rowsCount');
const qtySumEl = document.getElementById('qtySum');
const emptyRowsNote = document.getElementById('emptyRowsNote');
const scrapForm = document.getElementById('scrapForm');

function rootCategories() {
    return categories.filter(c => !c.parent_id);
}

function childCategories(parentId) {
    return categories.filter(c => String(c.parent_id || '') === String(parentId || ''));
}

function productsByCategory(categoryId) {
    return products.filter(p => String(p.category_id || '') === String(categoryId || ''));
}

function buildOptions(list, selected, placeholder, labelFn) {
    let html = `<option value="">${placeholder}</option>`;
    list.forEach(item => {
        const isSelected = String(item.id) === String(selected || '');
        html += `<option value="${item.id}" ${isSelected ? 'selected' : ''}>${labelFn(item)}</option>`;
    });
    return html;
}

function rowTemplate(index, data = {}) {
    return `
        <div class="item-row" data-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="item-badge row-no">${index + 1}</span>
                    <div>
                        <div class="fw-bold">ردیف کالا</div>
                        <div class="text-muted small">انتخاب سردسته، دسته‌بندی، کالا و تعداد</div>
                    </div>
                </div>

                <button type="button" class="btn btn-sm btn-outline-danger remove-btn">حذف ردیف</button>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small-label">سردسته</label>
                    <select class="form-select root-select" data-role="root">
                        ${buildOptions(rootCategories(), data.root_id, 'انتخاب سردسته...', item => item.name)}
                    </select>
                </div>

                <div class="col-lg-3">
                    <label class="form-label small-label">دسته‌بندی</label>
                    <select name="items[${index}][category_id]" class="form-select cat-select" data-role="category" required>
                        <option value="">انتخاب دسته‌بندی...</option>
                    </select>
                </div>

                <div class="col-lg-4">
                    <label class="form-label small-label">کالا</label>
                    <select name="items[${index}][product_id]" class="form-select product-select" data-role="product" required>
                        <option value="">انتخاب کالا...</option>
                    </select>
                </div>

                <div class="col-lg-2">
                    <label class="form-label small-label">تعداد</label>
                    <div class="qty-box">
                        <button type="button" class="qty-btn qty-minus">−</button>
                        <input
                            name="items[${index}][quantity]"
                            type="number"
                            min="1"
                            step="1"
                            value="${Number(data.quantity || 1)}"
                            class="form-control qty-input"
                            required
                        >
                        <button type="button" class="qty-btn qty-plus">+</button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function refreshIndexes() {
    const rows = Array.from(itemsWrap.querySelectorAll('.item-row'));

    rows.forEach((row, index) => {
        row.dataset.index = index;
        const rowNo = row.querySelector('.row-no');
        if (rowNo) rowNo.textContent = String(index + 1);

        const catSelect = row.querySelector('.cat-select');
        const prodSelect = row.querySelector('.product-select');
        const qtyInput = row.querySelector('.qty-input');

        if (catSelect) catSelect.name = `items[${index}][category_id]`;
        if (prodSelect) prodSelect.name = `items[${index}][product_id]`;
        if (qtyInput) qtyInput.name = `items[${index}][quantity]`;
    });

    updateSummary();
}

function updateSummary() {
    const rows = Array.from(itemsWrap.querySelectorAll('.item-row'));
    rowsCountEl.textContent = String(rows.length);

    let qtySum = 0;
    rows.forEach(row => {
        qtySum += Number(row.querySelector('.qty-input')?.value || 0);
    });
    qtySumEl.textContent = String(qtySum);

    emptyRowsNote.style.display = rows.length ? 'none' : '';
}

function fillCategoriesForRow(row, selectedCategoryId = '') {
    const rootValue = row.querySelector('.root-select').value || '';
    const catSelect = row.querySelector('.cat-select');
    catSelect.innerHTML = buildOptions(
        childCategories(rootValue),
        selectedCategoryId,
        'انتخاب دسته‌بندی...',
        item => item.name
    );
}

function fillProductsForRow(row, selectedProductId = '') {
    const categoryValue = row.querySelector('.cat-select').value || '';
    const prodSelect = row.querySelector('.product-select');

    prodSelect.innerHTML = buildOptions(
        productsByCategory(categoryValue),
        selectedProductId,
        'انتخاب کالا...',
        item => item.name + (item.sku ? ` (${item.sku})` : '')
    );
}

function bindRow(row, initialData = {}) {
    const rootSelect = row.querySelector('.root-select');
    const catSelect = row.querySelector('.cat-select');
    const prodSelect = row.querySelector('.product-select');
    const qtyInput = row.querySelector('.qty-input');
    const minusBtn = row.querySelector('.qty-minus');
    const plusBtn = row.querySelector('.qty-plus');
    const removeBtn = row.querySelector('.remove-btn');

    fillCategoriesForRow(row, initialData.category_id || '');
    fillProductsForRow(row, initialData.product_id || '');

    rootSelect.addEventListener('change', function () {
        fillCategoriesForRow(row, '');
        fillProductsForRow(row, '');
    });

    catSelect.addEventListener('change', function () {
        fillProductsForRow(row, '');
    });

    qtyInput.addEventListener('input', function () {
        let val = Number(qtyInput.value || 1);
        if (!Number.isFinite(val) || val < 1) val = 1;
        qtyInput.value = String(val);
        updateSummary();
    });

    minusBtn.addEventListener('click', function () {
        let val = Number(qtyInput.value || 1);
        val = Math.max(1, val - 1);
        qtyInput.value = String(val);
        updateSummary();
    });

    plusBtn.addEventListener('click', function () {
        let val = Number(qtyInput.value || 1);
        val = Math.max(1, val + 1);
        qtyInput.value = String(val);
        updateSummary();
    });

    removeBtn.addEventListener('click', function () {
        row.remove();
        refreshIndexes();
    });
}

function addRow(data = {}) {
    const index = itemsWrap.querySelectorAll('.item-row').length;
    itemsWrap.insertAdjacentHTML('beforeend', rowTemplate(index, data));
    const row = itemsWrap.querySelector('.item-row:last-child');
    bindRow(row, data);
    refreshIndexes();
}

addBtn.addEventListener('click', function () {
    addRow({});
});

document.addEventListener('DOMContentLoaded', function () {
    itemsWrap.innerHTML = '';
    oldItems.forEach(item => addRow(item));
    if (!oldItems.length) addRow({});
});

scrapForm.addEventListener('submit', function () {
    const btn = document.getElementById('submitBtn');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'در حال ثبت...';
    }
});
=======
const products=@json($productsJson),variants=@json($variantsJson),tbody=document.querySelector('#itemsTable tbody'),addBtn=document.getElementById('addItemBtn');
function po(s=''){return '<option value="">انتخاب محصول...</option>'+products.map(p=>`<option value="${p.id}" ${String(s)===String(p.id)?'selected':''}>${p.name} ${p.code? '('+p.code+')':''}</option>`).join('')}
function vo(pid,s=''){if(!pid)return '<option value="">ابتدا محصول...</option>';const rows=variants.filter(v=>String(v.product_id)===String(pid));return '<option value="">انتخاب تنوع...</option>'+rows.map(v=>`<option value="${v.id}" data-stock="${v.stock}" ${String(s)===String(v.id)?'selected':''}>${v.name||'بدون نام'} ${v.code? '['+v.code+']':''}</option>`).join('')}
function row(i){return `<tr><td><select name="items[${i}][product_id]" class="form-select p" required>${po()}</select><input type="hidden" name="items[${i}][category_id]" class="cat-hidden"></td><td><select name="items[${i}][variant_id]" class="form-select v" required><option value="">ابتدا محصول...</option></select></td><td><span class="badge text-bg-light st">—</span></td><td><input name="items[${i}][quantity]" type="number" min="1" class="form-control q" required></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">حذف</button></td></tr>`}
function bind(tr){const p=tr.querySelector('.p'),v=tr.querySelector('.v'),q=tr.querySelector('.q'),st=tr.querySelector('.st'),cat=tr.querySelector('.cat-hidden');const sync=()=>{const opt=v.selectedOptions[0];const s=Number(opt?.dataset.stock||0);if(s>0){q.max=String(s);if(Number(q.value||0)>s)q.value=String(s);st.textContent=s.toLocaleString('fa-IR');}else{q.removeAttribute('max');st.textContent='—';}const prod=products.find(x=>String(x.id)===String(p.value));cat.value=prod?String(prod.category_id||''):'';};p.addEventListener('change',()=>{v.innerHTML=vo(p.value);q.value='';sync();});v.addEventListener('change',sync);q.addEventListener('input',sync);}
addBtn.addEventListener('click',()=>{tbody.insertAdjacentHTML('beforeend',row(tbody.querySelectorAll('tr').length));bind(tbody.querySelector('tr:last-child'));});addBtn.click();
>>>>>>> a33829fcf03c65d3f859dc8aec4a0150336cd741
</script>
@endsection