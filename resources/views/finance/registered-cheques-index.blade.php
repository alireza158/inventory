@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h4 class="mb-3">چک‌های ثبت‌شده</h4>
  <form method="GET" class="card card-body mb-3">
    <div class="row g-2">
      <div class="col-md-3"><input class="form-control" name="customer_name" value="{{ request('customer_name') }}" placeholder="نام مشتری"></div>
      <div class="col-md-2"><input class="form-control" name="cheque_number" value="{{ request('cheque_number') }}" placeholder="شماره چک"></div>
      <div class="col-md-2"><input type="date" class="form-control" name="received_from" value="{{ request('received_from') }}" title="از تاریخ وصول"></div>
      <div class="col-md-2"><input type="date" class="form-control" name="received_to" value="{{ request('received_to') }}" title="تا تاریخ وصول"></div>
      <div class="col-md-2"><input type="date" class="form-control" name="due_from" value="{{ request('due_from') }}" title="از تاریخ سررسید"></div>
      <div class="col-md-2"><input type="date" class="form-control" name="due_to" value="{{ request('due_to') }}" title="تا تاریخ سررسید"></div>
      <div class="col-md-2">
        <select class="form-select" name="status">
          <option value="">همه وضعیت‌ها</option>
          <option value="registered" @selected(request('status')==='registered')>ثبت‌شده</option>
          <option value="cleared" @selected(request('status')==='cleared')>وصول‌شده</option>
        </select>
      </div>
      <div class="col-md-2"><button class="btn btn-primary w-100">جستجو</button></div>
    </div>
  </form>
  <div class="card">
    <div class="table-responsive">
      <table class="table mb-0">
        <thead><tr><th>تاریخ وصول</th><th>تاریخ سررسید</th><th>مشتری</th><th>شماره چک</th><th>مبلغ</th><th>وضعیت صیادی</th><th>فاکتور</th></tr></thead>
        <tbody>
        @forelse($cheques as $cheque)
          <tr>
            <td>{{ $cheque->received_at ?: '—' }}</td>
            <td>{{ $cheque->due_date ?: '—' }}</td>
            <td>{{ $cheque->customer_name ?: '—' }}</td>
            <td>{{ $cheque->cheque_number ?: '—' }}</td>
            <td>{{ number_format((int)$cheque->amount) }} تومان</td>
            <td>{{ $cheque->status === 'registered' ? 'ثبت‌شده' : 'وصول‌شده' }}</td>
            <td>{{ $cheque->payment?->invoice?->uuid ?: '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center text-muted">چکی یافت نشد.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="mt-3">{{ $cheques->links() }}</div>
</div>
@endsection
