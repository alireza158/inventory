@extends('layouts.app')

@php
$categoriesJson = $categories->map(fn($c) => ['id'=>$c->id,'name'=>$c->name,'parent_id'=>$c->parent_id])->values();
$productsJson = $products->map(fn($p) => ['id'=>$p->id,'name'=>$p->name,'sku'=>$p->sku,'category_id'=>$p->category_id])->values();
@endphp

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">ثبت حواله پرسنل</h4>
        <a class="btn btn-outline-secondary" href="{{ route('vouchers.section.index', 'personnel') }}">بازگشت</a>
    </div>

    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('vouchers.section.store', 'personnel') }}">
            @csrf
            <input type="hidden" name="voucher_type" value="personnel_asset">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">انبار مبدا</label><select name="from_warehouse_id" class="form-select" required><option value="">انتخاب...</option>@foreach($fromWarehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">پرسنل مقصد</label><select name="to_warehouse_id" class="form-select" required><option value="">انتخاب...</option>@foreach($personnelWarehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">نام تحویل‌گیرنده (اختیاری)</label><input name="beneficiary_name" class="form-control" value="{{ old('beneficiary_name') }}"></div>
                <div class="col-md-6"><label class="form-label">شماره حواله (اختیاری)</label><input name="reference" class="form-control" value="{{ old('reference') }}"></div>
                <div class="col-md-6"><label class="form-label">توضیحات (اختیاری)</label><input name="note" class="form-control" value="{{ old('note') }}"></div>
                <div class="col-12"><table class="table" id="itemsTable"><thead><tr><th>سردسته</th><th>دسته‌بندی</th><th>کالا</th><th>تعداد</th><th>کد اموال ۴ رقمی</th><th></th></tr></thead><tbody></tbody></table><button type="button" class="btn btn-outline-secondary btn-sm" id="addItemBtn">+ افزودن ردیف</button></div>
                <div class="col-12"><button class="btn btn-primary">ثبت حواله پرسنل</button></div>
            </div>
        </form>
    </div></div>
</div>
<script>
const categories=@json($categoriesJson),products=@json($productsJson),tbody=document.querySelector('#itemsTable tbody'),addBtn=document.getElementById('addItemBtn');
function ro(s=''){const r=categories.filter(c=>!c.parent_id);return `<option value="">انتخاب...</option>`+r.map(c=>`<option value="${c.id}" ${String(c.id)===String(s)?'selected':''}>${c.name}</option>`).join('')}
function co(pid,s=''){const c=categories.filter(x=>String(x.parent_id||'')===String(pid||''));return `<option value="">انتخاب...</option>`+c.map(x=>`<option value="${x.id}" ${String(x.id)===String(s)?'selected':''}>${x.name}</option>`).join('')}
function po(cid,s=''){const p=products.filter(x=>String(x.category_id)===String(cid||''));return `<option value="">انتخاب...</option>`+p.map(x=>`<option value="${x.id}" ${String(x.id)===String(s)?'selected':''}>${x.name}</option>`).join('')}
function row(i){return `<tr><td><select class="form-select root" required>${ro()}</select></td><td><select name="items[${i}][category_id]" class="form-select cat" required><option value="">انتخاب...</option></select></td><td><select name="items[${i}][product_id]" class="form-select prod" required><option value="">انتخاب...</option></select></td><td><input name="items[${i}][quantity]" type="number" min="1" value="1" class="form-control" required></td><td><input name="items[${i}][personnel_asset_code]" class="form-control" pattern="\d{4}" maxlength="4"></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">حذف</button></td></tr>`}
function bind(){tbody.querySelectorAll('.root').forEach(el=>{if(el.dataset.b)return;el.dataset.b=1;el.addEventListener('change',e=>{const tr=e.target.closest('tr');tr.querySelector('.cat').innerHTML=co(e.target.value);tr.querySelector('.prod').innerHTML='<option value="">انتخاب...</option>';});});tbody.querySelectorAll('.cat').forEach(el=>{if(el.dataset.b)return;el.dataset.b=1;el.addEventListener('change',e=>{const tr=e.target.closest('tr');tr.querySelector('.prod').innerHTML=po(e.target.value);});});}
addBtn.addEventListener('click',()=>{tbody.insertAdjacentHTML('beforeend',row(tbody.querySelectorAll('tr').length));bind();}); addBtn.click();
</script>
@endsection
