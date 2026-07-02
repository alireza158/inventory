@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h4 class="mb-1">🚚 در انتظار ارسال بار</h4>
      <div class="text-muted small">فاکتورهای جمع‌آوری‌شده که هنوز ارسال نشده‌اند.</div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('vouchers.sales.queue') }}">صف جمع‌آوری</a>
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>شماره فاکتور</th><th>مشتری</th><th>موبایل</th><th>تعداد</th><th>مبلغ</th><th>تکمیل جمع‌آوری</th><th>فروشنده</th><th class="text-end">ارسال</th></tr></thead>
        <tbody>
          @forelse($invoices as $invoice)
            <tr>
              <td>{{ $invoice->uuid }}</td>
              <td>{{ $invoice->customer_name }}</td>
              <td>{{ $invoice->customer_mobile }}</td>
              <td>{{ (int) $invoice->items->sum('quantity') }}</td>
              <td>{{ number_format((int) $invoice->total) }}</td>
              <td>{{ $invoice->collection_completed_at ? \App\Support\JalaliDate::dateTime($invoice->collection_completed_at) : '—' }}</td>
              <td>{{ $invoice->preinvoiceOrder?->creator?->name ?? '—' }}</td>
              <td class="text-end">
                <form method="POST" action="{{ route('warehouse.shipping.mark-shipped', $invoice->uuid) }}" class="d-flex gap-2 justify-content-end">
                  @csrf
                  <input name="shipping_note" class="form-control form-control-sm" style="max-width:260px" placeholder="توضیح اختیاری ارسال">
                  <button class="btn btn-sm btn-success">ارسال شد</button>
                  <a class="btn btn-sm btn-outline-secondary" href="{{ route('invoices.show', $invoice->uuid) }}">مشاهده</a>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted py-4">موردی در انتظار ارسال نیست.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="mt-3">{{ $invoices->links() }}</div>
</div>
@endsection
