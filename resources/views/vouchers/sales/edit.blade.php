@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h4 class="mb-3">✏️ ویرایش محصولات حواله فروش</h4>
  <div class="alert alert-info">کد فاکتور: {{ $invoice->uuid }} | مشتری: {{ $invoice->customer_name }}</div>

  <form method="POST" action="{{ route('vouchers.sales.update', $invoice->uuid) }}">
    @csrf
    @method('PUT')

    <div class="card">
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
            @foreach($invoice->items as $it)
              <tr>
                <td>{{ $it->product?->name ?? ('#'.$it->product_id) }}</td>
                <td>{{ $it->variant?->variant_name ?? '—' }}</td>
                <td>
                  <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $it->id }}">
                  <input class="form-control" type="number" min="1" name="items[{{ $loop->index }}][quantity]" value="{{ (int) $it->quantity }}" required>
                </td>
                <td>
                  <input class="form-control" type="number" min="0" name="items[{{ $loop->index }}][price]" value="{{ (int) $it->price }}" required>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('vouchers.sales.index') }}" class="btn btn-light">انصراف</a>
        <button class="btn btn-primary">ثبت تغییرات</button>
      </div>
    </div>
  </form>
</div>
@endsection
