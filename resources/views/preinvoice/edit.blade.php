<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="{{ asset('lib/bootstrap.rtl.min.css') }}">
  <link rel="stylesheet" href="{{ asset('lib/select2.min.css') }}">

  <script src="{{ asset('lib/jquery.min.js') }}"></script>
  <script src="{{ asset('lib/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('lib/select2.min.js') }}"></script>

  <title>ویرایش پیش‌نویس</title>

  <style>
    body {
      background: linear-gradient(180deg, #f6f8fc 0%, #eef2f9 100%);
    }
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
    .summary-input {
      background-color: #f8f9fa !important;
      border-color: #e9ecef;
    }
    .actions-bar .btn {
      min-width: 152px;
      border-radius: 10px;
    }
  </style>

<meta name="csrf-token" content="{{ csrf_token() }}">

</head>

<body class="py-4">
<div class="container page-shell">

  <div class="topbar mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <div class="h5 mb-0 fw-bold">📝 ویرایش پیش‌نویس</div>
      <div class="hint">کد: {{ $order->uuid }}</div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('preinvoice.draft.index') }}">📂 لیست پیش‌نویس‌ها</a>
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

  <form action="{{ route('preinvoice.draft.update', $order->uuid) }}" method="POST" id="orderForm">
    @csrf
    @method('PUT')
{{-- Customer Picker --}}
<div class="card-soft p-3 p-md-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
      <div class="section-title">👤 انتخاب مشتری</div>
      <button type="button" class="btn btn-outline-primary btn-sm" id="openCreateCustomer">
        ➕ ساخت مشتری جدید
      </button>
    </div>

    <label class="form-label fw-semibold">جستجو مشتری (نام/فامیل/موبایل)</label>
    <select id="customer_select" class="form-select"></select>

    <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id', $order->customer_id ?? '') }}">
    <div class="hint mt-2" id="customer_balance_hint"></div>
  </div>

    {{-- Customer --}}
    <div class="card-soft p-3 p-md-4 mb-4">
      <div class="section-title mb-3">👤 اطلاعات مشتری</div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">شماره موبایل</label>
          <input type="text" name="customer_mobile" id="customer_mobile" class="form-control"
                 value="{{ old('customer_mobile', $order->customer_mobile) }}" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">نام مشتری</label>
          <input type="text" name="customer_name" id="customer_name" class="form-control"
                 value="{{ old('customer_name', $order->customer_name) }}" required>
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
          <input type="hidden" id="shipping_price" name="shipping_price"
                 value="{{ old('shipping_price', $order->shipping_price) }}">
        </div>

        <div class="col-lg-6">
          <div class="p-3 rounded-4" style="background: rgba(13,110,253,.05); border:1px dashed rgba(13,110,253,.3)">
            <div class="fw-bold">📦 وضعیت</div>
            <div class="hint mt-2">بعد از تغییر روش ارسال/شهر، جمع کل دوباره محاسبه می‌شود.</div>
          </div>
        </div>
      </div>

      <div id="locationWrapper" class="row g-3 mt-2">
        <div class="col-md-6">
          <label class="form-label fw-semibold">استان</label>
          <select id="province_id" name="province_id" class="form-select">
            <option value=""></option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">شهر</label>
          <select id="city_id" name="city_id" class="form-select" disabled>
            <option value=""></option>
          </select>
        </div>
      </div>

      <div id="addressWrapper" class="mt-3">
        <label class="form-label fw-semibold">آدرس</label>
        <textarea id="customer_address" name="customer_address" class="form-control" rows="3" required>{{ old('customer_address', $order->customer_address) }}</textarea>
      </div>
    </div>

    {{-- Products --}}
    <div class="card-soft mb-4">
      <div class="p-3 p-md-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <div class="section-title mb-1">🛍️ محصولات</div>
          <div class="hint">آیتم‌ها را تغییر بده و ذخیره کن.</div>
        </div>

        <div class="d-flex gap-2 align-items-center flex-wrap actions-bar">
          <button type="button" id="addRow" class="btn btn-outline-primary">➕ افزودن محصول</button>
          <button type="submit" class="btn btn-primary">💾 ذخیره تغییرات</button>
        </div>
      </div>

      <div id="productRows" class="p-3 p-md-4"></div>
    </div>

    {{-- Summary --}}
    <div class="card-soft p-3 p-md-4 mb-4">
      <div class="section-title">💳 جمع‌بندی</div>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold">تخفیف</label>
          <input type="number" name="discount_amount" id="discount" class="form-control summary-input"
                 value="{{ old('discount_amount', $order->discount_amount) }}"
                 readonly>
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">هزینه ارسال</label>
          <input type="text" id="shipping_price_view" class="form-control summary-input" readonly
                 value="{{ number_format((int)$order->shipping_price) }} تومان">
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">جمع کل</label>
          <input type="text" name="total_price" id="total_price" class="form-control fw-bold summary-input" readonly>
        </div>
      </div>
    </div>

    <div class="sticky-submit">
      <button class="btn btn-primary w-100 fs-5 py-3 shadow-sm">💾 ذخیره تغییرات</button>
    </div>
  </form>
  <form method="POST" action="{{ route('preinvoice.draft.finalize', $order->uuid) }}" class="mt-3">
    @csrf
    <button class="btn btn-success w-100 py-3 fw-semibold shadow-sm">✅ ثبت نهایی و ساخت فاکتور</button>
  </form>


</div>
@php
  $draftItems = $order->items->map(function ($it) {
      return [
          'product_id' => (int) $it->product_id,
          'variety_id' => (int) $it->variant_id,
          'quantity'   => (int) $it->quantity,
      ];
  })->values();

  $draftOrder = [
      'shipping_id'    => (int) $order->shipping_id,
      'shipping_price' => (int) $order->shipping_price,
      'province_id'    => (int) $order->province_id,
      'city_id'        => (int) ($order->city_id ?? 0),
  ];
@endphp
<script>
  const draftOrder = @json($draftOrder);
  const draftItems = @json($draftItems);

  const initialProvinceId = {{ (int) old('province_id', $order->province_id) }};
  const initialCityId = {{ (int) old('city_id', $order->city_id ?? 0) }};
  const INITIAL_SHIPPINGS = @json($shippingMethods);

  const API = {
    products:  "{{ url('/preinvoice/api/products') }}",
    product:   "{{ url('/preinvoice/api/products') }}", // /{id}
    area:      "{{ url('/preinvoice/api/area') }}",
  };
  API.customers = "{{ url('/preinvoice/api/customers') }}";
</script>

<script>
/* =========================
   Helpers
========================= */
function createEl(html){
  const tmp = document.createElement('div');
  tmp.innerHTML = html.trim();
  return tmp.firstChild;
}
function formatPrice(val){
  const n = Number(val);
  if (!Number.isFinite(n)) return '';
  return n.toLocaleString('fa-IR');
}
function safeInt(v, def = 0){
  const n = parseInt(String(v ?? '').trim(), 10);
  return Number.isFinite(n) ? n : def;
}

/* =========================
   Select2
========================= */
function initSelect2(selectEl, placeholder){
  if (!window.jQuery || !window.jQuery.fn?.select2) return;
  const $el = $(selectEl);

  // ✅ اگر قبلاً select2 شده، دوباره destroy نکن
  if ($el.hasClass('select2-hidden-accessible')) return;

  $el.select2({ width:'100%', dir:'rtl', placeholder, allowClear:true });
  $el.on('select2:select select2:clear', function(){
    this.dispatchEvent(new Event('change', { bubbles:true }));
  });
}

function setSelectDisabled(selectEl, disabled){
  selectEl.disabled = !!disabled;
  if (window.jQuery && $(selectEl).hasClass('select2-hidden-accessible')) {
    $(selectEl).prop('disabled', !!disabled).trigger('change.select2');
  }
}

/* =========================
   Location + Shipping
========================= */
let shippings = INITIAL_SHIPPINGS || [];
let areaProvinces = [];

async function loadArea(){
  const res = await fetch(API.area, { headers: { 'Accept': 'application/json' } });
  const data = await res.json();
  areaProvinces = data?.data?.provinces ?? [];
}
function fillProvincesSelect(provinces){
  const el = document.getElementById('province_id');
  el.innerHTML = '<option value=""></option>';
  (provinces ?? []).forEach(p => {
    const opt = document.createElement('option');
    opt.value = p.id;
    opt.textContent = (p.name ?? '').trim();
    el.appendChild(opt);
  });
  initSelect2(el, 'انتخاب استان...');
}
function fillCities(cities){
  const el = document.getElementById('city_id');
  el.innerHTML = '<option value=""></option>';
  (cities ?? []).forEach(c => {
    const opt = document.createElement('option');
    opt.value = c.id;
    opt.textContent = (c.name ?? '').trim();
    el.appendChild(opt);
  });
  initSelect2(el, 'انتخاب شهر...');
  setSelectDisabled(el, !(cities && cities.length));
}
function fillCitiesByProvinceId(provinceId){
  const p = areaProvinces.find(x => Number(x.id) === Number(provinceId));
  fillCities(p?.cities ?? []);
}
function fillShippingSelect(){
  const el = document.getElementById('shipping_id');
  el.innerHTML = '<option value="">انتخاب روش ارسال...</option>';
  shippings.forEach(s => {
    const opt = document.createElement('option');
    opt.value = s.id;
    opt.textContent = s.name;
    el.appendChild(opt);
  });
}

/* =========================
   Products
========================= */
let allProducts = [];
const productDetailsCache = new Map();

async function loadAllProducts(){
  const res = await fetch(API.products, { headers: { 'Accept': 'application/json' } });
  const data = await res.json();
  allProducts = data?.data?.products?.data ?? [];
}
function fillProductSelect(selectEl){
  selectEl.innerHTML = '<option value="">انتخاب محصول</option>';
  allProducts.forEach(p => {
    const opt = document.createElement('option');
    opt.value = p.id;
    opt.textContent = `${(p.title ?? '').trim()} (${formatPrice(p.price)} تومان)`;
    selectEl.appendChild(opt);
  });
}
async function getProductDetails(productId){
  if (productDetailsCache.has(productId)) return productDetailsCache.get(productId);

  const res = await fetch(`${API.product}/${productId}`, { headers: { 'Accept': 'application/json' } });
  const data = await res.json();
  const product = data?.data?.product ?? null;

  productDetailsCache.set(productId, product);
  return product;
}
function setStockUI(row, stockQty){
  const qtyInput = row.querySelector('.quantity-input');
  const badge = row.querySelector('.stock-badge');
  const qty = Number.isFinite(Number(stockQty)) ? Number(stockQty) : 0;

  if (qty > 0) {
    qtyInput.disabled = false;
    qtyInput.min = '1';
    qtyInput.max = String(qty);

    // ✅ نکته: اگر مقدار quantity از DB بیشتر از stock باشد، همان quantity را نگه می‌داریم (برای ادیت)
    // پس max را فقط برای UX می‌گذاریم، ولی value را دست نمی‌زنیم.
    badge.className = 'badge bg-success stock-badge';
    badge.textContent = `موجودی: ${qty}`;
  } else {
    badge.className = 'badge bg-danger stock-badge';
    badge.textContent = 'ناموجود';
  }
}
function updateTotal(){
  const discount = Number(document.getElementById('discount')?.value || 0) || 0;
  const shipping = Number(document.getElementById('shipping_price')?.value || 0) || 0;

  let total = 0;
  document.querySelectorAll('.product-row').forEach(row => {
    const price = Number(row.querySelector('.price-raw')?.value || 0) || 0;
    const quantity = safeInt(row.querySelector('.quantity-input')?.value || 0, 0);
    total += price * quantity;
  });

  const finalTotal = Math.max(total + shipping - discount, 0);
  document.getElementById('total_price').value = formatPrice(finalTotal);
}

function renumberRows(){
  const rows = Array.from(document.querySelectorAll('#productRows .product-row'));
  rows.forEach((row, idx) => {
    row.querySelector('.row-title').textContent = `آیتم #${idx + 1}`;

    row.querySelector('.product-select').setAttribute('name', `products[${idx}][id]`);
    row.querySelector('.variety-select').setAttribute('name', `products[${idx}][variety_id]`);
    row.querySelector('.quantity-input').setAttribute('name', `products[${idx}][quantity]`);
    row.querySelector('.price-raw').setAttribute('name', `products[${idx}][price]`);
  });
}

function addProductRow(prefill = null){
  const container = document.getElementById('productRows');
  const index = container.children.length;

  const row = createEl(`
    <div class="product-row mb-3" data-prefill-variety-id="">
      <div class="border rounded-3 p-3 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-semibold row-title">آیتم #${index + 1}</div>
          <button type="button" class="btn btn-outline-danger btn-sm remove-row">حذف</button>
        </div>

        <div class="row g-2 align-items-end">
          <div class="col-md-5">
            <label class="form-label">محصول</label>
            <select class="form-select form-select-sm product-select" required>
              <option value="">انتخاب محصول</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">مدل</label>
            <select class="form-select form-select-sm variety-select" required disabled>
              <option value="">ابتدا محصول را انتخاب کنید</option>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label">تعداد</label>
            <input type="number" class="form-control form-control-sm quantity-input" min="1" value="1" required>
          </div>

          <div class="col-md-2">
            <label class="form-label">قیمت</label>
            <input type="text" class="form-control form-control-sm price-view" readonly>
            <input type="hidden" class="price-raw" value="0">
            <div class="mt-1"><span class="badge bg-secondary stock-badge">—</span></div>
          </div>
        </div>
      </div>
    </div>
  `);

  container.appendChild(row);

  const productSelect = row.querySelector('.product-select');
  fillProductSelect(productSelect);
  initSelect2(productSelect, 'جستجوی محصول...');

  const varietySelect = row.querySelector('.variety-select');
  initSelect2(varietySelect, 'انتخاب مدل...');

  row.querySelector('.remove-row').addEventListener('click', () => {
    row.remove();
    renumberRows();
    updateTotal();
  });

  // ✅ prefill
  if (prefill && prefill.product_id) {
    productSelect.value = String(prefill.product_id);
    if (window.jQuery) $(productSelect).trigger('change.select2');

    row.querySelector('.quantity-input').value = String(prefill.quantity ?? 1);

    // این همان ProductVariant.id است
    row.dataset.prefillVarietyId = String(prefill.variety_id ?? 0);

    // ✅ خیلی مهم: change واقعی
    productSelect.dispatchEvent(new Event('change', { bubbles:true }));
  }

  renumberRows();
  updateTotal();
  return row;
}

/* =========================
   Events
========================= */
document.addEventListener('change', async (e) => {
  // product changed
  if (e.target.classList.contains('product-select')) {
    const row = e.target.closest('.product-row');
    const productId = safeInt(e.target.value, 0);

    const varietySelect = row.querySelector('.variety-select');
    const priceRaw = row.querySelector('.price-raw');
    const priceView = row.querySelector('.price-view');

    varietySelect.innerHTML = '<option value="">در حال بارگذاری...</option>';
    setSelectDisabled(varietySelect, true);

    priceRaw.value = '0';
    priceView.value = '';
    updateTotal();

    if (!productId) {
      varietySelect.innerHTML = '<option value="">ابتدا محصول را انتخاب کنید</option>';
      initSelect2(varietySelect, 'انتخاب مدل...');
      return;
    }

    const product = await getProductDetails(productId);
    const varieties = product?.varieties ?? [];

    // محصول بدون مدل
    if (!varieties.length) {
      varietySelect.innerHTML = `<option value="0" selected>بدون مدل</option>`;
      setSelectDisabled(varietySelect, true);
      initSelect2(varietySelect, 'انتخاب مدل...');

      const price = safeInt(product?.price, 0);
      priceRaw.value = String(price);
      priceView.value = formatPrice(price);
      setStockUI(row, product?.quantity ?? 0);
      updateTotal();
      return;
    }

    // مدل‌ها
    varietySelect.innerHTML = '<option value="">انتخاب مدل...</option>';
    varieties.forEach(v => {
      const name =
        (v.attributes?.[0]?.pivot?.value ?? '').trim() ||
        (v.unique_attributes_key ?? '').trim() ||
        `مدل ${v.id}`;

      const opt = document.createElement('option');
      opt.value = String(v.id);
      opt.textContent = name;
      varietySelect.appendChild(opt);
    });

    setSelectDisabled(varietySelect, false);
    initSelect2(varietySelect, 'انتخاب مدل...');

    // ✅ prefill مدل
    const prefillVarietyId = safeInt(row.dataset.prefillVarietyId, 0);
    if (prefillVarietyId >= 0) {
      varietySelect.value = String(prefillVarietyId);
      if (window.jQuery) $(varietySelect).trigger('change.select2');
      varietySelect.dispatchEvent(new Event('change', { bubbles:true }));

    }
  }

  // variety changed
  if (e.target.classList.contains('variety-select')) {
    const row = e.target.closest('.product-row');
    const productId = safeInt(row.querySelector('.product-select')?.value, 0);
    const varietyId = safeInt(e.target.value, 0);

    const priceRaw = row.querySelector('.price-raw');
    const priceView = row.querySelector('.price-view');

    if (!productId) return;

    const product = await getProductDetails(productId);

    // بدون مدل
    if (varietyId === 0) {
      const price = safeInt(product?.price, 0);
      priceRaw.value = String(price);
      priceView.value = formatPrice(price);
      setStockUI(row, product?.quantity ?? 0);
      updateTotal();
      return;
    }

    const variety = (product?.varieties ?? []).find(v => Number(v.id) === Number(varietyId));
    if (!variety) return;

    const price = safeInt(variety.price ?? product?.price, 0);
    priceRaw.value = String(price);
    priceView.value = formatPrice(price);

    setStockUI(row, variety.quantity ?? 0);
    updateTotal();
  }
});

document.addEventListener('input', (e) => {
  if (e.target.classList.contains('quantity-input')) updateTotal();
});

/* =========================
   Init (ONE DOMContentLoaded)
========================= */
document.addEventListener('DOMContentLoaded', async () => {
  // location
  const shippingSelect = document.getElementById('shipping_id');
  const provinceSelect = document.getElementById('province_id');
  const citySelect = document.getElementById('city_id');

  initSelect2(provinceSelect, 'انتخاب استان...');
  initSelect2(citySelect, 'انتخاب شهر...');

  await loadArea();
  fillProvincesSelect(areaProvinces);

  provinceSelect.addEventListener('change', () => {
    const pid = safeInt(provinceSelect.value, 0);
    fillCitiesByProvinceId(pid);
    citySelect.value = '';
    if (window.jQuery) $(citySelect).trigger('change.select2');
  });

  if (initialProvinceId) {
    provinceSelect.value = String(initialProvinceId);
    if (window.jQuery) $(provinceSelect).trigger('change.select2');
    fillCitiesByProvinceId(initialProvinceId);

    if (initialCityId) {
      citySelect.value = String(initialCityId);
      setSelectDisabled(citySelect, false);
      if (window.jQuery) $(citySelect).trigger('change.select2');
    }
  }

  // shippings
  fillShippingSelect();

  const currentSid = safeInt(draftOrder.shipping_id, 0);
  if (currentSid) shippingSelect.value = String(currentSid);

  const baseShip = safeInt(document.getElementById('shipping_price')?.value, 0);
  document.getElementById('shipping_label').textContent = `هزینه ارسال: ${baseShip.toLocaleString()} تومان`;
  const view = document.getElementById('shipping_price_view');
  if (view) view.value = `${baseShip.toLocaleString()} تومان`;

  shippingSelect.addEventListener('change', () => {
    const sid = safeInt(shippingSelect.value, 0);
    const ship = shippings.find(x => Number(x.id) === Number(sid)) || null;
    const price = ship ? safeInt(ship.price, 0) : 0;

    document.getElementById('shipping_price').value = String(price);
    document.getElementById('shipping_label').textContent = `هزینه ارسال: ${price.toLocaleString()} تومان`;
    if (view) view.value = `${price.toLocaleString()} تومان`;
    updateTotal();
  });

  // ✅ products MUST load before building rows
  await loadAllProducts();
  // console.log('allProducts loaded:', allProducts.length);
  // console.log('draftItems:', draftItems);

  // build rows
  const container = document.getElementById('productRows');
  container.innerHTML = '';

  if (Array.isArray(draftItems) && draftItems.length) {
    draftItems.forEach(it => {
      addProductRow({ product_id: it.product_id, variety_id: it.variety_id, quantity: it.quantity });
    });
  } else {
    addProductRow();
  }

  document.getElementById('addRow').addEventListener('click', () => addProductRow());
  updateTotal();

  // ✅ اگر ردیفی بدون prefill ایجاد شد ولی محصول داشت، change رو صدا بزن
  document.querySelectorAll('.product-row .product-select').forEach(sel => {
    if (safeInt(sel.value, 0) > 0) {
      sel.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });
});
</script>

<script>
// ✅ تبدیل اعداد فارسی قبل submit
(function () {
  function toEnglishDigits(str) {
    return String(str || '')
      .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
      .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
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

  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('orderForm');
    if (!form) return;

    form.addEventListener('submit', () => {
      const totalEl = document.getElementById('total_price');
      if (totalEl) totalEl.value = String(toInt(totalEl.value));

      const shipEl = document.getElementById('shipping_price');
      if (shipEl) shipEl.value = String(toInt(shipEl.value));

      const discEl = document.getElementById('discount');
      if (discEl) discEl.value = String(toInt(discEl.value));

      document.querySelectorAll('.price-raw').forEach(el => el.value = String(toInt(el.value)));
      document.querySelectorAll('.quantity-input').forEach(el => el.value = String(toInt(el.value)));
    }, { capture: true });
  });
})();
</script>
<div class="modal fade" id="createCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <div class="fw-bold">➕ ساخت مشتری</div>
          <button type="button" class="btn-close ms-0" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">نام</label>
              <input class="form-control" id="c_first_name">
            </div>
            <div class="col-md-6">
              <label class="form-label">فامیل</label>
              <input class="form-control" id="c_last_name">
            </div>
            <div class="col-md-12">
              <label class="form-label">موبایل *</label>
              <input class="form-control" id="c_mobile" required>
            </div>
            <div class="col-md-12">
              <label class="form-label">آدرس</label>
              <textarea class="form-control" id="c_address" rows="2"></textarea>
            </div>
          </div>

          <div class="text-danger small mt-2 d-none" id="createCustomerError"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
          <button type="button" class="btn btn-primary" id="createCustomerBtn">ثبت مشتری</button>
        </div>
      </div>
    </div>
  </div>
  <script>
    (function(){
      const $ = window.jQuery;
      if (!$ || !$.fn.select2) return;

      const customerSelect = document.getElementById('customer_select');
      const customerIdInput = document.getElementById('customer_id');
      const hint = document.getElementById('customer_balance_hint');

      // select2 ajax search
      $(customerSelect).select2({
        width: '100%',
        dir: 'rtl',
        placeholder: 'جستجو مشتری...',
        allowClear: true,
        ajax: {
          delay: 250,
          url: API.customers,
          dataType: 'json',
          data: params => ({ q: params.term || '' }),
          processResults: (resp) => {
            const items = resp?.data?.customers || [];
            return {
              results: items.map(c => ({
                id: c.id,
                text: `${(c.first_name||'')} ${(c.last_name||'')} - ${c.mobile}`.trim(),
                raw: c
              }))
            };
          }
        }
      });

      function fillFromCustomer(c){
        // ست کردن فیلدهای فرم پیش‌فاکتور
        document.getElementById('customer_name').value = `${(c.first_name||'')} ${(c.last_name||'')}`.trim();
        document.getElementById('customer_mobile').value = c.mobile || '';
        document.getElementById('customer_address').value = c.address || '';

        // اگر province/city داری:
        if (c.province_id) {
          const provinceSelect = document.getElementById('province_id');
          provinceSelect.value = String(c.province_id);
          if (window.jQuery) $(provinceSelect).trigger('change.select2');
          if (typeof fillCitiesByProvinceId === 'function') fillCitiesByProvinceId(c.province_id);
        }
        if (c.city_id) {
          const citySelect = document.getElementById('city_id');
          citySelect.value = String(c.city_id);
          if (window.jQuery) $(citySelect).trigger('change.select2');
        }

        // نمایش مانده
        if (hint) {
          const debt = (c.debt || 0).toLocaleString('fa-IR');
          const credit = (c.credit || 0).toLocaleString('fa-IR');
          const balance = (c.balance || 0).toLocaleString('fa-IR');
          hint.textContent = `بدهکار: ${debt} | بستانکار: ${credit} | مانده: ${balance}`;
        }
      }

      // وقتی مشتری انتخاب شد
      $(customerSelect).on('select2:select', function(e){
        const c = e.params.data.raw;
        customerIdInput.value = String(c.id);
        fillFromCustomer(c);
      });

      // وقتی پاک شد
      $(customerSelect).on('select2:clear', function(){
        customerIdInput.value = '';
        if (hint) hint.textContent = '';
      });

      // اگر در ادیت customer_id داریم، با show لودش کن و ست کن
      const existingId = (customerIdInput.value || '').trim();
      if (existingId) {
        fetch(`${API.customers}/${existingId}`, { headers: { 'Accept':'application/json' } })
          .then(r => r.json())
          .then(resp => {
            const c = resp?.data?.customer;
            if (!c) return;

            // option بساز تا select2 بشناسد
            const opt = new Option(`${(c.first_name||'')} ${(c.last_name||'')} - ${c.mobile}`.trim(), c.id, true, true);
            customerSelect.appendChild(opt);
            $(customerSelect).trigger('change');
            fillFromCustomer(c);
          });
      }

      // modal create
      const modalEl = document.getElementById('createCustomerModal');
      const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

      document.getElementById('openCreateCustomer')?.addEventListener('click', () => {
        document.getElementById('createCustomerError').classList.add('d-none');
        modal?.show();
      });

      document.getElementById('createCustomerBtn')?.addEventListener('click', async () => {
        const err = document.getElementById('createCustomerError');
        err.classList.add('d-none');

        const payload = {
          first_name: document.getElementById('c_first_name').value || null,
          last_name: document.getElementById('c_last_name').value || null,
          mobile: document.getElementById('c_mobile').value || '',
          address: document.getElementById('c_address').value || null,

          // اگر می‌خوای از فرم اصلی بگیره:
          province_id: parseInt(document.getElementById('province_id').value || 0) || null,
          city_id: parseInt(document.getElementById('city_id').value || 0) || null,
        };

        try {
          const res = await fetch(API.customers, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(payload)
          });

          const data = await res.json();
          if (!res.ok) {
            err.textContent = data?.message || 'خطا در ثبت مشتری';
            err.classList.remove('d-none');
            return;
          }

          const c = data?.data?.customer;
          if (!c) return;

          // انتخاب خودکار مشتری جدید
          const text = `${(c.first_name||'')} ${(c.last_name||'')} - ${c.mobile}`.trim();
          const opt = new Option(text, c.id, true, true);
          customerSelect.appendChild(opt);
          $(customerSelect).trigger('change');

          customerIdInput.value = String(c.id);
          fillFromCustomer({ ...c, debt:0, credit:0, balance:0 });

          modal?.hide();
        } catch (e) {
          err.textContent = 'خطای شبکه';
          err.classList.remove('d-none');
        }
      });
    })();
    </script>
</body>
</html>
