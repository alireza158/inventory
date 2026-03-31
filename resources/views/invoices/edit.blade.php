@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">ویرایش فاکتور {{ $invoice->uuid }}</h4>
    <a class="btn btn-outline-secondary" href="{{ route('invoices.show', $invoice->uuid) }}">بازگشت</a>
  </div>

  <form method="POST" action="{{ route('invoices.update', $invoice->uuid) }}" class="card">
    @csrf
    @method('PUT')
    <div class="card-body">
      <div class="row g-2 mb-3">
        <div class="col-md-4"><input class="form-control" name="customer_name" value="{{ old('customer_name', $invoice->customer_name) }}" placeholder="نام مشتری" required></div>
        <div class="col-md-4"><input class="form-control" name="customer_mobile" value="{{ old('customer_mobile', $invoice->customer_mobile) }}" placeholder="موبایل" required></div>
        <div class="col-md-4"><input class="form-control" name="customer_address" value="{{ old('customer_address', $invoice->customer_address) }}" placeholder="آدرس"></div>
      </div>

      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>محصول</th><th>مدل</th><th>تعداد</th><th>قیمت</th></tr></thead>
          <tbody>
            @foreach($invoice->items as $item)
            <tr>
              <td>{{ $item->product?->name ?? ('#'.$item->product_id) }}</td>
              <td>{{ $item->variant?->variant_name ?? '—' }}</td>
              <td>
                <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                <input class="form-control" type="number" min="1" name="items[{{ $loop->index }}][quantity]" value="{{ $item->quantity }}" required>
              </td>
              <td><input class="form-control" type="number" min="0" name="items[{{ $loop->index }}][price]" value="{{ $item->price }}" required></td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer text-end">
      <button class="btn btn-primary">ذخیره تغییرات</button>
    </div>
  </form>
</div>
@endsection
