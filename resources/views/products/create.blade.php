@extends('layouts.app')

@section('content')
@php
  $warehouses = $warehouses ?? collect();
  $suppliers = $suppliers ?? collect();
  $products = $products ?? collect();
  $variants = $variants ?? collect();
  $categories = $categories ?? collect();
@endphp
<style>
  :root{
    --brd:#e8edf3;
    --muted:#6b7280;
    --soft:#f8fafc;
    --soft2:#f3f6fb;
    --warn:#f59e0b;
    --ok:#16a34a;
  }
  .card-soft{border:1px solid var(--brd);border-radius:16px;background:#fff;}
  .section-head{
    padding:12px 14px;
    border-bottom:1px solid var(--brd);
    background:linear-gradient(0deg,#fff,var(--soft2));
    border-top-left-radius:16px;border-top-right-radius:16px;
    display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;
  }
  .section-title{font-weight:800;font-size:14px;}
  .muted{color:var(--muted);font-size:12px;}
  .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;letter-spacing:1px;}
  .item-card{
    border:1px dashed #dbe6f3;
    border-radius:16px;
    padding:12px;
    background:#fff;
    margin-top:12px;
  }
  .item-top{
    display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;
    margin-bottom:10px;
  }
  .item-no{font-weight:800;font-size:13px;}
  .code-chip{
    display:inline-flex;align-items:center;gap:8px;
    padding:6px 10px;border-radius:999px;border:1px solid var(--brd);
    background:var(--soft);font-size:12px;
  }
  .code-chip.ok{border-color:#c7f0d3;background:#f0fdf4;}
  .code-chip.bad{border-color:#fde3b5;background:#fffbeb;}
  .quick-designs{
    margin-top:8px;
    padding-top:8px;
    border-top:1px solid #eef2f7;
    display:none;
  }
  .design-pill{
    display:inline-flex;align-items:center;gap:6px;
    padding:6px 10px;border-radius:999px;border:1px solid var(--brd);
    background:#fff;font-size:12px;cursor:pointer;
    margin:4px 6px 0 0;
  }
  .design-pill:hover{background:var(--soft);}
  .sum-box{
    border:1px solid var(--brd);
    border-radius:16px;
    background:#fff;
    padding:12px;
  }
  .sum-row{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:13px;}
  .sum-row b{font-size:14px;}
</style>

<div class="card shadow-sm">
  <div class="card-body">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">ثبت خرید جدید</h5>
      <a class="btn btn-outline-secondary" href="{{ route('purchases.index') }}">بازگشت</a>
    </div>

    <form method="POST" action="{{ route('purchases.store') }}" id="purchaseForm" class="row g-3">
      @csrf

      {{-- هدر خرید --}}
      <div class="col-12">
        <div class="card-soft">
          <div class="section-head">
            <div>
              <div class="section-title">اطلاعات خرید</div>
              <div class="muted">انبار مقصد + تامین‌کننده را مشخص کن</div>
            </div>
          </div>

          <div class="p-3">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">انبار مقصد</label>
                <select name="warehouse_id" id="warehouseId" class="form-select" required>
                  <option value="">انتخاب کنید...</option>
                  @foreach($warehouses as $w)
                    <option value="{{ $w->id }}" @selected(old('warehouse_id') == $w->id)>{{ $w->name }}</option>
                  @endforeach
                </select>
                <div class="form-text">موجودی این خرید به این انبار اضافه می‌شود.</div>
              </div>

              <div class="col-md-4">
                <label class="form-label">تامین‌کننده</label>
                <select name="supplier_id" id="supplierId" class="form-select" required>
                  <option value="">انتخاب کنید...</option>
                  @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>
                      {{ $s->name }} @if(!empty($s->phone)) ({{ $s->phone }}) @endif
                    </option>
                  @endforeach
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label">توضیحات (اختیاری)</label>
                <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="مثلاً خرید نقدی / شماره فاکتور...">
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- آیتم‌ها --}}
      <div class="col-12">
        <div class="card-soft">
          <div class="section-head">
            <div>
              <div class="section-title">اقلام خرید</div>
              <div class="muted">به ترتیب: دسته‌بندی → کالا → مدل‌لیست → طرح → کد ۱۱ رقمی</div>
            </div>

            <div class="d-flex gap-2">
              <button type="button" class="btn btn-outline-primary" id="btnAddRow">+ افزودن ردیف</button>
              <button type="button" class="btn btn-outline-secondary" id="btnClearRows">پاک کردن</button>
            </div>
          </div>

          <div class="p-3">
            <div id="itemsWrap"></div>

            <template id="rowTpl">
              <div class="item-card" data-index="__i__">
                <div class="item-top">
                  <div class="item-no">ردیف <span class="rowNo">1</span></div>
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="code-chip bad" data-role="codeChip">
                      <span class="muted">کد ۱۱:</span>
                      <span class="mono" data-role="codeText">—</span>
                    </span>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-role="removeRow">حذف</button>
                  </div>
                </div>

                <input type="hidden" name="items[__i__][variant_id]" data-role="variantId" value="">
                <input type="hidden" name="items[__i__][variant_code]" data-role="variantCode" value="">
                <input type="hidden" name="items[__i__][design2]" data-role="design2Hidden" value="">

                <div class="row g-2">
                  <div class="col-md-3">
                    <label class="form-label">دسته‌بندی</label>
                    <select class="form-select" data-role="catSel" name="items[__i__][category_id]" required>
                      <option value="">انتخاب...</option>
                      __cat_options__
                    </select>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">کالا</label>
                    <select class="form-select" data-role="prodSel" name="items[__i__][product_id]" required disabled>
                      <option value="">ابتدا دسته‌بندی</option>
                    </select>
                    <div class="form-text muted">کد کالا: <span class="mono" data-role="prodCode">—</span></div>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">مدل‌لیست</label>
                    <select class="form-select" data-role="modelSel" name="items[__i__][model_list_id]" required disabled>
                      <option value="">ابتدا کالا</option>
                    </select>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">طرح</label>
                    <select class="form-select" data-role="designSel" required disabled>
                      <option value="">ابتدا مدل</option>
                    </select>
                    <div class="form-text muted">موجودی فعلی: <b data-role="stockText">—</b></div>
                  </div>

                  <div class="col-md-2">
                    <label class="form-label">تعداد</label>
                    <input type="number" min="1" class="form-control" data-role="qty" name="items[__i__][qty]" value="1" required>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">قیمت خرید</label>
                    <input type="text" class="form-control" data-role="buyPrice" name="items[__i__][buy_price]" placeholder="مثلاً 1200000">
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">قیمت فروش</label>
                    <input type="text" class="form-control" data-role="sellPrice" name="items[__i__][sell_price]" placeholder="مثلاً 1500000">
                  </div>

                  <div class="col-md-4">
                    <label class="form-label">جمع ردیف (خرید)</label>
                    <div class="form-control" style="background:#f8fafc;" data-role="rowSum">0</div>
                  </div>
                </div>

                <div class="quick-designs" data-role="quickDesigns">
                  <div class="muted">طرح‌های این مدل (کلیک کن تا ردیف جدید برای همان کالا/مدل ساخته شود):</div>
                  <div data-role="designPills"></div>
                </div>

              </div>
            </template>

          </div>
        </div>
      </div>

      {{-- خلاصه --}}
      <div class="col-12">
        <div class="sum-box">
          <div class="row g-3 align-items-end">
            <div class="col-md-3">
              <label class="form-label">نوع تخفیف</label>
              <select class="form-select" id="discountType" name="discount_type">
                <option value="none" @selected(old('discount_type')==='none')>بدون تخفیف</option>
                <option value="amount" @selected(old('discount_type')==='amount')>مبلغی</option>
                <option value="percent" @selected(old('discount_type')==='percent')>درصدی</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">مقدار</label>
              <input type="number" min="0" class="form-control" id="discountValue" name="discount_value" value="{{ old('discount_value', 0) }}">
              <div class="form-text muted">اگر درصدی باشد 0 تا 100</div>
            </div>

            <div class="col-md-6">
              <div class="sum-row"><span>جمع کل خرید:</span> <b><span id="sumTotal">0</span> ریال</b></div>
              <div class="sum-row"><span>تخفیف:</span> <b><span id="sumDiscount">0</span> ریال</b></div>
              <hr>
              <div class="sum-row"><span>قابل پرداخت:</span> <b><span id="sumPayable">0</span> ریال</b></div>
            </div>
          </div>

          <div class="d-flex justify-content-end mt-3">
            <button type="submit" class="btn btn-primary">ثبت نهایی خرید</button>
          </div>
        </div>
      </div>

    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){

  // ===== داده‌ها از کنترلر =====
  var CATEGORIES = @json($categories);
  var PRODUCTS   = @json($products);
  var VARIANTS   = @json($variants);

  // ===== helpers =====
  function onlyDigits(s){
    return String(s || '').replace(/\D+/g,'');
  }
  function padLeft(s, len, ch){
    s = String(s || '');
    ch = ch || '0';
    while(s.length < len) s = ch + s;
    return s;
  }
  function normalizeModel3(code){
    var d = onlyDigits(code).substring(0,3);
    return padLeft(d, 3, '0');
  }
  function normalizeDesign2(d2){
    d2 = onlyDigits(d2).substring(0,2);
    return padLeft(d2, 2, '0');
  }
  function moneyToInt(val){
    var d = onlyDigits(val);
    return d ? parseInt(d,10) : 0;
  }
  function fmt(x){
    x = parseInt(x || 0, 10);
    return String(x).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  // ===== ساخت ایندکس‌های سریع =====
  // کلید: productId|modelId|design2  => variant
  var V_MAP = {};
  // productId => {models:[{id,name,code3}], designsByModel:{modelId:[{design2,title}]}}
  var P_META = {};

  function safeModelId(v){
    return v.model_list_id ? parseInt(v.model_list_id,10) : 0;
  }

  VARIANTS.forEach(function(v){
    var pid = parseInt(v.product_id,10);
    var mid = safeModelId(v);
    var design2 = normalizeDesign2(v.design2 || (v.variant_code ? String(v.variant_code).slice(-2) : '00'));

    var key = pid + '|' + mid + '|' + design2;
    V_MAP[key] = v;

    if(!P_META[pid]){
      P_META[pid] = { models: [], modelsMap:{}, designsByModel:{} };
    }

    // مدل
    if(!P_META[pid].modelsMap[mid]){
      var modelName = mid === 0 ? '— بدون مدل —' : (v.model_name ? v.model_name : ('مدل ' + mid));
      var modelCode3 = mid === 0 ? '000' : normalizeModel3(v.model_code);
      P_META[pid].modelsMap[mid] = { id: mid, name: modelName, code3: modelCode3 };
      P_META[pid].models.push(P_META[pid].modelsMap[mid]);
    }

    // طرح
    if(!P_META[pid].designsByModel[mid]) P_META[pid].designsByModel[mid] = [];
    var title = design2 === '00' ? '— بدون طرح —' : (v.design_title ? v.design_title : (v.variety_name ? v.variety_name : ('طرح ' + design2)));
    var arr = P_META[pid].designsByModel[mid];
    if(arr.findIndex(function(x){ return x.design2 === design2; }) === -1){
      arr.push({design2: design2, title: title});
    }
  });

  // مرتب‌سازی مدل‌ها/طرح‌ها
  Object.keys(P_META).forEach(function(pid){
    P_META[pid].models.sort(function(a,b){ return a.id - b.id; });
    Object.keys(P_META[pid].designsByModel).forEach(function(mid){
      P_META[pid].designsByModel[mid].sort(function(a,b){
        return parseInt(a.design2,10) - parseInt(b.design2,10);
      });
    });
  });

  // ===== DOM =====
  var itemsWrap = document.getElementById('itemsWrap');
  var rowTpl = document.getElementById('rowTpl');

  var btnAddRow = document.getElementById('btnAddRow');
  var btnClearRows = document.getElementById('btnClearRows');

  var discountType = document.getElementById('discountType');
  var discountValue = document.getElementById('discountValue');

  var sumTotal = document.getElementById('sumTotal');
  var sumDiscount = document.getElementById('sumDiscount');
  var sumPayable = document.getElementById('sumPayable');

  function catOptionsHtml(){
    var html = '';
    CATEGORIES.forEach(function(c){
      html += '<option value="' + c.id + '">' + c.name + '</option>';
    });
    return html;
  }

  function productsByCat(catId){
    catId = parseInt(catId,10);
    return PRODUCTS.filter(function(p){ return parseInt(p.category_id,10) === catId; });
  }

  function productById(id){
    id = parseInt(id,10);
    return PRODUCTS.find(function(p){ return parseInt(p.id,10) === id; }) || null;
  }

  function buildProductOptions(list){
    var html = '<option value="">انتخاب کالا...</option>';
    list.forEach(function(p){
      html += '<option value="' + p.id + '">' + p.name + '</option>';
    });
    return html;
  }

  function buildModelOptions(pid){
    var meta = P_META[pid];
    var html = '<option value="">انتخاب مدل...</option>';
    if(!meta || !meta.models.length){
      html += '<option value="0">— بدون مدل —</option>';
      return html;
    }
    meta.models.forEach(function(m){
      html += '<option value="' + m.id + '">' + m.name + '</option>';
    });
    return html;
  }

  function buildDesignOptions(pid, mid){
    var meta = P_META[pid];
    var html = '<option value="">انتخاب طرح...</option>';
    if(!meta) return html;

    var arr = meta.designsByModel[mid] || [];
    if(!arr.length){
      html += '<option value="00">— بدون طرح —</option>';
      return html;
    }

    arr.forEach(function(d){
      html += '<option value="' + d.design2 + '">' + d.title + '</option>';
    });
    return html;
  }

  function reindexRows(){
    var cards = Array.prototype.slice.call(itemsWrap.querySelectorAll('.item-card'));
    cards.forEach(function(card, idx){
      card.dataset.index = String(idx);
      var no = card.querySelector('.rowNo');
      if(no) no.textContent = String(idx + 1);

      // name ها را ری‌مپ کن
      var inputs = card.querySelectorAll('[name]');
      inputs.forEach(function(el){
        var n = el.getAttribute('name');
        if(!n) return;
        el.setAttribute('name', n.replace(/items\[\d+\]/, 'items[' + idx + ']'));
      });
    });
  }

  function addRow(prefill){
    prefill = prefill || {};
    var html = rowTpl.innerHTML;
    html = html.replace(/__i__/g, String(itemsWrap.querySelectorAll('.item-card').length));
    html = html.replace('__cat_options__', catOptionsHtml());

    var wrap = document.createElement('div');
    wrap.innerHTML = html;
    var card = wrap.firstElementChild;
    itemsWrap.appendChild(card);

    wireRow(card);

    // prefill (برای کلیک روی طرح‌ها)
    if(prefill.category_id){
      card.querySelector('[data-role="catSel"]').value = String(prefill.category_id);
      onCatChange(card);
    }
    if(prefill.product_id){
      card.querySelector('[data-role="prodSel"]').value = String(prefill.product_id);
      onProductChange(card);
    }
    if(prefill.model_list_id !== undefined){
      card.querySelector('[data-role="modelSel"]').value = String(prefill.model_list_id);
      onModelChange(card);
    }
    if(prefill.design2){
      card.querySelector('[data-role="designSel"]').value = String(prefill.design2);
      onDesignChange(card);
    }

    reindexRows();
    calcAll();
  }

  function removeRow(card){
    card.remove();
    reindexRows();
    calcAll();
  }

  function setCodeState(card, code, ok){
    var chip = card.querySelector('[data-role="codeChip"]');
    var txt = card.querySelector('[data-role="codeText"]');
    txt.textContent = code || '—';
    chip.classList.remove('ok');
    chip.classList.remove('bad');
    chip.classList.add(ok ? 'ok' : 'bad');
  }

  function setVariant(card, v){
    var variantId = card.querySelector('[data-role="variantId"]');
    var variantCode = card.querySelector('[data-role="variantCode"]');
    var design2Hidden = card.querySelector('[data-role="design2Hidden"]');
    var stockText = card.querySelector('[data-role="stockText"]');

    if(!v){
      variantId.value = '';
      variantCode.value = '';
      design2Hidden.value = '';
      stockText.textContent = '—';
      setCodeState(card, '—', false);
      return;
    }

    variantId.value = String(v.id);
    variantCode.value = String(v.variant_code || '');
    design2Hidden.value = String(v.design2 || String(v.variant_code || '').slice(-2) || '00');

    var stock = parseInt(v.stock || 0,10);
    var reserved = parseInt(v.reserved || 0,10);
    var avail = Math.max(0, stock - reserved);
    stockText.textContent = String(avail);

    setCodeState(card, String(v.variant_code || ''), true);

    // قیمت پیش‌فرض (اگر خالی بود)
    var buy = card.querySelector('[data-role="buyPrice"]');
    var sell = card.querySelector('[data-role="sellPrice"]');

    if(buy && (!buy.value || moneyToInt(buy.value) === 0) && v.buy_price !== null && v.buy_price !== undefined){
      buy.value = String(v.buy_price);
    }
    if(sell && (!sell.value || moneyToInt(sell.value) === 0) && v.sell_price !== null && v.sell_price !== undefined){
      sell.value = String(v.sell_price);
    }
  }

  function onCatChange(card){
    var catSel = card.querySelector('[data-role="catSel"]');
    var prodSel = card.querySelector('[data-role="prodSel"]');
    var modelSel = card.querySelector('[data-role="modelSel"]');
    var designSel = card.querySelector('[data-role="designSel"]');
    var prodCode = card.querySelector('[data-role="prodCode"]');

    var catId = catSel.value;
    prodSel.disabled = !catId;
    prodSel.innerHTML = catId ? buildProductOptions(productsByCat(catId)) : '<option value="">ابتدا دسته‌بندی</option>';

    modelSel.disabled = true;
    modelSel.innerHTML = '<option value="">ابتدا کالا</option>';

    designSel.disabled = true;
    designSel.innerHTML = '<option value="">ابتدا مدل</option>';

    prodCode.textContent = '—';
    setVariant(card, null);
    hideQuickDesigns(card);
  }

  function onProductChange(card){
    var prodSel = card.querySelector('[data-role="prodSel"]');
    var modelSel = card.querySelector('[data-role="modelSel"]');
    var designSel = card.querySelector('[data-role="designSel"]');
    var prodCode = card.querySelector('[data-role="prodCode"]');

    var pid = prodSel.value ? parseInt(prodSel.value,10) : 0;

    if(!pid){
      modelSel.disabled = true;
      modelSel.innerHTML = '<option value="">ابتدا کالا</option>';
      designSel.disabled = true;
      designSel.innerHTML = '<option value="">ابتدا مدل</option>';
      prodCode.textContent = '—';
      setVariant(card, null);
      hideQuickDesigns(card);
      return;
    }

    var p = productById(pid);
    prodCode.textContent = p && p.code ? String(p.code) : '—';

    modelSel.disabled = false;
    modelSel.innerHTML = buildModelOptions(pid);

    designSel.disabled = true;
    designSel.innerHTML = '<option value="">ابتدا مدل</option>';

    setVariant(card, null);
    hideQuickDesigns(card);
  }

  function onModelChange(card){
    var prodSel = card.querySelector('[data-role="prodSel"]');
    var modelSel = card.querySelector('[data-role="modelSel"]');
    var designSel = card.querySelector('[data-role="designSel"]');

    var pid = prodSel.value ? parseInt(prodSel.value,10) : 0;
    var mid = modelSel.value === '' ? null : parseInt(modelSel.value,10);

    if(!pid || mid === null){
      designSel.disabled = true;
      designSel.innerHTML = '<option value="">ابتدا مدل</option>';
      setVariant(card, null);
      hideQuickDesigns(card);
      return;
    }

    designSel.disabled = false;
    designSel.innerHTML = buildDesignOptions(pid, mid);

    setVariant(card, null);
    buildQuickDesigns(card, pid, mid);
  }

  function onDesignChange(card){
    var prodSel = card.querySelector('[data-role="prodSel"]');
    var modelSel = card.querySelector('[data-role="modelSel"]');
    var designSel = card.querySelector('[data-role="designSel"]');

    var pid = prodSel.value ? parseInt(prodSel.value,10) : 0;
    var mid = modelSel.value === '' ? null : parseInt(modelSel.value,10);
    var d2 = designSel.value ? normalizeDesign2(designSel.value) : null;

    if(!pid || mid === null || !d2){
      setVariant(card, null);
      return;
    }

    var key = pid + '|' + mid + '|' + d2;
    var v = V_MAP[key] || null;

    // اگر variant موجود نبود، کد را هم نشان بده ولی با حالت اخطار
    if(!v){
      // ساخت کد ۱۱ رقمی از product.code + model3 + design2
      var p = productById(pid);
      var p6 = p && p.code ? String(p.code) : '000000';
      var model3 = (mid === 0) ? '000' : (P_META[pid] && P_META[pid].modelsMap[mid] ? P_META[pid].modelsMap[mid].code3 : '000');
      var code11 = '' + p6 + model3 + d2;

      setCodeState(card, code11, false);
      setVariant(card, null);
      return;
    }

    // برای اینکه تو JS راحت باشه:
    v.design2 = d2;

    setVariant(card, v);
    calcRow(card);
    calcAll();
  }

  function buildQuickDesigns(card, pid, mid){
    var box = card.querySelector('[data-role="quickDesigns"]');
    var pillsWrap = card.querySelector('[data-role="designPills"]');

    pillsWrap.innerHTML = '';

    var meta = P_META[pid];
    var designs = meta && meta.designsByModel[mid] ? meta.designsByModel[mid] : [];

    if(!designs || designs.length <= 1){
      box.style.display = 'none';
      return;
    }

    designs.forEach(function(d){
      var pill = document.createElement('span');
      pill.className = 'design-pill';
      pill.textContent = d.title + ' (' + d.design2 + ')';
      pill.addEventListener('click', function(){
        // ردیف جدید با همان category/product/model و طرح انتخابی
        var catSel = card.querySelector('[data-role="catSel"]');
        addRow({
          category_id: catSel.value,
          product_id: pid,
          model_list_id: mid,
          design2: d.design2
        });
      });
      pillsWrap.appendChild(pill);
    });

    box.style.display = 'block';
  }

  function hideQuickDesigns(card){
    var box = card.querySelector('[data-role="quickDesigns"]');
    if(box) box.style.display = 'none';
  }

  function wireRow(card){
    var catSel = card.querySelector('[data-role="catSel"]');
    var prodSel = card.querySelector('[data-role="prodSel"]');
    var modelSel = card.querySelector('[data-role="modelSel"]');
    var designSel = card.querySelector('[data-role="designSel"]');

    var qty = card.querySelector('[data-role="qty"]');
    var buy = card.querySelector('[data-role="buyPrice"]');

    var btnRemove = card.querySelector('[data-role="removeRow"]');

    catSel.addEventListener('change', function(){ onCatChange(card); calcAll(); });
    prodSel.addEventListener('change', function(){ onProductChange(card); calcAll(); });
    modelSel.addEventListener('change', function(){ onModelChange(card); calcAll(); });
    designSel.addEventListener('change', function(){ onDesignChange(card); });

    qty.addEventListener('input', function(){ calcRow(card); calcAll(); });
    buy.addEventListener('input', function(){ calcRow(card); calcAll(); });

    var sell = card.querySelector('[data-role="sellPrice"]');
    sell.addEventListener('input', function(){ calcAll(); });

    btnRemove.addEventListener('click', function(){ removeRow(card); });
  }

  function calcRow(card){
    var qty = moneyToInt(card.querySelector('[data-role="qty"]').value);
    var buy = moneyToInt(card.querySelector('[data-role="buyPrice"]').value);
    var sum = qty * buy;
    card.querySelector('[data-role="rowSum"]').textContent = fmt(sum);
    return sum;
  }

  function calcAll(){
    var cards = Array.prototype.slice.call(itemsWrap.querySelectorAll('.item-card'));
    var total = 0;
    cards.forEach(function(c){
      total += calcRow(c);
    });

    // تخفیف
    var dtype = discountType.value;
    var dval = parseInt(discountValue.value || '0', 10);
    if(dval < 0) dval = 0;

    var disc = 0;
    if(dtype === 'amount'){
      disc = Math.min(total, dval);
    } else if(dtype === 'percent'){
      if(dval > 100) dval = 100;
      disc = Math.floor(total * (dval / 100));
    }

    var payable = Math.max(0, total - disc);

    sumTotal.textContent = fmt(total);
    sumDiscount.textContent = fmt(disc);
    sumPayable.textContent = fmt(payable);
  }

  // ===== دکمه‌ها =====
  btnAddRow.addEventListener('click', function(){ addRow(); });

  btnClearRows.addEventListener('click', function(){
    itemsWrap.innerHTML = '';
    addRow();
    calcAll();
  });

  discountType.addEventListener('change', calcAll);
  discountValue.addEventListener('input', calcAll);

  // ===== شروع =====
  addRow();
  calcAll();

  // ===== جلوگیری از ثبت با کد نامعتبر =====
  document.getElementById('purchaseForm').addEventListener('submit', function(e){
    var cards = Array.prototype.slice.call(itemsWrap.querySelectorAll('.item-card'));
    if(cards.length === 0){
      e.preventDefault();
      alert('حداقل یک ردیف خرید اضافه کن.');
      return;
    }

    for(var i=0;i<cards.length;i++){
      var vId = cards[i].querySelector('[data-role="variantId"]').value;
      var code = cards[i].querySelector('[data-role="codeText"]').textContent;
      if(!vId){
        e.preventDefault();
        alert('ردیف ' + (i+1) + ' تنوع معتبر ندارد. (کد/مدل/طرح باید دقیقاً مطابق تنوع‌های ساخته‌شده باشد) \nکد فعلی: ' + code);
        return;
      }
    }
  });

});
</script>
@endsection
