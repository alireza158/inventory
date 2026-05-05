@extends('layouts.app')

@section('content')

<style>
  .archive-page {
    --primary: #4f46e5;
    --primary-soft: #eef2ff;
    --border: #edf2f7;
    --text: #111827;
    --muted: #64748b;
  }

  .archive-hero {
    border-radius: 26px;
    padding: 26px;
    color: #fff;
    background: linear-gradient(135deg, #4f46e5, #7c3aed, #ec4899);
    box-shadow: 0 22px 50px rgba(79, 70, 229, .23);
  }

  .archive-tabs-wrapper {
    border-radius: 24px;
    background: #fff;
    border: 1px solid var(--border);
    box-shadow: 0 16px 40px rgba(15, 23, 42, .06);
    overflow: hidden;
  }

  .archive-tabs-header {
    padding: 16px;
    background: #f8fafc;
    border-bottom: 1px solid var(--border);
  }

  .archive-tabs {
    gap: 10px;
  }

  .archive-tabs .nav-link {
    border: 0;
    border-radius: 16px;
    padding: 12px 18px;
    color: #475569;
    font-weight: 900;
    background: #fff;
    box-shadow: 0 8px 18px rgba(15, 23, 42, .05);
  }

  .archive-tabs .nav-link.active {
    color: #fff;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    box-shadow: 0 12px 28px rgba(79, 70, 229, .25);
  }

  .archive-tabs-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 30px;
    height: 24px;
    padding: 0 8px;
    margin-right: 8px;
    border-radius: 999px;
    background: rgba(255,255,255,.22);
    font-size: .78rem;
  }

  .nav-link:not(.active) .archive-tabs-count {
    background: #eef2ff;
    color: #4f46e5;
  }

  .archive-tabs-body {
    padding: 18px;
    background: #fbfcff;
  }

  .archive-item {
    border: 1px solid var(--border);
    border-radius: 22px;
    padding: 18px;
    margin-bottom: 15px;
    background: #fff;
    transition: .18s ease;
  }

  .archive-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 34px rgba(15, 23, 42, .08);
  }

  .archive-code {
    direction: ltr;
    display: inline-flex;
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    padding: 8px 12px;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px solid #edf2f7;
    color: #334155;
    font-weight: 900;
    text-decoration: none;
  }

  .archive-code:hover {
    color: #4f46e5;
    background: #eef2ff;
  }

  .customer-name {
    margin-top: 8px;
    color: var(--text);
    font-weight: 950;
    font-size: 1.05rem;
  }

  .meta-line {
    color: var(--muted);
    font-size: .84rem;
    font-weight: 700;
    line-height: 2;
  }

  .status-pill {
    display: inline-flex;
    align-items: center;
    padding: 7px 12px;
    border-radius: 999px;
    background: #eef2ff;
    color: #4f46e5;
    font-size: .8rem;
    font-weight: 900;
  }

  .details-box {
    margin-top: 14px;
    border-radius: 18px;
    border: 1px solid #eef2f7;
    overflow: hidden;
    background: #fcfdff;
  }

  .details-box summary {
    cursor: pointer;
    padding: 13px 15px;
    background: #f8fafc;
    color: #334155;
    font-weight: 950;
    list-style: none;
  }

  .details-box summary::-webkit-details-marker {
    display: none;
  }

  .details-content {
    padding: 15px;
  }

  .clean-list {
    display: grid;
    gap: 8px;
    padding: 0;
    margin: 10px 0 0;
    list-style: none;
  }

  .clean-list li {
    padding: 10px 12px;
    border-radius: 14px;
    background: #fff;
    border: 1px solid #eef2f7;
    color: #475569;
    font-size: .84rem;
    font-weight: 700;
  }

  .sub-title {
    margin-top: 14px;
    margin-bottom: 8px;
    color: #111827;
    font-weight: 950;
  }

  .empty-state {
    padding: 45px 16px;
    text-align: center;
    color: #64748b;
    font-weight: 800;
  }

  .pagination {
    gap: 6px;
    flex-wrap: wrap;
  }

  .page-link {
    border: 0;
    border-radius: 12px !important;
    color: #4f46e5;
    font-weight: 900;
    box-shadow: 0 8px 18px rgba(15, 23, 42, .06);
  }

  .page-item.active .page-link {
    background: #4f46e5;
  }

  @media (max-width: 768px) {
    .archive-hero {
      padding: 21px;
      border-radius: 22px;
    }

    .archive-tabs .nav-link {
      width: 100%;
    }

    .archive-tabs {
      width: 100%;
    }

    .archive-tabs .nav-item {
      width: 100%;
    }
  }
</style>

<div class="container py-4 archive-page">

  <div class="archive-hero mb-4">
    <h4 class="mb-2 fw-bold">🗂️ بایگانی کامل پیش‌فاکتور و فاکتور</h4>
    <div class="opacity-75">
      مشاهده آرشیو کامل پیش‌فاکتورها، فاکتورها، پرداخت‌ها، یادداشت‌ها و تاریخچه تغییرات
    </div>
  </div>

  <div class="archive-tabs-wrapper">

    <div class="archive-tabs-header">
      <ul class="nav nav-pills archive-tabs" id="archiveTabs" role="tablist">

        <li class="nav-item" role="presentation">
          <button
            class="nav-link active"
            id="preinvoices-tab"
            data-bs-toggle="tab"
            data-bs-target="#preinvoices-pane"
            type="button"
            role="tab"
            aria-controls="preinvoices-pane"
            aria-selected="true"
          >
            📋 بایگانی پیش‌فاکتور
            <span class="archive-tabs-count">
              {{ method_exists($preinvoices, 'total') ? number_format($preinvoices->total()) : number_format($preinvoices->count()) }}
            </span>
          </button>
        </li>

        <li class="nav-item" role="presentation">
          <button
            class="nav-link"
            id="invoices-tab"
            data-bs-toggle="tab"
            data-bs-target="#invoices-pane"
            type="button"
            role="tab"
            aria-controls="invoices-pane"
            aria-selected="false"
          >
            🧾 بایگانی فاکتور
            <span class="archive-tabs-count">
              {{ method_exists($invoices, 'total') ? number_format($invoices->total()) : number_format($invoices->count()) }}
            </span>
          </button>
        </li>

      </ul>
    </div>

    <div class="tab-content archive-tabs-body" id="archiveTabsContent">

      {{-- Preinvoices Tab --}}
      <div
        class="tab-pane fade show active"
        id="preinvoices-pane"
        role="tabpanel"
        aria-labelledby="preinvoices-tab"
        tabindex="0"
      >

        @forelse($preinvoices as $o)
          <div class="archive-item">

            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
              <div>
                <a href="{{ route('archive.preinvoices.show', $o->uuid) }}" class="archive-code" title="{{ $o->uuid }}">
                  {{ $o->uuid }}
                </a>

                <div class="customer-name">
                  {{ $o->customer_name }}
                </div>
              </div>

              <span class="status-pill">
                {{ $o->status_label }}
              </span>
            </div>

            <div class="meta-line mt-2">
              ثبت‌کننده: {{ $o->creator?->name ?? '---' }}
              |
              بازبین انبار: {{ $o->warehouseReviewer?->name ?? '---' }}
            </div>

            <div class="meta-line">
              تاریخ ثبت: {{ $o->created_at }}
              |
              فریز تا: {{ $o->stock_frozen_until ?? '---' }}
              |
              آزادسازی: {{ $o->stock_released_at ?? '---' }}
            </div>

            <details class="details-box">
              <summary>مشاهده جزئیات پیش‌فاکتور</summary>

              <div class="details-content">

                <div class="sub-title">🧾 اقلام</div>
                <ul class="clean-list">
                  @forelse($o->items as $it)
                    <li>
                      {{ $it->product?->name ?? 'محصول نامشخص' }}
                      /
                      {{ $it->variant?->variant_name ?? 'تنوع نامشخص' }}
                      |
                      تعداد: {{ number_format((int) $it->quantity) }}
                      |
                      قیمت: {{ number_format((int) $it->price) }}
                    </li>
                  @empty
                    <li>آیتمی ثبت نشده است.</li>
                  @endforelse
                </ul>

                <div class="sub-title">🕘 تاریخچه بازبینی‌ها</div>
                <ul class="clean-list">
                  @forelse($o->reviews as $r)
                    <li>
                      {{ $r->created_at }}
                      |
                      {{ $r->user?->name ?? '---' }}
                      |
                      {{ $r->action }}
                      @if($r->reason)
                        |
                        {{ $r->reason }}
                      @endif
                    </li>
                  @empty
                    <li>تاریخچه‌ای ثبت نشده است.</li>
                  @endforelse
                </ul>

              </div>
            </details>

          </div>
        @empty
          <div class="empty-state">
            📭 پیش‌فاکتوری در بایگانی وجود ندارد.
          </div>
        @endforelse

        <div class="mt-3 d-flex justify-content-center justify-content-md-end">
          {{ $preinvoices->appends(['tab' => 'preinvoices'])->links() }}
        </div>

      </div>

      {{-- Invoices Tab --}}
      <div
        class="tab-pane fade"
        id="invoices-pane"
        role="tabpanel"
        aria-labelledby="invoices-tab"
        tabindex="0"
      >

        @forelse($invoices as $inv)
          <div class="archive-item">

            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
              <div>
                <a href="{{ route('archive.invoices.show', $inv->uuid) }}" class="archive-code" title="{{ $inv->uuid }}">
                  {{ $inv->uuid }}
                </a>

                <div class="customer-name">
                  {{ $inv->customer_name }}
                </div>
              </div>

              <div class="text-md-end">
                <span class="status-pill">
                  {{ $inv->status }}
                </span>

                <div class="mt-2 fw-bold">
                  جمع کل:
                  {{ number_format((int) $inv->total) }}
                </div>
              </div>
            </div>

            <details class="details-box">
              <summary>مشاهده جزئیات فاکتور</summary>

              <div class="details-content">

                <div class="sub-title">🧾 آیتم‌ها</div>
                <ul class="clean-list">
                  @forelse($inv->items as $it)
                    <li>
                      {{ $it->product?->name ?? 'محصول نامشخص' }}
                      /
                      {{ $it->variant?->variant_name ?? 'تنوع نامشخص' }}
                      |
                      تعداد: {{ number_format((int) $it->quantity) }}
                      |
                      قیمت: {{ number_format((int) $it->price) }}
                    </li>
                  @empty
                    <li>آیتمی ثبت نشده است.</li>
                  @endforelse
                </ul>

                <div class="sub-title">💳 پرداخت‌ها</div>
                <ul class="clean-list">
                  @forelse($inv->payments as $p)
                    <li>
                      {{ $p->created_at }}
                      |
                      {{ $p->creator?->name ?? '---' }}
                      |
                      {{ $p->method }}
                      |
                      مبلغ: {{ number_format((int) $p->amount) }}
                    </li>
                  @empty
                    <li>پرداختی ثبت نشده است.</li>
                  @endforelse
                </ul>

                <div class="sub-title">📝 یادداشت‌ها</div>
                <ul class="clean-list">
                  @forelse($inv->notes as $n)
                    <li>
                      {{ $n->created_at }}
                      |
                      {{ $n->user?->name ?? '---' }}
                      |
                      {{ $n->body }}
                    </li>
                  @empty
                    <li>یادداشتی ثبت نشده است.</li>
                  @endforelse
                </ul>

                <div class="sub-title">🔁 تاریخچه وضعیت/تغییرات</div>
                <ul class="clean-list">
                  @forelse($inv->histories as $h)
                    <li>
                      {{ $h->done_at ?? $h->created_at }}
                      |
                      {{ $h->actor?->name ?? '---' }}
                      |
                      {{ $h->action_type }}
                      |
                      {{ $h->field_name }}
                      |
                      {{ $h->old_value }}
                      ←
                      {{ $h->new_value }}
                      |
                      {{ $h->description }}
                    </li>
                  @empty
                    <li>تاریخچه‌ای ثبت نشده است.</li>
                  @endforelse
                </ul>

              </div>
            </details>

          </div>
        @empty
          <div class="empty-state">
            📭 فاکتوری در بایگانی وجود ندارد.
          </div>
        @endforelse

        <div class="mt-3 d-flex justify-content-center justify-content-md-end">
          {{ $invoices->appends(['tab' => 'invoices'])->links() }}
        </div>

      </div>

    </div>
  </div>

</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const activeTab = params.get('tab');

    if (activeTab === 'invoices') {
      const invoicesTab = document.querySelector('#invoices-tab');

      if (invoicesTab && window.bootstrap) {
        new bootstrap.Tab(invoicesTab).show();
      }
    }
  });
</script>

@endsection