@extends('layouts.app')
@section('content')
@php
$isEdit = $document->exists;
$items = old('items', $isEdit ? $document->items->map(fn($item)=>[
  'item_name' => $item->item_name,
  'quantity' => $item->quantity,
  'asset_codes_input' => $item->codes->pluck('asset_code')->join("\n"),
  'description' => $item->description,
])->values()->all() : [['item_name'=>'','quantity'=>1,'asset_codes_input'=>'','description'=>'']]);
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">{{ $isEdit ? 'ویرایش سند اموال پرسنل' : 'ثبت سند اموال پرسنل' }}</h4>
  <a href="{{ route('asset.documents.index') }}" class="btn btn-outline-secondary">بازگشت</a>
</div>

<form method="POST" action="{{ $action }}" class="card border-0 shadow-sm" id="assetDocumentForm" enctype="multipart/form-data">
  @csrf
  @if($method !== 'POST') @method($method) @endif

  <div class="card-body">
    <div class="row g-3 mb-3">
      <div class="col-md-4"><label class="form-label">تاریخ سند</label><input type="date" name="document_date" class="form-control" required value="{{ old('document_date', optional($document->document_date)->toDateString() ?: now()->toDateString()) }}"></div>
      <div class="col-md-8"><label class="form-label">پرسنل</label><select name="personnel_id" class="form-select" required><option value="">انتخاب...</option>@foreach($personnel as $p)<option value="{{ $p->id }}" @selected(old('personnel_id', $document->personnel_id)==$p->id)>{{ $p->full_name }} ({{ $p->personnel_code }})</option>@endforeach</select></div>
      <div class="col-12"><label class="form-label">توضیحات کلی (اختیاری)</label><textarea name="description" class="form-control" rows="2">{{ old('description', $document->description) }}</textarea></div>
      <div class="col-12">
        <label class="form-label">نامه / فرم امضاشده (اختیاری)</label>
        <input type="file" name="signed_form" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
        <small class="text-muted">فرمت‌های مجاز: jpg، jpeg، png، webp، pdf (حداکثر ۵ مگابایت)</small>
        @if($isEdit && $document->signed_form_path)
          <div class="mt-2">
            <a href="{{ route('asset.documents.signed-form.view', $document) }}" target="_blank" class="btn btn-sm btn-outline-primary">مشاهده فایل فعلی</a>
            <a href="{{ route('asset.documents.signed-form.download', $document) }}" class="btn btn-sm btn-outline-secondary">دانلود فایل فعلی</a>
          </div>
        @endif
      </div>
    </div>

    <hr>
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="mb-0">ردیف‌های اموال</h6>
      <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">+ افزودن ردیف</button>
    </div>

    <datalist id="assetItemNameSuggestions">
      @foreach($itemNameSuggestions as $name)
        <option value="{{ $name }}"></option>
      @endforeach
    </datalist>

    <div id="itemsWrap" class="d-grid gap-2"></div>
  </div>

  <div class="card-footer bg-white d-flex justify-content-end">
    <button class="btn btn-success">ذخیره سند</button>
  </div>
</form>

<script>
const itemsWrap = document.getElementById('itemsWrap');
const initialItems = @json($items);

function parseCodes(text){
  const normalized = (text || '').trim().replace(/[\s,]+/g, ',');
  if(!normalized) return [];
  return normalized.split(',').map(x=>x.trim()).filter(Boolean);
}

function createRow(item = {item_name:'',quantity:1,asset_codes_input:'',description:''}) {
  const row = document.createElement('div');
  row.className = 'border rounded p-2';
  row.innerHTML = `
    <div class="row g-2">
      <div class="col-md-4"><label class="form-label">نام کالا</label><input data-name-template="items[__INDEX__][item_name]" class="form-control" list="assetItemNameSuggestions" value="${item.item_name||''}" required></div>
      <div class="col-md-2"><label class="form-label">تعداد</label><div class="input-group"><button type="button" class="btn btn-outline-secondary qty-minus">-</button><input data-name-template="items[__INDEX__][quantity]" type="number" min="1" class="form-control text-center qty" value="${item.quantity||1}" required><button type="button" class="btn btn-outline-secondary qty-plus">+</button></div></div>
      <div class="col-md-6"><label class="form-label">کدهای اموال</label><textarea data-name-template="items[__INDEX__][asset_codes_input]" class="form-control codes" rows="3" placeholder="هر کد 4 رقمی را در یک خط یا با ویرگول وارد کنید" required>${item.asset_codes_input||''}</textarea><small class="text-muted codes-help"></small></div>
      <div class="col-md-10"><label class="form-label">توضیحات ردیف</label><input data-name-template="items[__INDEX__][description]" class="form-control" value="${item.description||''}"></div>
      <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-outline-danger w-100 remove-row">حذف</button></div>
    </div>
  `;

  const qty = row.querySelector('.qty');
  const codes = row.querySelector('.codes');
  const help = row.querySelector('.codes-help');

  function refreshHelp(){
    const needed = parseInt(qty.value || '0', 10);
    const entered = parseCodes(codes.value).length;
    help.textContent = `${entered} کد وارد شده از ${needed} کد مورد نیاز`;
    help.className = entered === needed ? 'text-success small codes-help' : 'text-warning small codes-help';
  }

  qty.addEventListener('input', refreshHelp);
  codes.addEventListener('input', refreshHelp);
  row.querySelector('.qty-plus').addEventListener('click', () => {
    qty.value = Math.max(1, (parseInt(qty.value || '1', 10) || 1) + 1);
    refreshHelp();
  });
  row.querySelector('.qty-minus').addEventListener('click', () => {
    qty.value = Math.max(1, (parseInt(qty.value || '1', 10) || 1) - 1);
    refreshHelp();
  });
  row.querySelector('.remove-row').addEventListener('click', () => {
    row.remove();
    reindexRows();
  });
  refreshHelp();

  itemsWrap.appendChild(row);
  reindexRows();
}

document.getElementById('addItemBtn').addEventListener('click', ()=> createRow());
initialItems.forEach(createRow);
if(!initialItems.length){ createRow(); }

function reindexRows(){
  [...itemsWrap.children].forEach((row, index) => {
    row.querySelectorAll('[data-name-template]').forEach(el => {
      const tpl = el.getAttribute('data-name-template');
      el.name = tpl.replace('__INDEX__', String(index));
    });
  });
}

document.getElementById('assetDocumentForm').addEventListener('submit', function () {
  reindexRows();
});
</script>
@endsection
