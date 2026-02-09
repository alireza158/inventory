@extends('layouts.app')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <div class="h5 fw-bold mb-0">🧾 فاکتورها</div>
      <div class="text-muted small">لیست فاکتورهای ثبت نهایی</div>
    </div>
    <form class="d-flex gap-2" method="GET">
      <input class="form-control" name="q" value="{{ $q }}" placeholder="جستجو کد/نام/موبایل">
      <button class="btn btn-primary">جستجو</button>
    </form>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>کد</th>
            <th>مشتری</th>
            <th>موبایل</th>
            <th>وضعیت</th>
            <th>مبلغ</th>
            <th>مانده</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($invoices as $inv)
            <tr>
              <td class="text-nowrap">{{ $inv->uuid }}</td>
              <td>{{ $inv->customer_name ?: '—' }}</td>
              <td class="text-nowrap">{{ $inv->customer_mobile ?: '—' }}</td>
              <td>{{ $inv->status }}</td>
              <td class="text-nowrap">{{ number_format($inv->total) }}</td>
              <td class="text-nowrap fw-bold">{{ number_format($inv->remaining_amount) }}</td>
              <td class="text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('invoices.show', $inv->uuid) }}">جزئیات</a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">{{ $invoices->links() }}</div>
</div>
@endsection
