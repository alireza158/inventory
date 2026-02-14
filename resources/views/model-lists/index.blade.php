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
            <input name="brand" class="form-control" value="{{ old('brand') }}" placeholder="مثلاً Samsung یا iPhone" required>
          </div>
          <div class="col-12">
            <label class="form-label">نام مدل</label>
            <input name="model_name" class="form-control" value="{{ old('model_name') }}" placeholder="مثلاً S24 Ultra" required>
          </div>
          <div class="col-12 d-grid">
            <button class="btn btn-primary">ذخیره مدل</button>
          </div>
        </form>

        <hr>

        <form method="POST" action="{{ route('model-lists.import-from-products') }}">
          @csrf
          <button class="btn btn-outline-secondary w-100">دریافت مدل‌ها از کالاهای موجود</button>
          <div class="small text-muted mt-2">
            فرمت پیشنهادی برای مدل کالا: «برند - مدل» مثل «Samsung - S24 Ultra».
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="mb-3">لیست مدل‌ها</h6>

        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>برند</th>
                <th>مدل</th>
              </tr>
            </thead>
            <tbody>
              @forelse($modelLists as $item)
                <tr>
                  <td class="fw-semibold">{{ $item->brand }}</td>
                  <td>{{ $item->model_name }}</td>
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
