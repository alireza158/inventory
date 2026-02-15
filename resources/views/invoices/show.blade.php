@extends('layouts.app')

@section('content')
@php
  use Morilog\Jalali\Jalalian;

  $methodFa = fn($m) => match($m){
    'cash' => 'نقدی',
    'card' => 'کارت',
    'cheque' => 'چک',
    default => $m,
  };

  $statusFa = fn($s) => match($s){
    'processing' => 'در حال پردازش',
    'shipped' => 'ارسال شده',
    'delivered' => 'تحویل شده',
    'canceled' => 'کنسل شده',
    default => $s,
  };

  $jalali = function($dt){
    if (!$dt) return '—';
    return Jalalian::fromDateTime($dt)->format('Y/m/d');
  };

  $jalaliDT = function($dt){
    if (!$dt) return '—';
    return Jalalian::fromDateTime($dt)->format('Y/m/d H:i');
  };

  $toman = function($amount){
    $n = (int)($amount ?? 0);
    return number_format($n).' تومان';
  };

  $productTitle = function($it){
    return $it->product?->title
        ?? $it->product?->name
        ?? ('#'.$it->product_id);
  };

  $variantTitle = function($it){
    if ($it->variant) {
      if (!empty($it->variant->title)) return $it->variant->title;
      if (!empty($it->variant->name)) return $it->variant->name;
      if (!empty($it->variant->variant_name)) return $it->variant->variant_name;
      if (!empty($it->variant->unique_attributes_key)) return $it->variant->unique_attributes_key;
    }
    return $it->variant_id ? ('#'.$it->variant_id) : 'بدون مدل';
  };

  $badgeStatus = fn($s) => match($s){
    'processing' => 'bg-warning text-dark',
    'shipped' => 'bg-info text-dark',
    'delivered' => 'bg-success',
    'canceled' => 'bg-danger',
    default => 'bg-secondary',
  };

  $badgeMethod = fn($m) => match($m){
    'cash' => 'bg-success',
    'card' => 'bg-primary',
    'cheque' => 'bg-warning text-dark',
    default => 'bg-secondary',
  };
@endphp

<style>
  .card-soft{background:#fff;border:1px solid rgba(0,0,0,.07);border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.04)}
  .section-title{font-weight:800}
  .hint{color:#6c757d;font-size:.9rem}
  .kv{display:flex;gap:.5rem;align-items:center;margin-bottom:.35rem}
  .kv .k{min-width:92px;color:#6c757d}
  .kv .v{font-weight:600}
  .sticky-side{position:sticky;top:16px}
</style>

<div class="container">

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <div class="h5 fw-bold mb-0">🧾 فاکتور</div>
      <div class="text-muted small">{{ $invoice->uuid }}</div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('invoices.index') }}">بازگشت</a>
  </div>

  <div class="row g-3">

    {{-- LEFT --}}
    <div class="col-lg-7">

      {{-- Customer --}}
      <div class="card-soft p-3 p-md-4 mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="section-title">👤 اطلاعات مشتری</div>
          <span class="badge {{ $badgeStatus($invoice->status) }} px-3 py-2">
            {{ $statusFa($invoice->status) }}
          </span>
        </div>

        <div class="mt-3">
          <div class="kv"><div class="k">نام:</div><div class="v">{{ $invoice->customer_name ?: '—' }}</div></div>
          <div class="kv"><div class="k">موبایل:</div><div class="v">{{ $invoice->customer_mobile ?: '—' }}</div></div>
          <div class="kv"><div class="k">آدرس:</div><div class="v fw-normal">{{ $invoice->customer_address ?: '—' }}</div></div>
        </div>
      </div>

      {{-- Items --}}
      <div class="card-soft overflow-hidden">
        <div class="p-3 p-md-4 border-bottom d-flex justify-content-between align-items-center">
          <div>
            <div class="section-title mb-1">🛍️ آیتم‌ها</div>
            <div class="hint">جزئیات محصولات داخل فاکتور</div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>محصول</th>
                <th>مدل</th>
                <th class="text-nowrap">تعداد</th>
                <th class="text-nowrap">قیمت</th>
                <th class="text-nowrap">جمع</th>
              </tr>
            </thead>
            <tbody>
              @foreach($invoice->items as $it)
                @php $line = (int)($it->line_total ?? ($it->price * $it->quantity)); @endphp
                <tr>
                  <td class="fw-semibold">{{ $productTitle($it) }}</td>
                  <td>{{ $variantTitle($it) }}</td>
                  <td class="text-nowrap">{{ number_format($it->quantity) }}</td>
                  <td class="text-nowrap">{{ $toman($it->price) }}</td>
                  <td class="text-nowrap fw-bold">{{ $toman($line) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="p-3 p-md-4 border-top">
          <div class="row g-2">
            <div class="col-6"><span class="text-muted">جمع جزء:</span> <b>{{ $toman($invoice->subtotal) }}</b></div>
            <div class="col-6"><span class="text-muted">هزینه ارسال:</span> <b>{{ $toman($invoice->shipping_price) }}</b></div>
            <div class="col-6"><span class="text-muted">تخفیف:</span> <b>{{ $toman($invoice->discount_amount) }}</b></div>
            <div class="col-6 fs-5"><span class="text-muted">مبلغ کل:</span> <b>{{ $toman($invoice->total) }}</b></div>
          </div>
        </div>
      </div>
    </div>

    {{-- RIGHT --}}
    <div class="col-lg-5">
      <div class="sticky-side">

        {{-- Status --}}
        <div class="card-soft p-3 p-md-4 mb-3">
          <div class="section-title mb-2">📦 وضعیت سفارش</div>
          <form method="POST" action="{{ route('invoices.status', $invoice->uuid) }}" class="d-flex gap-2">
            @csrf
            <select name="status" class="form-select">
              <option value="processing" @selected($invoice->status==='processing')>در حال پردازش</option>
              <option value="shipped" @selected($invoice->status==='shipped')>ارسال شده</option>
              <option value="delivered" @selected($invoice->status==='delivered')>تحویل شده</option>
              <option value="canceled" @selected($invoice->status==='canceled')>کنسل شده</option>
            </select>
            <button class="btn btn-primary">ثبت</button>
          </form>
          <div class="hint mt-2">آخرین بروزرسانی: {{ $jalaliDT($invoice->updated_at) }}</div>
        </div>

        {{-- Payments --}}
        <div class="card-soft p-3 p-md-4 mb-3">
          <div class="section-title mb-2">💳 پرداخت‌ها</div>

          <div class="d-flex justify-content-between">
            <div class="hint">پرداخت شده</div>
            <div class="fw-bold">{{ $toman($invoice->paid_amount) }}</div>
          </div>
          <div class="d-flex justify-content-between mb-3">
            <div class="hint">مانده</div>
            <div class="fw-bold text-danger">{{ $toman($invoice->remaining_amount) }}</div>
          </div>

          {{-- Add payment --}}
          <div class="border rounded-3 p-2 p-md-3" style="background:rgba(25,135,84,.04);border-color:rgba(25,135,84,.25)!important">
            <div class="fw-bold mb-2">➕ ثبت پرداخت</div>

            <form method="POST" action="{{ route('invoices.payments.store', $invoice->uuid) }}" enctype="multipart/form-data">
              @csrf
              <div class="row g-2">

                <div class="col-4">
                  <select name="method" class="form-select" required>
                    <option value="cash">نقدی</option>
                    <option value="card">کارت</option>
                    <option value="cheque">چک</option>
                  </select>
                </div>

                <div class="col-8">
                    {{-- نمایش با جداکننده --}}
                    <input id="amount_view" type="text" inputmode="numeric"
                           class="form-control" placeholder="مبلغ (تومان)" autocomplete="off" required>

                    {{-- مقدار واقعی برای ارسال --}}
                    <input name="amount" id="amount" type="hidden">
                  </div>


                {{-- Jalali picker (view) + hidden Gregorian --}}
                <div class="col-12">
                  <input id="paid_at_jalali" type="text" class="form-control" placeholder="تاریخ پرداخت (شمسی)">
                  <input name="paid_at" id="paid_at" type="hidden">
                  <div class="hint mt-1">اختیاری — اگر خالی باشد، امروز ثبت می‌شود.</div>
                </div>

                <div class="col-12">
                  <input name="receipt_image" type="file" class="form-control" accept="image/*">
                  <div class="hint mt-1">اختیاری — عکس رسید/فیش</div>
                </div>

                <div class="col-12">
                  <textarea name="note" class="form-control" rows="2" placeholder="یادداشت پرداخت (اختیاری)"></textarea>
                </div>

                <div class="col-12">
                  <button class="btn btn-success w-100">ثبت پرداخت</button>
                </div>

              </div>
            </form>
          </div>

          <hr class="my-3">

          {{-- Payments list --}}
          @forelse($invoice->payments as $p)
            <div class="border rounded-3 p-2 mb-2">
              <div class="d-flex justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge {{ $badgeMethod($p->method) }}">{{ $methodFa($p->method) }}</span>
                  <span class="fw-bold">{{ $toman($p->amount) }}</span>
                </div>
                <div class="text-muted small">{{ $jalali($p->paid_at) }}</div>
              </div>

              @if($p->receipt_image)
                <div class="mt-2">
                  <a target="_blank" class="btn btn-sm btn-outline-secondary"
                     href="{{ asset('storage/'.$p->receipt_image) }}">📎 مشاهده رسید</a>
                </div>
              @endif

              @if($p->method === 'cheque')
                <div class="mt-2 p-2 rounded-3" style="background:rgba(255,193,7,.12)">
                  @if($p->cheque)
                    <div class="small text-muted">
                      بانک: {{ $p->cheque->bank_name ?: '—' }} |
                      شماره چک: {{ $p->cheque->cheque_number ?: '—' }} |
                      سررسید: {{ $jalali($p->cheque->due_date) }} |
                      وضعیت: {{ $p->cheque->status ?: '—' }}
                    </div>
                    @if($p->cheque->image)
                      <div class="mt-2">
                        <a target="_blank" class="btn btn-sm btn-outline-warning"
                           href="{{ asset('storage/'.$p->cheque->image) }}">📷 مشاهده عکس چک</a>
                      </div>
                    @endif
                  @else
                    <form method="POST" action="{{ route('cheques.store', $p->id) }}" enctype="multipart/form-data" class="mt-2">
                      @csrf
                      <div class="row g-2">
                        <div class="col-12">
                          <input class="form-control" name="bank_name" placeholder="بانک">
                        </div>
                        <div class="col-12">
                          <input class="form-control" name="cheque_number" placeholder="شماره چک">
                        </div>

                        {{-- اگر اینجا هم می‌خوای شمسی بشه، می‌تونیم مثل paid_at درست کنیم.
                             فعلا میلادی گذاشتم تا بک‌اند اذیت نشه. --}}
                        <div class="col-12">
                          <input class="form-control" name="due_date" type="date">
                        </div>

                        <div class="col-12">
                          <input class="form-control" name="image" type="file" accept="image/*">
                        </div>

                        <div class="col-12">
                          <button class="btn btn-outline-primary btn-sm w-100">ثبت اطلاعات چک</button>
                        </div>
                      </div>
                    </form>
                  @endif
                </div>
              @endif

              @if($p->note)
                <div class="small mt-2">{{ $p->note }}</div>
              @endif
            </div>
          @empty
            <div class="text-muted small">پرداختی ثبت نشده است.</div>
          @endforelse
        </div>

        {{-- Notes --}}
        <div class="card-soft p-3 p-md-4">
          <div class="section-title mb-2">📝 یادداشت‌ها</div>

          <form method="POST" action="{{ route('invoices.notes.store', $invoice->uuid) }}" class="mb-3">
            @csrf
            <textarea name="body" class="form-control" rows="2" placeholder="یادداشت جدید..." required></textarea>
            <button class="btn btn-primary w-100 mt-2">ثبت یادداشت</button>
          </form>

          @forelse($invoice->notes as $n)
            <div class="border rounded-3 p-2 mb-2">
              <div class="small text-muted">{{ $jalaliDT($n->created_at) }}</div>
              <div>{{ $n->body }}</div>
            </div>
          @empty
            <div class="text-muted small">یادداشتی ثبت نشده است.</div>
          @endforelse
        </div>

      </div>
    </div>

  </div>
</div>

<script>
    (function(){
      const view = document.getElementById('amount_view');
      const hidden = document.getElementById('amount');
      if (!view || !hidden) return;

      function toEnglishDigits(str) {
        return String(str || '')
          .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
          .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
      }

      function cleanNumber(str){
        return toEnglishDigits(str)
          .replaceAll(',', '')
          .replaceAll('٬', '')
          .replaceAll('،', '')
          .replaceAll(' ', '')
          .trim();
      }

      function formatWithComma(nStr){
        const n = parseInt(nStr, 10);
        if (!Number.isFinite(n)) return '';
        return n.toLocaleString('fa-IR'); // جداکننده فارسی
      }

      function sync(){
        const raw = cleanNumber(view.value);
        // فقط عدد
        const onlyDigits = raw.replace(/\D/g,'');
        hidden.value = onlyDigits ? String(parseInt(onlyDigits, 10)) : '';

        // فرمت نمایشی
        view.value = onlyDigits ? formatWithComma(onlyDigits) : '';
      }

      view.addEventListener('input', sync);
      view.addEventListener('blur', sync);

      // قبل submit هم حتما ست شود
      const form = view.closest('form');
      if (form) {
        form.addEventListener('submit', () => {
          sync();
          if (!hidden.value) {
            // اگر خالی بود، فرم required را fail کند
            view.focus();
          }
        }, { capture:true });
      }
    })();
    </script>

<script>
  (function () {
    if (!window.jalaliDatepicker) return;

    const jalaliInput = document.getElementById('paid_at_jalali');
    const gregorianInput = document.getElementById('paid_at');
    if (!jalaliInput || !gregorianInput) return;

    function div(a, b) { return ~~(a / b); }
    function pad(v) { return String(v).padStart(2, '0'); }
    function jalaliToGregorian(jy, jm, jd) {
      jy += 1595;
      let days = -355668 + (365 * jy) + div(jy, 33) * 8 + div((jy % 33) + 3, 4) + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
      let gy = 400 * div(days, 146097);
      days %= 146097;
      if (days > 36524) {
        gy += 100 * div(--days, 36524);
        days %= 36524;
        if (days >= 365) days++;
      }
      gy += 4 * div(days, 1461);
      days %= 1461;
      if (days > 365) {
        gy += div(days - 1, 365);
        days = (days - 1) % 365;
      }
      let gd = days + 1;
      const sal_a = [0,31,((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? 29 : 28,31,30,31,30,31,31,30,31,30,31];
      let gm = 0;
      for (gm = 1; gm <= 12 && gd > sal_a[gm]; gm++) gd -= sal_a[gm];
      return [gy, gm, gd];
    }

    function jalaliStringToGregorian(str) {
      const m = (str || '').trim().match(/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})$/);
      if (!m) return '';
      const g = jalaliToGregorian(Number(m[1]), Number(m[2]), Number(m[3]));
      return `${g[0]}-${pad(g[1])}-${pad(g[2])}`;
    }

    jalaliInput.setAttribute('data-jdp', '');
    jalaliInput.setAttribute('data-jdp-only-date', '');
    jalaliInput.setAttribute('autocomplete', 'off');
    jalaliDatepicker.startWatch();

    function syncPaidAt() {
      gregorianInput.value = jalaliStringToGregorian(jalaliInput.value);
    }

    jalaliInput.addEventListener('change', syncPaidAt);
    jalaliInput.addEventListener('input', syncPaidAt);

    const form = jalaliInput.closest('form');
    if (form) {
      form.addEventListener('submit', syncPaidAt);
    }
  })();
</script>

@endsection
