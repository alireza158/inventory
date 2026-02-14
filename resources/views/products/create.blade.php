@extends('layouts.app')

@section('content')
<div class="card shadow-sm">
  <div class="card-body">
    <h5 class="mb-3">افزودن محصول</h5>

    @if($categories->count() === 0)
      <div class="alert alert-warning">
        هنوز دسته‌بندی ندارید. اول یک دسته‌بندی بسازید.
        <a class="ms-2" href="{{ route('categories.create') }}">ساخت دسته‌بندی</a>
      </div>
    @endif

    <form method="POST" action="{{ route('products.store') }}">
      @csrf

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">نام محصول</label>
          <input name="name" class="form-control" value="{{ old('name') }}" placeholder="مثلاً گارد آیفون">
        </div>

        <div class="col-md-6">
          <label class="form-label">SKU (شناسه)</label>
          <input name="sku" class="form-control" value="{{ old('sku') }}" placeholder="مثلاً ARIYA-6404">
        </div>


        <div class="col-md-6">
          <label class="form-label">بارکد</label>
          <div class="input-group">
            <input id="barcode_input" name="barcode" class="form-control" value="{{ old('barcode') }}" placeholder="بارکد خودکار تولید می‌شود">
            <button type="button" class="btn btn-outline-secondary" id="generateBarcodeBtn">تولید رندوم</button>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label">دسته‌بندی</label>
          <select name="category_id" class="form-select">
            <option value="">انتخاب کنید</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>
                {{ $cat->name }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      <hr class="my-4">

      <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="mb-0">مدل‌ها (Variant)</h6>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addVariantRow()">
          + افزودن مدل
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-sm align-middle" id="variantsTable">
          <thead>
            <tr>
              <th>نام مدل</th>
              <th style="width:180px;">قیمت فروش</th>
              <th style="width:180px;">قیمت خرید</th>
              <th style="width:140px;">موجودی</th>
              <th style="width:60px;"></th>
            </tr>
          </thead>
          <tbody>
            @php
              $oldVariantsRaw = old('variants', []);
              $oldVariants = is_array($oldVariantsRaw) ? $oldVariantsRaw : [];
              $modelListItems = is_iterable($modelListOptions) ? $modelListOptions : [];
            @endphp
            @foreach($oldVariants as $i => $v)
              <tr>
                <td>
                  <select class="form-select model-select" name="variants[{{ $i }}][variant_name]" required>
                    @if(!empty($v['variant_name']))
                      <option value="{{ $v['variant_name'] }}" selected>{{ $v['variant_name'] }}</option>
                    @endif
                    @foreach($modelListItems as $model)
                      <option value="{{ $model }}">{{ $model }}</option>
                    @endforeach
                  </select>
                </td>
                <td><input class="form-control" type="number" min="0" name="variants[{{ $i }}][sell_price]" value="{{ $v['sell_price'] ?? 0 }}"></td>
                <td><input class="form-control" type="number" min="0" name="variants[{{ $i }}][buy_price]" value="{{ $v['buy_price'] ?? '' }}"></td>
                <td><input class="form-control" type="number" min="0" name="variants[{{ $i }}][stock]" value="{{ $v['stock'] ?? 0 }}"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">×</button></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary" @disabled($categories->count() === 0)>ثبت</button>
        <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">بازگشت</a>
      </div>
    </form>
  </div>
</div>

<script>
let variantIndex = {{ count(old('variants', [])) }};
const modelOptions = @json(collect($modelListItems)->values());

function buildModelOptionsHtml(selected = '') {
  let html = '<option value=""></option>';
  for (const model of modelOptions) {
    const isSelected = model === selected ? 'selected' : '';
    html += `<option value="${model}" ${isSelected}>${model}</option>`;
  }

  if (selected && !modelOptions.includes(selected)) {
    html += `<option value="${selected}" selected>${selected}</option>`;
  }

  return html;
}

function initModelSelects(context = document) {
  if (!window.jQuery || !$.fn.select2) return;

  $(context).find('.model-select').each(function () {
    if ($(this).hasClass('select2-hidden-accessible')) {
      return;
    }

    $(this).select2({
      width: '100%',
      placeholder: 'جستجو یا انتخاب مدل...',
      allowClear: true,
      tags: true,
      dir: 'rtl',
      language: {
        noResults: () => 'مدلی پیدا نشد',
        searching: () => 'در حال جستجو...'
      }
    });
  });
}

function addVariantRow() {
  const tbody = document.querySelector('#variantsTable tbody');
  const i = variantIndex++;

  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td>
      <select class="form-select model-select" name="variants[${i}][variant_name]" required>
        ${buildModelOptionsHtml('')}
      </select>
    </td>
    <td><input class="form-control" type="number" min="0" name="variants[${i}][sell_price]" value="0"></td>
    <td><input class="form-control" type="number" min="0" name="variants[${i}][buy_price]" value=""></td>
    <td><input class="form-control" type="number" min="0" name="variants[${i}][stock]" value="0"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">×</button></td>
  `;
  tbody.appendChild(tr);
  initModelSelects(tr);
}

document.addEventListener('DOMContentLoaded', () => {
  const barcodeInput = document.getElementById('barcode_input');
  const generateBtn = document.getElementById('generateBarcodeBtn');

  const randomBarcode = () => String(Math.floor(100000000000 + Math.random() * 900000000000));

  const generateIfEmpty = () => {
    if (barcodeInput && !barcodeInput.value.trim()) {
      barcodeInput.value = randomBarcode();
    }
  };

  generateBtn?.addEventListener('click', () => {
    if (barcodeInput) barcodeInput.value = randomBarcode();
  });

  initModelSelects(document);
  generateIfEmpty();
});

</script>
@endsection
