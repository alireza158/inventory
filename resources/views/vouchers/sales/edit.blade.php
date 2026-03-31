@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">🧾 مدیریت حواله فروش</h4>
    <a href="{{ route('vouchers.sales.index') }}" class="btn btn-outline-secondary">بازگشت</a>
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <form method="POST" action="{{ route('vouchers.sales.update', $invoice->uuid) }}" class="card">
    @csrf
    @method('PUT')
    <div class="card-body">
      <div class="row g-2 mb-3">
        <div class="col-md-6"><b>فاکتور:</b> {{ $invoice->uuid }}</div>
        <div class="col-md-6">
          <label class="form-label">وضعیت</label>
          <select name="status" class="form-select">
            @foreach(['warehouse_pending','warehouse_collecting','warehouse_checking','warehouse_packing','warehouse_sent','canceled'] as $st)
              <option value="{{ $st }}" @selected($invoice->status===$st)>{{ $st }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>محصول</th><th>مدل</th><th>تعداد</th><th>قیمت</th></tr></thead>
          <tbody>
            @foreach($invoice->items as $it)
              <tr>
                <td>{{ $it->product?->name ?? '#'.$it->product_id }}</td>
                <td>{{ $it->variant?->variant_name ?? '—' }}</td>
                <td>
                  <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $it->id }}">
                  <input type="number" min="1" name="items[{{ $loop->index }}][quantity]" value="{{ (int)$it->quantity }}" class="form-control">
                </td>
                <td><input type="number" min="0" name="items[{{ $loop->index }}][price]" value="{{ (int)$it->price }}" class="form-control"></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer text-end">
      <button class="btn btn-success">ذخیره تغییرات حواله فروش</button>
    </div>
  </form>
</div>
@endsection
