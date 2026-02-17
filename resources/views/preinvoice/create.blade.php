<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <script src="{{ asset('lib/bootstrap.bundle.min.js') }}"></script>
  <link rel="stylesheet" href="{{ asset('lib/select2.min.css') }}">
  <link rel="stylesheet" href="{{ asset('lib/bootstrap.rtl.min.css') }}">
  <script src="{{ asset('lib/jquery.min.js') }}"></script>
  <script src="{{ asset('lib/select2.min.js') }}"></script>

  <title>ایجاد پیش‌فاکتور</title>

  <style>
    .page-shell { max-width: 1100px; }
    .card-soft { background: #fff; border: 1px solid rgba(0,0,0,.08); border-radius: 18px; box-shadow: 0 10px 30px rgba(0,0,0,.04); }
    .section-title { font-weight: 800; }
    .hint { color: #6c757d; font-size: .9rem; }
    .sticky-submit { position: sticky; bottom: 10px; }
  </style>
</head>

<body class="py-4">
<div class="container page-shell">

  <div class="topbar mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <div class="h5 mb-0 fw-bold">🧾 ایجاد پیش‌فاکتور (Draft)</div>
      <div class="hint">اطلاعات ذخیره می‌شود و بعداً قابل ویرایش است.</div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('preinvoice.draft.index') }}">📂 لیست پیش‌نویس‌ها</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm rounded-4 fw-bold">
      ✅ {{ session('success') }}
    </div>
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
          <label class="form-label fw-semibold">انتخاب مشتری (اختیاری)</label>
          <select id="customer_select" class="form-select"></select>
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
          <select id="province_id" name="province_id" class="form-select">
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
          <div class="hint">می‌تونی آیتم جدید اضافه کنی یا تعداد/مدل‌ها را تغییر بدی.</div>
        </div>

        <div class="d-flex gap-2 align-items-center flex-wrap">
            <button type="submit" class="btn btn-primary">💾 ذخیره پیش‌نویس</button>
          </div>


      <div id="productRows" class="p-3 p-md-4"></div>

      <div class="p-3 p-md-4 border-top d-flex justify-content-center fw-semibold">
        <button type="button" id="addRow" class="btn btn-primary" style="width:190px;height:50px;">
          ➕ افزودن محصول
        </button>
      </div>
    </div>

    {{-- Summary --}}
    <div class="card-soft p-3 p-md-4 mb-4">
      <div class="section-title">💳 جمع‌بندی</div>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold">تخفیف (تومان)</label>
          <input type="number" name="discount_amount" id="discount" class="form-control"
                 value="{{ old('discount_amount', 0) }}" readonly style="background-color: var(--bs-secondary-bg);">
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">هزینه ارسال</label>
          <input type="text" id="shipping_price_view" class="form-control" readonly
                 value="0 تومان" style="background-color: var(--bs-secondary-bg);">
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">جمع کل (تومان)</label>
          <input type="text" name="total_price" id="total_price" class="form-control fw-bold" readonly
                 style="background-color: var(--bs-secondary-bg);">
        </div>
      </div>

      <input type="hidden" name="payment_status" value="pending">
    </div>

    <div class="sticky-submit">
      <button class="btn btn-primary w-100 fs-5 py-3 shadow-sm">💾 ذخیره پیش‌نویس</button>
    </div>
  </form>

</div>
<script>
  const draftOrder = null;
  const draftItems = [];
  const initialProvinceId = 0;
  const initialCityId = 0;

  const API = {
    products:  "{{ url('/preinvoice/api/products') }}",
    product:   "{{ url('/preinvoice/api/products') }}", // /{id}
    area:      "{{ url('/preinvoice/api/area') }}",
    shippings: "{{ url('/preinvoice/api/shippings') }}",
    customers: "{{ url('/preinvoice/api/customers') }}",
    customer:  "{{ url('/preinvoice/api/customers') }}", // /{id}
  };
</script>

<script>
let shippings = [];
let areaProvinces = [];

function initLocationSelect2(selectEl, placeholder) {
  if (!window.jQuery || !window.jQuery.fn?.select2) return;
  const $el = $(selectEl);
  if ($el.hasClass('select2-hidden-accessible')) {
    $el.off('select2:select select2:clear');
    $el.select2('destroy');
  }
  $el.select2({ width:'100%', dir:'rtl', placeholder, allowClear:true });
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
  areaProvinces = data?.data?.provinces ?? [];
}
function fillProvincesSelect(provincesToShow) {
  const provinceSelect = document.getElementById('province_id');
  provinceSelect.innerHTML = '<option value=""></option>';
  (provincesToShow ?? []).forEach(p => {
    const opt = document.createElement('option');
    opt.value = p.id; opt.textContent = (p.name ?? '').trim();
    provinceSelect.appendChild(opt);
  });
  initLocationSelect2(provinceSelect, 'انتخاب استان...');
}
function fillCities(citiesToShow) {
  const citySelect = document.getElementById('city_id');
  citySelect.innerHTML = '<option value=""></option>';
  (citiesToShow ?? []).forEach(c => {
    const opt = document.createElement('option');
    opt.value = c.id; opt.textContent = (c.name ?? '').trim();
    citySelect.appendChild(opt);
  });
  setSelectDisabled(citySelect, (citiesToShow ?? []).length === 0);
  initLocationSelect2(citySelect, 'انتخاب شهر...');
}
function fillCitiesByProvinceId(provinceId) {
  const province = areaProvinces.find(p => Number(p.id) === Number(provinceId));
  fillCities(province?.cities ?? []);
}

async function loadShippings() {
  const res = await fetch(API.shippings, { headers: { 'Accept': 'application/json' } });
  const data = await res.json();
  shippings = data?.data?.shippings?.data ?? [];
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

function formatPrice(val){ const n=Number(val); if(!Number.isFinite(n)) return ''; return n.toLocaleString('fa-IR'); }

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

  if (!customerSelect || !window.jQuery || !window.jQuery.fn?.select2) return;

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
      data: params => ({ q: params.term || '' }),
      processResults: resp => {
        const items = resp?.data?.customers || [];
        return {
          results: items.map(c => ({
            id: c.id,
            text: `${(c.first_name || '').trim()} ${(c.last_name || '').trim()} - ${c.mobile}`.trim()
          }))
        };
      }
    }
  });

  $(customerSelect).on('select2:select', async function (e) {
    const customerId = e.params?.data?.id || null;
    customerIdInput.value = customerId || '';
    if (!customerId) return;

    const res = await fetch(`${API.customer}/${customerId}`, { headers: { 'Accept':'application/json' } });
    const json = await res.json();
    const c = json?.data?.customer || null;
    if (!c) return;

    document.getElementById('customer_name').value = `${(c.first_name || '').trim()} ${(c.last_name || '').trim()}`.trim();
    document.getElementById('customer_mobile').value = c.mobile || '';
    document.getElementById('customer_address').value = c.address || '';

    setCustomerLocation(c.province_id, c.city_id);

    hint.textContent = `مانده حساب: ${(Number(c.balance || 0)).toLocaleString()} تومان`;
  });

  $(customerSelect).on('select2:clear', function () {
    customerIdInput.value = '';
    hint.textContent = '';
  });
}

document.addEventListener('DOMContentLoaded', async () => {
  const shippingSelect = document.getElementById('shipping_id');
  const provinceSelect = document.getElementById('province_id');
  const citySelect = document.getElementById('city_id');

  initLocationSelect2(provinceSelect, 'انتخاب استان...');
  initLocationSelect2(citySelect, 'انتخاب شهر...');

  await loadArea();
  fillProvincesSelect(areaProvinces);

  await loadShippings();
  fillShippingSelect();

  initCustomerSelect();

  const oldCustomerId = parseInt(document.getElementById('customer_id')?.value || 0);
  if (oldCustomerId > 0) {
    try {
      const res = await fetch(`${API.customer}/${oldCustomerId}`, { headers: { 'Accept':'application/json' } });
      const json = await res.json();
      const c = json?.data?.customer || null;
      if (c) {
        const customerSelect = document.getElementById('customer_select');
        const txt = `${(c.first_name || '').trim()} ${(c.last_name || '').trim()} - ${c.mobile}`.trim();
        const option = new Option(txt, c.id, true, true);
        customerSelect.appendChild(option);
        if (window.jQuery) $(customerSelect).trigger('change');

        setCustomerLocation(c.province_id, c.city_id);
        const hint = document.getElementById('customer_balance_hint');
        if (hint) hint.textContent = `مانده حساب: ${(Number(c.balance || 0)).toLocaleString()} تومان`;
      }
    } catch (e) {}
  }

  provinceSelect.addEventListener('change', () => {
    fillCitiesByProvinceId(provinceSelect.value);
  });

  shippingSelect.addEventListener('change', () => {
    const sid = parseInt(shippingSelect.value || 0);
    const ship = shippings.find(x => Number(x.id) === Number(sid)) || null;
    const price = ship ? (parseInt(ship.price || 0)) : 0;

    document.getElementById('shipping_price').value = String(price);
    document.getElementById('shipping_label').textContent = `هزینه ارسال: ${price.toLocaleString()} تومان`;
    document.getElementById('shipping_price_view').value = `${price.toLocaleString()} تومان`;
    updateTotal();
  });
});
</script>

<script>
let allProducts = [];
const productDetailsCache = new Map();

function createEl(html){ const tmp=document.createElement('div'); tmp.innerHTML=html.trim(); return tmp.firstChild; }

async function loadAllProducts() {
  const res = await fetch(API.products, { headers: { 'Accept': 'application/json' } });
  const data = await res.json();
  allProducts = data?.data?.products?.data ?? [];
}

function fillProductSelect(selectEl) {
  selectEl.innerHTML = '<option value="">انتخاب محصول</option>';
  allProducts.forEach(p => {
    const opt = document.createElement('option');
    opt.value = p.id;
    opt.textContent = `${p.title} (${formatPrice(p.price)} تومان)`;
    selectEl.appendChild(opt);
  });
}
function initProductSelect2(selectEl) {
  $(selectEl).select2({ width:'100%', dir:'rtl', placeholder:'جستجوی محصول...', allowClear:true });
  $(selectEl).on('select2:select select2:clear', function () {
    this.dispatchEvent(new Event('change', { bubbles: true }));
  });
}

async function getProductDetails(productId) {
  if (productDetailsCache.has(productId)) return productDetailsCache.get(productId);
  const res = await fetch(`${API.product}/${productId}`, { headers: { 'Accept': 'application/json' } });
  const data = await res.json();
  const product = data?.data?.product ?? null;
  productDetailsCache.set(productId, product);
  return product;
}

function setStockUI(row, stockQty) {
  const qtyInput = row.querySelector('.quantity-input');
  const badge = row.querySelector('.stock-badge');
  const qty = Number.isFinite(Number(stockQty)) ? Number(stockQty) : 0;

  if (qty > 0) {
    qtyInput.disabled = false;
    qtyInput.min = '1';
    qtyInput.max = String(qty);
    const current = parseInt(qtyInput.value || '0');
    if (current < 1) qtyInput.value = '1';
    if (current > qty) qtyInput.value = String(qty);
    badge.className = 'badge bg-success stock-badge';
    badge.textContent = `موجودی: ${qty}`;
  } else {
    qtyInput.value = '0';
    qtyInput.min = '0';
    qtyInput.max = '0';
    qtyInput.disabled = true;
    badge.className = 'badge bg-danger stock-badge';
    badge.textContent = 'ناموجود';
  }
}


function updateTotal() {
  const discount = parseFloat(document.getElementById('discount')?.value || 0) || 0;
  const shipping = parseFloat(document.getElementById('shipping_price')?.value || 0) || 0;

  let total = 0;
  document.querySelectorAll('.product-row').forEach(row => {
    const price = parseFloat(row.querySelector('.price-raw')?.value || 0) || 0;
    const quantity = parseInt(row.querySelector('.quantity-input')?.value || 0) || 0;
    total += price * quantity;
  });

  const finalTotal = Math.max(total + shipping - discount, 0);
  document.getElementById('total_price').value = finalTotal.toLocaleString('fa-IR');
}

function addProductRow(prefill = null) {
  const container = document.getElementById('productRows');
  const index = container.children.length;

  const row = createEl(`
    <div class="product-row mb-3">
      <div class="border rounded-3 p-3 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-semibold">آیتم #${index + 1}</div>
          <button type="button" class="btn btn-outline-danger btn-sm remove-row">حذف</button>
        </div>

        <div class="row g-2 align-items-end">
          <div class="col-md-5">
            <label class="form-label">محصول</label>
            <select name="products[${index}][id]" class="form-select form-select-sm product-select" required>
              <option value="">انتخاب محصول</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">مدل</label>
            <select name="products[${index}][variety_id]" class="form-select form-select-sm variety-select" required>
              <option value="">ابتدا محصول را انتخاب کنید</option>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label">تعداد</label>
            <input type="number" name="products[${index}][quantity]" class="form-control form-control-sm quantity-input" min="1" value="1" required>
          </div>

          <div class="col-md-2">
            <label class="form-label">قیمت</label>
            <input type="text" class="form-control form-control-sm price-view" readonly>
            <input type="hidden" name="products[${index}][price]" class="price-raw" value="0">
            <div class="mt-1"><span class="badge bg-secondary stock-badge">—</span></div>
          </div>
        </div>
      </div>
    </div>
  `);

  container.appendChild(row);

  const productSelect = row.querySelector('.product-select');
  fillProductSelect(productSelect);
  initProductSelect2(productSelect);

  row.querySelector('.remove-row').addEventListener('click', () => {
    row.remove();
    updateTotal();
  });

  if (prefill?.product_id) {
    productSelect.value = String(prefill.product_id);
    if (window.jQuery) $(productSelect).trigger('change.select2');

    setTimeout(() => {
      if (prefill.quantity) row.querySelector('.quantity-input').value = String(prefill.quantity);
      updateTotal();
    }, 400);
  }

  updateTotal();
  return row;
}

document.addEventListener('change', async (e) => {
  if (e.target.classList.contains('product-select')) {
    const row = e.target.closest('.product-row');
    const productId = parseInt(e.target.value || 0);
    const varietySelect = row.querySelector('.variety-select');
    const priceRaw = row.querySelector('.price-raw');
    const priceView = row.querySelector('.price-view');

    varietySelect.innerHTML = '<option value="">در حال بارگذاری...</option>';
    varietySelect.disabled = true;
    priceRaw.value = '0';
    priceView.value = '';
    setStockUI(row, 0);
    updateTotal();

    if (!productId) {
      varietySelect.innerHTML = '<option value="">ابتدا محصول را انتخاب کنید</option>';
      return;
    }

    const product = await getProductDetails(productId);
    const varieties = product?.varieties ?? [];

    if (!varieties.length) {
      varietySelect.innerHTML = `<option value="${product.id}" selected>بدون مدل</option>`;
      varietySelect.disabled = true;

      const price = product.price || 0;
      priceRaw.value = String(price);
      priceView.value = price.toLocaleString('fa-IR');
      setStockUI(row, product.quantity ?? 0);
      updateTotal();
      return;
    }

    varietySelect.innerHTML = '<option value="">انتخاب مدل...</option>';
    varieties.forEach(v => {
      const rawModelName =
        (v.attributes?.map(a => a.pivot?.value).join(' ').trim()) ||
        (v.unique_attributes_key?.trim()) ||
        `مدل ${v.id}`;

      const opt = document.createElement('option');
      opt.value = v.id;
      opt.textContent = rawModelName;
      varietySelect.appendChild(opt);
    });
    varietySelect.disabled = false;
  }

  if (e.target.classList.contains('variety-select')) {
    const row = e.target.closest('.product-row');
    const productId = parseInt(row.querySelector('.product-select').value || 0);
    const varietyId = parseInt(e.target.value || 0);

    const priceRaw = row.querySelector('.price-raw');
    const priceView = row.querySelector('.price-view');

    if (!productId || !varietyId) return;

    const product = await getProductDetails(productId);
    const variety = (product?.varieties ?? []).find(v => Number(v.id) === Number(varietyId));
    if (!variety) return;

    const price = variety.price || product.price || 0;
    priceRaw.value = String(price);
    priceView.value = price.toLocaleString('fa-IR');
    setStockUI(row, variety.quantity ?? 0);
    updateTotal();
  }
});

document.addEventListener('input', (e) => {
  if (e.target.classList.contains('quantity-input')) updateTotal();
});

document.addEventListener('DOMContentLoaded', async () => {
  await loadAllProducts();

  addProductRow();
  document.getElementById('addRow').addEventListener('click', () => addProductRow());

  updateTotal();
});
</script>

<script>
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

</body>
</html>
