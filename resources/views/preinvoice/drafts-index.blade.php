@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">🧾 پیش‌نویس‌های پیش‌فاکتور</h4>
    <a href="{{ route('preinvoice.create') }}" class="btn btn-primary">➕ ایجاد پیش‌فاکتور</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>کد</th>
            <th>مشتری</th>
            <th>موبایل</th>
            <th>جمع کل</th>
            <th>تاریخ</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($orders as $o)
            <tr>
              <td>{{ $o->id }}</td>
              <td>{{ $o->uuid }}</td>
              <td>{{ $o->customer_name }}</td>
              <td>{{ $o->customer_mobile }}</td>
              <td>{{ number_format((int)$o->total_price) }}</td>
              <td>{{ $o->created_at?->format('Y-m-d H:i') }}</td>
              <td>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('preinvoice.draft.edit', $o->uuid) }}">ویرایش</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center py-4">موردی نیست</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $orders->links() }}
  </div>
</div>
@endsection
