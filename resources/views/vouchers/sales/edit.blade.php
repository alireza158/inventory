@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">🧾 ویرایش حواله فروش</h4>
    <div class="d-flex gap-2">
      <a href="{{ route('vouchers.sales.print', $invoice->uuid) }}" target="_blank" class="btn btn-outline-success">چاپ</a>
      <a href="{{ route('vouchers.sales.show', $invoice->uuid) }}" class="btn btn-outline-secondary">نمایش</a>
      <a href="{{ route('vouchers.sales.index') }}" class="btn btn-outline-dark">بازگشت</a>
    </div>
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  @unless($canEditItems)
    <div class="alert alert-warning">این حواله در وضعیت «{{ $statusLabels[$invoice->status] ?? $invoice->status }}» قابل ویرایش آیتم نیست.</div>
  @endunless

  <form method="POST" action="{{ route('vouchers.sales.status', $invoice->uuid) }}" class="card border-0 shadow-sm mb-3">
    @csrf
    <div class="card-body row g-2 align-items-end">
      <div class="col-md-5">
        <label class="form-label">تغییر وضعیت حواله</label>
        <select name="status" class="form-select">
          @foreach($statusLabels as $key => $label)
            <option value="{{ $key }}" @selected($invoice->status===$key)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-5">
        <label class="form-label">یادداشت</label>
        <input name="note" class="form-control" placeholder="برای ارسال‌شده الزامی است">
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100">ثبت وضعیت</button>
      </div>
    </div>
  </form>

  <form method="POST" action="{{ route('vouchers.sales.update', $invoice->uuid) }}" class="card border-0 shadow-sm">
    @csrf
    @method('PUT')
    <div class="card-body">
      <div class="row g-2 mb-3">
        <div class="col-md-6"><b>کد حواله:</b> {{ $invoice->uuid }}</div>
        <div class="col-md-6 text-md-end"><b>وضعیت:</b> {{ $statusLabels[$invoice->status] ?? $invoice->status }}</div>
      </div>

      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th>محصول</th><th>مدل</th><th>تعداد</th><th>قیمت</th><th>حذف</th></tr></thead>
          <tbody>
            @foreach($invoice->items as $it)
              <tr>
                <td>{{ $it->product?->name ?? '#'.$it->product_id }}</td>
                <td>{{ $it->variant?->variant_name ?? '—' }}</td>
                <td>
                  <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $it->id }}">
                  <input type="number" min="0" name="items[{{ $loop->index }}][quantity]" value="{{ (int)$it->quantity }}" class="form-control" @disabled(!$canEditItems)>
                </td>
                <td><input type="number" min="0" name="items[{{ $loop->index }}][price]" value="{{ (int)$it->price }}" class="form-control" @disabled(!$canEditItems)></td>
                <td><button type="button" class="btn btn-outline-danger btn-sm js-zero-item" @disabled(!$canEditItems)>حذف از فاکتور</button></td>
              </tr>
            @endforeach
            <tr class="table-info">
                <td><input name="items[999][product_id]" class="form-control" placeholder="شناسه محصول جدید" @disabled(!$canEditItems)></td>
                <td><input name="items[999][variant_id]" class="form-control" placeholder="شناسه تنوع فعال" @disabled(!$canEditItems)></td>
                <td><input type="number" min="0" name="items[999][quantity]" value="0" class="form-control" @disabled(!$canEditItems)></td>
                <td><input type="number" min="0" name="items[999][price]" value="0" class="form-control" @disabled(!$canEditItems)></td>
                <td class="text-muted small">برای افزودن کالا، شناسه تنوع و تعداد را وارد کنید.</td>
              </tr>
          </tbody>
        </table>
      </div>
      <div class="row g-2">
        <div class="col-md-4">
          <label class="form-label">دلیل تغییر اقلام <span class="text-danger">*</span></label>
          <select name="edit_reason" class="form-select" required @disabled(!$canEditItems)>
            <option value="">انتخاب کنید</option>
            <option value="physical_shortage">کالا در نرم‌افزار موجود بود ولی فیزیکی پیدا نشد</option>
            <option value="customer_cancelled">انصراف مشتری</option>
            <option value="wrong_item">کالای اشتباه ثبت شده بود</option>
            <option value="warehouse_correction">اصلاح انبار</option>
            <option value="finance_correction">اصلاح مالی</option>
            <option value="replacement">جایگزینی کالا</option>
            <option value="other">سایر</option>
          </select>
        </div>
        <div class="col-md-8">
          <label class="form-label">توضیح تغییر</label>
          <input name="edit_note" class="form-control" placeholder="توضیح تکمیلی حذف، کاهش، افزایش یا افزودن کالا" @disabled(!$canEditItems)>
        </div>
      </div>
    </div>
    <div class="card-footer text-end">
      <button class="btn btn-success" @disabled(!$canEditItems)>ذخیره تغییرات حواله فروش</button>
    </div>
  </form>
</div>
<script>
document.querySelectorAll('.js-zero-item').forEach((button) => {
  button.addEventListener('click', () => {
    const row = button.closest('tr');
    const quantity = row?.querySelector('input[name$="[quantity]"]');
    if (quantity) {
      quantity.value = 0;
      row.classList.add('table-danger');
    }
  });
});
</script>
@endsection
