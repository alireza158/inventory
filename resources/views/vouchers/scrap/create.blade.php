@extends('layouts.app')

@php
$productsJson = $products->map(fn($p) => ['id'=>$p->id,'name'=>$p->name,'code'=>$p->code ?? $p->sku,'category_id'=>$p->category_id])->values();
$variantsJson = $variants->map(fn($v) => ['id'=>$v->id,'product_id'=>$v->product_id,'name'=>$v->variant_name,'code'=>$v->variant_code,'stock'=>(int)$v->stock])->values();
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
            </div>
        </form>
    </div></div>
</div>

<script>
const products=@json($productsJson),variants=@json($variantsJson),tbody=document.querySelector('#itemsTable tbody'),addBtn=document.getElementById('addItemBtn');
function po(s=''){return '<option value="">انتخاب محصول...</option>'+products.map(p=>`<option value="${p.id}" ${String(s)===String(p.id)?'selected':''}>${p.name} ${p.code? '('+p.code+')':''}</option>`).join('')}
function vo(pid,s=''){if(!pid)return '<option value="">ابتدا محصول...</option>';const rows=variants.filter(v=>String(v.product_id)===String(pid));return '<option value="">انتخاب تنوع...</option>'+rows.map(v=>`<option value="${v.id}" data-stock="${v.stock}" ${String(s)===String(v.id)?'selected':''}>${v.name||'بدون نام'} ${v.code? '['+v.code+']':''}</option>`).join('')}
function row(i){return `<tr><td><select name="items[${i}][product_id]" class="form-select p" required>${po()}</select><input type="hidden" name="items[${i}][category_id]" class="cat-hidden"></td><td><select name="items[${i}][variant_id]" class="form-select v" required><option value="">ابتدا محصول...</option></select></td><td><span class="badge text-bg-light st">—</span></td><td><input name="items[${i}][quantity]" type="number" min="1" class="form-control q" required></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">حذف</button></td></tr>`}
function bind(tr){const p=tr.querySelector('.p'),v=tr.querySelector('.v'),q=tr.querySelector('.q'),st=tr.querySelector('.st'),cat=tr.querySelector('.cat-hidden');const sync=()=>{const opt=v.selectedOptions[0];const s=Number(opt?.dataset.stock||0);if(s>0){q.max=String(s);if(Number(q.value||0)>s)q.value=String(s);st.textContent=s.toLocaleString('fa-IR');}else{q.removeAttribute('max');st.textContent='—';}const prod=products.find(x=>String(x.id)===String(p.value));cat.value=prod?String(prod.category_id||''):'';};p.addEventListener('change',()=>{v.innerHTML=vo(p.value);q.value='';sync();});v.addEventListener('change',sync);q.addEventListener('input',sync);}
addBtn.addEventListener('click',()=>{tbody.insertAdjacentHTML('beforeend',row(tbody.querySelectorAll('tr').length));bind(tbody.querySelector('tr:last-child'));});addBtn.click();
</script>
@endsection
