@extends('layouts.app')

@section('title', 'لیست فاکتورها')
@section('content_class', 'app-content-wide')

@section('content')
@php
  use Morilog\Jalali\Jalalian;
  use Illuminate\Support\Str;

  $statusFa = fn($s) => match($s){
    'pending_warehouse_approval' => 'در انتظار تایید انبار',
    'collecting' => 'در حال جمع‌آوری',
    'checking_discrepancy' => 'در حال مغایرت',
    'final_check' => 'کنترل نهایی',
    'packing' => 'بسته‌بندی',
    'shipped' => 'ارسال شد',
    'not_shipped' => 'کنسل شده',
    default => $s ?: '—',
  };
  $statusBadge = fn($s) => match($s){
    'pending_warehouse_approval' => 'text-bg-info',
    'collecting', 'checking_discrepancy', 'final_check', 'packing' => 'text-bg-primary',
    'shipped' => 'text-bg-success',
    'not_shipped' => 'text-bg-danger',
    default => 'text-bg-secondary',
  };
  $rial = fn($amount) => \App\Support\Currency::formatRial((int) $amount);
@endphp

<style>
  .sales-wide-page{max-width:100%; overflow-x:hidden;}
  .sales-page-head,.sales-card{border:0; border-radius:18px; box-shadow:0 10px 28px rgba(15,23,42,.06);}
  .sales-page-head{background:linear-gradient(135deg,#fff,#f8fafc); padding:18px;}
  .sales-filter-card .form-label{font-size:.82rem; color:#64748b; font-weight:800;}
  .sales-table{table-layout:fixed; width:100%;}
  .sales-table th{font-size:.78rem; color:#64748b; white-space:nowrap;}
  .sales-table td{font-size:.88rem; vertical-align:middle;}
  .code-cell{direction:ltr; unicode-bidi:plaintext; display:inline-block; max-width:112px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;}
  .customer-cell{max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;}
  .money-cell{white-space:nowrap; font-variant-numeric:tabular-nums;}
  .action-stack{display:flex; gap:.35rem; justify-content:flex-end; flex-wrap:wrap;}
  .action-stack .btn{padding:.22rem .45rem; font-size:.76rem;}
  .invoice-mobile-card{border:1px solid #e5e7eb; border-radius:16px; padding:14px; background:#fff; box-shadow:0 6px 18px rgba(15,23,42,.04);}
  .payment-modal .modal-dialog{max-width:920px;}
  @media (min-width: 992px){
    .invoice-table-wrap{overflow-x:visible!important;}
  }
</style>

<div class="sales-wide-page">
  <div class="sales-page-head mb-3 d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
      <div class="h4 fw-black mb-1">🧾 لیست فاکتورها</div>
      <div class="text-muted small">مرجع اصلی همه فاکتورهای ثبت نهایی، پرداخت‌ها و مانده حساب هر فاکتور</div>
    </div>

    <div class="d-flex gap-2 flex-wrap align-items-center justify-content-end">
      <a class="btn btn-outline-secondary" href="{{ route('vouchers.index', ['voucher_type' => 'sale']) }}">حواله فروش کالا</a>
      <form class="d-flex gap-2 flex-wrap" method="GET" action="{{ route('invoices.index') }}">
        @foreach(($filters ?? []) as $key => $value)
          @if($key !== 'date')
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
          @endif
        @endforeach
        <input type="text" class="form-control" style="max-width:220px" name="export_date" value="{{ $reportDateInput ?? '' }}" placeholder="تاریخ خروجی 1403/03/21">
        <button class="btn btn-outline-success" type="submit" name="export" value="daily_csv">خروجی CSV</button>
      </form>
    </div>
  </div>

  <div class="card sales-card sales-filter-card mb-3">
    <div class="card-body">
      <form class="row g-3 align-items-end" method="GET" action="{{ route('invoices.index') }}">
        <div class="col-sm-6 col-xl-3">
          <label class="form-label">تاریخ شمسی ثبت فاکتور</label>
          <input type="text" class="form-control" name="date" value="{{ $filters['date'] ?? '' }}" dir="ltr" placeholder="1403/03/21 یا ۱۴۰۳/۰۳/۲۱">
        </div>
        <div class="col-sm-6 col-xl-3">
          <label class="form-label">شماره فاکتور</label>
          <input class="form-control" name="invoice_number" value="{{ $filters['invoice_number'] ?? $q ?? '' }}" placeholder="جستجوی بخشی از شماره">
        </div>
        <div class="col-sm-6 col-xl-3">
          <label class="form-label">کد شخص / مشتری</label>
          <input class="form-control" name="customer_code" value="{{ $filters['customer_code'] ?? '' }}" placeholder="کد داخلی یا CRM">
        </div>
        <div class="col-sm-6 col-xl-3">
          <label class="form-label">نام شخص / مشتری</label>
          <input class="form-control" name="customer_name" value="{{ $filters['customer_name'] ?? '' }}" placeholder="نام یا نام خانوادگی">
        </div>
        <div class="col-12 d-flex gap-2 justify-content-end flex-wrap">
          <a class="btn btn-outline-secondary" href="{{ route('invoices.index') }}">پاک‌کردن فیلترها</a>
          <button class="btn btn-primary px-4">جستجو</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card sales-card d-none d-lg-block">
    <div class="table-responsive invoice-table-wrap">
      <table class="table table-hover align-middle mb-0 sales-table">
        <colgroup>
          <col style="width:8%"><col style="width:14%"><col style="width:6%"><col style="width:9%"><col style="width:10%"><col style="width:9%"><col style="width:9%"><col style="width:9%"><col style="width:7%"><col style="width:7%"><col style="width:12%">
        </colgroup>
        <thead class="table-light">
          <tr>
            <th>شماره</th><th>مشتری</th><th>کد</th><th>موبایل</th><th>وضعیت</th><th>پرداخت</th><th>مبلغ</th><th>مانده</th><th>تاریخ</th><th>ثبت‌کننده</th><th class="text-end">عملیات</th>
          </tr>
        </thead>
        <tbody>
          @forelse($invoices as $inv)
            @php
              $paid = (int) ($inv->paid_total ?? 0);
              $remaining = max((int) $inv->total - $paid, 0);
              $payStatus = $remaining <= 0 ? 'تسویه شده' : ($paid > 0 ? 'پرداخت ناقص' : 'پرداخت نشده');
              $payStatusClass = $remaining <= 0 ? 'text-bg-success' : ($paid > 0 ? 'text-bg-warning' : 'text-bg-danger');
              $customerCode = $inv->customer?->crm_customer_id ?: $inv->customer_id;
              $customerName = $inv->customer_name ?: $inv->customer?->display_name ?: '—';
            @endphp
            <tr>
              <td><span class="code-cell fw-bold" title="{{ $inv->uuid }}">{{ Str::limit($inv->uuid, 10, '…') }}</span></td>
              <td><span class="customer-cell" title="{{ $customerName }}">{{ $customerName }}</span></td>
              <td class="text-nowrap">{{ $customerCode ?: '—' }}</td>
              <td class="text-nowrap">{{ $inv->customer_mobile ?: $inv->customer?->mobile ?: '—' }}</td>
              <td><span class="badge {{ $statusBadge($inv->status) }}">{{ $statusFa($inv->status) }}</span></td>
              <td><span class="badge {{ $payStatusClass }}">{{ $payStatus }}</span></td>
              <td class="money-cell">{{ $rial($inv->total) }}</td>
              <td class="money-cell fw-bold {{ $remaining > 0 ? 'text-danger' : 'text-success' }}">{{ $rial($remaining) }}</td>
              <td class="text-nowrap">{{ $inv->created_at ? Jalalian::fromDateTime($inv->created_at)->format('Y/m/d') : '—' }}</td>
              <td><span class="customer-cell" title="{{ $inv->preinvoiceOrder?->creator?->name ?? '—' }}">{{ $inv->preinvoiceOrder?->creator?->name ?? '—' }}</span></td>
              <td>
                <div class="action-stack">
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('invoices.show', $inv->uuid) }}">جزئیات</a>
                  @if(($canRegisterPayments ?? false) && $remaining > 0)
                    <button type="button" class="btn btn-sm btn-success js-open-payment" data-action="{{ route('invoices.payments.store', $inv->uuid) }}" data-invoice="{{ $inv->uuid }}" data-customer="{{ $customerName }}" data-remaining="{{ $remaining }}" data-remaining-label="{{ $rial($remaining) }}">پرداخت</button>
                  @endif
                  <a class="btn btn-sm btn-outline-dark" href="{{ route('invoices.print', $inv->uuid) }}" target="_blank">چاپ</a>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="11" class="text-center text-muted py-4">هیچ فاکتوری با فیلترهای انتخاب‌شده یافت نشد.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="d-lg-none vstack gap-2">
    @forelse($invoices as $inv)
      @php
        $paid = (int) ($inv->paid_total ?? 0);
        $remaining = max((int) $inv->total - $paid, 0);
        $payStatus = $remaining <= 0 ? 'تسویه شده' : ($paid > 0 ? 'پرداخت ناقص' : 'پرداخت نشده');
        $payStatusClass = $remaining <= 0 ? 'text-bg-success' : ($paid > 0 ? 'text-bg-warning' : 'text-bg-danger');
        $customerName = $inv->customer_name ?: $inv->customer?->display_name ?: '—';
      @endphp
      <div class="invoice-mobile-card">
        <div class="d-flex justify-content-between gap-2 mb-2">
          <div class="fw-bold">{{ $customerName }}</div>
          <span class="code-cell" title="{{ $inv->uuid }}">{{ Str::limit($inv->uuid, 12, '…') }}</span>
        </div>
        <div class="d-flex flex-wrap gap-2 mb-2">
          <span class="badge {{ $statusBadge($inv->status) }}">{{ $statusFa($inv->status) }}</span>
          <span class="badge {{ $payStatusClass }}">{{ $payStatus }}</span>
        </div>
        <div class="small text-muted d-flex justify-content-between"><span>مبلغ</span><strong>{{ $rial($inv->total) }}</strong></div>
        <div class="small text-muted d-flex justify-content-between"><span>مانده</span><strong class="{{ $remaining > 0 ? 'text-danger' : 'text-success' }}">{{ $rial($remaining) }}</strong></div>
        <div class="action-stack mt-3 justify-content-start">
          <a class="btn btn-sm btn-outline-primary" href="{{ route('invoices.show', $inv->uuid) }}">جزئیات</a>
          @if(($canRegisterPayments ?? false) && $remaining > 0)
            <button type="button" class="btn btn-sm btn-success js-open-payment" data-action="{{ route('invoices.payments.store', $inv->uuid) }}" data-invoice="{{ $inv->uuid }}" data-customer="{{ $customerName }}" data-remaining="{{ $remaining }}" data-remaining-label="{{ $rial($remaining) }}">افزودن پرداخت</button>
          @endif
          <a class="btn btn-sm btn-outline-dark" href="{{ route('invoices.print', $inv->uuid) }}" target="_blank">چاپ</a>
        </div>
      </div>
    @empty
      <div class="invoice-mobile-card text-center text-muted">هیچ فاکتوری با فیلترهای انتخاب‌شده یافت نشد.</div>
    @endforelse
  </div>

  <div class="mt-3">{{ $invoices->links() }}</div>
</div>

@if($canRegisterPayments ?? false)
<div class="modal fade payment-modal" id="invoicePaymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 rounded-4">
      <div class="modal-header bg-light">
        <div>
          <h5 class="modal-title fw-bold">➕ افزودن پرداخت</h5>
          <div class="small text-muted" id="paymentModalSubtitle">—</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
      </div>
      <form method="POST" action="#" enctype="multipart/form-data" id="invoicePaymentQuickForm">
        @csrf
        <div class="modal-body">
          <div class="alert alert-light border d-flex justify-content-between flex-wrap gap-2">
            <span>مانده قابل پرداخت</span><strong id="paymentRemainingLabel">—</strong>
          </div>
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">روش پرداخت</label>
              <select name="method" id="quick_payment_method" class="form-select" required>
                <option value="cash">نقدی</option>
                <option value="cheque">چکی</option>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label">مبلغ پرداخت</label>
              <input id="quick_amount_view" type="text" inputmode="numeric" class="form-control" placeholder="مبلغ (ریال)" required>
              <input name="amount" id="quick_amount" type="hidden">
            </div>
            <div class="col-md-4">
              <label class="form-label">تاریخ پرداخت شمسی</label>
              <input id="quick_paid_at_jalali" type="text" class="form-control" placeholder="1403/03/21" required>
              <input name="paid_at" id="quick_paid_at" type="hidden">
            </div>
            <div class="col-md-4 cash-fields">
              <label class="form-label">اسم بانک</label>
              <input name="bank_name" class="form-control" placeholder="مثال: ملی">
            </div>
            <div class="col-md-4 cash-fields">
              <label class="form-label">شماره پیگیری/رسید</label>
              <input name="payment_identifier" class="form-control" placeholder="اختیاری">
            </div>
            <div class="col-md-4 cash-fields">
              <label class="form-label">تصویر رسید</label>
              <input name="receipt_image" type="file" class="form-control" accept="image/*">
            </div>
            <div class="col-12 cheque-fields d-none">
              <div class="row g-3">
                <div class="col-md-3"><label class="form-label">شماره چک</label><input name="cheque_number" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">نام بانک</label><input name="cheque_bank_name" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">نام شعبه</label><input name="cheque_branch_name" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">تاریخ سررسید</label><input name="cheque_due_date" type="date" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">تاریخ دریافت</label><input name="cheque_received_at" type="date" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">نام مشتری</label><input name="cheque_customer_name" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">کد مشتری</label><input name="cheque_customer_code" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">وضعیت چک</label><select name="cheque_status" class="form-select"><option value="pending">در انتظار وصول</option><option value="cleared">وصول شده</option><option value="bounced">برگشتی</option></select></div>
              </div>
            </div>
            <div class="col-12"><label class="form-label">توضیحات</label><textarea name="note" class="form-control" rows="2"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
          <button class="btn btn-success">ثبت پرداخت</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

<script>
(function(){
  const modalEl = document.getElementById('invoicePaymentModal');
  const form = document.getElementById('invoicePaymentQuickForm');
  if (!modalEl || !form) return;
  const modal = new bootstrap.Modal(modalEl);
  const amountView = document.getElementById('quick_amount_view');
  const amountHidden = document.getElementById('quick_amount');
  const method = document.getElementById('quick_payment_method');
  const jalaliInput = document.getElementById('quick_paid_at_jalali');
  const gregInput = document.getElementById('quick_paid_at');
  let maxRemaining = 0;

  function toEnglishDigits(str){return String(str||'').replace(/[۰-۹]/g,d=>'۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g,d=>'٠١٢٣٤٥٦٧٨٩'.indexOf(d));}
  function cleanNumber(str){return toEnglishDigits(str).replace(/[٬,،\s]/g,'').trim();}
  function syncAmount(){const digits=cleanNumber(amountView.value).replace(/\D/g,''); amountHidden.value=digits?String(parseInt(digits,10)):''; amountView.value=digits?parseInt(digits,10).toLocaleString('fa-IR'):'';}
  function div(a,b){return ~~(a/b)}; function pad(v){return String(v).padStart(2,'0')}
  function jalaliToGregorian(jy,jm,jd){jy+=1595;let days=-355668+(365*jy)+div(jy,33)*8+div((jy%33)+3,4)+jd+((jm<7)?(jm-1)*31:((jm-7)*30)+186);let gy=400*div(days,146097);days%=146097;if(days>36524){gy+=100*div(--days,36524);days%=36524;if(days>=365)days++;}gy+=4*div(days,1461);days%=1461;if(days>365){gy+=div(days-1,365);days=(days-1)%365;}let gd=days+1;const sal=[0,31,((gy%4===0&&gy%100!==0)||(gy%400===0))?29:28,31,30,31,30,31,31,30,31,30,31];let gm=1;for(;gm<=12&&gd>sal[gm];gm++)gd-=sal[gm];return [gy,gm,gd];}
  function syncDate(){const m=(jalaliInput.value||'').trim().match(/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})$/); if(!m){gregInput.value=''; return;} const g=jalaliToGregorian(Number(m[1]),Number(m[2]),Number(m[3])); gregInput.value=`${g[0]}-${pad(g[1])}-${pad(g[2])}`;}
  function toggleMethod(){const isCheque=method.value==='cheque'; form.querySelectorAll('.cheque-fields').forEach(e=>e.classList.toggle('d-none',!isCheque)); form.querySelectorAll('.cash-fields').forEach(e=>e.classList.toggle('d-none',isCheque));}

  document.querySelectorAll('.js-open-payment').forEach(btn=>btn.addEventListener('click',()=>{
    form.reset(); amountHidden.value=''; gregInput.value=''; maxRemaining=parseInt(btn.dataset.remaining||'0',10)||0; form.action=btn.dataset.action;
    document.getElementById('paymentModalSubtitle').textContent=`فاکتور ${btn.dataset.invoice} | ${btn.dataset.customer}`;
    document.getElementById('paymentRemainingLabel').textContent=btn.dataset.remainingLabel || '';
    toggleMethod(); modal.show();
  }));
  amountView?.addEventListener('input', syncAmount); amountView?.addEventListener('blur', syncAmount);
  method?.addEventListener('change', toggleMethod);
  if (window.jalaliDatepicker && jalaliInput) { jalaliInput.setAttribute('data-jdp',''); jalaliInput.setAttribute('data-jdp-only-date',''); jalaliDatepicker.startWatch(); }
  jalaliInput?.addEventListener('input', syncDate); jalaliInput?.addEventListener('change', syncDate);
  form.addEventListener('submit', function(e){ syncAmount(); syncDate(); if((parseInt(amountHidden.value||'0',10)||0)>maxRemaining){ e.preventDefault(); alert('مبلغ پرداختی نمی‌تواند بیشتر از مانده فاکتور باشد.'); }});
  toggleMethod();
})();
</script>
@endsection
