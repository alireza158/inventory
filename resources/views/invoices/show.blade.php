@extends('layouts.app')

@section('content')
<div class="container">

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <div class="h5 fw-bold mb-0">🧾 فاکتور</div>
      <div class="text-muted small">{{ $invoice->uuid }}</div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('invoices.index') }}">بازگشت</a>
  </div>

  <div class="row g-3">

    <div class="col-lg-7">
      <div class="card mb-3">
        <div class="card-header fw-bold">اطلاعات مشتری</div>
        <div class="card-body">
          <div>👤 {{ $invoice->customer_name }}</div>
          <div>📞 {{ $invoice->customer_mobile }}</div>
          <div class="text-muted mt-2">{{ $invoice->customer_address }}</div>
        </div>
      </div>

      <div class="card">
        <div class="card-header fw-bold">آیتم‌ها</div>
        <div class="table-responsive">
          <table class="table mb-0">
            <thead class="table-light">
            <tr>
              <th>محصول</th><th>مدل</th><th>تعداد</th><th>قیمت</th><th>جمع</th>
            </tr>
            </thead>
            <tbody>
            @foreach($invoice->items as $it)
              <tr>
                <td>#{{ $it->product_id }}</td>
                <td>{{ $it->variant_id ?: 'بدون مدل' }}</td>
                <td>{{ $it->quantity }}</td>
                <td>{{ number_format($it->price) }}</td>
                <td class="fw-bold">{{ number_format($it->line_total) }}</td>
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
        <div class="card-body border-top">
          <div>جمع: <b>{{ number_format($invoice->subtotal) }}</b></div>
          <div>ارسال: <b>{{ number_format($invoice->shipping_price) }}</b></div>
          <div>تخفیف: <b>{{ number_format($invoice->discount_amount) }}</b></div>
          <div class="mt-2 fs-5">کل: <b>{{ number_format($invoice->total) }}</b></div>
        </div>
      </div>
    </div>

    <div class="col-lg-5">

      <div class="card mb-3">
        <div class="card-header fw-bold">وضعیت سفارش</div>
        <div class="card-body">
          <form method="POST" action="{{ route('invoices.status', $invoice->uuid) }}" class="d-flex gap-2">
            @csrf
            <select name="status" class="form-select">
              <option value="processing" @selected($invoice->status==='processing')>درحال پردازش</option>
              <option value="shipped" @selected($invoice->status==='shipped')>ارسال شده</option>
              <option value="delivered" @selected($invoice->status==='delivered')>تحویل شده</option>
              <option value="canceled" @selected($invoice->status==='canceled')>کنسل شده</option>
            </select>
            <button class="btn btn-primary">ثبت</button>
          </form>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header fw-bold">پرداخت‌ها</div>
        <div class="card-body">
          <div class="mb-2">پرداخت شده: <b>{{ number_format($invoice->paid_amount) }}</b></div>
          <div class="mb-3">مانده: <b class="text-danger">{{ number_format($invoice->remaining_amount) }}</b></div>

          <form method="POST" action="{{ route('invoices.payments.store', $invoice->uuid) }}" enctype="multipart/form-data" class="border rounded p-2">
            @csrf
            <div class="row g-2">
              <div class="col-4">
                <select name="method" class="form-select" required>
                  <option value="cash">نقدی</option>
                  <option value="card">کارت</option>
                  <option value="cheque">چک</option>
                </select>
              </div>
              <div class="col-8">
                <input name="amount" type="number" class="form-control" placeholder="مبلغ" required>
              </div>
              <div class="col-12">
                <input name="paid_at" type="date" class="form-control">
              </div>
              <div class="col-12">
                <input name="receipt_image" type="file" class="form-control">
              </div>
              <div class="col-12">
                <textarea name="note" class="form-control" rows="2" placeholder="یادداشت پرداخت..."></textarea>
              </div>
              <div class="col-12">
                <button class="btn btn-success w-100">ثبت پرداخت</button>
              </div>
            </div>
          </form>

          <hr>

          @foreach($invoice->payments as $p)
            <div class="border rounded p-2 mb-2">
              <div class="d-flex justify-content-between">
                <div>
                  <b>{{ $p->method }}</b> — {{ number_format($p->amount) }}
                </div>
                <div class="text-muted small">{{ $p->paid_at }}</div>
              </div>

              @if($p->receipt_image)
                <div class="mt-1">
                  <a target="_blank" href="{{ asset('storage/'.$p->receipt_image) }}">📎 رسید</a>
                </div>
              @endif

              @if($p->method === 'cheque')
                <div class="mt-2">
                  @if($p->cheque)
                    <div class="small text-muted">چک: {{ $p->cheque->cheque_number }} | سررسید: {{ $p->cheque->due_date }} | وضعیت: {{ $p->cheque->status }}</div>
                    @if($p->cheque->image)
                      <a target="_blank" href="{{ asset('storage/'.$p->cheque->image) }}">📷 عکس چک</a>
                    @endif
                  @else
                    <form method="POST" action="{{ route('cheques.store', $p->id) }}" enctype="multipart/form-data" class="mt-2">
                      @csrf
                      <input class="form-control mb-2" name="bank_name" placeholder="بانک">
                      <input class="form-control mb-2" name="cheque_number" placeholder="شماره چک">
                      <input class="form-control mb-2" name="due_date" type="date">
                      <input class="form-control mb-2" name="image" type="file">
                      <button class="btn btn-outline-primary btn-sm">ثبت اطلاعات چک</button>
                    </form>
                  @endif
                </div>
              @endif

              @if($p->note)
                <div class="small mt-1">{{ $p->note }}</div>
              @endif
            </div>
          @endforeach

        </div>
      </div>

      <div class="card">
        <div class="card-header fw-bold">یادداشت‌ها</div>
        <div class="card-body">
          <form method="POST" action="{{ route('invoices.notes.store', $invoice->uuid) }}" class="mb-2">
            @csrf
            <textarea name="body" class="form-control" rows="2" placeholder="یادداشت جدید..." required></textarea>
            <button class="btn btn-primary w-100 mt-2">ثبت یادداشت</button>
          </form>

          @foreach($invoice->notes as $n)
            <div class="border rounded p-2 mb-2">
              <div class="small text-muted">{{ $n->created_at }}</div>
              <div>{{ $n->body }}</div>
            </div>
          @endforeach
        </div>
      </div>

    </div>
  </div>
</div>
@endsection
