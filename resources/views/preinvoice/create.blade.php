@extends('layouts.app')

@section('content')
@php
  // برای جلوگیری از Undefined variable در همه حالت‌ها
  $order = $order ?? null;

  // برای حالت خطای ولیدیشن یا ادیت (اگر بعداً خواستی ادیت رو هم همین ویو کنی)
  $initRows = old('products');
  if (!$initRows && $order) {
    $initRows = $order->items->map(function($it){
      return [
        'id' => (int)$it->product_id,
        'variety_id' => (int)$it->variant_id,
        'quantity' => (int)$it->quantity,
        'price' => (int)$it->price,
      ];
    })->values();
  }
  if(!$initRows) $initRows = [];
@endphp

<link rel="stylesheet" href="{{ asset('lib/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('lib/bootstrap.rtl.min.css') }}">
<script src="{{ asset('lib/jquery.min.js') }}"></script>
<script src="{{ asset('lib/select2.min.js') }}"></script>
<script src="{{ asset('lib/bootstrap.bundle.min.js') }}"></script>

<style>
  body { background: linear-gradient(180deg, #f6f8fc 0%, #eef2f9 100%); }
  .page-shell { max-width: 1120px; }
  .card-soft {
    background: #fff;
    border: 1px solid rgba(13, 110, 253, .12);
    border-radius: 18px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, .06);
  }
  .section-title { font-weight: 800; letter-spacing: -.2px; }
  .hint { color: #6c757d; font-size: .9rem; }
  .topbar {
    background: #fff;
    border: 1px solid rgba(13,110,253,.12);
    border-radius: 16px;
    padding: 1rem 1.25rem;
    box-shadow: 0 8px 20px rgba(15, 23, 42, .05);
  }
  .sticky-submit {
    position: sticky;
    bottom: 10px;
    z-index: 12;
    background: rgba(246, 248, 252, .85);
    backdrop-filter: blur(4px);
    border-radius: 14px;
    padding: .5rem;
  }
  .summary-input { background-color: #f8f9fa !important; border-color: #e9ecef; }

  .product-block {
    background: #ffffff;
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    transition: border-color 0.2s;
    overflow: hidden;
  }
  .product-block:hover { border-color: #cbd5e1; }

  .product-head{
    padding: 14px;
    background: linear-gradient(0deg, #fff, #f6f8ff);
    border-bottom: 1px solid #e2e8f0;
  }

  .varieties-wrapper {
    background: #f8fafc;
    padding: 14px;
  }
  .variety-row {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    transition: all 0.2s;
    padding: 10px;
  }
  .variety-row:hover {
    border-color: #94a3b8;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  }
  .fs-7 { font-size: 0.85rem; }
  .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; letter-spacing: 1px; }
  .chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    border: 1px solid #e2e8f0;
    background: #fff;
    font-size: .85rem;
  }
</style>

<div class="container page-shell py-4">

  <div class="topbar mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <div class="h5 mb-0 fw-bold">🧾 ایجاد پیش‌فاکتور (Draft)</div>
      <div class="hint">محصول را یکبار انتخاب کن، زیرش هر تعداد مدل/طرح و تعداد اضافه کن.</div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('preinvoice.draft.index') }}">📂 لیست پیش‌نویس‌ها</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm rounded-4 fw-bold">✅ {{ session('success') }}</div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm rounded-4 fw-bold" style="white-space: pre-wrap">
      {!! session('error') !!}
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm rounded-4">
      <div class="fw-bold mb-2">⚠️ خطا:</div>
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form action="{{ route('preinvoice.draft.save') }}" method="POST" id="orderForm">
    @csrf

    {{-- Customer --}}
    <div class="card-soft p-3 p-md-4 mb-4">
      <div class="section-title mb-3">👤 اطلاعات مشتری</div>

      <div class="row g-3">
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <label class="form-label fw-semibold mb-0">انتخاب مشتری (اختیاری)</label>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickCustomerModal">
              ➕ ساخت مشتری جدید
            </button>
          </div>
          <select id="customer_select" class="form-select mt-2"></select>
          <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id', '') }}">
          <div class="hint mt-2" id="customer_balance_hint"></div>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">شماره موبایل</label>
          <input type="text" name="customer_mobile" id="customer_mobile" class="form-control"
                 value="{{ old('customer_mobile') }}" required>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">نام مشتری</label>
          <input type="text" name="customer_name" id="customer_name" class="form-control"
                 value="{{ old('customer_name') }}" required>
        </div>
      </div>
    </div>

    {{-- Shipping --}}
    <div class="card-soft p-3 p-md-4 mb-4">
      <div class="section-title">🚚 ارسال و مقصد</div>

      <div class="row g-3 align-items-end">
        <div class="col-lg-6">
          <label class="form-label fw-semibold">شیوه ارسال</label>
          <select id="shipping_id" name="shipping_id" class="form-select" required>
            <option value="">انتخاب روش ارسال...</option>
          </select>

          <div class="hint mt-2" id="shipping_label">هزینه ارسال</div>
          <input type="hidden" id="shipping_price" name="shipping_price" value="{{ old('shipping_price', 0) }}">
        </div>

        <div class="col-lg-6">
          <div class="p-3 rounded-4" style="background: rgba(13,110,253,.05); border:1px dashed rgba(13,110,253,.3)">
            <div class="fw-bold">📦 وضعیت</div>
            <div class="hint mt-2">با تغییر روش ارسال/شهر، جمع کل دوباره محاسبه می‌شود.</div>
          </div>
        </div>
      </div>

      <div id="locationWrapper" class="row g-3 mt-2">
        <div class="col-md-6">
          <label class="form-label fw-semibold">استان</label>
          <select id="province_id" name="province_id" class="form-select" required>
            <option value=""></option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">شهر</label>
          <select id="city_id" name="city_id" class="form-select">
            <option value=""></option>
          </select>
        </div>
      </div>

      <div id="addressWrapper" class="mt-3">
        <label class="form-label fw-semibold">آدرس</label>
        <textarea id="customer_address" name="customer_address" class="form-control" rows="3" required>{{ old('customer_address') }}</textarea>
      </div>
    </div>

    {{-- Products --}}
    <div class="card-soft mb-4">
      <div class="p-3 p-md-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <div class="section-title mb-1">🛍️ محصولات</div>
          <div class="hint">محصول را سرچ کن (نام یا کد ۴ رقمی) → انتخاب کن → زیرش مدل/طرح‌های مختلف اضافه کن.</div>
        </div>

        <div class="d-flex gap-2 align-items-center flex-wrap">
          <button type="button" id="addProductBlockBtn" class="btn btn-outline-primary">➕ افزودن محصول</button>
        </div>
      </div>

      <div id="productBlocksContainer" class="p-3 p-md-4"></div>
    </div>

    {{-- Summary --}}
    <div class="card-soft p-3 p-md-4 mb-4">
      <div class="section-title">💳 جمع‌بندی</div>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold">تخفیف (تومان)</label>
          <input type="number" name="discount_amount" id="discount" class="form-control summary-input"
                 value="{{ old('discount_amount', 0) }}">
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">هزینه ارسال</label>
          <input type="text" id="shipping_price_view" class="form-control summary-input" readonly value="0 تومان">
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">جمع کل (تومان)</label>
          <input type="text" name="total_price" id="total_price" class="form-control fw-bold summary-input" readonly>
        </div>
      </div>

      <input type="hidden" name="payment_status" value="pending">
    </div>

    <div class="sticky-submit">
      <button class="btn btn-primary w-100 fs-5 py-3 shadow-sm">💾 ذخیره پیش‌نویس</button>
    </div>
  </form>

</div>

<div class="modal fade" id="quickCustomerModal" tabindex="-1" aria-labelledby="quickCustomerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" id="quickCustomerForm">
      <div class="modal-header">
        <h5 class="modal-title" id="quickCustomerModalLabel">➕ افزودن مشتری جدید</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" id="quickCustomerErrors"></div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">نام مشتری *</label>
            <input type="text" class="form-control" name="customer_name" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">شماره موبایل *</label>
            <input type="text" class="form-control" name="mobile" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">استان</label>
            <select class="form-select" name="province_id" id="quick_customer_province_id">
              <option value=""></option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">شهر</label>
            <select class="form-select" name="city_id" id="quick_customer_city_id" disabled>
              <option value=""></option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">آدرس</label>
            <textarea class="form-control" rows="2" name="address"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
        <button type="submit" class="btn btn-primary" id="quickCustomerSubmitBtn">ثبت مشتری</button>
      </div>
    </form>
  </div>
</div>

<script>
  const API = {
    products:  "{{ url('/preinvoice/api/products') }}",
    product:   "{{ url('/preinvoice/api/products') }}", // /{id}
    area:      "{{ url('/preinvoice/api/area') }}",
    customers: "{{ url('/preinvoice/api/customers') }}",
    customer:  "{{ url('/preinvoice/api/customers') }}", // /{id}
  };

  var INIT_ROWS = @json($initRows);
  const INITIAL_SHIPPINGS = @json($shippingMethods);
</script>

{{-- مشتری/آدرس/ارسال (همون منطق خودت، بدون template literal) --}}
<script>
let shippings = INITIAL_SHIPPINGS || [];
let areaProvinces = [];

function initLocationSelect2(selectEl, placeholder) {
  if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;
  const $el = $(selectEl);
  if ($el.hasClass('select2-hidden-accessible')) {
    $el.off('select2:select select2:clear');
    $el.select2('destroy');
  }
  $el.select2({ width:'100%', dir:'rtl', placeholder: placeholder, allowClear:true });
  $el.on('select2:select select2:clear', function(){ this.dispatchEvent(new Event('change',{bubbles:true})); });
}
function setSelectDisabled(selectEl, disabled) {
  selectEl.disabled = disabled;
  if (window.jQuery && $(selectEl).hasClass('select2-hidden-accessible')) {
    $(selectEl).prop('disabled', disabled).trigger('change.select2');
  }
}

async function loadArea() {
  const res = await fetch(API.area, { headers: { 'Accept': 'application/json' } });
  const data = await res.json();
  areaProvinces = (data && data.data && data.data.provinces) ? data.data.provinces : [];
}
function fillProvincesSelect(provincesToShow) {
  const provinceSelect = document.getElementById('province_id');
  provinceSelect.innerHTML = '<option value=""></option>';
  (provincesToShow || []).forEach(p => {
    const opt = document.createElement('option');
    opt.value = p.id; opt.textContent = (p.name || '').trim();
    provinceSelect.appendChild(opt);
  });
  initLocationSelect2(provinceSelect, 'انتخاب استان...');
}
function fillCities(citiesToShow) {
  const citySelect = document.getElementById('city_id');
  citySelect.innerHTML = '<option value=""></option>';
  (citiesToShow || []).forEach(c => {
    const opt = document.createElement('option');
    opt.value = c.id; opt.textContent = (c.name || '').trim();
    citySelect.appendChild(opt);
  });
  setSelectDisabled(citySelect, (citiesToShow || []).length === 0);
  initLocationSelect2(citySelect, 'انتخاب شهر...');
}
function fillCitiesByProvinceId(provinceId) {
  const province = areaProvinces.find(p => Number(p.id) === Number(provinceId));
  fillCities(province && province.cities ? province.cities : []);
}

function fillShippingSelect() {
  const shippingSelect = document.getElementById('shipping_id');
  shippingSelect.innerHTML = '<option value="">انتخاب روش ارسال...</option>';
  shippings.forEach(s => {
    const opt = document.createElement('option');
    opt.value = s.id;
    opt.textContent = s.name;
    shippingSelect.appendChild(opt);
  });
}

function setCustomerLocation(provinceId, cityId) {
  const provinceSelect = document.getElementById('province_id');
  const citySelect = document.getElementById('city_id');

  if (provinceId) {
    provinceSelect.value = String(provinceId);
    if (window.jQuery) $(provinceSelect).trigger('change.select2');
    fillCitiesByProvinceId(provinceId);
  }

  if (cityId) {
    citySelect.value = String(cityId);
    if (window.jQuery) $(citySelect).trigger('change.select2');
  }
}

function initCustomerSelect() {
  const customerSelect = document.getElementById('customer_select');
  const customerIdInput = document.getElementById('customer_id');
  const hint = document.getElementById('customer_balance_hint');

  if (!customerSelect || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;

  $(customerSelect).select2({
    width: '100%',
    dir: 'rtl',
    placeholder: 'جستجو مشتری با نام/موبایل...',
    allowClear: true,
    minimumInputLength: 2,
    ajax: {
      url: API.customers,
      dataType: 'json',
      delay: 250,
      data: function(params){ return { q: params.term || '' }; },
      processResults: function(resp){
        const items = (resp && resp.data && resp.data.customers) ? resp.data.customers : [];
        return {
          results: items.map(function(c){
            const t = ((c.first_name || '').trim() + ' ' + (c.last_name || '').trim()).trim();
            return { id: c.id, text: (t !== '' ? t : '—') + ' - ' + (c.mobile || '') };
          })
        };
      }
    }
  });

  $(customerSelect).on('select2:select', async function (e) {
    const customerId = e && e.params && e.params.data ? e.params.data.id : null;
    customerIdInput.value = customerId || '';
    if (!customerId) return;

    const res = await fetch(API.customer + '/' + customerId, { headers: { 'Accept':'application/json' } });
    const json = await res.json();
    const c = json && json.data ? json.data.customer : null;
    if (!c) return;

    document.getElementById('customer_name').value = (((c.first_name || '').trim() + ' ' + (c.last_name || '').trim()).trim());
    document.getElementById('customer_mobile').value = c.mobile || '';
    document.getElementById('customer_address').value = c.address || '';

    setCustomerLocation(c.province_id, c.city_id);

    hint.textContent = 'مانده حساب: ' + (Number(c.balance || 0)).toLocaleString() + ' تومان';
  });

  $(customerSelect).on('select2:clear', function () {
    customerIdInput.value = '';
    hint.textContent = '';
  });
}

function upsertCustomerOption(customer) {
  const customerSelect = document.getElementById('customer_select');
  if (!customerSelect || !window.jQuery) return;

  const title = (((customer.first_name || '').trim() + ' ' + (customer.last_name || '').trim()).trim()) || '—';
  const text = title + ' - ' + (customer.mobile || '');

  const exists = Array.from(customerSelect.options).some(function (opt) {
    return Number(opt.value) === Number(customer.id);
  });

  if (!exists) {
    const newOption = new Option(text, customer.id, false, false);
    customerSelect.add(newOption);
  }

  $(customerSelect).val(String(customer.id)).trigger('change');
  $(customerSelect).trigger({
    type: 'select2:select',
    params: {
      data: {
        id: customer.id,
        text: text
      }
    }
  });
}

function initQuickCustomerModal() {
  const form = document.getElementById('quickCustomerForm');
  const modalEl = document.getElementById('quickCustomerModal');
  const provinceSelect = document.getElementById('quick_customer_province_id');
  const citySelect = document.getElementById('quick_customer_city_id');
  const errorsBox = document.getElementById('quickCustomerErrors');
  const submitBtn = document.getElementById('quickCustomerSubmitBtn');
  if (!form || !modalEl || !provinceSelect || !citySelect) return;

  const modalInstance = new bootstrap.Modal(modalEl);

  function resetErrors() {
    errorsBox.classList.add('d-none');
    errorsBox.innerHTML = '';
  }

  function showErrors(messages) {
    errorsBox.classList.remove('d-none');
    errorsBox.innerHTML = '<ul class="mb-0">' + messages.map(function (msg) {
      return '<li>' + msg + '</li>';
    }).join('') + '</ul>';
  }

  function setCityOptions(provinceId) {
    const province = areaProvinces.find(function (item) {
      return Number(item.id) === Number(provinceId);
    });
    const cities = province && province.cities ? province.cities : [];

    citySelect.innerHTML = '<option value=""></option>';
    cities.forEach(function (city) {
      const option = document.createElement('option');
      option.value = city.id;
      option.textContent = city.name || '';
      citySelect.appendChild(option);
    });
    setSelectDisabled(citySelect, cities.length === 0);
  }

  provinceSelect.addEventListener('change', function () {
    setCityOptions(this.value || '');
  });

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    resetErrors();
    submitBtn.disabled = true;

    const formData = new FormData(form);
    const payload = {
      customer_name: formData.get('customer_name') || '',
      mobile: formData.get('mobile') || '',
      province_id: formData.get('province_id') || null,
      city_id: formData.get('city_id') || null,
      address: formData.get('address') || ''
    };

    try {
      const res = await fetch(API.customers, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(payload)
      });

      const json = await res.json();
      if (!res.ok) {
        const messages = json && json.errors ? Object.values(json.errors).flat() : ['خطا در ثبت مشتری.'];
        showErrors(messages);
        return;
      }

      const customer = json && json.data ? json.data.customer : null;
      if (!customer) {
        showErrors(['پاسخ سرور نامعتبر است.']);
        return;
      }

      upsertCustomerOption(customer);
      form.reset();
      citySelect.innerHTML = '<option value=""></option>';
      setSelectDisabled(citySelect, true);
      modalInstance.hide();
    } catch (error) {
      showErrors(['ارتباط با سرور برقرار نشد.']);
    } finally {
      submitBtn.disabled = false;
    }
  });

  modalEl.addEventListener('shown.bs.modal', function () {
    resetErrors();
    provinceSelect.innerHTML = '<option value=""></option>';
    areaProvinces.forEach(function (province) {
      const option = document.createElement('option');
      option.value = province.id;
      option.textContent = province.name || '';
      provinceSelect.appendChild(option);
    });
    setCityOptions('');
    initLocationSelect2(provinceSelect, 'انتخاب استان...');
    initLocationSelect2(citySelect, 'انتخاب شهر...');
  });
}

document.addEventListener('DOMContentLoaded', async () => {
  const shippingSelect = document.getElementById('shipping_id');
  const provinceSelect = document.getElementById('province_id');

  initLocationSelect2(document.getElementById('province_id'), 'انتخاب استان...');
  initLocationSelect2(document.getElementById('city_id'), 'انتخاب شهر...');

  await loadArea();
  fillProvincesSelect(areaProvinces);

  fillShippingSelect();

  initCustomerSelect();
  initQuickCustomerModal();

  provinceSelect.addEventListener('change', () => {
    fillCitiesByProvinceId(provinceSelect.value);
  });

  shippingSelect.addEventListener('change', () => {
    const sid = parseInt(shippingSelect.value || 0);
    const ship = shippings.find(x => Number(x.id) === Number(sid)) || null;
    const price = ship ? parseInt(ship.price || 0) : 0;

    document.getElementById('shipping_price').value = String(price);
    document.getElementById('shipping_label').textContent = 'هزینه ارسال: ' + price.toLocaleString() + ' تومان';
    document.getElementById('shipping_price_view').value = price.toLocaleString() + ' تومان';
    updateTotal();
  });

  document.getElementById('discount').addEventListener('input', updateTotal);
});
</script>

{{-- بخش محصولات: محصول یکبار انتخاب می‌شود و زیرش چند مدل/طرح اضافه می‌کنیم --}}
<script>
let globalRowIndex = 0;
const productCache = new Map();

function createEl(html){
  const tmp = document.createElement('div');
  tmp.innerHTML = html.trim();
  return tmp.firstChild;
}

function formatPrice(val){
  const n = Number(val);
  if(!Number.isFinite(n)) return '';
  return n.toLocaleString('fa-IR');
}

async function getProductDetails(productId){
  const id = String(productId || '');
  if(productCache.has(id)) return productCache.get(id);

  const res = await fetch(API.product + '/' + id, { headers: { 'Accept':'application/json' } });
  const data = await res.json();
  const product = data && data.data ? data.data.product : null;

  productCache.set(id, product);
  return product;
}

function buildVarietyLabel(v){
  // سازگار با ساختاری که خودت نوشتی
  const attrs = (v && v.attributes) ? v.attributes : [];
  let name = '';
  if(Array.isArray(attrs) && attrs.length){
    name = attrs.map(a => (a && a.pivot ? a.pivot.value : '')).join(' ').trim();
  }
  if(!name) name = (v && v.unique_attributes_key ? String(v.unique_attributes_key).trim() : '');
  if(!name) name = 'مدل ' + (v && v.id ? v.id : '');
  return name;
}

function setStockUI(row, stockQty){
  const qtyInput = row.querySelector('.quantity-input');
  const badge = row.querySelector('.stock-badge');

  const qty = Number.isFinite(Number(stockQty)) ? Number(stockQty) : 0;

  if(qty > 0){
    qtyInput.disabled = false;
    qtyInput.min = '1';
    qtyInput.max = String(qty);
    const cur = parseInt(qtyInput.value || '0', 10);
    if(cur < 1) qtyInput.value = '1';
    if(cur > qty) qtyInput.value = String(qty);

    badge.className = 'badge bg-success stock-badge';
    badge.textContent = 'موجودی: ' + qty;
  } else {
    qtyInput.value = '0';
    qtyInput.min = '0';
    qtyInput.max = '0';
    qtyInput.disabled = true;

    badge.className = 'badge bg-danger stock-badge';
    badge.textContent = 'ناموجود';
  }
}

function updateTotal(){
  const discount = parseFloat(document.getElementById('discount')?.value || 0) || 0;
  const shipping = parseFloat(document.getElementById('shipping_price')?.value || 0) || 0;

  let total = 0;
  document.querySelectorAll('.variety-row').forEach(row => {
    const price = parseFloat(row.querySelector('.price-raw')?.value || 0) || 0;
    const quantity = parseInt(row.querySelector('.quantity-input')?.value || 0) || 0;
    total += price * quantity;
  });

  const finalTotal = Math.max(total + shipping - discount, 0);
  document.getElementById('total_price').value = finalTotal.toLocaleString('fa-IR');
}

function initProductSelect2(selectEl){
  $(selectEl).select2({
    width:'100%',
    dir:'rtl',
    placeholder:'جستجوی نام یا کد 4 رقمی محصول...',
    allowClear:true,
    minimumInputLength: 1,
    ajax: {
      url: API.products,
      dataType: 'json',
      delay: 250,
      data: function(params){
        return { q: params.term || '', page: params.page || 1 };
      },
      processResults: function(resp, params){
        params.page = params.page || 1;

        const list = (resp && resp.data && resp.data.products && resp.data.products.data) ? resp.data.products.data : [];
        const more = (resp && resp.data && resp.data.products && resp.data.products.next_page_url) ? true : false;

        return {
          results: list.map(function(p){
            const code = p.code || '';
            const title = p.title || '';
            return {
              id: p.id,
              text: '[کد: ' + code + '] ' + title
            };
          }),
          pagination: { more: more }
        };
      }
    }
  });

  $(selectEl).on('select2:select select2:clear', function(){
    this.dispatchEvent(new Event('change', {bubbles:true}));
  });
}

function addProductBlock(prefill){
  prefill = prefill || {};
  const container = document.getElementById('productBlocksContainer');
  const blockIndex = container.children.length + 1;

  const block = createEl(
    '<div class="product-block mb-4 shadow-sm">' +
      '<div class="product-head">' +
        '<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">' +
          '<div>' +
            '<div class="fw-bold text-primary">محصول #' + blockIndex + '</div>' +
            '<div class="hint">محصول را انتخاب کن، سپس مدل/طرح‌ها را زیرش اضافه کن.</div>' +
          '</div>' +
          '<button type="button" class="btn btn-outline-danger btn-sm remove-block">🗑️ حذف این محصول</button>' +
        '</div>' +

        '<div class="mt-3">' +
          '<label class="form-label fw-semibold">انتخاب کالا (جستجو نام یا کد 4 رقمی)</label>' +
          '<select class="form-select product-select" required></select>' +
        '</div>' +

        '<div class="mt-2 d-flex gap-2 flex-wrap">' +
          '<span class="chip"><span class="text-muted">کد:</span> <span class="fw-bold product-code">—</span></span>' +
          '<span class="chip"><span class="text-muted">قیمت پیش‌فرض:</span> <span class="fw-bold product-price">—</span></span>' +
        '</div>' +
      '</div>' +

      '<div class="varieties-wrapper">' +
        '<div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom flex-wrap gap-2">' +
          '<div class="fw-semibold text-secondary">مدل/طرح‌های انتخابی برای این محصول</div>' +
          '<button type="button" class="btn btn-sm btn-primary add-variety-btn">➕ افزودن مدل/طرح</button>' +
        '</div>' +
        '<div class="varieties-list-container d-flex flex-column gap-2 mt-3"></div>' +
      '</div>' +
    '</div>'
  );

  container.appendChild(block);

  const productSelect = block.querySelector('.product-select');
  initProductSelect2(productSelect);

  block.querySelector('.remove-block').addEventListener('click', function(){
    block.remove();
    updateTotal();
  });

  block.querySelector('.add-variety-btn').addEventListener('click', function(){
    const pid = productSelect.value || '';
    if(!pid){
      alert('اول محصول را انتخاب کن.');
      return;
    }
    addVarietyRow(block, pid, null);
  });

  productSelect.addEventListener('change', async function(){
    const pid = productSelect.value || '';

    // reset header
    block.querySelector('.product-code').textContent = '—';
    block.querySelector('.product-price').textContent = '—';

    // پاک کردن همه ردیف‌ها و ساخت یک ردیف جدید
    const list = block.querySelector('.varieties-list-container');
    list.innerHTML = '';

    if(!pid){
      updateTotal();
      return;
    }

    const product = await getProductDetails(pid);
    const code = product && product.code ? product.code : (product && product.id ? product.id : '—');
    const price = product && product.price ? product.price : 0;

    block.querySelector('.product-code').textContent = String(code);
    block.querySelector('.product-price').textContent = formatPrice(price) + ' تومان';

    addVarietyRow(block, pid, null);
    updateTotal();
  });

  // prefill برای حالت ادیت/old
  if(prefill.product_id){
    // ست کردن مقدار select2
    (async function(){
      const pid = String(prefill.product_id);
      const product = await getProductDetails(pid);
      const code = product && product.code ? product.code : '';
      const title = product && product.title ? product.title : '';
      const text = '[کد: ' + code + '] ' + title;

      const opt = new Option(text, pid, true, true);
      productSelect.appendChild(opt);
      $(productSelect).trigger('change');

      // اگر ردیف‌ها داشت
      if(prefill.rows && prefill.rows.length){
        const list = block.querySelector('.varieties-list-container');
        list.innerHTML = '';
        for(let i=0;i<prefill.rows.length;i++){
          addVarietyRow(block, pid, prefill.rows[i]);
        }
      }
      updateTotal();
    })();
  }
}

async function addVarietyRow(block, productId, prefillRow){
  const list = block.querySelector('.varieties-list-container');
  const idx = globalRowIndex++;

  const row = createEl(
    '<div class="variety-row row g-2 align-items-end">' +
      '<input type="hidden" name="products[' + idx + '][id]" class="hidden-product-id" value="' + String(productId) + '">' +

      '<div class="col-md-5">' +
        '<label class="form-label fs-7">مدل/طرح</label>' +
        '<select name="products[' + idx + '][variety_id]" class="form-select form-select-sm variety-select" required>' +
          '<option value="">در حال بارگذاری...</option>' +
        '</select>' +
      '</div>' +

      '<div class="col-md-2">' +
        '<label class="form-label fs-7">تعداد</label>' +
        '<input type="number" name="products[' + idx + '][quantity]" class="form-control form-control-sm quantity-input" min="1" value="1" required>' +
      '</div>' +

      '<div class="col-md-4">' +
        '<label class="form-label fs-7">قیمت واحد</label>' +
        '<input type="text" class="form-control form-control-sm price-view" readonly>' +
        '<input type="hidden" name="products[' + idx + '][price]" class="price-raw" value="0">' +
        '<div class="mt-1 d-flex gap-2 flex-wrap align-items-center">' +
          '<span class="badge bg-secondary stock-badge">—</span>' +
          '<span class="badge bg-light text-dark mono code11-badge" style="border:1px solid #e2e8f0;">—</span>' +
        '</div>' +
      '</div>' +

      '<div class="col-md-1 text-center">' +
        '<button type="button" class="btn btn-outline-danger btn-sm w-100 remove-variety" title="حذف این مدل">❌</button>' +
      '</div>' +
    '</div>'
  );

  list.appendChild(row);

  const varietySelect = row.querySelector('.variety-select');
  const qtyInput = row.querySelector('.quantity-input');
  const priceRaw = row.querySelector('.price-raw');
  const priceView = row.querySelector('.price-view');
  const codeBadge = row.querySelector('.code11-badge');

  // حذف ردیف
  row.querySelector('.remove-variety').addEventListener('click', function(){
    if(list.children.length <= 1){
      alert('حداقل یک مدل/طرح باید باشد. در صورت عدم نیاز، کل محصول را حذف کن.');
      return;
    }
    row.remove();
    updateTotal();
  });

  // لود جزئیات محصول و مدل‌ها
  const product = await getProductDetails(productId);
  const varieties = product && product.varieties ? product.varieties : [];

  varietySelect.innerHTML = '';

  if(!varieties.length){
    // محصول بدون مدل => variety_id = 0
    const opt = document.createElement('option');
    opt.value = '0';
    opt.textContent = 'بدون مدل';
    varietySelect.appendChild(opt);
    varietySelect.value = '0';
    varietySelect.disabled = true;

    const price = product && product.price ? product.price : 0;
    priceRaw.value = String(price);
    priceView.value = formatPrice(price) + ' تومان';

    setStockUI(row, product && product.quantity ? product.quantity : 0);
    codeBadge.textContent = (product && product.code ? String(product.code) : '—');
    updateTotal();
  } else {
    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = 'انتخاب مدل/طرح...';
    varietySelect.appendChild(opt0);

    varieties.forEach(function(v){
      const opt = document.createElement('option');
      opt.value = String(v.id);
      opt.textContent = buildVarietyLabel(v);
      varietySelect.appendChild(opt);
    });

    varietySelect.disabled = false;

    // prefill
    if(prefillRow){
      if(prefillRow.quantity) qtyInput.value = String(prefillRow.quantity);
      if(prefillRow.variety_id !== undefined && prefillRow.variety_id !== null){
        varietySelect.value = String(prefillRow.variety_id);
      }
    }

    // وقتی مدل انتخاب شد قیمت/موجودی/کد را بزن
    const applySelectedVariety = function(){
      const vid = parseInt(varietySelect.value || 0);
      if(!vid){
        priceRaw.value = '0';
        priceView.value = '';
        setStockUI(row, 0);
        codeBadge.textContent = '—';
        updateTotal();
        return;
      }

      const v = varieties.find(function(x){ return Number(x.id) === Number(vid); });
      if(!v){
        updateTotal();
        return;
      }

      const price = v.price || (product && product.price ? product.price : 0);
      priceRaw.value = String(price);
      priceView.value = formatPrice(price) + ' تومان';

      setStockUI(row, v.quantity ?? 0);

      // اگر API کد 11 رقمی بده نمایش می‌دیم، وگرنه یک شناسه ساده
      const code11 = (v.variant_code || v.code || v.barcode || '');
      codeBadge.textContent = code11 ? String(code11) : ('VID-' + String(v.id));

      updateTotal();
    };

    varietySelect.addEventListener('change', applySelectedVariety);
    qtyInput.addEventListener('input', updateTotal);

    // اگر prefill داشت، اعمال کن
    applySelectedVariety();

    // اگر prefill قیمت داشت و می‌خوای override بشه:
    if(prefillRow && prefillRow.price !== undefined && prefillRow.price !== null){
      priceRaw.value = String(prefillRow.price);
      priceView.value = formatPrice(prefillRow.price) + ' تومان';
      updateTotal();
    }
  }

  qtyInput.addEventListener('input', updateTotal);
}

function initFromOldOrEdit(){
  const container = document.getElementById('productBlocksContainer');

  if(!INIT_ROWS || !INIT_ROWS.length){
    addProductBlock({});
    return;
  }

  // group by product id so product is selected once
  const grouped = {};
  INIT_ROWS.forEach(function(r){
    const pid = parseInt(r.id || 0, 10);
    if(!pid) return;
    if(!grouped[pid]) grouped[pid] = [];
    grouped[pid].push({
      product_id: pid,
      variety_id: parseInt(r.variety_id || 0, 10),
      quantity: parseInt(r.quantity || 1, 10),
      price: parseInt(r.price || 0, 10),
    });
  });

  Object.keys(grouped).forEach(function(pidStr){
    const pid = parseInt(pidStr, 10);
    addProductBlock({
      product_id: pid,
      rows: grouped[pid]
    });
  });

  updateTotal();
}

document.addEventListener('DOMContentLoaded', function(){
  document.getElementById('addProductBlockBtn').addEventListener('click', function(){
    addProductBlock({});
  });

  initFromOldOrEdit();
  updateTotal();
});
</script>

{{-- تبدیل ورودی‌ها به عدد قبل از submit --}}
<script>
(function () {
  function toEnglishDigits(str) {
    return String(str || '')
      .replace(/[۰-۹]/g, function(d){ return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); })
      .replace(/[٠-٩]/g, function(d){ return '٠١٢٣٤٥٦٧٨٩'.indexOf(d); });
  }
  function toInt(val) {
    const s = toEnglishDigits(val)
      .replaceAll(',', '')
      .replaceAll('٬', '')
      .replaceAll('،', '')
      .trim();
    const n = parseFloat(s);
    return Number.isFinite(n) ? Math.trunc(n) : 0;
  }

  document.addEventListener('DOMContentLoaded', function(){
    const form = document.getElementById('orderForm');
    if (!form) return;

    form.addEventListener('submit', function(){
      const totalEl = document.getElementById('total_price');
      if (totalEl) totalEl.value = String(toInt(totalEl.value));

      const shipEl = document.getElementById('shipping_price');
      if (shipEl) shipEl.value = String(toInt(shipEl.value));

      const discEl = document.getElementById('discount');
      if (discEl) discEl.value = String(toInt(discEl.value));

      document.querySelectorAll('.price-raw').forEach(function(el){ el.value = String(toInt(el.value)); });
      document.querySelectorAll('.quantity-input').forEach(function(el){ el.value = String(toInt(el.value)); });
    }, { capture: true });
  });
})();
</script>

@endsection
