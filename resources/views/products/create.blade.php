@extends('layouts.app')

@section('content')
<div class="card shadow-sm">
  <div class="card-body">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">ایجاد کالا و ساخت تنوع‌ها</h5>
      <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">بازگشت</a>
    </div>

    <form method="POST" action="{{ route('products.store') }}" class="row g-3">
      @csrf

      {{-- 1) نام کالا --}}
      <div class="col-md-6">
        <label class="form-label">نام کالا</label>
        <input name="name" class="form-control" value="{{ old('name') }}" required placeholder="مثلاً گارد یونیک A16">
      </div>

      {{-- 2) دسته‌بندی --}}
      <div class="col-md-6">
        <label class="form-label">دسته‌بندی کالا</label>
        <select name="category_id" class="form-select" required>
          <option value="">انتخاب کنید</option>
          @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>
              {{ $cat->name }} (کد: {{ $cat->code ?: '---' }})
            </option>
          @endforeach
        </select>
        <div class="form-text">
          کد دسته‌بندی باید 3 رقمی باشد (مثلاً 101)
        </div>
      </div>

      {{-- 3) مدل لیست‌ها --}}
      <div class="col-12">
        <label class="form-label">مدل‌لیست‌ها (از بخش «مدل لیست‌ها»)</label>
        <select name="model_list_ids[]" class="form-select" multiple size="10" required>
          @foreach($modelLists as $model)
            <option value="{{ $model->id }}" @selected(collect(old('model_list_ids', []))->contains($model->id))>
              {{ $model->model_name }} (کد: {{ $model->code }})
            </option>
          @endforeach
        </select>
        <div class="form-text">
          چند مدل انتخاب کن. سیستم برای هر مدل × تعداد طرح، تنوع می‌سازد.
        </div>
      </div>

      {{-- 4) تعداد طرح --}}
      <div class="col-md-4">
        <label class="form-label">تعداد طرح</label>
        <input type="number" min="1" max="500" name="design_count"
               class="form-control" value="{{ old('design_count', 1) }}" required>
        <div class="form-text">
          مثال: اگر 10 مدل انتخاب کنی و تعداد طرح 10 باشد → 100 تنوع ساخته می‌شود.
        </div>
      </div>

      {{-- نمایش نتیجه --}}
      <div class="col-md-8">
        <label class="form-label">راهنمای کد کالا/تنوع</label>
        <div class="alert alert-light border mb-0">
          <div class="small text-muted mb-1">فرمت کد 12 رقمی (طبق خواسته شما):</div>
          <div class="fw-bold">CCC + PPPPP + VVVV</div>
          <div class="small text-muted mt-2">
            CCC = کد 3 رقمی دسته‌بندی / PPPPP = ترتیب 5 رقمی کالا / VVVV = ترتیب 4 رقمی تنوع
          </div>
        </div>
      </div>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary">تشکیل لیست و ساخت تنوع‌ها</button>
        <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">انصراف</a>
      </div>

      <div class="col-12 small text-muted">
        نکته: تولید «کد یکتا» باید در سمت سرور (ProductController@store) انجام شود تا تکراری نشود.
      </div>

    </form>
  </div>
</div>
@endsection
