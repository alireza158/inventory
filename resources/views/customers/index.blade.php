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
        <input class="form-control" name="q" value="{{ $q ?? '' }}" placeholder="جستجو نام مشتری/موبایل">
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
            <th>نام مشتری</th>
            <th>موبایل</th>
            <th>اطلاعات تکمیلی</th>
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
              <td>{{ $c->display_name ?: '—' }}</td>
              <td class="text-nowrap">{{ $c->mobile }}</td>
              <td style="max-width: 360px">
                <div>{{ $c->address ?: '—' }}</div>
                @if($c->postal_code)
                  <div class="small text-muted">کدپستی: {{ $c->postal_code }}</div>
                @endif
              </td>
              <td class="text-nowrap">{{ number_format((int)($c->debt ?? 0)) }}</td>
              <td class="text-nowrap">{{ number_format((int)($c->credit ?? 0)) }}</td>
              <td class="text-nowrap fw-bold">{{ number_format((int)($c->balance ?? 0)) }}</td>
              <td class="text-nowrap">
                <button
                  type="button"
                  class="btn btn-sm btn-outline-warning"
                  data-bs-toggle="modal"
                  data-bs-target="#editCustomerModal"
                  data-customer-id="{{ $c->id }}"
                  data-customer-name="{{ $c->display_name }}"
                  data-mobile="{{ $c->mobile }}"
                  data-address="{{ $c->address }}"
                  data-postal-code="{{ $c->postal_code }}"
                  data-extra-description="{{ $c->extra_description }}"
                  data-province-id="{{ $c->province_id }}"
                  data-city-id="{{ $c->city_id }}"
                  data-update-url="{{ route('customers.update', $c) }}"
                >
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

<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" id="editCustomerForm" action="#">
      @csrf
      @method('PUT')

      <div class="modal-header">
        <div class="fw-bold" id="editCustomerTitle">✏️ ویرایش مشتری</div>
        <button type="button" class="btn-close ms-0" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-2">
          <div class="col-md-12">
            <label class="form-label">نام مشتری *</label>
            <input class="form-control" name="customer_name" id="edit_customer_name" required>
          </div>

          <div class="col-md-12">
            <label class="form-label">موبایل *</label>
            <input class="form-control" name="mobile" id="edit_mobile" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">استان (اختیاری)</label>
            <select class="form-select" name="province_id" id="edit_province_id"><option value=""></option></select>
          </div>

          <div class="col-md-6">
            <label class="form-label">شهر (اختیاری)</label>
            <select class="form-select" name="city_id" id="edit_city_id"><option value=""></option></select>
          </div>

          <div class="col-md-12">
            <label class="form-label">آدرس (اختیاری)</label>
            <textarea class="form-control" name="address" id="edit_address" rows="2"></textarea>
          </div>

          <div class="col-md-12">
            <label class="form-label">کد پستی (اختیاری)</label>
            <input class="form-control" name="postal_code" id="edit_postal_code">
          </div>

          <div class="col-md-12">
            <label class="form-label">توضیحات اضافی (اختیاری)</label>
            <textarea class="form-control" name="extra_description" id="edit_extra_description" rows="2"></textarea>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-primary">ذخیره تغییرات</button>
      </div>
    </form>
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
          <div class="col-md-12">
            <label class="form-label">نام مشتری *</label>
            <input class="form-control" name="customer_name" value="{{ old('customer_name', old('first_name')) }}" required>
          </div>

          <div class="col-md-12">
            <label class="form-label">موبایل *</label>
            <input class="form-control" name="mobile" value="{{ old('mobile') }}" required>
            <div class="form-text">ترجیحاً یکتا باشد.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label">استان (اختیاری)</label>
            <select class="form-select" name="province_id" id="create_province_id"><option value=""></option></select>
          </div>

          <div class="col-md-6">
            <label class="form-label">شهر (اختیاری)</label>
            <select class="form-select" name="city_id" id="create_city_id"><option value=""></option></select>
          </div>

          <div class="col-md-12">
            <label class="form-label">آدرس (اختیاری)</label>
            <textarea class="form-control" name="address" rows="2">{{ old('address') }}</textarea>
          </div>

          <div class="col-md-12">
            <label class="form-label">کد پستی (اختیاری)</label>
            <input class="form-control" name="postal_code" value="{{ old('postal_code') }}">
          </div>

          <div class="col-md-12">
            <label class="form-label">توضیحات اضافی (اختیاری)</label>
            <textarea class="form-control" name="extra_description" rows="2">{{ old('extra_description') }}</textarea>
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

@push('scripts')
<script>
const AREA_API = "{{ url('/preinvoice/api/area') }}";
let provinces = [];

function initSelect2(selectEl, placeholder) {
  if (!window.jQuery || !window.jQuery.fn?.select2) return;
  const $el = $(selectEl);
  if ($el.hasClass('select2-hidden-accessible')) {
    $el.off('select2:select select2:clear');
    $el.select2('destroy');
  }
  $el.select2({ width:'100%', dir:'rtl', placeholder, allowClear:true, dropdownParent: $el.closest('.modal') });
  $el.on('select2:select select2:clear', function(){ this.dispatchEvent(new Event('change',{bubbles:true})); });
}

function setSelectDisabled(selectEl, disabled) {
  selectEl.disabled = disabled;
  if (window.jQuery && $(selectEl).hasClass('select2-hidden-accessible')) {
    $(selectEl).prop('disabled', disabled).trigger('change.select2');
  }
}

function setProvinceOptions(selectEl) {
  selectEl.innerHTML = '<option value=""></option>';
  provinces.forEach((p) => {
    const opt = document.createElement('option');
    opt.value = p.id;
    opt.textContent = p.name;
    selectEl.appendChild(opt);
  });
}

function setCityOptions(selectEl, provinceId, selectedCityId = null) {
  const province = provinces.find((p) => Number(p.id) === Number(provinceId));
  const cities = province?.cities ?? province?.city ?? [];
  selectEl.innerHTML = '<option value=""></option>';

  cities.forEach((c) => {
    const opt = document.createElement('option');
    opt.value = c.id;
    opt.textContent = c.name;
    if (selectedCityId && Number(selectedCityId) === Number(c.id)) opt.selected = true;
    selectEl.appendChild(opt);
  });

  setSelectDisabled(selectEl, cities.length === 0);
  if (window.jQuery) $(selectEl).trigger('change.select2');
}

document.addEventListener('DOMContentLoaded', async function () {
  const editModal = document.getElementById('editCustomerModal');
  const createProvince = document.getElementById('create_province_id');
  const createCity = document.getElementById('create_city_id');
  const editProvince = document.getElementById('edit_province_id');
  const editCity = document.getElementById('edit_city_id');

  try {
    const res = await fetch(AREA_API, { headers: { 'Accept': 'application/json' } });
    const json = await res.json();
    provinces = json?.data?.provinces ?? [];
  } catch (e) {
    provinces = [];
  }

  setProvinceOptions(createProvince);
  setProvinceOptions(editProvince);
  setCityOptions(createCity, null, null);
  setCityOptions(editCity, null, null);

  initSelect2(createProvince, 'انتخاب استان...');
  initSelect2(createCity, 'انتخاب شهر...');
  initSelect2(editProvince, 'انتخاب استان...');
  initSelect2(editCity, 'انتخاب شهر...');

  createProvince.addEventListener('change', function () {
    setCityOptions(createCity, this.value, null);
  });

  editProvince.addEventListener('change', function () {
    setCityOptions(editCity, this.value, null);
  });

  editModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    if (!button) return;

    const form = document.getElementById('editCustomerForm');
    form.action = button.getAttribute('data-update-url') || '#';

    const provinceId = button.getAttribute('data-province-id') || '';
    const cityId = button.getAttribute('data-city-id') || '';

    document.getElementById('editCustomerTitle').textContent = `✏️ ویرایش مشتری #${button.getAttribute('data-customer-id') || ''}`;
    document.getElementById('edit_customer_name').value = button.getAttribute('data-customer-name') || '';
    document.getElementById('edit_mobile').value = button.getAttribute('data-mobile') || '';
    document.getElementById('edit_address').value = button.getAttribute('data-address') || '';
    document.getElementById('edit_postal_code').value = button.getAttribute('data-postal-code') || '';
    document.getElementById('edit_extra_description').value = button.getAttribute('data-extra-description') || '';

    editProvince.value = provinceId;
    if (window.jQuery) $(editProvince).trigger('change.select2');
    setCityOptions(editCity, provinceId, cityId);
  });
});
</script>
@endpush
