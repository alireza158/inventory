@extends('layouts.app')

@php
  use Morilog\Jalali\Jalalian;

  $subtotal = $order->items->sum(fn ($it) => ((int) $it->price) * ((int) $it->quantity));
  $shipping = (int) $order->shipping_price;
  $discount = (int) $order->discount_amount;
  $grandTotal = max($subtotal + $shipping - $discount, 0);
@endphp

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">✅ مشاهده و تایید مالی پیش‌فاکتور</h4>
    <a href="{{ route('preinvoice.draft.index') }}" class="btn btn-outline-secondary">بازگشت به صف مالی</a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card mb-3 shadow-sm border-0">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3 col-sm-6">
          <div class="text-muted small mb-1">کد پیش‌فاکتور</div>
          <div class="fw-semibold">{{ $order->uuid }}</div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="text-muted small mb-1">تاریخ ثبت پیش‌فاکتور</div>
          <div class="fw-semibold">{{ $order->created_at ? Jalalian::fromDateTime($order->created_at)->format('Y/m/d H:i') : '—' }}</div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="text-muted small mb-1">مشتری</div>
          <div class="fw-semibold">{{ $order->customer_name ?: '—' }}</div>
          <small class="text-muted">{{ $order->customer_mobile ?: 'شماره تماس ثبت نشده' }}</small>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="text-muted small mb-1">ثبت‌شده توسط</div>
          <div class="fw-semibold">{{ $order->creator?->name ?? '—' }}</div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="text-muted small mb-1">وضعیت حساب مشتری</div>
          <div class="fw-semibold {{ $customerBalanceStatus === 'بدهکار' ? 'text-danger' : ($customerBalanceStatus === 'بستانکار' ? 'text-success' : '') }}">
            {{ $customerBalanceStatus }} {{ $customerBalanceStatus === 'تسویه' ? '' : number_format($customerBalanceAmount) . ' تومان' }}
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="text-muted small mb-1">وضعیت</div>
          <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">در انتظار تایید مالی</span>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="text-muted small mb-1">جمع کل فاکتور</div>
          <div class="fw-bold">{{ number_format($grandTotal) }} تومان</div>
        </div>
      </div>
    </div>
  </div>

  <form method="POST" action="{{ route('preinvoice.draft.finalize', $order->uuid) }}" enctype="multipart/form-data" class="card shadow-sm border-0">
    @csrf
    <div class="card-body">
      <div class="row g-3">
        <div class="col-lg-5">
          <div class="card border h-100">
            <div class="card-header bg-white">
              <h6 class="mb-0">ثبت فیش / چک مشتری (اصلی)</h6>
              <small class="text-muted">می‌توانید چند ردیف پرداخت (نقدی، کارت، چک) ثبت کنید.</small>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">لیست پرداخت‌های ثبت‌شده</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addPaymentRow">+ افزودن پرداخت</button>
              </div>

              <div id="paymentRows" class="d-grid gap-3"></div>
              <div class="alert alert-light border mb-0 small text-muted" id="paymentGuide">
                هنوز پرداختی اضافه نشده است. در صورت نیاز روی «افزودن پرداخت» بزنید.
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-7">
          <div class="card border h-100">
            <div class="card-header bg-white">
              <h6 class="mb-0">اقلام پیش‌فاکتور و خلاصه مالی</h6>
            </div>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>محصول</th>
                    <th>مدل</th>
                    <th>تعداد</th>
                    <th>مبلغ واحد</th>
                    <th>جمع ردیف</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($order->items as $it)
                    <tr>
                      <td>{{ $it->product?->name ?? ('#'.$it->product_id) }}</td>
                      <td>{{ $it->variant?->variant_name ?? '—' }}</td>
                      <td>{{ number_format((int) $it->quantity) }}</td>
                      <td>{{ number_format((int) $it->price) }}</td>
                      <td>{{ number_format(((int) $it->price) * ((int) $it->quantity)) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <div class="card-body border-top">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">جمع اقلام</span>
                <strong>{{ number_format($subtotal) }} تومان</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">هزینه ارسال</span>
                <strong>{{ number_format($shipping) }} تومان</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">تخفیف لحاظ شده</span>
                <strong class="text-danger">- {{ number_format($discount) }} تومان</strong>
              </div>
              <hr>
              <div class="d-flex justify-content-between">
                <span class="fw-semibold">مبلغ نهایی فاکتور</span>
                <strong class="fs-5">{{ number_format($grandTotal) }} تومان</strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card-footer d-flex justify-content-end gap-2">
      <a href="{{ route('preinvoice.draft.edit', $order->uuid) }}" class="btn btn-outline-secondary">ویرایش فاکتور</a>
      <button class="btn btn-success" onclick="return confirm('تاییدیه نهایی مالی ثبت شود؟ با این کار، پیش‌فاکتور به فاکتور تبدیل می‌شود و در صف حواله فروش انبار قرار می‌گیرد.')">تاییدیه نهایی پیش‌فاکتور از سمت مالی</button>
    </div>
  </form>
</div>

<script>
  (function () {
    const rowsWrap = document.getElementById('paymentRows');
    const addBtn = document.getElementById('addPaymentRow');
    const guide = document.getElementById('paymentGuide');
    const form = rowsWrap.closest('form');

    const rowTemplate = () => `
      <div class="border rounded p-3 payment-row bg-light-subtle">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <strong>ردیف پرداخت</strong>
          <button type="button" class="btn btn-sm btn-outline-danger js-remove-row">حذف</button>
        </div>
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label">روش پرداخت</label>
            <select class="form-select js-method" data-field="method">
              <option value="cash">نقدی</option>
              <option value="card">واریزی/کارت</option>
              <option value="cheque">چک</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">مبلغ</label>
            <input type="text" inputmode="numeric" class="form-control money" data-field="amount" placeholder="مثلاً 10,000,000" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">تاریخ پرداخت</label>
            <input type="date" class="form-control" data-field="paid_at">
          </div>
          <div class="col-12">
            <label class="form-label">فیش واریزی</label>
            <input type="file" class="form-control" data-field="receipt_image" accept="image/*">
          </div>
          <div class="col-12">
            <label class="form-label">یادداشت</label>
            <textarea class="form-control" rows="2" data-field="note" placeholder="توضیح کوتاه..."></textarea>
          </div>
        </div>
        <div class="mt-3 p-2 border rounded bg-white js-cheque-fields d-none">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">نام بانک</label>
              <input type="text" class="form-control" data-field="cheque_bank_name">
            </div>
            <div class="col-md-6">
              <label class="form-label">شماره چک</label>
              <input type="text" class="form-control" data-field="cheque_number">
            </div>
            <div class="col-md-6">
              <label class="form-label">تاریخ سررسید</label>
              <input type="date" class="form-control" data-field="cheque_due_date">
            </div>
            <div class="col-md-6">
              <label class="form-label">وضعیت چک</label>
              <select class="form-select" data-field="cheque_status">
                <option value="pending">در انتظار وصول</option>
                <option value="cleared">وصول شده</option>
                <option value="bounced">برگشتی</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">تصویر چک</label>
              <input type="file" class="form-control" data-field="cheque_image" accept="image/*">
            </div>
          </div>
        </div>
      </div>
    `;

    const toggleChequeBox = (row) => {
      const method = row.querySelector('.js-method').value;
      const chequeBox = row.querySelector('.js-cheque-fields');
      chequeBox.classList.toggle('d-none', method !== 'cheque');
    };

    const reindexRows = () => {
      rowsWrap.querySelectorAll('.payment-row').forEach((row, idx) => {
        row.querySelectorAll('[data-field]').forEach((input) => {
          const field = input.getAttribute('data-field');
          input.setAttribute('name', `payments[${idx}][${field}]`);
        });
      });

      guide.classList.toggle('d-none', rowsWrap.querySelectorAll('.payment-row').length > 0);
    };

    addBtn.addEventListener('click', () => {
      rowsWrap.insertAdjacentHTML('beforeend', rowTemplate());
      const newRow = rowsWrap.lastElementChild;
      newRow.querySelector('.js-method').addEventListener('change', () => toggleChequeBox(newRow));
      newRow.querySelector('.js-remove-row').addEventListener('click', () => {
        newRow.remove();
        reindexRows();
      });
      toggleChequeBox(newRow);
      reindexRows();

      if (typeof initJalaliDatepickers === 'function') {
        initJalaliDatepickers();
      }
    });

    form.addEventListener('submit', () => {
      rowsWrap.querySelectorAll('[data-field="amount"]').forEach((input) => {
        input.value = (input.value || '').replace(/[^\d]/g, '');
      });
    });
  })();
</script>
@endsection
