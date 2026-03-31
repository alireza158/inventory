@extends('layouts.app')

@php
$categoriesJson = $categories->map(fn($c) => ['id'=>$c->id,'name'=>$c->name,'parent_id'=>$c->parent_id])->values();
$productsJson = $products->map(fn($p) => ['id'=>$p->id,'name'=>$p->name,'sku'=>$p->sku,'category_id'=>$p->category_id])->values();
@endphp

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">ثبت حواله ضایعات</h4>
        <a class="btn btn-outline-secondary" href="{{ route('vouchers.section.index', 'scrap') }}">بازگشت</a>
    </div>

    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('vouchers.section.store', 'scrap') }}">
            @csrf
            <input type="hidden" name="voucher_type" value="scrap">
            <input type="hidden" name="from_warehouse_id" value="{{ $centralWarehouseId }}">
            <input type="hidden" name="to_warehouse_id" value="{{ $centralWarehouseId }}">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">انبار مبدا</label>
                    <input class="form-control" value="انبار مرکزی" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">شماره حواله (اختیاری)</label>
                    <input name="reference" class="form-control" value="{{ old('reference') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">توضیحات (اختیاری)</label>
                    <input name="note" class="form-control" value="{{ old('note') }}">
                </div>
                <div class="col-12">
                    <table class="table" id="itemsTable"><thead><tr><th>سردسته</th><th>دسته‌بندی</th><th>کالا</th><th>تعداد</th><th></th></tr></thead><tbody></tbody></table>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="addItemBtn">+ افزودن ردیف</button>
                </div>
                <div class="col-12"><button class="btn btn-primary">ثبت حواله ضایعات</button></div>
            </div>
        </form>
    </div></div>
</div>

<script>
const categories = @json($categoriesJson); const products = @json($productsJson);
const tbody = document.querySelector('#itemsTable tbody'); const addBtn = document.getElementById('addItemBtn');
function rootOptions(s=''){const r=categories.filter(c=>!c.parent_id);return `<option value="">انتخاب...</option>`+r.map(c=>`<option value="${c.id}" ${String(c.id)===String(s)?'selected':''}>${c.name}</option>`).join('')}
function childOptions(pid,s=''){const c=categories.filter(x=>String(x.parent_id||'')===String(pid||''));return `<option value="">انتخاب...</option>`+c.map(x=>`<option value="${x.id}" ${String(x.id)===String(s)?'selected':''}>${x.name}</option>`).join('')}
function productOptions(cid,s=''){const p=products.filter(x=>String(x.category_id)===String(cid||''));return `<option value="">انتخاب...</option>`+p.map(x=>`<option value="${x.id}" ${String(x.id)===String(s)?'selected':''}>${x.name}</option>`).join('')}
function row(i){return `<tr><td><select class="form-select root" required>${rootOptions()}</select></td><td><select name="items[${i}][category_id]" class="form-select cat" required><option value="">انتخاب...</option></select></td><td><select name="items[${i}][product_id]" class="form-select prod" required><option value="">انتخاب...</option></select></td><td><input name="items[${i}][quantity]" type="number" min="1" value="1" class="form-control" required></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">حذف</button></td></tr>`}
function bind(){tbody.querySelectorAll('.root').forEach(el=>{if(el.dataset.b) return; el.dataset.b=1; el.addEventListener('change',e=>{const tr=e.target.closest('tr'); tr.querySelector('.cat').innerHTML=childOptions(e.target.value); tr.querySelector('.prod').innerHTML='<option value="">انتخاب...</option>';});});tbody.querySelectorAll('.cat').forEach(el=>{if(el.dataset.b) return; el.dataset.b=1; el.addEventListener('change',e=>{const tr=e.target.closest('tr'); tr.querySelector('.prod').innerHTML=productOptions(e.target.value);});});}
addBtn.addEventListener('click',()=>{tbody.insertAdjacentHTML('beforeend',row(tbody.querySelectorAll('tr').length));bind();}); addBtn.click();
</script>
@endsection
