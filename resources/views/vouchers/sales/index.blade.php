@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">📦 حواله فروش کالا</h4>
    <form class="d-flex gap-2" method="GET">
      <input class="form-control" name="q" value="{{ $q }}" placeholder="جستجو">
      <button class="btn btn-primary">جستجو</button>
    </form>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr>
            <th>کد فاکتور</th><th>مشتری</th><th>مبلغ</th><th>وضعیت</th><th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($invoices as $inv)
            <tr>
              <td>{{ $inv->uuid }}</td>
              <td>{{ $inv->customer_name }}</td>
              <td>{{ number_format((int)$inv->total) }}</td>
              <td>{{ $inv->status }}</td>
              <td><a class="btn btn-sm btn-outline-primary" href="{{ route('vouchers.sales.edit', $inv->uuid) }}">مدیریت حواله</a></td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-3">موردی نیست</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="mt-3">{{ $invoices->links() }}</div>
</div>
@endsection
