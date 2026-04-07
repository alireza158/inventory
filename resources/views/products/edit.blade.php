@extends('layouts.app')

@section('content')
<div class="card shadow-sm"><div class="card-body">
<h5 class="mb-3">ویرایش محصول</h5>
<form method="POST" action="{{ route('products.update', $product) }}">@csrf @method('PUT')
<div class="row g-3">
<div class="col-md-4">
    <label class="form-label">نام محصول</label>
    <input name="name" class="form-control" value="{{ old('name',$product->name) }}" required>
</div>
<div class="col-md-8">
    <label class="form-label">دسته‌بندی (قابل جستجو/افزودن)</label>
    <select id="categorySelect" name="category_id" class="form-select" required>
        <option value="">انتخاب</option>
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}" data-parent-id="{{ $cat->parent_id }}" @selected(old('category_id',$product->category_id)==$cat->id)>{{ $cat->name }} ({{ $cat->code }})</option>
        @endforeach
    </select>
    <div class="form-text">اگر دسته جدید بنویسی، همان لحظه ساخته می‌شود و روی فرم قرار می‌گیرد.</div>
</div>
</div><hr>
<div class="d-flex justify-content-between mb-2"><h6>مدل/تنوع/طرح</h6><button type="button" class="btn btn-sm btn-outline-primary" onclick="addVariantRow()">+ افزودن</button></div>
<table class="table table-sm" id="variantsTable"><thead><tr><th>مدل لیست</th><th>عنوان طرح/رنگ</th><th>کد طرح</th><th>کد نهایی</th><th>فروش</th><th>خرید</th><th>موجودی</th><th>وضعیت</th><th></th></tr></thead><tbody></tbody></table>
<button class="btn btn-primary">ذخیره</button></form></div></div>
<script>
const modelOptions = @json($modelListOptions);
const csrfToken = @json(csrf_token());
const ensureCategoryUrl = @json(route('ajax.categories.ensure'));
const ensureModelListUrl = @json(route('ajax.model-lists.ensure'));
let idx=0;

function normalizeForCompare(value){
    return String(value || '').replace(/[\u200c\u200d\u200e\u200f\ufeff]/g,' ').replace(/\s+/g,' ').trim().toLocaleLowerCase('fa-IR');
}

async function postJson(url, body){
    const res = await fetch(url, {
        method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken},
        body: JSON.stringify(body || {}),
    });
    const payload = await res.json().catch(()=>({}));
    if(!res.ok){
        throw new Error(payload?.message || 'خطا در ثبت اطلاعات.');
    }
    return payload;
}

function code12(c,m,v){return `${c||'0000'}${String(m||'').padStart(4,'0')}${v||'0000'}`}

function optionText(m){
    return `${m.brand ? (m.brand + ' - ') : ''}${m.model_name} (${m.code||'---'})`;
}

function initModelSelect(selectEl, selected=''){
    if (!window.jQuery || !window.jQuery.fn?.select2) return;
    const $el = window.jQuery(selectEl);
    if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
    }

    $el.select2({
        width:'100%',
        dir:'rtl',
        tags:true,
        placeholder:'انتخاب یا افزودن مدل لیست',
        createTag: function(params){
            const term = window.jQuery.trim(params.term || '');
            if (!term) return null;
            return { id:'__new__:'+term, text:'افزودن مدل لیست جدید: '+term, raw:term, newTag:true };
        }
    });

    if(selected){
        $el.val(String(selected)).trigger('change.select2');
    }

    $el.off('select2:select.ensure').on('select2:select.ensure', async function(e){
        const data = e.params?.data || {};
        const val = String(data.id || '');
        if(!val.startsWith('__new__:')) return;

        const typed = String(data.raw || val.replace('__new__:','')).trim();
        if(!typed){
            $el.val('').trigger('change.select2');
            return;
        }

        try{
            const res = await postJson(ensureModelListUrl, {model_name: typed, brand:'سایر'});
            const item = res.item || {};
            const id = String(item.id || '');
            if(!id) throw new Error('پاسخ سرور معتبر نیست.');

            let existing = modelOptions.find((m)=>String(m.id)===id);
            if(!existing){
                existing = {id: Number(id), brand: item.brand || 'سایر', model_name: item.model_name || typed, code: item.code || ''};
                modelOptions.push(existing);
            }

            if(!$el.find('option[value="'+id+'"]').length){
                const opt = new Option(optionText(existing), id, true, true);
                opt.dataset.code = existing.code || '';
                $el.append(opt);
            }

            $el.val(id).trigger('change.select2');
            alert(res.message || 'مدل لیست انتخاب شد.');
        }catch(err){
            alert(err.message || 'ثبت مدل لیست جدید انجام نشد.');
            $el.val('').trigger('change.select2');
        }
    });
}

function addVariantRow(data={}){
    const tb=document.querySelector('#variantsTable tbody');
    const i=idx++;
    const opts=modelOptions.map(m=>`<option value="${m.id}" data-code="${m.code||''}" ${String(data.model_list_id||'')===String(m.id)?'selected':''}>${optionText(m)}</option>`).join('');
    const tr=document.createElement('tr');
    tr.innerHTML=`<td><input type="hidden" name="variants[${i}][id]" value="${data.id||''}"><select class="form-select model" name="variants[${i}][model_list_id]" required><option value="">انتخاب</option>${opts}</select></td><td><input class="form-control vname" name="variants[${i}][variant_name]" value="${data.variant_name||''}" required></td><td><input class="form-control variety" maxlength="4" name="variants[${i}][variety_code]" value="${data.variety_code||''}" required></td><td><input class="form-control final" readonly><input type="hidden" class="variety-name" name="variants[${i}][variety_name]" value="${data.variety_name||''}"></td><td><input class="form-control" type="number" name="variants[${i}][sell_price]" value="${data.sell_price||0}" min="0" required></td><td><input class="form-control" type="number" name="variants[${i}][buy_price]" value="${data.buy_price||''}" min="0"></td><td><input class="form-control" type="number" name="variants[${i}][stock]" value="${data.stock||0}" min="0" required></td><td><input type="hidden" name="variants[${i}][is_active]" value="0"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="variants[${i}][is_active]" value="1" ${data.is_active === false || Number(data.is_active) === 0 ? '' : 'checked'}></div></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">×</button></td>`;
    tb.appendChild(tr);
    tr.addEventListener('input',()=>refreshRow(tr));
    tr.addEventListener('change',()=>refreshRow(tr));
    initModelSelect(tr.querySelector('.model'), data.model_list_id || '');
    refreshRow(tr)
}

function refreshRow(tr){
    const catCode=(document.querySelector('select[name="category_id"]').selectedOptions[0]?.textContent.match(/\((\d{2,4})\)/)||[])[1]||'0000';
    const m=tr.querySelector('.model').selectedOptions[0]?.dataset.code||'0000';
    const v=(tr.querySelector('.variety').value||'').padStart(4,'0').slice(-4);
    tr.querySelector('.final').value=code12(catCode,m,v);
    tr.querySelector('.variety-name').value=tr.querySelector('.vname').value;
}

function initCategorySelect(){
    if (!window.jQuery || !window.jQuery.fn?.select2) return;
    const $el = window.jQuery('#categorySelect');

    $el.select2({
        width:'100%',
        dir:'rtl',
        tags:true,
        placeholder:'انتخاب یا افزودن دسته‌بندی',
        createTag: function(params){
            const term = window.jQuery.trim(params.term || '');
            if (!term) return null;
            return { id:'__new__:'+term, text:'افزودن دسته‌بندی جدید: '+term, raw:term, newTag:true };
        }
    });

    $el.on('select2:select', async function(e){
        const data = e.params?.data || {};
        const val = String(data.id || '');
        if(!val.startsWith('__new__:')){
            document.querySelectorAll('#variantsTable tbody tr').forEach(refreshRow);
            return;
        }

        const typed = String(data.raw || val.replace('__new__:','')).trim();
        if(!typed){
            $el.val('').trigger('change.select2');
            return;
        }

        const selected = this.selectedOptions[0];
        const parentId = selected?.dataset?.parentId ? Number(selected.dataset.parentId) : null;

        try{
            const res = await postJson(ensureCategoryUrl, {name: typed, parent_id: parentId});
            const item = res.item || {};
            const id = String(item.id || '');
            if(!id) throw new Error('پاسخ سرور معتبر نیست.');

            if(!$el.find('option[value="'+id+'"]').length){
                const option = new Option((item.name || typed) + (item.code ? ` (${item.code})` : ''), id, true, true);
                option.dataset.parentId = item.parent_id || '';
                $el.append(option);
            }

            $el.val(id).trigger('change.select2');
            document.querySelectorAll('#variantsTable tbody tr').forEach(refreshRow);
            alert(res.message || 'دسته‌بندی انتخاب شد.');
        }catch(err){
            alert(err.message || 'ثبت دسته‌بندی جدید انجام نشد.');
            $el.val('{{ old('category_id',$product->category_id) }}').trigger('change.select2');
        }
    });
}

document.querySelector('select[name="category_id"]').addEventListener('change',()=>document.querySelectorAll('#variantsTable tbody tr').forEach(refreshRow));
const variants=@json(old('variants', $product->variants->toArray()));
if(variants.length) variants.forEach(addVariantRow); else addVariantRow();
initCategorySelect();
</script>
@endsection
