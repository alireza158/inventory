@extends('layouts.app')

@section('content')

@php
  $toman = fn($a) => number_format((int) $a) . ' تومان';

  $dateFa = function ($date) {
    if (!$date) return '---';

    try {
      return \Morilog\Jalali\Jalalian::fromDateTime($date)->format('Y/m/d H:i');
    } catch (\Throwable $e) {
      return $date;
    }
  };

  $statusClass = fn($s) => match($s) {
    'draft' => 'status-draft',
    'pending' => 'status-warning',
    'pending_warehouse_approval' => 'status-warning',
    'approved' => 'status-success',
    'confirmed' => 'status-success',
    'rejected' => 'status-danger',
    'canceled' => 'status-danger',
    'cancelled' => 'status-danger',
    'expired' => 'status-muted',
    default => 'status-default',
  };

  $itemsCount = $order->items->sum('quantity');
  $itemsTotal = $order->items->sum(fn($it) => (int) $it->quantity * (int) $it->price);
  $logLabels = ['attributes' => 'مقادیر ثبت‌شده', 'changes' => 'مقادیر جدید', 'old' => 'مقادیر قبلی', 'original' => 'مقادیر قبلی'];
@endphp

<style>
  .preinvoice-show-page {
    --primary: #4f46e5;
    --purple: #7c3aed;
    --pink: #ec4899;
    --success: #059669;
    --warning: #ea580c;
    --danger: #dc2626;
    --text: #111827;
    --muted: #64748b;
    --border: #eef2f7;
    --soft: #f8fafc;
  }

  .preinvoice-hero {
    position: relative;
    overflow: hidden;
    border-radius: 30px;
    padding: 28px;
    color: #fff;
    background:
      radial-gradient(circle at top left, rgba(255,255,255,.26), transparent 34%),
      linear-gradient(135deg, #4f46e5 0%, #7c3aed 52%, #ec4899 100%);
    box-shadow: 0 24px 55px rgba(79, 70, 229, .25);
  }

  .preinvoice-hero::after {
    content: "";
    position: absolute;
    left: -90px;
    bottom: -110px;
    width: 280px;
    height: 280px;
    border-radius: 999px;
    background: rgba(255,255,255,.13);
  }

  .hero-content {
    position: relative;
    z-index: 1;
  }

  .hero-icon {
    width: 62px;
    height: 62px;
    display: grid;
    place-items: center;
    border-radius: 22px;
    background: rgba(255,255,255,.17);
    border: 1px solid rgba(255,255,255,.23);
    backdrop-filter: blur(10px);
    font-size: 1.9rem;
  }

  .hero-title {
    font-weight: 950;
    letter-spacing: -.5px;
  }

  .hero-subtitle {
    color: rgba(255,255,255,.82);
    font-size: .92rem;
  }

  .back-btn {
    border: 0;
    border-radius: 16px;
    padding: 10px 18px;
    font-weight: 900;
    color: #3730a3;
    background: #fff;
    box-shadow: 0 14px 30px rgba(15, 23, 42, .15);
  }

  .back-btn:hover {
    color: #312e81;
    transform: translateY(-1px);
  }

  .preinvoice-code {
    direction: ltr;
    display: inline-flex;
    max-width: 270px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    padding: 8px 12px;
    border-radius: 14px;
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.25);
    color: #fff;
    font-size: .86rem;
    font-weight: 900;
  }

  .status-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 13px;
    border-radius: 999px;
    font-size: .82rem;
    font-weight: 950;
  }

  .status-pill::before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: currentColor;
  }

  .status-draft {
    background: #f1f5f9;
    color: #475569;
  }

  .status-warning {
    background: #fff7ed;
    color: #ea580c;
  }

  .status-success {
    background: #ecfdf5;
    color: #059669;
  }

  .status-danger {
    background: #fef2f2;
    color: #dc2626;
  }

  .status-muted {
    background: #f8fafc;
    color: #64748b;
  }

  .status-default {
    background: #eef2ff;
    color: #4f46e5;
  }

  .glass-status {
    background: rgba(255,255,255,.18);
    color: #fff;
    border: 1px solid rgba(255,255,255,.24);
  }

  .summary-card,
  .panel-card {
    border: 1px solid var(--border);
    border-radius: 24px;
    background: #fff;
    box-shadow: 0 16px 40px rgba(15, 23, 42, .06);
  }

  .summary-card {
    padding: 18px;
    height: 100%;
  }

  .summary-label {
    color: var(--muted);
    font-size: .82rem;
    font-weight: 850;
    margin-bottom: 6px;
  }

  .summary-value {
    color: var(--text);
    font-size: 1.12rem;
    font-weight: 950;
  }

  .summary-value.big {
    font-size: 1.35rem;
    color: #0f172a;
  }

  .summary-icon {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border-radius: 17px;
    background: #eef2ff;
    font-size: 1.35rem;
  }

  .panel-card {
    overflow: hidden;
  }

  .panel-header {
    padding: 17px 19px;
    border-bottom: 1px solid var(--border);
    background: linear-gradient(180deg, #fff 0%, #fbfcff 100%);
    font-weight: 950;
    color: var(--text);
  }

  .panel-body {
    padding: 18px;
    background: #fff;
  }

  .info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
  }

  .info-item {
    padding: 13px;
    border-radius: 18px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
  }

  .info-item span {
    display: block;
    color: var(--muted);
    font-size: .78rem;
    font-weight: 850;
    margin-bottom: 5px;
  }

  .info-item strong {
    color: #1e293b;
    font-size: .92rem;
    font-weight: 950;
  }

  .table-modern {
    margin-bottom: 0;
  }

  .table-modern thead th {
    background: #f8fafc;
    color: #64748b;
    border-bottom: 1px solid var(--border);
    padding: 14px;
    font-size: .78rem;
    font-weight: 950;
    white-space: nowrap;
  }

  .table-modern tbody td {
    padding: 15px 14px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    vertical-align: middle;
    white-space: nowrap;
  }

  .table-modern tbody tr:hover {
    background: #fafaff;
  }

  .product-name {
    color: #111827;
    font-weight: 950;
  }

  .variant-pill {
    display: inline-flex;
    padding: 7px 10px;
    border-radius: 999px;
    background: #f1f5f9;
    color: #475569;
    font-size: .8rem;
    font-weight: 850;
  }

  .qty-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 42px;
    height: 32px;
    padding: 0 10px;
    border-radius: 12px;
    background: #eef2ff;
    color: #4f46e5;
    font-weight: 950;
  }

  .money-bold {
    color: #0f172a;
    font-weight: 950;
  }

  .side-list {
    display: grid;
    gap: 10px;
    padding: 0;
    margin: 0;
    list-style: none;
  }

  .side-list li {
    padding: 12px;
    border-radius: 17px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
  }

  .list-date {
    color: var(--muted);
    font-size: .78rem;
    font-weight: 850;
    margin-bottom: 5px;
  }

  .list-body {
    color: #334155;
    font-size: .86rem;
    font-weight: 750;
    line-height: 1.9;
  }

  .audit-section { margin-top: 10px; border: 1px dashed var(--border); border-radius: 12px; padding: 10px 12px; background: #fff; }
  .audit-section-title { font-size: .8rem; font-weight: 900; color: #334155; margin-bottom: 6px; }
  .audit-row { display: flex; gap: 8px; font-size: .8rem; line-height: 1.8; border-top: 1px solid #f1f5f9; padding-top: 4px; margin-top: 4px; }
  .audit-row:first-child { border-top: 0; margin-top: 0; padding-top: 0; }
  .audit-key { min-width: 140px; color: var(--muted); font-weight: 800; word-break: break-word; }
  .audit-value { color: #0f172a; word-break: break-word; white-space: pre-wrap; }

  .empty-box {
    padding: 22px 12px;
    text-align: center;
    border-radius: 18px;
    background: #f8fafc;
    color: #94a3b8;
    font-size: .88rem;
    font-weight: 850;
  }

  .history-box {
    max-height: 520px;
    overflow: auto;
    padding-left: 4px;
  }

  .history-box::-webkit-scrollbar {
    width: 7px;
  }

  .history-box::-webkit-scrollbar-thumb {
    background: #dbe2ef;
    border-radius: 999px;
  }

  @media (max-width: 768px) {
    .preinvoice-hero {
      padding: 22px;
      border-radius: 24px;
    }

    .back-btn {
      width: 100%;
      justify-content: center;
    }

    .info-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="container py-4 preinvoice-show-page">

  <div class="preinvoice-hero mb-4">
    <div class="hero-content">
      <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-4">

        <div class="d-flex gap-3 align-items-start">
          <div class="hero-icon">📄</div>

          <div>
            <h4 class="hero-title mb-2">جزئیات پیش‌فاکتور</h4>

            <div class="hero-subtitle mb-3">
              پرونده کامل پیش‌فاکتور، اطلاعات مشتری، اقلام و لاگ بازبینی کاربران
            </div>

            <div class="preinvoice-code">
              {{ $order->uuid }}
            </div>
          </div>
        </div>

        <div class="d-flex flex-column align-items-stretch align-items-lg-end gap-3">
          <span class="status-pill glass-status">
            {{ $order->status_label }}
          </span>

          <div class="d-flex flex-wrap gap-2 no-print justify-content-lg-end">
            <button type="button" onclick="window.print()" class="btn back-btn">پرینت</button>
            <a href="{{ route('archive.index') }}" class="btn back-btn d-inline-flex align-items-center gap-2">
              <span>بازگشت</span>
              <span>↩</span>
            </a>
          </div>
        </div>

      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">

    <div class="col-md-3 col-6">
      <div class="summary-card">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="summary-label">تعداد اقلام</div>
            <div class="summary-value">{{ number_format((int) $itemsCount) }}</div>
          </div>
          <div class="summary-icon">🛍️</div>
        </div>
      </div>
    </div>

    <div class="col-md-3 col-6">
      <div class="summary-card">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="summary-label">جمع پیش‌فاکتور</div>
            <div class="summary-value big">{{ $toman($itemsTotal) }}</div>
          </div>
          <div class="summary-icon">💵</div>
        </div>
      </div>
    </div>

    <div class="col-md-3 col-6">
      <div class="summary-card">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="summary-label">تعداد لاگ‌ها</div>
            <div class="summary-value">{{ number_format((int) $order->reviews->count()) }}</div>
          </div>
          <div class="summary-icon">🕓</div>
        </div>
      </div>
    </div>

    <div class="col-md-3 col-6">
      <div class="summary-card">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="summary-label">وضعیت</div>
            <div class="summary-value">
              <span class="status-pill {{ $statusClass($order->status ?? '') }}">
                {{ $order->status_label }}
              </span>
            </div>
          </div>
          <div class="summary-icon">✅</div>
        </div>
      </div>
    </div>

  </div>

  <div class="row g-4">

    <div class="col-lg-7">

      <div class="panel-card mb-4">
        <div class="panel-header">
          👤 اطلاعات مشتری و پیش‌فاکتور
        </div>

        <div class="panel-body">
          <div class="info-grid">

            <div class="info-item">
              <span>نام مشتری</span>
              <strong>{{ $order->customer_name ?? '---' }}</strong>
            </div>

            <div class="info-item">
              <span>موبایل مشتری</span>
              <strong>{{ $order->customer_mobile ?? '---' }}</strong>
            </div>

            <div class="info-item">
              <span>ثبت‌کننده</span>
              <strong>{{ $order->creator?->name ?? '---' }}</strong>
            </div>

            <div class="info-item">
              <span>بازبین انبار</span>
              <strong>{{ $order->warehouseReviewer?->name ?? '---' }}</strong>
            </div>

            <div class="info-item">
              <span>تاریخ ثبت</span>
              <strong>{{ $dateFa($order->created_at ?? null) }}</strong>
            </div>

            <div class="info-item">
              <span>فریز موجودی تا</span>
              <strong>{{ $dateFa($order->stock_frozen_until ?? null) }}</strong>
            </div>

            <div class="info-item">
              <span>زمان آزادسازی موجودی</span>
              <strong>{{ $dateFa($order->stock_released_at ?? null) }}</strong>
            </div>

            <div class="info-item">
              <span>وضعیت فعلی</span>
              <strong>
                <span class="status-pill {{ $statusClass($order->status ?? '') }}">
                  {{ $order->status_label }}
                </span>
              </strong>
            </div>

          </div>
        </div>
      </div>

      <div class="panel-card">
        <div class="panel-header">
          🛍️ اقلام پیش‌فاکتور
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle table-modern">
            <thead>
              <tr>
                <th>کالا</th>
                <th>تنوع</th>
                <th>تعداد</th>
                <th>قیمت واحد</th>
                <th>جمع</th>
              </tr>
            </thead>

            <tbody>
              @forelse($order->items as $it)
                <tr>
                  <td>
                    <span class="product-name">
                      {{ $it->product?->name ?? 'محصول نامشخص' }}
                    </span>
                  </td>

                  <td>
                    <span class="variant-pill">
                      {{ $it->variant?->variant_name ?? '---' }}
                    </span>
                  </td>

                  <td>
                    <span class="qty-badge">
                      {{ number_format((int) $it->quantity) }}
                    </span>
                  </td>

                  <td>
                    {{ $toman($it->price) }}
                  </td>

                  <td>
                    <span class="money-bold">
                      {{ $toman((int) $it->quantity * (int) $it->price) }}
                    </span>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5">
                    <div class="empty-box">
                      آیتمی برای این پیش‌فاکتور ثبت نشده است.
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <div class="col-lg-5">

      <div class="panel-card">
        <div class="panel-header">
          🕓 لاگ کامل تغییرات کاربران
        </div>

        <div class="panel-body history-box">
          @if($order->activityLogs->isNotEmpty())
            <ul class="side-list">
              @foreach($order->activityLogs as $log)
                <li>
                  <div class="list-date">
                    {{ $dateFa($log->occurred_at ?? $log->created_at) }}
                    |
                    {{ $log->user?->name ?? 'سیستم' }}
                    |
                    {{ $log->action ?? '---' }}
                  </div>

                  <div class="list-body">
                    <strong>{{ $log->description ?? '---' }}</strong>

                    @php($properties = is_array($log->properties) ? $log->properties : [])
                    @foreach($properties as $groupKey => $groupValue)
                      <div class="audit-section">
                        <div class="audit-section-title">{{ $logLabels[$groupKey] ?? $groupKey }}</div>
                        @if(is_array($groupValue))
                          @foreach($groupValue as $field => $fieldValue)
                            <div class="audit-row">
                              <div class="audit-key">{{ $field }}</div>
                              <div class="audit-value">{{ is_array($fieldValue) ? json_encode($fieldValue, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : ($fieldValue === null ? '—' : $fieldValue) }}</div>
                            </div>
                          @endforeach
                        @else
                          <div class="audit-row">
                            <div class="audit-key">{{ $groupKey }}</div>
                            <div class="audit-value">{{ $groupValue === null ? '—' : $groupValue }}</div>
                          </div>
                        @endif
                      </div>
                    @endforeach
                  </div>
                </li>
              @endforeach
            </ul>
          @endif

          @if($order->reviews->isNotEmpty())
            <div class="mt-3 fw-bold">تاریخچه بازبینی انبار/مالی</div>
            <ul class="side-list mt-2">
              @foreach($order->reviews as $r)
                <li>
                  <div class="list-date">
                    {{ $dateFa($r->created_at) }}
                    |
                    {{ $r->user?->name ?? '---' }}
                  </div>
                  <div class="list-body">
                    عملیات: <strong>{{ $r->action ?? '---' }}</strong>
                    @if($r->reason)
                      <br>دلیل: {{ $r->reason }}
                    @endif
                  </div>
                </li>
              @endforeach
            </ul>
          @endif

          @if($order->activityLogs->isEmpty() && $order->reviews->isEmpty())
            <div class="empty-box">لاگی ثبت نشده است.</div>
          @endif
        </div>
      </div>

    </div>

  </div>
</div>

@if(request()->boolean('print'))
  <script>window.print();</script>
@endif

@endsection
