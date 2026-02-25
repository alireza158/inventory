@extends('layouts.app')

@section('content')
<style>
  .preview-box{border:1px solid #e8edf3;border-radius:14px;background:#fff;}
  .preview-item{display:flex;justify-content:space-between;gap:10px;padding:8px 10px;border-top:1px solid #eef2f7;font-size:13px;}
  .preview-item:first-child{border-top:0;}
  .preview-meta{color:#6b7280;font-size:12px;}
  .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;letter-spacing:1px;}
  .soft-hint{background:#f8fafc;border:1px solid #e8edf3;border-radius:12px;padding:10px;}
  .hidden{display:none!important;}

  /* dropdown مدل لیست: جمع‌وجورتر */
  .dropdown-menu.model-menu{
    max-height: 300px;
    overflow: auto;
    border-radius: 14px;
    padding: 10px;
  }
  .model-item{
    display:flex;
    align-items:center;
    gap:8px;
    padding:6px 8px;
    border-radius:10px;
    font-size: 12.5px;
    line-height: 1.35;
  }
  .model-item:hover{ background:#f8fafc; }
  .model-item .form-check-input{
    margin: 0;
    flex: 0 0 auto;
  }
  .model-item .form-check-label{
    margin: 0;
    flex: 1 1 auto;
    cursor: pointer;
  }

  /* پیش‌نمایش: بدون اسکرول داخلی */
  .preview-list{
    overflow: visible;
    max-height: none;
  }
</style>

@php
  // ✅ PPPP پیش‌نمایش از کنترلر میاد (ممکنه لحظه ثبت تغییر کنه)
  $previewSeq4 = $previewSeq4 ?? '0000';
@endphp

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

      {{-- گزینه‌ها --}}
      <div class="col-12">
        <label class="form-label">ویژگی‌های تنوع</label>
        <div class="d-flex gap-4 flex-wrap">
          <label class="form-check">
            <input class="form-check-input" type="checkbox" name="use_models" id="useModels" {{ old('use_models') ? 'checked' : '' }}>
            <span class="form-check-label">مدل‌لیست دارد</span>
          </label>

          <label class="form-check">
            <input class="form-check-input" type="checkbox" name="use_designs" id="useDesigns" {{ old('use_designs') ? 'checked' : '' }}>
            <span class="form-check-label">طرح‌بندی دارد</span>
          </label>
        </div>

        <div class="soft-hint mt-2">
          فرمت کد اتومات هر تنوع: <span class="mono fw-bold">CCPPPPMMMDD</span><br>
          <span class="small text-muted">
            CC=کد ۲ رقمی دسته‌بندی | PPPP=شماره محصول ۴ رقمی | MMM=کد مدل‌لیست ۳ رقمی (اگر مدل‌لیست ندارد 000) | DD=کد طرح ۲ رقمی (اگر طرح ندارد 00)
          </span>
          <div class="small text-muted mt-1">
            نکته: PPPP در لحظه ثبت نهایی ممکن است تغییر کند (اگر همزمان کالای دیگری ثبت شود).
          </div>
        </div>
      </div>

      {{-- بخش مدل‌لیست --}}
      <div class="col-12 hidden" id="modelsSection">
        <div class="row g-3">

          <div class="col-md-4">
            <label class="form-label">گروه برند مدل‌لیست</label>
            <select name="model_brand_group" id="brandGroup" class="form-select">
              <option value="">انتخاب گروه</option>
              <option value="Samsung" @selected(old('model_brand_group')==='Samsung')>سامسونگ</option>
              <option value="Apple (iPhone)" @selected(old('model_brand_group')==='Apple (iPhone)')>آیفون</option>
              <option value="Xiaomi/Realme" @selected(old('model_brand_group')==='Xiaomi/Realme')>شیائومی و ریلمی</option>
              <option value="Huawei/Honor" @selected(old('model_brand_group')==='Huawei/Honor')>هواوی و هانر</option>
              <option value="سایر" @selected(old('model_brand_group')==='سایر')>سایر</option>
            </select>
            <div class="form-text">اول گروه را انتخاب کن.</div>
          </div>

          <div class="col-md-8">
            <label class="form-label">جستجو در مدل‌لیست‌ها</label>
            <input type="text" id="modelSearch" class="form-control" placeholder="مثلاً A16 یا iPhone 13 Pro...">
          </div>

          <div class="col-12">
            <label class="form-label">مدل‌لیست‌های این کالا</label>

            {{-- کشویی واقعی + چند انتخابی با checkbox --}}
            <div class="dropdown w-100">
              <button
                class="btn btn-outline-secondary w-100 d-flex justify-content-between align-items-center"
                type="button"
                id="modelsDropdownBtn"
                data-bs-toggle="dropdown"
                data-bs-auto-close="outside"
                aria-expanded="false">
                <span>انتخاب مدل‌لیست‌ها</span>
                <span class="badge text-bg-primary" id="modelsSelectedBadge">0</span>
              </button>

              <div class="dropdown-menu w-100 model-menu" aria-labelledby="modelsDropdownBtn">
                <div class="small text-muted mb-2" id="modelsDropdownHint">ابتدا گروه برند را انتخاب کنید.</div>

                @foreach($modelLists as $model)
                  <div
                    class="model-item"
                    data-brand="{{ $model->brand }}"
                    data-code="{{ $model->code }}"
                    data-name="{{ $model->model_name }}"
                    data-text="{{ ($model->brand ? ($model->brand.' ') : '') . $model->model_name . ' ' . $model->code }}"
                  >
                    <input
                      class="form-check-input model-check"
                      type="checkbox"
                      name="model_list_ids[]"
                      value="{{ $model->id }}"
                      id="ml{{ $model->id }}"
                      {{ collect(old('model_list_ids', []))->contains($model->id) ? 'checked' : '' }}
                    >
                    <label class="form-check-label" for="ml{{ $model->id }}">
                      {{ $model->brand ? ($model->brand.' - ') : '' }}{{ $model->model_name }}
                      <span class="text-muted">({{ $model->code }})</span>
                    </label>
                  </div>
                @endforeach
              </div>
            </div>

            <div class="form-text">می‌توانید چند مدل (مثلاً 10 تا) را تیک بزنید.</div>
          </div>

        </div>
      </div>

      {{-- بخش طرح --}}
      <div class="col-md-4 hidden" id="designSection">
        <label class="form-label">تعداد طرح</label>
        <input type="number" min="1" max="99" name="design_count" id="pDesignCount"
               class="form-control" value="{{ old('design_count', 1) }}">
        <div class="form-text">حداکثر 99 چون DD دو رقمی است.</div>

        <div class="mt-2" id="designNotesWrap"></div>
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
            <div class="fw-bold">پیش‌نمایش تنوع‌ها + بارکد</div>
            <div class="preview-meta" id="previewHint"></div>
          </div>

          <div class="preview-list mt-2" id="previewList"></div>

          {{-- pagination --}}
          <div class="d-flex justify-content-between align-items-center mt-2" id="previewPager" style="display:none;">
            <div class="small text-muted" id="pageInfo"></div>
            <nav>
              <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
            </nav>
          </div>
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
  const PREVIEW_PPPP = @json($previewSeq4); // 4 digits from controller

  const useModels = document.getElementById('useModels');
  const useDesigns = document.getElementById('useDesigns');

  const modelsSection = document.getElementById('modelsSection');
  const designSection = document.getElementById('designSection');

  const brandGroup = document.getElementById('brandGroup');
  const modelSearch = document.getElementById('modelSearch');

  const modelsSelectedBadge = document.getElementById('modelsSelectedBadge');
  const modelsDropdownHint = document.getElementById('modelsDropdownHint');

  const nameEl = document.getElementById('pName');
  const catEl = document.getElementById('pCategory');
  const designEl = document.getElementById('pDesignCount');
  const designNotesWrap = document.getElementById('designNotesWrap');

  const calcModels = document.getElementById('calcModels');
  const calcDesigns = document.getElementById('calcDesigns');
  const calcTotal = document.getElementById('calcTotal');

  const btnBuild = document.getElementById('btnBuild');
  const previewBox = document.getElementById('previewBox');
  const previewList = document.getElementById('previewList');
  const previewHint = document.getElementById('previewHint');

  const previewPager = document.getElementById('previewPager');
  const pageInfo = document.getElementById('pageInfo');
  const pagination = document.getElementById('pagination');

  const modelItems = Array.from(document.querySelectorAll('.model-item'));
  const modelChecks = Array.from(document.querySelectorAll('.model-check'));

  // pagination state
  const PER_PAGE = 25;
  let currentPage = 1;
  let lastBuildState = null; // {baseName, cat2, pppp, models[], d, total, useModels, useDesigns}

  function getCat2(){
    const opt = catEl.selectedOptions[0];
    const code = (opt?.dataset?.code || '').trim();
    // اگر دسته‌بندی کد نداشت یا غلط بود
    if(!/^\d{2}$/.test(code)) return '00';
    return code;
  }

  function brandMatch(group, optionBrand){
    const b = (optionBrand || '').trim();
    if(!group) return false;
    if(group === 'Samsung') return ['Samsung','سامسونگ'].includes(b);
    if(group === 'Apple (iPhone)') return ['Apple (iPhone)','Apple','iPhone','آیفون','اپل'].includes(b);
    if(group === 'Xiaomi/Realme') return ['Xiaomi/Realme','Xiaomi','Realme','شیائومی','ریلمی'].includes(b);
    if(group === 'Huawei/Honor') return ['Huawei/Honor','Huawei','Honor','هواوی','هانر'].includes(b);
    if(group === 'سایر') return b === '' || ['سایر','Other'].includes(b);
    return false;
  }

  function selectedModels(){
    return modelChecks
      .filter(ch => ch.checked)
      .map(ch => {
        const item = ch.closest('.model-item');
        return {
          id: ch.value,
          code: (item?.dataset.code || '000').padStart(3,'0').slice(-3),
          name: item?.dataset.name || '',
          brand: item?.dataset.brand || ''
        };
      });
  }

  function updateSelectedBadge(){
    modelsSelectedBadge.textContent = String(selectedModels().length);
  }

  function filterModels(){
    if(!useModels.checked) return;

    const group = brandGroup.value;
    const q = (modelSearch.value || '').trim().toLowerCase();

    let visible = 0;

    modelItems.forEach(item => {
      const okBrand = group ? brandMatch(group, item.dataset.brand) : false;
      const txt = (item.dataset.text || '').toLowerCase();
      const okSearch = q ? txt.includes(q) : true;

      const show = okBrand && okSearch;
      item.style.display = show ? '' : 'none';
      if(show) visible++;
    });

    if(!group){
      modelsDropdownHint.textContent = 'ابتدا گروه برند را انتخاب کنید.';
    } else {
      modelsDropdownHint.textContent = visible ? `نمایش ${visible} مدل` : 'مدلی پیدا نشد.';
    }
  }



  const oldDesignNotes = @json(array_values(old('design_notes', [])));

  function getDesignNote(index){
    const input = document.querySelector(`[name="design_notes[]"][data-design-index="${index}"]`);
    return (input?.value || '').trim();
  }

  function getDesignTitle(index){
    const note = getDesignNote(index);
    return note ? `طرح ${index} (${note})` : `طرح ${index}`;
  }

  function renderDesignNotesInputs(){
    if(!designNotesWrap) return;

    const count = useDesigns.checked ? Math.max(parseInt(designEl.value || '0', 10), 0) : 0;
    designNotesWrap.innerHTML = '';

    if(count < 1){
      return;
    }

    const title = document.createElement('div');
    title.className = 'form-text mb-1';
    title.textContent = 'توضیح هر طرح را وارد کنید تا در نام تنوع ثبت شود (اختیاری).';
    designNotesWrap.appendChild(title);

    for(let i=1; i<=count; i++){
      const wrap = document.createElement('div');
      wrap.className = 'input-group input-group-sm mt-1';

      const span = document.createElement('span');
      span.className = 'input-group-text';
      span.textContent = `طرح ${i}`;

      const input = document.createElement('input');
      input.type = 'text';
      input.className = 'form-control';
      input.name = 'design_notes[]';
      input.dataset.designIndex = String(i);
      input.placeholder = 'مثلاً مشکی سالیوان';
      input.maxLength = 120;
      input.value = oldDesignNotes[i - 1] || '';

      wrap.appendChild(span);
      wrap.appendChild(input);
      designNotesWrap.appendChild(wrap);
    }
  }

  function updateCounts(){
    const m = useModels.checked ? selectedModels().length : 0;
    const d = useDesigns.checked ? parseInt(designEl.value || '0', 10) : 0;

    let total = 1;
    if(useModels.checked && useDesigns.checked) total = m * Math.max(d,0);
    else if(useModels.checked) total = m;
    else if(useDesigns.checked) total = Math.max(d,0);

    calcModels.textContent = `مدل‌ها: ${m}`;
    calcDesigns.textContent = `طرح‌ها: ${d}`;
    calcTotal.textContent = `کل تنوع: ${total}`;

    updateSelectedBadge();
    return {m,d,total};
  }

  function toggleSections(){
    modelsSection.classList.toggle('hidden', !useModels.checked);
    designSection.classList.toggle('hidden', !useDesigns.checked);
    filterModels();
    renderDesignNotesInputs();
    updateCounts();
  }

  // code builder: CCPP PPM MMDD => CCPP PP + MMM + DD
  function code11(cat2, pppp, model3, design2){
    return `${cat2}${pppp}${model3}${design2}`;
  }
>>>>>>> main

  // generate item by index (without building full list)
  function getItemByIndex(state, idx){
    const {baseName, models, d, useModels, useDesigns, cat2, pppp} = state;

    let model = null;
    let designNo = 0;

    if(useModels && useDesigns){
      const modelIndex = Math.floor(idx / d);
      const designIndex = (idx % d) + 1;
      model = models[modelIndex];
      designNo = designIndex;
    } else if(useModels && !useDesigns){
      model = models[idx];
      designNo = 0;
    } else if(!useModels && useDesigns){
      model = null;
      designNo = idx + 1;
    } else {
      model = null;
      designNo = 0;
    }

    const model3 = useModels ? (model?.code || '000') : '000';
    const design2 = useDesigns ? String(designNo).padStart(2,'0') : '00';
    const barcode = code11(cat2, pppp, model3, design2);

    // label
    let label = baseName;
    if(useModels && model) label += ` ${model.name}`;
    if(useDesigns && designNo > 0) label += ` ${getDesignTitle(designNo)}`;

    return {label, barcode};
  }

  function renderPagination(totalItems, page){
    const totalPages = Math.max(1, Math.ceil(totalItems / PER_PAGE));
    currentPage = Math.min(Math.max(1, page), totalPages);

    // info
    pageInfo.textContent = `صفحه ${currentPage} از ${totalPages} — نمایش ${PER_PAGE} مورد در هر صفحه`;

    // build buttons
    pagination.innerHTML = '';

    const makeLi = (label, disabled, active, onClick) => {
      const li = document.createElement('li');
      li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
      const a = document.createElement('a');
      a.className = 'page-link';
      a.href = '#';
      a.textContent = label;
      a.addEventListener('click', (e) => {
        e.preventDefault();
        if(disabled) return;
        onClick();
      });
      li.appendChild(a);
      return li;
    };

    pagination.appendChild(makeLi('قبلی', currentPage === 1, false, () => renderPage(currentPage - 1)));

    // limited range
    const windowSize = 5;
    let start = Math.max(1, currentPage - Math.floor(windowSize/2));
    let end = Math.min(totalPages, start + windowSize - 1);
    start = Math.max(1, end - windowSize + 1);

    if(start > 1){
      pagination.appendChild(makeLi('1', false, currentPage===1, () => renderPage(1)));
      if(start > 2){
        const li = document.createElement('li');
        li.className = 'page-item disabled';
        li.innerHTML = `<span class="page-link">…</span>`;
        pagination.appendChild(li);
      }
    }

    for(let p=start;p<=end;p++){
      pagination.appendChild(makeLi(String(p), false, p===currentPage, () => renderPage(p)));
    }

    if(end < totalPages){
      if(end < totalPages - 1){
        const li = document.createElement('li');
        li.className = 'page-item disabled';
        li.innerHTML = `<span class="page-link">…</span>`;
        pagination.appendChild(li);
      }
      pagination.appendChild(makeLi(String(totalPages), false, currentPage===totalPages, () => renderPage(totalPages)));
    }

    pagination.appendChild(makeLi('بعدی', currentPage === totalPages, false, () => renderPage(currentPage + 1)));
  }

  function renderPage(page){
    if(!lastBuildState) return;

    const total = lastBuildState.total;
    const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
    const p = Math.min(Math.max(1, page), totalPages);

    previewList.innerHTML = '';

    const startIdx = (p - 1) * PER_PAGE;
    const endIdx = Math.min(total, startIdx + PER_PAGE);

    for(let i=startIdx; i<endIdx; i++){
      const item = getItemByIndex(lastBuildState, i);
      const row = document.createElement('div');
      row.className = 'preview-item';
      row.innerHTML = `<div>${item.label}</div><div class="preview-meta mono">${item.barcode}</div>`;
      previewList.appendChild(row);
    }

    previewPager.style.display = total > PER_PAGE ? 'flex' : 'none';
    renderPagination(total, p);
  }

  // Events
  useModels.addEventListener('change', toggleSections);
  useDesigns.addEventListener('change', toggleSections);
  brandGroup.addEventListener('change', () => { filterModels(); updateCounts(); });
  modelSearch.addEventListener('input', filterModels);
  designEl.addEventListener('input', () => { renderDesignNotesInputs(); updateCounts(); });
  modelChecks.forEach(ch => ch.addEventListener('change', updateCounts));
  catEl.addEventListener('change', updateCounts);

  toggleSections();
  renderDesignNotesInputs();

  // Build Preview
  btnBuild.addEventListener('click', () => {
    const baseName = (nameEl.value || '').trim();
    const {m,d,total} = updateCounts();
    const models = selectedModels();
    const cat2 = getCat2();
    const pppp = PREVIEW_PPPP || '0000';

    previewBox.style.display = 'block';

    // validations
    if(!baseName){
      previewHint.textContent = 'نام کالا را وارد کنید.';
      previewList.innerHTML = '';
      previewPager.style.display = 'none';
      return;
    }

    if(useModels.checked){
      if(!brandGroup.value){
        previewHint.textContent = 'برای مدل‌لیست: ابتدا گروه برند را انتخاب کنید.';
        previewList.innerHTML = '';
        previewPager.style.display = 'none';
        return;
      }
      if(m < 1){
        previewHint.textContent = 'برای مدل‌لیست: حداقل یک مدل انتخاب کنید.';
        previewList.innerHTML = '';
        previewPager.style.display = 'none';
        return;
      }
    }

    if(useDesigns.checked && (!d || d < 1)){
      previewHint.textContent = 'برای طرح‌بندی: تعداد طرح باید حداقل 1 باشد.';
      previewList.innerHTML = '';
      previewPager.style.display = 'none';
      return;
    }

    // store state for pagination render
    lastBuildState = {
      baseName,
      cat2,
      pppp,
      models,
      d: useDesigns.checked ? d : 0,
      total,
      useModels: useModels.checked,
      useDesigns: useDesigns.checked
    };

    previewHint.textContent = `کل تنوع: ${total} — نمایش کد/بارکد هر تنوع (PPPP پیش‌نمایش: ${pppp})`;

    // render first page
    renderPage(1);
  });
});
</script>
@endsection
