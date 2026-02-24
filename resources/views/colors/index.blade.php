@extends('layouts.app')

@section('content')
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="mb-3">افزودن رنگ جدید</h6>

        <form method="POST" action="{{ route('colors.store') }}" class="row g-2">
          @csrf
          <div class="col-12">
            <label class="form-label">کد رنگ (۲ رقم)</label>
            <input name="code" class="form-control mb-2" maxlength="2" value="{{ old('code') }}" required>

            <label class="form-label">نام رنگ</label>
            <input name="name" class="form-control" value="{{ old('name') }}" required>
          </div>

          <div class="col-12 d-grid">
            <button class="btn btn-primary">ذخیره رنگ</button>
          </div>
        </form>

        <hr>

        <form method="POST" action="{{ route('colors.seed-defaults') }}">
          @csrf
          <button class="btn btn-success w-100">بارگذاری رنگ‌های پیش‌فرض (۳۲ رنگ)</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="mb-3">لیست رنگ‌ها</h6>

        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>کد</th>
                <th>نام رنگ</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse($colors as $color)
                <tr>
                  <td style="width:100px">{{ $color->code }}</td>
                  <td>
                    <form method="POST" action="{{ route('colors.update', $color) }}" class="d-flex gap-2">
                      @csrf
                      @method('PUT')
                      <input name="code" class="form-control" maxlength="2" value="{{ $color->code }}" style="max-width:120px" required>
                      <input name="name" class="form-control" value="{{ $color->name }}" required>
                      <button class="btn btn-outline-primary btn-sm">ذخیره</button>
                    </form>
                  </td>
                  <td class="text-end" style="width:90px">
                    <form method="POST" action="{{ route('colors.destroy', $color) }}" onsubmit="return confirm('حذف شود؟')">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-outline-danger btn-sm">حذف</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" class="text-center text-muted py-4">هنوز رنگی ثبت نشده است.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{ $colors->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
