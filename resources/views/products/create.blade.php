@extends('layouts.app')

@section('content')
<div class="card shadow-sm">
  <div class="card-body">
    <h5 class="mb-3">افزودن محصول</h5>

    <form method="POST" action="{{ route('products.store') }}">
      @csrf
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">نام محصول</label>
          <input name="name" class="form-control" value="{{ old('name') }}" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">دسته‌بندی</label>
          <select name="category_id" class="form-select" required>
            <option value="">انتخاب کنید</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}" @selected(old('category_id')==$cat->id)>{{ $cat->name }} ({{ $cat->code }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">SKU (اختیاری)</label>
          <input name="sku" class="form-control" value="{{ old('sku') }}">
        </div>
      </div>

      <hr class="my-4">
      <div class="d-flex justify-content-between mb-2">
        <h6 class="mb-0">مدل/تنوع/طرح</h6>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addVariantRow()">+ افزودن ردیف</button>
      </div>

      <table class="table table-sm" id="variantsTable">
        <thead><tr><th>مدل لیست</th><th>عنوان طرح/رنگ</th><th>کد طرح</th><th>کد نهایی</th><th>فروش</th><th>خرید</th><th>موجودی</th><th></th></tr></thead>
        <tbody></tbody>
      </table>

      <button class="btn btn-primary">ثبت</button>
    </form>
  </div>
</div>
<script>
const modelOptions = @json($modelListOptions);
let idx = 0;
function code12(cat, model, variety){ return `${cat||'0000'}${model||'0000'}${variety||'0000'}`; }
function addVariantRow(data = {}) {
  const tb = document.querySelector('#variantsTable tbody');
  const i = idx++;
  const options = modelOptions.map(m => `<option value="${m.id}" data-code="${m.code}" ${String(data.model_list_id||'')===String(m.id)?'selected':''}>${m.model_name} (${m.code||'----'})</option>`).join('');
  const tr = document.createElement('tr');
  tr.innerHTML = `<td><select class="form-select model" name="variants[${i}][model_list_id]" required><option value="">انتخاب</option>${options}</select></td>
  <td><input class="form-control vname" name="variants[${i}][variant_name]" value="${data.variant_name||''}" required></td>
  <td><input class="form-control variety" maxlength="4" name="variants[${i}][variety_code]" value="${data.variety_code||''}" required></td>
  <td><input class="form-control final" readonly><input type="hidden" class="variety-name" name="variants[${i}][variety_name]" value="${data.variety_name||''}"></td>
  <td><input class="form-control" type="number" name="variants[${i}][sell_price]" value="${data.sell_price||0}" min="0" required></td>
  <td><input class="form-control" type="number" name="variants[${i}][buy_price]" value="${data.buy_price||''}" min="0"></td>
  <td><input class="form-control" type="number" name="variants[${i}][stock]" value="${data.stock||0}" min="0" required></td>
  <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">×</button></td>`;
  tb.appendChild(tr);
  tr.addEventListener('input', ()=>refreshRow(tr));
  tr.addEventListener('change', ()=>refreshRow(tr));
  refreshRow(tr);
}
function refreshRow(tr){
  const catCode = (document.querySelector('select[name="category_id"]')?.selectedOptions[0]?.textContent.match(/\((\d{4})\)/)||[])[1] || '0000';
  const modelCode = tr.querySelector('.model')?.selectedOptions[0]?.dataset.code || '0000';
  const varietyCode = (tr.querySelector('.variety').value||'').padStart(4,'0').slice(-4);
  tr.querySelector('.final').value = code12(catCode, modelCode, varietyCode);
  tr.querySelector('.variety-name').value = tr.querySelector('.vname').value;
}
document.querySelector('select[name="category_id"]').addEventListener('change', ()=>document.querySelectorAll('#variantsTable tbody tr').forEach(refreshRow));
@php $oldVariants = old('variants', []); @endphp
const oldVariants = @json($oldVariants);
if (oldVariants.length) oldVariants.forEach(addVariantRow); else addVariantRow();
</script>
@endsection
