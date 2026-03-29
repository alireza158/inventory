@extends('layouts.app')

@section('content')
@php
    $brandGroups = $modelLists->pluck('brand')->filter()->unique()->sort()->values();

    $categoriesJs = $categories->map(function ($c) {
        return [
            'id'   => $c->id,
            'name' => $c->name,
            'code' => $c->code,
        ];
    })->values();

    $modelListsJs = $modelLists->map(function ($m) {
        return [
            'id'         => $m->id,
            'brand'      => $m->brand,
            'model_name' => $m->model_name,
            'code'       => $m->code,
        ];
    })->values();

    $oldModelIds = array_map('intval', old('model_list_ids', []));
    $oldDesignNotes = array_values(old('design_notes', []));
@endphp

<style>
    :root{
        --brd:#e8edf3;
        --muted:#6b7280;
        --soft:#f8fafc;
        --soft2:#f3f6fb;
        --ok:#16a34a;
        --info:#2563eb;
        --warn:#f59e0b;
    }
    .card-soft{
        border:1px solid var(--brd);
        border-radius:16px;
        background:#fff;
    }
    .section-head{
        padding:12px 14px;
        border-bottom:1px solid var(--brd);
        background:linear-gradient(0deg,#fff,var(--soft2));
        border-top-left-radius:16px;
        border-top-right-radius:16px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        flex-wrap:wrap;
    }
    .section-title{
        font-weight:800;
        font-size:14px;
    }
    .muted{
        color:var(--muted);
        font-size:12px;
    }
    .chip{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:6px 10px;
        border-radius:999px;
        border:1px solid var(--brd);
        background:var(--soft);
        font-size:12px;
    }
    .mono{
        font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;
        letter-spacing:1px;
    }
    .preview-box{
        border:1px dashed #cfe0ff;
        background:#f8fbff;
        border-radius:16px;
        padding:14px;
    }
    .preview-code{
        font-size:18px;
        font-weight:900;
        letter-spacing:1px;
    }
    .helper-box{
        border:1px solid var(--brd);
        border-radius:16px;
        background:#fff;
        padding:12px;
    }
    .design-note-item{
        border:1px dashed #dbe6f3;
        border-radius:12px;
        padding:10px;
        background:#fff;
    }
    .examples-list{
        margin:0;
        padding-right:18px;
        font-size:13px;
    }
    .examples-list li{
        margin-bottom:4px;
    }
    .sticky-actions{
        position:sticky;
        bottom:16px;
        z-index:5;
        background:rgba(255,255,255,.9);
        backdrop-filter:blur(6px);
        border:1px solid var(--brd);
        border-radius:16px;
        padding:12px;
    }
</style>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">افزودن کالا</h5>
            <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">بازگشت</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-bold mb-2">لطفاً خطاهای زیر را بررسی کن:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="alert alert-info">
            در این بخش فقط <b>تعریف کالا و تنوع‌ها</b> انجام می‌شود.
            <br>
            <b>موجودی، قیمت خرید و قیمت فروش</b> در بخش <b>خرید کالا</b> ثبت می‌شوند.
        </div>

        <form method="POST" action="{{ route('products.store') }}" id="productCreateForm" class="row g-3">
            @csrf

            {{-- اطلاعات پایه --}}
            <div class="col-12">
                <div class="card-soft">
                    <div class="section-head">
                        <div>
                            <div class="section-title">اطلاعات پایه کالا</div>
                            <div class="muted">در این بخش فقط دسته‌بندی و نام کالا ثبت می‌شود</div>
                        </div>

                        <div class="chip">
                            <span class="muted">پیش‌نمایش شماره کالا:</span>
                            <span class="mono" id="previewSeq4">{{ $previewSeq4 }}</span>
                        </div>
                    </div>

                    <div class="p-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">دسته‌بندی</label>
                                <select name="category_id" id="categoryId" class="form-select" required>
                                    <option value="">انتخاب کنید...</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>
                                            {{ $cat->name }} @if(!empty($cat->code)) ({{ $cat->code }}) @endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">کد ۲ رقمی دسته‌بندی از همین مورد خوانده می‌شود.</div>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label">نام کالا</label>
                                <input type="text"
                                       name="name"
                                       class="form-control"
                                       value="{{ old('name') }}"
                                       required
                                       maxlength="255"
                                       placeholder="مثلاً کیف دوشی چرمی">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label d-block">وضعیت فروش</label>
                                <input type="hidden" name="is_sellable" value="0">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="isSellable" name="is_sellable" value="1" @checked(old('is_sellable', 1))>
                                    <label class="form-check-label" for="isSellable">این کالا قابل فروش باشد</label>
                                </div>
                            </div>
                        </div>

                        <div class="preview-box mt-3">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div>
                                    <div class="muted mb-1">پیش‌نمایش کد ۶ رقمی کالا</div>
                                    <div class="preview-code mono" id="productCodePreview">--{{ $previewSeq4 }}</div>
                                </div>

                                <div>
                                    <div class="muted mb-1">الگوی کد تنوع ۱۱ رقمی</div>
                                    <div class="preview-code mono" id="variantPatternPreview">CC{{ $previewSeq4 }}MMMDD</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ساختار تنوع‌ها --}}
            <div class="col-12">
                <div class="card-soft">
                    <div class="section-head">
                        <div>
                            <div class="section-title">ساختار تنوع‌ها</div>
                            <div class="muted">مشخص کن کالا مدل دارد یا طرح دارد یا هر دو</div>
                        </div>
                    </div>

                    <div class="p-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="helper-box h-100">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" value="1" id="useModels" name="use_models" @checked(old('use_models'))>
                                        <label class="form-check-label fw-bold" for="useModels">این کالا مدل‌لیست دارد</label>
                                    </div>
                                    <div class="muted mt-2">
                                        مثال: یک کالا برای چند مدل گوشی یا چند مدل دستگاه تعریف می‌شود.
                                    </div>

                                    <div id="modelsSection" class="mt-3" style="display:none;">
                                        <div class="mb-3">
                                            <label class="form-label">گروه برند</label>
                                            <select name="model_brand_group" id="modelBrandGroup" class="form-select">
                                                <option value="">انتخاب برند...</option>
                                                @foreach($brandGroups as $brand)
                                                    <option value="{{ $brand }}" @selected(old('model_brand_group') == $brand)>{{ $brand }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label">مدل‌ها</label>
                                            <select name="model_list_ids[]" id="modelListIds" class="form-select" multiple size="10">
                                            </select>
                                            <div class="form-text">
                                                با نگه داشتن Ctrl یا Cmd می‌توانی چند مدل را انتخاب کنی.
                                            </div>
                                        </div>

                                        <div class="chip mt-2">
                                            <span class="muted">تعداد مدل‌های انتخاب‌شده:</span>
                                            <span id="selectedModelsCount">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="helper-box h-100">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" value="1" id="useDesigns" name="use_designs" @checked(old('use_designs'))>
                                        <label class="form-check-label fw-bold" for="useDesigns">این کالا طرح‌بندی دارد</label>
                                    </div>
                                    <div class="muted mt-2">
                                        مثال: طرح ۱، طرح ۲، طرح ۳ یا طرح‌های چاپی مختلف.
                                    </div>

                                    <div id="designsSection" class="mt-3" style="display:none;">
                                        <div class="mb-3">
                                            <label class="form-label">تعداد طرح</label>
                                            <input type="number"
                                                   min="1"
                                                   max="99"
                                                   name="design_count"
                                                   id="designCount"
                                                   class="form-control"
                                                   value="{{ old('design_count', 1) }}">
                                            <div class="form-text">اگر این کالا طرح دارد، تعداد طرح‌ها را وارد کن.</div>
                                        </div>

                                        <div>
                                            <label class="form-label">توضیح هر طرح (اختیاری)</label>
                                            <div id="designNotesWrap"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="helper-box mt-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="muted">تعداد تنوع‌هایی که ساخته می‌شود</div>
                                    <div class="fw-bold fs-4" id="variantsCountPreview">1</div>
                                </div>

                                <div class="col-md-8">
                                    <div class="muted mb-2">نمونه کد تنوع‌های قابل ساخت</div>
                                    <ul class="examples-list" id="variantExamples"></ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ثبت --}}
            <div class="col-12">
                <div class="sticky-actions d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="muted">
                        بعد از ثبت کالا، موجودی و قیمت را از بخش <b>خرید کالا</b> وارد کن.
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">انصراف</a>
                        <button type="submit" class="btn btn-primary">ثبت کالا</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const categories = @json($categoriesJs);
    const modelLists = @json($modelListsJs);
    const previewSeq4 = @json($previewSeq4);
    const oldModelIds = @json($oldModelIds);
    const oldDesignNotes = @json($oldDesignNotes);

    const categoryIdEl = document.getElementById('categoryId');
    const useModelsEl = document.getElementById('useModels');
    const useDesignsEl = document.getElementById('useDesigns');

    const modelsSection = document.getElementById('modelsSection');
    const designsSection = document.getElementById('designsSection');

    const modelBrandGroupEl = document.getElementById('modelBrandGroup');
    const modelListIdsEl = document.getElementById('modelListIds');
    const selectedModelsCountEl = document.getElementById('selectedModelsCount');

    const designCountEl = document.getElementById('designCount');
    const designNotesWrap = document.getElementById('designNotesWrap');

    const productCodePreviewEl = document.getElementById('productCodePreview');
    const variantPatternPreviewEl = document.getElementById('variantPatternPreview');
    const variantsCountPreviewEl = document.getElementById('variantsCountPreview');
    const variantExamplesEl = document.getElementById('variantExamples');

    function onlyDigits(s) {
        return String(s || '').replace(/\D+/g, '');
    }

    function padLeft(s, len, ch) {
        s = String(s || '');
        ch = ch || '0';
        while (s.length < len) s = ch + s;
        return s;
    }

    function normalizeCategory2(code) {
        const d = onlyDigits(code).substring(0, 2);
        return d.length === 2 ? d : '--';
    }

    function normalizeModel3(code) {
        const d = onlyDigits(code).substring(0, 3);
        return padLeft(d, 3, '0');
    }

    function selectedCategory() {
        const id = parseInt(categoryIdEl.value || '0', 10);
        return categories.find(c => parseInt(c.id, 10) === id) || null;
    }

    function updateCategoryPreview() {
        const cat = selectedCategory();
        const cat2 = cat ? normalizeCategory2(cat.code) : '--';
        productCodePreviewEl.textContent = cat2 + previewSeq4;
        variantPatternPreviewEl.textContent = cat2 + previewSeq4 + 'MMMDD';
    }

    function syncSections() {
        const modelsOn = useModelsEl.checked;
        const designsOn = useDesignsEl.checked;

        modelsSection.style.display = modelsOn ? '' : 'none';
        designsSection.style.display = designsOn ? '' : 'none';

        modelBrandGroupEl.disabled = !modelsOn;
        modelListIdsEl.disabled = !modelsOn;
        designCountEl.disabled = !designsOn;

        if (!modelsOn) {
            Array.from(modelListIdsEl.options).forEach(opt => opt.selected = false);
        }

        if (!designsOn) {
            designNotesWrap.innerHTML = '';
        }

        updateSelectedModelsCount();
        renderDesignNotes();
        renderVariantPreview();
    }

    function renderModelOptions() {
        const brand = modelBrandGroupEl.value || '';
        const currentSelected = Array.from(modelListIdsEl.selectedOptions).map(o => parseInt(o.value, 10));

        modelListIdsEl.innerHTML = '';

        const filtered = modelLists.filter(function (m) {
            if (!brand) return false;
            return String(m.brand || '') === brand;
        });

        filtered.forEach(function (m) {
            const opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = `${m.model_name} (${m.code || '---'})`;
            if (oldModelIds.includes(parseInt(m.id, 10)) || currentSelected.includes(parseInt(m.id, 10))) {
                opt.selected = true;
            }
            modelListIdsEl.appendChild(opt);
        });

        updateSelectedModelsCount();
        renderVariantPreview();
    }

    function updateSelectedModelsCount() {
        const count = Array.from(modelListIdsEl.selectedOptions).length;
        selectedModelsCountEl.textContent = String(count);
    }

    function getDesignCount() {
        if (!useDesignsEl.checked) return 0;
        let count = parseInt(designCountEl.value || '0', 10);
        if (isNaN(count) || count < 1) count = 1;
        if (count > 99) count = 99;
        return count;
    }

    function currentDesignNotesValues() {
        return Array.from(designNotesWrap.querySelectorAll('input[name="design_notes[]"]')).map(i => i.value || '');
    }

    function renderDesignNotes() {
        if (!useDesignsEl.checked) {
            designNotesWrap.innerHTML = '';
            return;
        }

        const count = getDesignCount();
        const existing = currentDesignNotesValues();
        designNotesWrap.innerHTML = '';

        for (let i = 0; i < count; i++) {
            const val = existing[i] !== undefined ? existing[i] : (oldDesignNotes[i] || '');

            const box = document.createElement('div');
            box.className = 'design-note-item mb-2';

            box.innerHTML = `
                <label class="form-label">طرح ${i + 1}</label>
                <input type="text" name="design_notes[]" class="form-control" maxlength="120" placeholder="مثلاً گلدار / مشکی / مات" value="${escapeHtml(val)}">
            `;

            designNotesWrap.appendChild(box);
        }
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function selectedModelsData() {
        const ids = Array.from(modelListIdsEl.selectedOptions).map(o => parseInt(o.value, 10));
        return modelLists.filter(m => ids.includes(parseInt(m.id, 10)));
    }

    function calcVariantsCount() {
        const modelsOn = useModelsEl.checked;
        const designsOn = useDesignsEl.checked;

        const modelCount = selectedModelsData().length;
        const designCount = getDesignCount();

        if (!modelsOn && !designsOn) return 1;
        if (modelsOn && !designsOn) return modelCount;
        if (!modelsOn && designsOn) return designCount;
        return modelCount * designCount;
    }

    function renderVariantPreview() {
        const cat = selectedCategory();
        const productCode6 = (cat ? normalizeCategory2(cat.code) : '--') + previewSeq4;

        variantsCountPreviewEl.textContent = String(calcVariantsCount());

        const examples = [];

        const modelsOn = useModelsEl.checked;
        const designsOn = useDesignsEl.checked;
        const models = selectedModelsData();
        const designCount = getDesignCount();

        if (!modelsOn && !designsOn) {
            examples.push(productCode6 + '00000');
        }

        if (modelsOn && !designsOn) {
            models.forEach(function (m) {
                examples.push(productCode6 + normalizeModel3(m.code) + '00');
            });
        }

        if (!modelsOn && designsOn) {
            for (let i = 1; i <= designCount; i++) {
                examples.push(productCode6 + '000' + padLeft(i, 2, '0'));
            }
        }

        if (modelsOn && designsOn) {
            models.forEach(function (m) {
                for (let i = 1; i <= designCount; i++) {
                    examples.push(productCode6 + normalizeModel3(m.code) + padLeft(i, 2, '0'));
                }
            });
        }

        variantExamplesEl.innerHTML = '';

        if (examples.length === 0) {
            const li = document.createElement('li');
            li.textContent = 'هنوز تنوعی برای پیش‌نمایش انتخاب نشده است.';
            variantExamplesEl.appendChild(li);
            return;
        }

        examples.slice(0, 8).forEach(function (code) {
            const li = document.createElement('li');
            li.className = 'mono';
            li.textContent = code;
            variantExamplesEl.appendChild(li);
        });

        if (examples.length > 8) {
            const li = document.createElement('li');
            li.textContent = `و ${examples.length - 8} مورد دیگر...`;
            variantExamplesEl.appendChild(li);
        }
    }

    categoryIdEl.addEventListener('change', function () {
        updateCategoryPreview();
        renderVariantPreview();
    });

    useModelsEl.addEventListener('change', syncSections);
    useDesignsEl.addEventListener('change', syncSections);

    modelBrandGroupEl.addEventListener('change', function () {
        renderModelOptions();
    });

    modelListIdsEl.addEventListener('change', function () {
        updateSelectedModelsCount();
        renderVariantPreview();
    });

    designCountEl.addEventListener('input', function () {
        renderDesignNotes();
        renderVariantPreview();
    });

    updateCategoryPreview();
    renderModelOptions();
    syncSections();
});
</script>
@endsection