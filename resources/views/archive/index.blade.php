@extends('layouts.app')
@section('content')
<div class="container py-4">
  <h4 class="mb-3">🗂️ بایگانی پیش‌فاکتور و فاکتور</h4>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card shadow-sm border-0"><div class="card-header bg-white fw-bold">پیش‌فاکتور‌ها</div><div class="card-body">
        @foreach($preinvoices as $o)
          <div class="border rounded p-2 mb-2">
            <div><b>{{ $o->uuid }}</b> | {{ $o->customer_name }} | وضعیت: {{ $o->status_label }}</div>
            <ul class="mb-0 small mt-1">
              @foreach($o->items as $it)
                <li>{{ $it->product?->name }} - {{ $it->variant?->variant_name }} | تعداد: {{ $it->quantity }}</li>
              @endforeach
            </ul>
          </div>
        @endforeach
        {{ $preinvoices->links() }}
      </div></div>
    </div>
    <div class="col-lg-6">
      <div class="card shadow-sm border-0"><div class="card-header bg-white fw-bold">فاکتور‌ها</div><div class="card-body">
        @foreach($invoices as $inv)
          <div class="border rounded p-2 mb-2">
            <div><b>{{ $inv->uuid }}</b> | {{ $inv->customer_name }} | وضعیت: {{ $inv->status }}</div>
            <ul class="mb-0 small mt-1">
              @foreach($inv->items as $it)
                <li>{{ $it->product?->name }} - {{ $it->variant?->variant_name }} | تعداد: {{ $it->quantity }}</li>
              @endforeach
            </ul>
          </div>
        @endforeach
        {{ $invoices->links() }}
      </div></div>
    </div>
  </div>
</div>
@endsection
