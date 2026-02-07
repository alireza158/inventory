@extends('layouts.app')

@section('content')
<div class="card shadow-sm">
  <div class="card-body">
    <h5 class="mb-3">ویرایش محصول</h5>

    <form method="POST" action="{{ route('products.update', $product) }}">
      @csrf
      @method('PUT')

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">نام محصول</label>
          <input name="name" class="form-control" value="{{ old('name', $product->name) }}">
        </div>

        <div class="col-md-6">
          <label class="form-label">SKU (شناسه)</label>
          <input name="sku" class="form-control" value="{{ old('sku', $product->sku) }}">
        </div>

        <div class="col-md-6">
          <label class="form-label">دسته‌بندی</label>
          <select name="category_id" class="form-select">
            <option value="">انتخاب کنید</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id)>
                {{ $cat->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">موجودی (خلاصه)</label>
          <input class="form-control" value="{{ $product->stock }}" disabled>
        </div>

        <div class="col-md-3">
          <label class="form-label">قیمت (خلاصه)</label>
          <input class="form-control" value="{{ number_format($product->price) }}" disabled>
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
              $oldVariants = old('variants');
              $variants = is_array($oldVariants) ? $oldVariants : $product->variants->toArray();
            @endphp

            @foreach($variants as $i => $v)
              <tr>
                <td>
                  <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $v['id'] ?? '' }}">
                  <input class="form-control" name="variants[{{ $i }}][variant_name]" value="{{ $v['variant_name'] ?? '' }}">
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
        <button class="btn btn-primary">ذخیره</button>
        <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">بازگشت</a>
      </div>
    </form>
  </div>
</div>

<script>
let variantIndex = {{ count(is_array(old('variants')) ? old('variants') : $product->variants) }};

function addVariantRow() {
  const tbody = document.querySelector('#variantsTable tbody');
  const i = variantIndex++;

  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td>
      <input type="hidden" name="variants[${i}][id]" value="">
      <input class="form-control" name="variants[${i}][variant_name]" value="">
    </td>
    <td><input class="form-control" type="number" min="0" name="variants[${i}][sell_price]" value="0"></td>
    <td><input class="form-control" type="number" min="0" name="variants[${i}][buy_price]" value=""></td>
    <td><input class="form-control" type="number" min="0" name="variants[${i}][stock]" value="0"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">×</button></td>
  `;
  tbody.appendChild(tr);
}
</script>
@endsection
