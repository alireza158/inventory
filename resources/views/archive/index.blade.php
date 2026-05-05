@extends('layouts.app')
@section('content')
<div class="container py-4">
  <h4 class="mb-3">🗂️ بایگانی کامل پیش‌فاکتور و فاکتور</h4>

  <div class="row g-3">
    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold">بایگانی پیش‌فاکتور (کامل)</div>
        <div class="card-body">
          @foreach($preinvoices as $o)
            <div class="border rounded p-3 mb-3">
              <div class="fw-bold">{{ $o->uuid }} | {{ $o->customer_name }}</div>
              <div class="small text-muted">وضعیت: {{ $o->status_label }} | ثبت‌کننده: {{ $o->creator?->name ?? '---' }} | بازبین انبار: {{ $o->warehouseReviewer?->name ?? '---' }}</div>
              <div class="small text-muted">تاریخ ثبت: {{ $o->created_at }} | فریز تا: {{ $o->stock_frozen_until ?? '---' }} | آزادسازی: {{ $o->stock_released_at ?? '---' }}</div>
              <ul class="mt-2 mb-2 small">
                @foreach($o->items as $it)
                  <li>{{ $it->product?->name }} / {{ $it->variant?->variant_name }} | تعداد: {{ $it->quantity }} | قیمت: {{ number_format((int)$it->price) }}</li>
                @endforeach
              </ul>
              <div class="small">
                <b>تاریخچه بازبینی‌ها:</b>
                <ul class="mb-0">
                  @foreach($o->reviews as $r)
                    <li>{{ $r->created_at }} | {{ $r->user?->name ?? '---' }} | {{ $r->action }} | {{ $r->reason }}</li>
                  @endforeach
                </ul>
              </div>
            </div>
          @endforeach
          {{ $preinvoices->links() }}
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold">بایگانی فاکتور (کامل)</div>
        <div class="card-body">
          @foreach($invoices as $inv)
            <div class="border rounded p-3 mb-3">
              <div class="fw-bold">{{ $inv->uuid }} | {{ $inv->customer_name }}</div>
              <div class="small text-muted">وضعیت فعلی: {{ $inv->status }} | جمع کل: {{ number_format((int)$inv->total) }}</div>

              <div class="mt-2"><b>آیتم‌ها:</b>
                <ul class="small mb-2">
                  @foreach($inv->items as $it)
                    <li>{{ $it->product?->name }} / {{ $it->variant?->variant_name }} | تعداد: {{ $it->quantity }} | قیمت: {{ number_format((int)$it->price) }}</li>
                  @endforeach
                </ul>
              </div>

              <div><b>پرداخت‌ها:</b>
                <ul class="small mb-2">
                  @foreach($inv->payments as $p)
                    <li>{{ $p->created_at }} | {{ $p->creator?->name ?? '---' }} | {{ $p->method }} | مبلغ: {{ number_format((int)$p->amount) }}</li>
                  @endforeach
                </ul>
              </div>

              <div><b>یادداشت‌ها:</b>
                <ul class="small mb-2">
                  @foreach($inv->notes as $n)
                    <li>{{ $n->created_at }} | {{ $n->creator?->name ?? '---' }} | {{ $n->note }}</li>
                  @endforeach
                </ul>
              </div>

              <div><b>تاریخچه وضعیت/تغییرات:</b>
                <ul class="small mb-0">
                  @foreach($inv->histories as $h)
                    <li>{{ $h->done_at ?? $h->created_at }} | {{ $h->actor?->name ?? '---' }} | {{ $h->action_type }} | {{ $h->field_name }} | {{ $h->old_value }} => {{ $h->new_value }} | {{ $h->description }}</li>
                  @endforeach
                </ul>
              </div>
            </div>
          @endforeach
          {{ $invoices->links() }}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
