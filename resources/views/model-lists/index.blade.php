@extends('layouts.app')

@section('content')
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="mb-3">افزودن مدل جدید</h6>

        <form method="POST" action="{{ route('model-lists.store') }}" class="row g-2">
          @csrf
          <div class="col-12">
            <label class="form-label">کد مدل (۴ رقم) - اختیاری</label>
            <input name="code" class="form-control mb-2"
                   maxlength="4"
                   inputmode="numeric"
                   value="{{ old('code') }}"
                   placeholder="مثلاً 0016">
            <div class="form-text">اگر خالی بگذارید سیستم به‌صورت خودکار کد بعدی را می‌سازد.</div>

            <label class="form-label mt-2">مدل کامل</label>
            <input name="model_name" class="form-control"
                   value="{{ old('model_name') }}"
                   placeholder="مثلاً Samsung A16 / iPhone 14 Pro Max"
                   required>
            <div class="form-text">مدل را کامل وارد کنید.</div>
          </div>

          <div class="col-12 d-grid">
            <button class="btn btn-primary">ذخیره مدل</button>
          </div>
        </form>

        <hr>

        <form method="POST" action="{{ route('model-lists.assign-codes') }}">
          @csrf
          <button class="btn btn-outline-primary w-100">تولید کد برای مدل‌های بدون کد</button>
          <div class="small text-muted mt-2">
            برای مدل‌هایی که کد ندارند، کد ۴ رقمی خودکار می‌سازد.
          </div>
        </form>

        <hr>

        <form method="POST" action="{{ route('model-lists.import-from-products') }}">
          @csrf
          <button class="btn btn-outline-secondary w-100">دریافت مدل‌ها از کالاهای موجود</button>
          <div class="small text-muted mt-2">
            مدل‌ها از روی نام تنوع‌ها استخراج می‌شوند و همراه با کد خودکار ذخیره می‌گردند.
          </div>
        </form>

      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <h6 class="mb-0">لیست مدل‌ها</h6>

          <form method="GET" action="{{ route('model-lists.index') }}" class="d-flex gap-2">
            <input name="q" class="form-control form-control-sm" value="{{ $q ?? '' }}" placeholder="جستجو: مدل یا کد">
            <button class="btn btn-sm btn-outline-secondary">جستجو</button>
          </form>
        </div>

        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th style="width:180px;">کد</th>
                <th>مدل کامل</th>
              </tr>
            </thead>
            <tbody>
              @forelse($modelLists as $item)
                <tr>
                  <td>
                    <form method="POST" action="{{ route('model-lists.update', $item) }}" class="d-flex gap-2 align-items-center">
                      @csrf
                      @method('PUT')
                      <input name="code"
                             class="form-control form-control-sm"
                             maxlength="4"
                             inputmode="numeric"
                             value="{{ $item->code }}"
                             placeholder="----"
                             style="max-width:110px;">
                      <button class="btn btn-sm btn-outline-primary">ذخیره</button>
                    </form>
                    @if(!$item->code)
                      <div class="small text-danger mt-1">بدون کد</div>
                    @endif
                  </td>
                  <td class="fw-semibold">{{ $item->model_name }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="2" class="text-center text-muted py-4">هنوز مدلی ثبت نشده است.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{ $modelLists->links() }}
      </div>
    </div>
  </div>
</div>
@endsection