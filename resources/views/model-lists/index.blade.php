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
            <label class="form-label">برند</label>
            <select name="brand" class="form-select mb-2" required>
              <option value="">انتخاب برند</option>
              @foreach(($brands ?? []) as $brand)
                <option value="{{ $brand }}" @selected(old('brand') === $brand)>{{ $brand }}</option>
              @endforeach
              <option value="سایر" @selected(old('brand') === 'سایر')>سایر</option>
            </select>

            <label class="form-label">کد مدل (۳ رقم)</label>
            <input name="code" class="form-control mb-2" maxlength="3" value="{{ old('code') }}" placeholder="مثلاً 016" required>

            <label class="form-label">مدل گوشی</label>
            <input name="model_name" class="form-control" value="{{ old('model_name') }}" placeholder="مثلاً Galaxy S24 Ultra" required>
            <div class="form-text">کد مدل باید سه‌رقمی باشد تا برای بارکدسازی قابل استفاده باشد.</div>
          </div>
          <div class="col-12 d-grid">
            <button class="btn btn-primary">ذخیره مدل</button>
          </div>
        </form>

        <hr>

        <form method="POST" action="{{ route('model-lists.import-phone-catalog') }}" class="mb-2">
          @csrf
          <button class="btn btn-success w-100">بارگذاری بانک کامل مدل‌های برندها</button>
          <div class="small text-muted mt-2">
            برندهای آیفون، سامسونگ، شیائومی، ریلمی، هواوی و هانر به‌صورت یک‌جا ثبت می‌شوند.
          </div>
        </form>

        <form method="POST" action="{{ route('model-lists.import-from-products') }}">
          @csrf
          <button class="btn btn-outline-secondary w-100">دریافت مدل‌ها از کالاهای موجود</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="mb-3">لیست مدل‌ها (تفکیک برند)</h6>

        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>برند</th>
                <th>کد ۳ رقمی</th>
                <th>مدل گوشی</th>
              </tr>
            </thead>
            <tbody>
              @forelse($modelLists as $item)
                <tr>
                  <td><span class="badge bg-info-subtle text-dark">{{ $item->brand ?: 'سایر' }}</span></td>
                  <td><span class="badge bg-light text-dark">{{ $item->code ?: '—' }}</span></td>
                  <td class="fw-semibold">{{ $item->model_name }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center text-muted py-4">هنوز مدلی ثبت نشده است.</td>
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
