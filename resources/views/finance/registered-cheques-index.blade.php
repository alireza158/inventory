@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h4 class="mb-3">چک‌های ثبت‌شده</h4>
  <form method="GET" class="card card-body mb-3">
    <div class="row g-2">
      <div class="col-md-3"><input class="form-control" name="customer_name" value="{{ request('customer_name') }}" placeholder="نام مشتری"></div>
      <div class="col-md-2"><input class="form-control" name="cheque_number" value="{{ request('cheque_number') }}" placeholder="شماره چک"></div>
      <div class="col-md-2"><input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}"></div>
      <div class="col-md-2"><input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}"></div>
      <div class="col-md-2">
        <select class="form-select" name="status">
          <option value="">همه وضعیت‌ها</option>
          <option value="registered" @selected(request('status')==='registered')>ثبت‌شده</option>
          <option value="cleared" @selected(request('status')==='cleared')>وصول‌شده</option>
        </select>
      </div>
      <div class="col-md-1"><button class="btn btn-primary w-100">جستجو</button></div>
    </div>
  </form>
  <div class="card">
    <div class="table-responsive">
      <table class="table mb-0">
        <thead><tr><th>تاریخ</th><th>مشتری</th><th>شماره چک</th><th>مبلغ</th><th>وضعیت صیادی</th><th>فاکتور</th></tr></thead>
        <tbody>
        @forelse($cheques as $cheque)
          <tr>
            <td>{{ $cheque->received_at ?: '—' }}</td>
            <td>{{ $cheque->customer_name ?: '—' }}</td>
            <td>{{ $cheque->cheque_number ?: '—' }}</td>
            <td>{{ number_format((int)$cheque->amount) }} تومان</td>
            <td>{{ $cheque->status === 'registered' ? 'ثبت‌شده' : 'وصول‌شده' }}</td>
            <td>{{ $cheque->payment?->invoice?->uuid ?: '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center text-muted">چکی یافت نشد.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="mt-3">{{ $cheques->links() }}</div>
</div>
@endsection
