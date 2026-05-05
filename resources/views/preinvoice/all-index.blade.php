@extends('layouts.app')

@php use Morilog\Jalali\Jalalian; @endphp

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">📋 همه پیش‌فاکتورها</h4>
    <a href="{{ route('preinvoice.create') }}" class="btn btn-primary">➕ ایجاد پیش‌فاکتور</a>
  </div>

  <form method="GET" class="card card-body shadow-sm border-0 mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label">فیلتر وضعیت</label>
        <select name="status" class="form-select" onchange="this.form.submit()">
          <option value="">همه وضعیت‌ها</option>
          @foreach($statusLabels as $key => $label)
            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
    </div>
  </form>

  <div class="card shadow-sm border-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr class="table-light">
            <th>#</th><th>کد</th><th>وضعیت</th><th>مشتری</th><th>ثبت‌کننده</th><th>اقلام</th><th>جمع</th><th>انقضای فریز</th><th>تاریخ ثبت</th>
          </tr>
        </thead>
        <tbody>
          @forelse($orders as $o)
            <tr>
              <td>{{ $o->id }}</td><td>{{ $o->uuid }}</td><td>{{ $o->status_label }}</td><td>{{ $o->customer_name }}</td>
              <td>{{ $o->creator?->name ?? '—' }}</td><td>{{ $o->items_count }}</td><td>{{ number_format((int)$o->total_price) }}</td>
              <td>{{ $o->stock_frozen_until ? Jalalian::fromDateTime($o->stock_frozen_until)->format('Y/m/d H:i') : '—' }}</td>
              <td>{{ $o->created_at ? Jalalian::fromDateTime($o->created_at)->format('Y/m/d H:i') : '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center py-4">موردی یافت نشد.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="mt-3">{{ $orders->links() }}</div>
</div>
@endsection
