@extends('layouts.app')

@section('content')
<style>
  :root{
    --brd:#e8edf3;
    --muted:#6b7280;
    --soft:#f8fafc;
    --soft2:#f3f6fb;
    --primary:#2563eb;
  }

  .preview-box{border:1px solid var(--brd);border-radius:14px;background:#fff;}
  .preview-item{display:flex;justify-content:space-between;gap:10px;padding:8px 10px;border-top:1px solid #eef2f7;font-size:13px;}
  .preview-item:first-child{border-top:0;}
  .preview-meta{color:var(--muted);font-size:12px;}
  .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;letter-spacing:1px;}
  .soft-hint{background:var(--soft);border:1px solid var(--brd);border-radius:12px;padding:10px;}
  .hidden{display:none!important;}

  /* ===== مدل لیست ردیفی ===== */
  .model-panel{
    border:1px solid var(--brd);
    border-radius:16px;
    background:#fff;
    overflow:hidden;
  }
  .model-panel-head{
    padding:10px 12px;
    background:linear-gradient(0deg, #fff, var(--soft2));
    border-bottom:1px solid var(--brd);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    flex-wrap:wrap;
  }
  .model-panel-title{
    font-weight:700;
    font-size:13px;
  }
  .model-panel-actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    align-items:center;
  }
  .model-panel-actions .btn{
    padding:6px 10px;
    border-radius:10px;
    font-size:12px;
  }
  .model-hint{
    font-size:12px;
    color:var(--muted);
  }

  .model-list{
    max-height:360px;
    overflow:auto;
  }

  .model-row{
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px 12px;
    border-top:1px solid #eef2f7;
    cursor:pointer;
    user-select:none;
  }
  .model-row:first-child{border-top:0;}
  .model-row:hover{background:var(--soft);}
  .model-row.is-checked{background:#eef6ff;}
  .model-row .form-check-input{
    margin:0;
    flex:0 0 auto;
  }
  .model-row-main{
    flex:1 1 auto;
    min-width:0;
    display:flex;
    flex-direction:column;
    gap:2px;
  }
  .model-row-name{
    font-size:13px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }
  .model-row-sub{
    font-size:12px;
    color:var(--muted);
    display:flex;
    gap:8px;
    flex-wrap:wrap;
  }
  .pill{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:2px 8px;
    border:1px solid var(--brd);
    border-radius:999px;
    background:#fff;
    font-size:12px;
    color:#111827;
  }
  .pill .mono{font-size:11px;color:#334155;}
  .pill-btn{
    border:0;
    background:transparent;
    color:#64748b;
    font-size:14px;
    line-height:1;
    padding:0 2px;
    cursor:pointer;
  }
  .pill-btn:hover{color:#0f172a;}

  .selected-chips{
    padding:10px 12px;
    border-top:1px solid var(--brd);
    background:#fff;
  }
  .selected-chips-title{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:8px;
  }
  .selected-chips-title span{
    font-size:12px;
    color:var(--muted);
  }
  .chips-wrap{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
  }

  /* پیش‌نمایش: بدون اسکرول داخلی */
  .preview-list{
    overflow: visible;
    max-height: none;
  }
</style>

@php
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

      {{-- بخش مدل‌لیست (ردیفی) --}}
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
            <div class="form-text">اگر گروه را انتخاب نکنی، فقط انتخاب‌شده‌ها نمایش داده می‌شوند.</div>
          </div>

          <div class="col-md-8">
            <label class="form-label">جستجو در مدل‌لیست‌ها</label>
            <input type="text" id="modelSearch" class="form-control" placeholder="مثلاً A16 یا iPhone 13 Pro...">
          </div>

          <div class="col-12">
            <div class="model-panel">
              <div class="model-panel-head">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <div class="model-panel-title">مدل‌لیست‌های این کالا</div>
                  <span class="badge text-bg-primary" id="modelsSelectedBadge">0</span>
                  <span class="model-hint" id="modelsHint">ابتدا گروه برند را انتخاب کنید.</span>
                </div>

                <div class="model-panel-actions">
                  <button type="button" class="btn btn-outline-secondary" id="btnOnlySelected">فقط انتخاب‌شده‌ها</button>
                  <button type="button" class="btn btn-outline-primary" id="btnSelectAllVisible">انتخاب همهٔ مواردِ قابل‌مشاهده</button>
                  <button type="button" class="btn btn-outline-danger" id="btnClearAll">پاک کردن انتخاب‌ها</button>
                </div>
              </div>

              <div class="model-list" id="modelList">
                @foreach($modelLists as $model)
                  <div
                    class="model-row model-item"
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

                    <div class="model-row-main">
                      <div class="model-row-name">
                        {{ $model->brand ? ($model->brand.' - ') : '' }}{{ $model->model_name }}
                      </div>
                      <div class="model-row-sub">
                        <span class="pill">کد: <span class="mono">{{ $model->code }}</span></span>
                        @if($model->brand)
                          <span class="pill">برند: {{ $model->brand }}</span>
                        @endif
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>

              <div class="selected-chips">
                <div class="selected-chips-title">
                  <span>مدل‌های انتخاب‌شده (برای حذف روی × بزن)</span>
                  <span id="selectedCountText">0 مورد</span>
                </div>
                <div class="chips-wrap" id="selectedChips"></div>
              </div>
            </div>

            <div class="form-text">برای سرعت: روی ردیف کلیک کن تا تیک بخورد/برداشته شود.</div>
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
  const PREVIEW_PPPP = @json($previewSeq4);

  const useModels = document.getElementById('useModels');
  const useDesigns = document.getElementById('useDesigns');

  const modelsSection = document.getElementById('modelsSection');
  const designSection = document.getElementById('designSection');

  const brandGroup = document.getElementById('brandGroup');
  const modelSearch = document.getElementById('modelSearch');

  const modelsSelectedBadge = document.getElementById('modelsSelectedBadge');
  const modelsHint = document.getElementById('modelsHint');

  const btnOnlySelected = document.getElementById('btnOnlySelected');
  const btnSelectAllVisible = document.getElementById('btnSelectAllVisible');
  const btnClearAll = document.getElementById('btnClearAll');

  const selectedChips = document.getElementById('selectedChips');
  const selectedCountText = document.getElementById('selectedCountText');

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
  let lastBuildState = null;

  // مدل‌لیست state
  let onlySelected = false;

  function getCat2(){
    const opt = catEl.selectedOptions[0];
    const code = (opt?.dataset?.code || '').trim();
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
    const count = selectedModels().length;
    modelsSelectedBadge.textContent = String(count);
    selectedCountText.textContent = `${count} مورد`;
  }

  function paintCheckedRows(){
    modelItems.forEach(item => {
      const ch = item.querySelector('.model-check');
      item.classList.toggle('is-checked', !!ch?.checked);
    });
  }

  function renderSelectedChips(){
    const models = selectedModels();
    selectedChips.innerHTML = '';

    if(models.length === 0){
      const empty = document.createElement('div');
      empty.className = 'text-muted small';
      empty.textContent = 'چیزی انتخاب نشده.';
      selectedChips.appendChild(empty);
      return;
    }

    models.forEach(m => {
      const pill = document.createElement('span');
      pill.className = 'pill';
      pill.innerHTML = `
        <span>${m.name}</span>
        <span class="mono">${m.code}</span>
        <button type="button" class="pill-btn" aria-label="remove">×</button>
      `;
      pill.querySelector('button').addEventListener('click', (e) => {
        e.preventDefault();
        const ch = document.querySelector(\`.model-check[value="\${m.id}"]\`);
        if(ch){
          ch.checked = false;
          ch.dispatchEvent(new Event('change', {bubbles:true}));
        }
      });
      selectedChips.appendChild(pill);
    });
  }

  function filterModels(){
    if(!useModels.checked) return;

    const group = (brandGroup.value || '').trim();
    const q = (modelSearch.value || '').trim().toLowerCase();

    let visible = 0;
    let matched = 0;

    modelItems.forEach(item => {
      const txt = (item.dataset.text || '').toLowerCase();
      const ch = item.querySelector('.model-check');
      const isChecked = !!ch?.checked;

      // اگر گروه انتخاب نشده، فقط انتخاب‌شده‌ها را نشان بده
      let okBrand = group ? brandMatch(group, item.dataset.brand) : isChecked;

      const okSearch = q ? txt.includes(q) : true;

      const okOnlySelected = onlySelected ? isChecked : true;

      const show = okBrand && okSearch && okOnlySelected;

      item.style.display = show ? '' : 'none';
      if(show) visible++;

      // صرفاً برای متن راهنما: تعداد گزینه‌های match (بدون onlySelected)
      const showForMatchCount = okBrand && okSearch;
      if(showForMatchCount) matched++;
    });

    // hint
    if(!group){
      modelsHint.textContent = selectedModels().length
        ? 'گروه انتخاب نشده؛ فقط مدل‌های تیک‌خورده نمایش داده می‌شوند.'
        : 'ابتدا گروه برند را انتخاب کنید.';
    } else {
      if(matched === 0) modelsHint.textContent = 'مدلی با این فیلتر پیدا نشد.';
      else modelsHint.textContent = onlySelected
        ? `نمایش ${visible} مورد از انتخاب‌شده‌ها`
        : `نمایش ${visible} مدل`;
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

    if(count < 1) return;

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
    paintCheckedRows();
    renderSelectedChips();
    return {m,d,total};
  }

  function toggleSections(){
    modelsSection.classList.toggle('hidden', !useModels.checked);
    designSection.classList.toggle('hidden', !useDesigns.checked);
    filterModels();
    renderDesignNotesInputs();
    updateCounts();
  }

  // code builder
  function code11(cat2, pppp, model3, design2){
    return `${cat2}${pppp}${model3}${design2}`;
  }

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

    let label = baseName;
    if(useModels && model) label += ` ${model.name}`;
    if(useDesigns && designNo > 0) label += ` ${getDesignTitle(designNo)}`;

    return {label, barcode};
  }

  function renderPagination(totalItems, page){
    const totalPages = Math.max(1, Math.ceil(totalItems / PER_PAGE));
    currentPage = Math.min(Math.max(1, page), totalPages);

    pageInfo.textContent = `صفحه ${currentPage} از ${totalPages} — نمایش ${PER_PAGE} مورد در هر صفحه`;

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

  // ===== Events =====
  useModels.addEventListener('change', toggleSections);
  useDesigns.addEventListener('change', toggleSections);

  brandGroup.addEventListener('change', () => { filterModels(); updateCounts(); });
  modelSearch.addEventListener('input', filterModels);

  designEl.addEventListener('input', () => { renderDesignNotesInputs(); updateCounts(); });

  catEl.addEventListener('change', updateCounts);

  // کلیک روی ردیف = تیک/آن‌تیک
  modelItems.forEach(item => {
    item.addEventListener('click', (e) => {
      // اگر مستقیم روی checkbox کلیک شد، خودش مدیریت می‌کنه
      if(e.target && (e.target.classList.contains('model-check'))) return;

      const ch = item.querySelector('.model-check');
      if(!ch) return;

      ch.checked = !ch.checked;
      ch.dispatchEvent(new Event('change', {bubbles:true}));
    });
  });

  modelChecks.forEach(ch => ch.addEventListener('change', () => {
    filterModels();
    updateCounts();
  }));

  btnOnlySelected.addEventListener('click', () => {
    onlySelected = !onlySelected;
    btnOnlySelected.classList.toggle('btn-outline-secondary', !onlySelected);
    btnOnlySelected.classList.toggle('btn-secondary', onlySelected);
    btnOnlySelected.textContent = onlySelected ? 'نمایش همه' : 'فقط انتخاب‌شده‌ها';
    filterModels();
  });

  btnSelectAllVisible.addEventListener('click', () => {
    if(!useModels.checked) return;

    let changed = 0;
    modelItems.forEach(item => {
      if(item.style.display === 'none') return;
      const ch = item.querySelector('.model-check');
      if(ch && !ch.checked){
        ch.checked = true;
        changed++;
      }
    });

    if(changed){
      updateCounts();
      filterModels();
    }
  });

  btnClearAll.addEventListener('click', () => {
    let changed = 0;
    modelChecks.forEach(ch => {
      if(ch.checked){
        ch.checked = false;
        changed++;
      }
    });
    if(changed){
      updateCounts();
      filterModels();
    }
  });

  toggleSections();
  renderDesignNotesInputs();
  updateCounts();
  filterModels();

  // ===== Build Preview =====
  btnBuild.addEventListener('click', () => {
    const baseName = (nameEl.value || '').trim();
    const {m,d,total} = updateCounts();
    const models = selectedModels();
    const cat2 = getCat2();
    const pppp = PREVIEW_PPPP || '0000';

    previewBox.style.display = 'block';

    if(!baseName){
      previewHint.textContent = 'نام کالا را وارد کنید.';
      previewList.innerHTML = '';
      previewPager.style.display = 'none';
      return;
    }

    if(useModels.checked){
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
    renderPage(1);
  });
});
</script>
@endsection