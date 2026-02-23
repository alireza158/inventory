@extends('layouts.app')

@section('content')
<style>
  .preview-box{ border:1px solid #e8edf3; border-radius:14px; background:#fff; }
  .preview-list{ max-height:340px; overflow:auto; }
  .preview-item{ display:flex; justify-content:space-between; gap:10px; padding:8px 10px; border-top:1px solid #eef2f7; font-size:13px; }
  .preview-item:first-child{ border-top:0; }
  .preview-meta{ color:#6b7280; font-size:12px; }
  .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; letter-spacing:1px; }
  .soft-hint{ background:#f8fafc; border:1px solid #e8edf3; border-radius:12px; padding:10px; }
</style>

<div class="card shadow-sm">
  <div class="card-body">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">افزودن کالا (ساخت اتومات تنوع‌ها)</h5>
      <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">بازگشت</a>
    </div>

    <form method="POST" action="{{ route('products.store') }}" class="row g-3" id="createProductForm">
      @csrf

      <div class="col-md-6">
        <label class="form-label">نام کالا</label>
        <input name="name" id="pName" class="form-control" value="{{ old('name') }}" required placeholder="مثلاً گارد یونیک سامسونگ">
      </div>

      <div class="col-md-6">
        <label class="form-label">دسته‌بندی کالا</label>
        <select name="category_id" id="pCategory" class="form-select" required>
          <option value="">انتخاب کنید</option>
          @foreach($categories as $cat)
            <option value="{{ $cat->id }}" data-code="{{ $cat->code }}" @selected(old('category_id') == $cat->id)>
              {{ $cat->name }} (کد: {{ $cat->code ?: '--' }})
            </option>
          @endforeach
        </select>
        <div class="form-text">کد دسته‌بندی باید ۲ رقمی باشد (00 تا 99).</div>
      </div>

      {{-- نوع تنوع --}}
      <div class="col-12">
        <label class="form-label">نوع تنوع کالا</label>
        <div class="d-flex gap-3 flex-wrap">
          <label class="form-check">
            <input class="form-check-input" type="radio" name="variant_type" value="design" {{ old('variant_type','both')==='design'?'checked':'' }}>
            <span class="form-check-label">فقط طرح (مثل هدفون)</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="radio" name="variant_type" value="model" {{ old('variant_type','both')==='model'?'checked':'' }}>
            <span class="form-check-label">فقط مدل‌لیست (مثل گلس)</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="radio" name="variant_type" value="both" {{ old('variant_type','both')==='both'?'checked':'' }}>
            <span class="form-check-label">مدل‌لیست + طرح (مثل گارد)</span>
          </label>
        </div>

        <div class="soft-hint mt-2">
          فرمت کد اتومات هر تنوع: <span class="mono fw-bold">CCPPPPMMMDD</span><br>
          <span class="small text-muted">
            CC=کد ۲ رقمی دسته‌بندی | PPPP=شماره محصول ۴ رقمی | MMM=کد مدل‌لیست ۳ رقمی (اگر نبود 000) | DD=کد طرح ۲ رقمی (اگر نبود 00)
          </span>
        </div>
      </div>

      {{-- مدل‌لیست‌ها --}}
      <div class="col-12" id="modelsBlock">
        <label class="form-label">مدل‌لیست‌های این کالا</label>
        <select name="model_list_ids[]" id="pModels" class="form-select" multiple size="10">
          @foreach($modelLists as $model)
            <option value="{{ $model->id }}"
                    data-code="{{ $model->code }}"
                    data-name="{{ $model->model_name }}"
                    @selected(collect(old('model_list_ids', []))->contains($model->id))>
              {{ $model->brand ? ($model->brand.' - ') : '' }}{{ $model->model_name }} ({{ $model->code }})
            </option>
          @endforeach
        </select>
        <div class="form-text">برای «فقط مدل» یا «مدل + طرح»، حداقل یک مدل انتخاب کنید.</div>
      </div>

      {{-- تعداد طرح --}}
      <div class="col-md-4" id="designBlock">
        <label class="form-label">تعداد طرح</label>
        <input type="number" min="1" max="99" name="design_count" id="pDesignCount"
               class="form-control" value="{{ old('design_count', 1) }}">
        <div class="form-text">حداکثر 99 (چون DD دو رقمی است).</div>
      </div>

      <div class="col-md-8">
        <label class="form-label">خلاصه</label>
        <div class="alert alert-light border mb-0">
          <div class="mt-2">
            <span class="badge text-bg-secondary" id="calcModels">مدل‌ها: 0</span>
            <span class="badge text-bg-secondary" id="calcDesigns">طرح‌ها: 0</span>
            <span class="badge text-bg-primary" id="calcTotal">کل تنوع: 0</span>
          </div>
        </div>
      </div>

      <div class="col-12 d-flex gap-2">
        <button type="button" class="btn btn-outline-primary" id="btnBuild">تشکیل لیست</button>
        <button type="submit" class="btn btn-primary">ثبت کالا و ساخت تنوع‌ها</button>
        <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">انصراف</a>
      </div>

      {{-- Preview --}}
      <div class="col-12">
        <div class="preview-box p-3" id="previewBox" style="display:none;">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="fw-bold">پیش‌نمایش تنوع‌ها</div>
            <div class="preview-meta" id="previewHint"></div>
          </div>
          <div class="preview-list mt-2" id="previewList"></div>
        </div>
      </div>

      <div class="col-12 small text-muted">
        نکته: کدهای نهایی در سمت سرور ساخته می‌شوند (برای جلوگیری از تکرار).
      </div>
    </form>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const modelsBlock = document.getElementById('modelsBlock');
  const designBlock = document.getElementById('designBlock');

  const nameEl = document.getElementById('pName');
  const modelsEl = document.getElementById('pModels');
  const designEl = document.getElementById('pDesignCount');

  const calcModels = document.getElementById('calcModels');
  const calcDesigns = document.getElementById('calcDesigns');
  const calcTotal = document.getElementById('calcTotal');

  const btnBuild = document.getElementById('btnBuild');
  const previewBox = document.getElementById('previewBox');
  const previewList = document.getElementById('previewList');
  const previewHint = document.getElementById('previewHint');

  function getType(){
    return document.querySelector('input[name="variant_type"]:checked')?.value || 'both';
  }

  function selectedModels(){
    return Array.from(modelsEl.selectedOptions).map(o => ({
      id: o.value,
      code: o.dataset.code || '000',
      name: o.dataset.name || o.textContent.trim()
    }));
  }

  function updateVisibility(){
    const t = getType();
    if(t === 'design'){
      modelsBlock.style.display = 'none';
      designBlock.style.display = '';
    } else if(t === 'model'){
      modelsBlock.style.display = '';
      designBlock.style.display = 'none';
    } else {
      modelsBlock.style.display = '';
      designBlock.style.display = '';
    }
    updateCounts();
  }

  function updateCounts(){
    const t = getType();
    const m = selectedModels().length;
    const d = parseInt(designEl.value || '0', 10);

    let total = 0;
    if(t === 'design') total = Math.max(d,0);
    if(t === 'model') total = m;
    if(t === 'both') total = m * Math.max(d,0);

    calcModels.textContent = `مدل‌ها: ${m}`;
    calcDesigns.textContent = `طرح‌ها: ${t==='model' ? 0 : d}`;
    calcTotal.textContent = `کل تنوع: ${total}`;
    return {t,m,d,total};
  }

  document.querySelectorAll('input[name="variant_type"]').forEach(r => r.addEventListener('change', updateVisibility));
  modelsEl.addEventListener('change', updateCounts);
  designEl.addEventListener('input', updateCounts);

  updateVisibility();

  btnBuild.addEventListener('click', () => {
    const baseName = (nameEl.value || '').trim();
    const {t,m,d,total} = updateCounts();
    const models = selectedModels();

    previewList.innerHTML = '';
    previewBox.style.display = 'block';

    if(!baseName){
      previewHint.textContent = 'نام کالا را وارد کنید.';
      return;
    }

    // قوانین
    if(t !== 'design' && m < 1){
      previewHint.textContent = 'حداقل یک مدل‌لیست انتخاب کنید.';
      return;
    }
    if(t !== 'model' && (!d || d < 1)){
      previewHint.textContent = 'تعداد طرح باید حداقل 1 باشد.';
      return;
    }

    previewHint.textContent = `کل تنوع: ${total} (نمایش حداکثر 200 مورد)`;

    const MAX_RENDER = 200;
    let rendered = 0;

    const push = (txt) => {
      rendered++;
      if(rendered > MAX_RENDER) return false;
      const row = document.createElement('div');
      row.className = 'preview-item';
      row.innerHTML = `<div>${txt}</div><div class="preview-meta">تنوع</div>`;
      previewList.appendChild(row);
      return true;
    };

    if(t === 'design'){
      for(let i=1;i<=d;i++){
        if(!push(`${baseName} طرح ${i}`)) break;
      }
    } else if(t === 'model'){
      models.forEach(mm => { push(`${baseName} ${mm.name}`); });
    } else {
      models.forEach(mm => {
        for(let i=1;i<=d;i++){
          if(!push(`${baseName} ${mm.name} طرح ${i}`)) return;
        }
      });
    }

    if(total > MAX_RENDER){
      const more = document.createElement('div');
      more.className = 'preview-item';
      more.innerHTML = `<div class="preview-meta">... و ${total - MAX_RENDER} مورد دیگر</div><div></div>`;
      previewList.appendChild(more);
    }
  });
});
</script>
@endsection