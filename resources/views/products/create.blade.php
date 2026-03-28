@extends('layouts.app')

@section('content')
@php
  $categories = collect($categories ?? []);
  $modelLists = collect($modelLists ?? []);
  $previewSeq4 = $previewSeq4 ?? '----';
@endphp

<div class="card shadow-sm">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">افزودن کالای جدید</h5>
      <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">بازگشت</a>
    </div>

    <div class="alert alert-info py-2">
      در این مرحله فقط مشخصات پایه کالا ثبت می‌شود (نام، دسته‌بندی، مدل/طرح).
      <br>
      قیمت خرید/فروش و موجودی را بعداً در بخش <b>خرید کالا</b> ثبت کنید.
    </div>

    <form method="POST" action="{{ route('products.store') }}" id="productCreateForm" class="row g-3">
      @csrf

      <div class="col-md-4">
        <label class="form-label">دسته‌بندی</label>
        <select name="category_id" class="form-select" required>
          <option value="">انتخاب کنید...</option>
          @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>
              {{ $cat->name }} ({{ $cat->code }})
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-md-5">
        <label class="form-label">نام کالا</label>
        <input
          type="text"
          name="name"
          class="form-control"
          value="{{ old('name') }}"
          placeholder="مثلاً گوشی موبایل ..."
          required
        >
      </div>

      <div class="col-md-3">
        <label class="form-label d-block">وضعیت فروش</label>
        <input type="hidden" name="is_sellable" value="0">
        <div class="form-check form-switch mt-2">
          <input class="form-check-input" type="checkbox" id="isSellable" name="is_sellable" value="1" @checked(old('is_sellable', true))>
          <label class="form-check-label" for="isSellable">قابل فروش باشد</label>
        </div>
      </div>

      <div class="col-12"><hr class="my-1"></div>

      <div class="col-md-4">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="useModels" name="use_models" value="1" @checked(old('use_models'))>
          <label class="form-check-label" for="useModels">این کالا مدل‌لیست دارد</label>
        </div>
      </div>

      <div class="col-md-4">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="useDesigns" name="use_designs" value="1" @checked(old('use_designs'))>
          <label class="form-check-label" for="useDesigns">این کالا طرح‌بندی دارد</label>
        </div>
      </div>

      <div class="col-md-4 text-md-end">
        <div class="text-muted small">پیش‌نمایش کد داخلی کالا: <b>{{ $previewSeq4 }}</b></div>
      </div>

      <div class="col-12" id="modelsBox" style="display:none;">
        <div class="border rounded p-3 bg-light-subtle">
          <div class="row g-3 align-items-end">
            <div class="col-md-4">
              <label class="form-label">گروه برند</label>
              <select name="model_brand_group" id="modelBrandGroup" class="form-select">
                <option value="">انتخاب کنید...</option>
                @foreach($modelLists->pluck('brand')->filter()->unique()->sort()->values() as $brand)
                  <option value="{{ $brand }}" @selected(old('model_brand_group') == $brand)>{{ $brand }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-8">
              <label class="form-label">مدل‌ها</label>
              <select name="model_list_ids[]" id="modelListIds" class="form-select" multiple size="8">
                @foreach($modelLists as $m)
                  <option
                    value="{{ $m->id }}"
                    data-brand="{{ $m->brand }}"
                    @selected(in_array($m->id, old('model_list_ids', [])))
                  >
                    {{ $m->brand ? ($m->brand . ' - ') : '' }}{{ $m->model_name }} ({{ $m->code }})
                  </option>
                @endforeach
              </select>
              <div class="form-text">برای انتخاب چند مدل از Ctrl/Command استفاده کنید.</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12" id="designsBox" style="display:none;">
        <div class="border rounded p-3 bg-light-subtle">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">تعداد طرح</label>
              <input type="number" min="1" max="99" name="design_count" id="designCount" class="form-control" value="{{ old('design_count') }}" placeholder="مثلاً 3">
            </div>
            <div class="col-md-8">
              <label class="form-label">توضیح هر طرح (اختیاری)</label>
              <div id="designNotesWrap" class="d-grid gap-2"></div>
              <div class="form-text">مثال: مشکی، طلایی، نقره‌ای ...</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">ثبت کالا</button>
      </div>
    </form>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const useModels = document.getElementById('useModels');
    const useDesigns = document.getElementById('useDesigns');
    const modelsBox = document.getElementById('modelsBox');
    const designsBox = document.getElementById('designsBox');
    const modelBrandGroup = document.getElementById('modelBrandGroup');
    const modelListIds = document.getElementById('modelListIds');
    const designCount = document.getElementById('designCount');
    const designNotesWrap = document.getElementById('designNotesWrap');

    const oldDesignNotes = @json(array_values(old('design_notes', [])));

    function toggleSections() {
      modelsBox.style.display = useModels.checked ? 'block' : 'none';
      designsBox.style.display = useDesigns.checked ? 'block' : 'none';

      modelBrandGroup.required = useModels.checked;
      modelListIds.required = useModels.checked;
      designCount.required = useDesigns.checked;

      if (!useModels.checked) {
        modelBrandGroup.value = '';
        Array.from(modelListIds.options).forEach(opt => { opt.selected = false; opt.hidden = false; });
      }

      if (!useDesigns.checked) {
        designCount.value = '';
        designNotesWrap.innerHTML = '';
      }
    }

    function filterModelsByBrand() {
      const selectedBrand = modelBrandGroup.value;
      Array.from(modelListIds.options).forEach(opt => {
        const show = !selectedBrand || opt.dataset.brand === selectedBrand;
        opt.hidden = !show;
        if (!show) opt.selected = false;
      });
    }

    function renderDesignNotes() {
      const count = Math.max(0, Math.min(99, parseInt(designCount.value || '0', 10) || 0));
      designNotesWrap.innerHTML = '';

      for (let i = 0; i < count; i++) {
        const row = document.createElement('div');
        row.innerHTML = `
          <input
            type="text"
            class="form-control"
            name="design_notes[${i}]"
            placeholder="طرح ${i + 1} (اختیاری)"
            value="${(oldDesignNotes[i] || '').replace(/"/g, '&quot;')}"
          >
        `;
        designNotesWrap.appendChild(row);
      }
    }

    useModels.addEventListener('change', toggleSections);
    useDesigns.addEventListener('change', toggleSections);
    modelBrandGroup.addEventListener('change', filterModelsByBrand);
    designCount.addEventListener('input', renderDesignNotes);

    toggleSections();
    filterModelsByBrand();
    if (useDesigns.checked) renderDesignNotes();
  });
</script>
@endsection
