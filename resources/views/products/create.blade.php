@extends('layouts.app')

@section('content')
<div class="card shadow-sm">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">افزودن محصول</h5>
      <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">بازگشت به کالاها</a>
    </div>

    <form method="POST" action="{{ route('products.store') }}" class="row g-3">
      @csrf
      <div class="col-md-4">
        <label class="form-label">اسم محصول</label>
        <input name="name" class="form-control" value="{{ old('name') }}" required>
      </div>

      <div class="col-md-4">
        <label class="form-label">گروه کالا</label>
        <select name="category_id" class="form-select" required>
          <option value="">انتخاب کنید</option>
          @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>
              {{ $cat->name }} ({{ $cat->code ?: '----' }})
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label">تعداد طرح</label>
        <input type="number" min="1" max="20" name="design_count" class="form-control" value="{{ old('design_count', 1) }}" required>
      </div>

      <div class="col-12">
        <label class="form-label">مدل لیست‌ها (از بخش مدل لیست)</label>
        <select name="model_list_ids[]" class="form-select" multiple size="10" required>
          @foreach($modelLists as $model)
            <option value="{{ $model->id }}" @selected(collect(old('model_list_ids', []))->contains($model->id))>
              {{ $model->brand ? ($model->brand . ' - ') : '' }}{{ $model->model_name }} ({{ $model->code }})
            </option>
          @endforeach
        </select>
        <div class="form-text">چند مدل انتخاب کنید. سیستم به تعداد مدل × تعداد طرح، تنوع می‌سازد.</div>
      </div>

      <div class="col-md-3">
        <label class="form-label">قیمت خرید اولیه (اختیاری)</label>
        <input type="number" min="0" name="buy_price" class="form-control" value="{{ old('buy_price') }}">
      </div>

      <div class="col-md-3">
        <label class="form-label">قیمت فروش اولیه (اختیاری)</label>
        <input type="number" min="0" name="sell_price" class="form-control" value="{{ old('sell_price') }}">
      </div>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary">ساخت محصول و تنوع‌ها</button>
        <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">انصراف</a>
      </div>

      <div class="col-12 small text-muted">
        فرمت کدها: دسته‌بندی (CCCC) + مدل لیست (MMMM) + طرح/رنگ (VVVV) = کد نهایی هر تنوع.
      </div>
    </form>
  </div>
</div>
@endsection
