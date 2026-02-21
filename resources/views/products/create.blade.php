@extends('layouts.app')

@section('content')
<style>
  .preview-box{
    border: 1px solid #e8edf3;
    border-radius: 14px;
    background: #fff;
  }
  .preview-list{
    max-height: 340px;
    overflow: auto;
  }
  .preview-item{
    display:flex;
    justify-content:space-between;
    gap:10px;
    padding: 8px 10px;
    border-top: 1px solid #eef2f7;
    font-size: 13px;
  }
  .preview-item:first-child{ border-top: 0; }
  .preview-meta{ color:#6b7280; font-size: 12px; }
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
            <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>
              {{ $cat->name }} (کد: {{ $cat->code ?: '---' }})
            </option>
          @endforeach
        </select>
        <div class="form-text">کد دسته‌بندی باید 3 رقمی باشد (مثلاً 101)</div>
      </div>

      <div class="col-12">
        <label class="form-label">مدل‌لیست‌های این کالا</label>
        <select name="model_list_ids[]" id="pModels" class="form-select" multiple size="10" required>
          @foreach($modelLists as $model)
            <option value="{{ $model->id }}" data-name="{{ $model->model_name }}" @selected(collect(old('model_list_ids', []))->contains($model->id))>
              {{ $model->model_name }} (کد: {{ $model->code }})
            </option>
          @endforeach
        </select>
        <div class="form-text">چند مدل انتخاب کن. سیستم برای هر مدل × تعداد طرح، تنوع می‌سازد.</div>
      </div>

      <div class="col-md-4">
        <label class="form-label">تعداد طرح (برای هر مدل)</label>
        <input type="number" min="1" max="500" name="design_count" id="pDesignCount"
               class="form-control" value="{{ old('design_count', 1) }}" required>
      </div>

      <div class="col-md-8">
        <label class="form-label">خلاصه</label>
        <div class="alert alert-light border mb-0">
          <div class="small text-muted mb-1">فرمت کد تنوع (12 رقمی):</div>
          <div class="fw-bold">CCC + PPPPP + VVVV</div>
          <div class="small text-muted mt-2">
            CCC = کد 3 رقمی دسته‌بندی / PPPPP = ترتیب 5 رقمی کالا / VVVV = ترتیب 4 رقمی تنوع (از 0001)
          </div>
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
        نکته: ساخت نهایی تنوع‌ها و کدهای یکتا در سمت سرور انجام می‌شود.
      </div>
    </form>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
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

  function getSelectedModelNames(){
    return Array.from(modelsEl.selectedOptions).map(o => o.dataset.name || o.textContent.trim());
  }

  function updateCounts(){
    const m = modelsEl.selectedOptions.length;
    const d = parseInt(designEl.value || '0', 10);
    const total = m * d;

    calcModels.textContent = `مدل‌ها: ${m}`;
    calcDesigns.textContent = `طرح‌ها: ${d}`;
    calcTotal.textContent = `کل تنوع: ${total}`;
    return {m,d,total};
  }

  modelsEl.addEventListener('change', updateCounts);
  designEl.addEventListener('input', updateCounts);
  updateCounts();

  btnBuild.addEventListener('click', () => {
    const baseName = (nameEl.value || '').trim();
    const models = getSelectedModelNames();
    const d = parseInt(designEl.value || '0', 10);

    const {total} = updateCounts();

    previewList.innerHTML = '';
    if(!baseName || models.length === 0 || d < 1){
      previewBox.style.display = 'block';
      previewHint.textContent = 'برای پیش‌نمایش: نام کالا + حداقل یک مدل + تعداد طرح را وارد کنید.';
      return;
    }

    previewBox.style.display = 'block';
    previewHint.textContent = `نمونه نام‌گذاری: «${baseName} [مدل] طرح [شماره]»`;

    // برای جلوگیری از سنگین شدن UI، زیادها را محدود نمایش می‌دهیم
    const MAX_RENDER = 200;
    let rendered = 0;

    models.forEach(model => {
      for(let i=1;i<=d;i++){
        rendered++;
        if(rendered > MAX_RENDER) return;
        const row = document.createElement('div');
        row.className = 'preview-item';
        row.innerHTML = `<div>${baseName} ${model} طرح ${i}</div><div class="preview-meta">تنوع</div>`;
        previewList.appendChild(row);
      }
    });

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