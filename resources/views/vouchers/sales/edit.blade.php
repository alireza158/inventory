@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">🧾 ویرایش حواله فروش</h4>
    <div class="d-flex gap-2">
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

  <form method="POST" action="{{ route('invoices.status', $invoice->uuid) }}" class="card border-0 shadow-sm mb-3">
    @csrf
    <div class="card-body row g-2 align-items-end">
      <div class="col-md-5">
        <label class="form-label">تغییر وضعیت حواله</label>
        <select name="status" class="form-select">
          @foreach($statusLabels as $key => $label)
            @continue(!in_array($key, $allowedStatuses ?? [], true))
            <option value="{{ $key }}" @selected($invoice->status===$key)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-5">
        <label class="form-label">یادداشت</label>
        <input name="note" class="form-control" placeholder="اختیاری">
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
          <thead><tr><th>محصول</th><th>مدل</th><th>تعداد</th><th>قیمت</th></tr></thead>
          <tbody>
            @foreach($invoice->items as $it)
              <tr>
                <td>{{ $it->product?->name ?? '#'.$it->product_id }}</td>
                <td>{{ $it->variant?->variant_name ?? '—' }}</td>
                <td>
                  <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $it->id }}">
                  <input type="number" min="1" name="items[{{ $loop->index }}][quantity]" value="{{ (int)$it->quantity }}" class="form-control" @disabled(!$canEditItems)>
                </td>
                <td><input type="number" min="0" name="items[{{ $loop->index }}][price]" value="{{ (int)$it->price }}" class="form-control" @disabled(!$canEditItems)></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer text-end">
      <button class="btn btn-success" @disabled(!$canEditItems)>ذخیره تغییرات حواله فروش</button>
    </div>
  </form>
</div>
@endsection
