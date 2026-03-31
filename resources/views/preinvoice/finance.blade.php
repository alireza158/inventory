@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">✅ تایید مالی پیش‌فاکتور</h4>
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

  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-3"><b>کد:</b> {{ $order->uuid }}</div>
        <div class="col-md-3"><b>مشتری:</b> {{ $order->customer_name }}</div>
        <div class="col-md-3"><b>موبایل:</b> {{ $order->customer_mobile }}</div>
        <div class="col-md-3"><b>جمع کل:</b> {{ number_format((int) $order->total_price) }} تومان</div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header">اقلام پیش‌فاکتور</div>
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr>
            <th>محصول</th>
            <th>مدل</th>
            <th>تعداد</th>
            <th>قیمت</th>
          </tr>
        </thead>
        <tbody>
          @foreach($order->items as $it)
            <tr>
              <td>{{ $it->product?->name ?? ('#'.$it->product_id) }}</td>
              <td>{{ $it->variant?->variant_name ?? '—' }}</td>
              <td>{{ number_format((int) $it->quantity) }}</td>
              <td>{{ number_format((int) $it->price) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <form method="POST" action="{{ route('preinvoice.draft.finalize', $order->uuid) }}" enctype="multipart/form-data" class="card">
    @csrf
    <div class="card-header">ثبت واریزی / چک (اختیاری) قبل از تایید نهایی</div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-3">
          <label class="form-label">روش پرداخت</label>
          <select name="payment_method" id="payment_method" class="form-select">
            <option value="">بدون پرداخت</option>
            <option value="cash">نقدی</option>
            <option value="card">واریزی/کارت</option>
            <option value="cheque">چک</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">مبلغ</label>
          <input type="number" min="1" name="payment_amount" class="form-control" placeholder="مبلغ">
        </div>
        <div class="col-md-3">
          <label class="form-label">تاریخ پرداخت</label>
          <input type="date" name="payment_paid_at" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">فیش واریزی</label>
          <input type="file" name="payment_receipt_image" class="form-control" accept="image/*">
        </div>
        <div class="col-12">
          <label class="form-label">یادداشت پرداخت</label>
          <textarea name="payment_note" class="form-control" rows="2"></textarea>
        </div>
      </div>

      <div class="mt-3" id="chequeActions" style="display:none;">
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#chequeModal">ثبت جزئیات چک</button>
        <span class="text-muted small me-2">برای پرداخت چکی، جزئیات چک را در پاپ‌آپ ثبت کنید.</span>
      </div>
    </div>

    <div class="card-footer d-flex justify-content-end gap-2">
      <a href="{{ route('preinvoice.draft.edit', $order->uuid) }}" class="btn btn-outline-secondary">ویرایش فاکتور</a>
      <button class="btn btn-success" onclick="return confirm('تایید نهایی انجام شود؟ با تایید، فاکتور ساخته و به حواله انبار ارسال می‌شود.')">تایید نهایی مالی</button>
    </div>

    <input type="hidden" name="cheque_bank_name" id="cheque_bank_name">
    <input type="hidden" name="cheque_number" id="cheque_number">
    <input type="hidden" name="cheque_due_date" id="cheque_due_date">
    <input type="hidden" name="cheque_status" id="cheque_status" value="pending">
    <input type="file" name="cheque_image" id="cheque_image" class="d-none">
  </form>
</div>

<div class="modal fade" id="chequeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">شمای چک و ثبت اطلاعات</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="border rounded p-3 mb-3 bg-light">
          <div class="fw-bold">🧾 چک</div>
          <div class="small text-muted">فیلدهای چک را تکمیل کنید.</div>
        </div>
        <div class="row g-2">
          <div class="col-12"><input class="form-control" id="cheque_bank_name_ui" placeholder="نام بانک"></div>
          <div class="col-12"><input class="form-control" id="cheque_number_ui" placeholder="شماره چک"></div>
          <div class="col-12"><input class="form-control" id="cheque_due_date_ui" type="date"></div>
          <div class="col-12">
            <select class="form-select" id="cheque_status_ui">
              <option value="pending">در انتظار وصول</option>
              <option value="cleared">وصول شده</option>
              <option value="bounced">برگشتی</option>
            </select>
          </div>
          <div class="col-12"><input class="form-control" id="cheque_image_ui" type="file" accept="image/*"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
        <button type="button" class="btn btn-primary" id="saveCheque">ذخیره اطلاعات چک</button>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    const method = document.getElementById('payment_method');
    const chequeActions = document.getElementById('chequeActions');
    function toggleCheque() {
      chequeActions.style.display = method.value === 'cheque' ? 'block' : 'none';
    }
    method.addEventListener('change', toggleCheque);
    toggleCheque();

    document.getElementById('saveCheque').addEventListener('click', function () {
      document.getElementById('cheque_bank_name').value = document.getElementById('cheque_bank_name_ui').value;
      document.getElementById('cheque_number').value = document.getElementById('cheque_number_ui').value;
      document.getElementById('cheque_due_date').value = document.getElementById('cheque_due_date_ui').value;
      document.getElementById('cheque_status').value = document.getElementById('cheque_status_ui').value;

      const uiFile = document.getElementById('cheque_image_ui');
      const hiddenFile = document.getElementById('cheque_image');
      if (uiFile.files.length > 0) {
        const dt = new DataTransfer();
        dt.items.add(uiFile.files[0]);
        hiddenFile.files = dt.files;
      }

      const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('chequeModal'));
      modal.hide();
    });
  })();
</script>
@endsection
