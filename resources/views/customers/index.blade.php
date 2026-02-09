@extends('layouts.app')

@section('content')
<div class="container">

  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <div class="h5 fw-bold mb-0">👥 مشتریان</div>
      <div class="text-muted small">لیست مشتری‌ها + مانده حساب</div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
      <form class="d-flex gap-2" method="GET" action="{{ route('customers.index') }}">
        <input class="form-control" name="q" value="{{ $q ?? '' }}" placeholder="جستجو نام/فامیل/موبایل">
        <button class="btn btn-primary">جستجو</button>
      </form>

      <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createCustomerModal">
        ➕ ساخت مشتری
      </button>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>نام</th>
            <th>موبایل</th>
            <th>آدرس</th>
            <th class="text-nowrap">بدهکار</th>
            <th class="text-nowrap">بستانکار</th>
            <th class="text-nowrap">مانده</th>
            <th class="text-nowrap">عملیات</th>
          </tr>
        </thead>
        <tbody>
          @forelse($customers as $c)
            <tr>
              <td>{{ $c->id }}</td>
              <td>{{ trim(($c->first_name ?? '').' '.($c->last_name ?? '')) ?: '—' }}</td>
              <td class="text-nowrap">{{ $c->mobile }}</td>
              <td style="max-width: 360px">{{ $c->address ?: '—' }}</td>
              <td class="text-nowrap">{{ number_format((int)($c->debt ?? 0)) }}</td>
              <td class="text-nowrap">{{ number_format((int)($c->credit ?? 0)) }}</td>
              <td class="text-nowrap fw-bold">{{ number_format((int)($c->balance ?? 0)) }}</td>
              <td class="text-nowrap">
                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editCustomerModal{{ $c->id }}">
                  ویرایش
                </button>

                <form method="POST" action="{{ route('customers.destroy', $c) }}" class="d-inline"
                      onsubmit="return confirm('مشتری حذف شود؟')">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">حذف</button>
                </form>
              </td>
            </tr>

            <div class="modal fade" id="editCustomerModal{{ $c->id }}" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('customers.update', $c) }}">
                  @csrf
                  @method('PUT')

                  <div class="modal-header">
                    <div class="fw-bold">✏️ ویرایش مشتری #{{ $c->id }}</div>
                    <button type="button" class="btn-close ms-0" data-bs-dismiss="modal"></button>
                  </div>

                  <div class="modal-body">
                    <div class="row g-2">
                      <div class="col-md-6">
                        <label class="form-label">نام</label>
                        <input class="form-control" name="first_name" value="{{ old('first_name', $c->first_name) }}">
                      </div>

                      <div class="col-md-6">
                        <label class="form-label">فامیل</label>
                        <input class="form-control" name="last_name" value="{{ old('last_name', $c->last_name) }}">
                      </div>

                      <div class="col-md-12">
                        <label class="form-label">موبایل *</label>
                        <input class="form-control" name="mobile" value="{{ old('mobile', $c->mobile) }}" required>
                      </div>

                      <div class="col-md-12">
                        <label class="form-label">آدرس</label>
                        <textarea class="form-control" name="address" rows="2">{{ old('address', $c->address) }}</textarea>
                      </div>
                    </div>
                  </div>

                  <div class="modal-footer">
                    <button class="btn btn-primary">ذخیره تغییرات</button>
                  </div>
                </form>
              </div>
            </div>
          @empty
            <tr><td colspan="8" class="text-center text-muted py-4">مشتری یافت نشد</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $customers->links() }}
  </div>

</div>

<div class="modal fade" id="createCustomerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" action="{{ route('customers.store') }}">
      @csrf

      <div class="modal-header">
        <div class="fw-bold">➕ ساخت مشتری</div>
        <button type="button" class="btn-close ms-0" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">نام</label>
            <input class="form-control" name="first_name" value="{{ old('first_name') }}">
          </div>

          <div class="col-md-6">
            <label class="form-label">فامیل</label>
            <input class="form-control" name="last_name" value="{{ old('last_name') }}">
          </div>

          <div class="col-md-12">
            <label class="form-label">موبایل *</label>
            <input class="form-control" name="mobile" value="{{ old('mobile') }}" required>
            <div class="form-text">ترجیحاً یکتا باشد.</div>
          </div>

          <div class="col-md-12">
            <label class="form-label">آدرس</label>
            <textarea class="form-control" name="address" rows="2">{{ old('address') }}</textarea>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-primary">ثبت مشتری</button>
      </div>
    </form>
  </div>
</div>
@endsection
