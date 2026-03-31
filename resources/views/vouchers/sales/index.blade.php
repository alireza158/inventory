@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">🚚 حواله فروش کالا</h4>
      <small class="text-muted">فاکتورهای تایید مالی‌شده برای عملیات انبار و ویرایش آیتم‌ها</small>
    </div>
    <a href="{{ route('vouchers.index') }}" class="btn btn-outline-secondary">بازگشت</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <form class="d-flex gap-2 mb-3" method="GET">
    <input class="form-control" name="q" value="{{ $q }}" placeholder="جستجو کد/نام/موبایل">
    <button class="btn btn-primary">جستجو</button>
  </form>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th>کد فاکتور</th>
            <th>مشتری</th>
            <th>مبلغ</th>
            <th>وضعیت</th>
            <th>عملیات</th>
          </tr>
        </thead>
        <tbody>
          @forelse($invoices as $inv)
            <tr>
              <td>{{ $inv->uuid }}</td>
              <td>{{ $inv->customer_name }}</td>
              <td>{{ number_format((int) $inv->total) }}</td>
              <td>{{ $inv->status }}</td>
              <td class="d-flex gap-1">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('vouchers.sales.edit', $inv->uuid) }}">ویرایش محصولات</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('invoices.show', $inv->uuid) }}">وضعیت/جزئیات</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center py-4">موردی نیست</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">{{ $invoices->links() }}</div>
</div>
@endsection
