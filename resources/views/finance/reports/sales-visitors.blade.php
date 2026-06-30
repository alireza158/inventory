@extends('layouts.app')

@section('title', 'گزارش فروش ویزیتورها')
@section('content_class', 'app-content-wide')

@section('content')
@php
  $rial = fn($amount) => \App\Support\Currency::formatRial((int) $amount);
  $jalali = fn($date) => \App\Support\JalaliDate::date($date);
  $gregorianToJalali = function ($value) {
      if (!$value) return '';
      try { return \Morilog\Jalali\Jalalian::fromDateTime($value)->format('Y/m/d'); } catch (\Throwable) { return $value; }
  };
  $fromJalali = $gregorianToJalali($filters['from_date'] ?? '');
  $toJalali = $gregorianToJalali($filters['to_date'] ?? '');
@endphp

<style>
  .finance-report-page{max-width:100%; overflow-x:hidden;}
  .report-head,.report-card{border:0; border-radius:18px; box-shadow:0 10px 28px rgba(15,23,42,.06);}
  .report-head{background:linear-gradient(135deg,#fff,#f8fafc); padding:18px;}
  .report-filter-card .form-label{font-size:.8rem; color:#64748b; font-weight:800;}
  .summary-card{border:1px solid #eef2f7; border-radius:16px; padding:14px; background:#fff; height:100%;}
  .summary-card .label{font-size:.78rem; color:#64748b; font-weight:800;}
  .summary-card .value{font-size:1rem; font-weight:950; margin-top:6px;}
  .money-cell{white-space:nowrap; font-variant-numeric:tabular-nums; text-align:left; direction:ltr;}
  .code-cell{direction:ltr; unicode-bidi:plaintext; display:inline-block;}
  @media print{.no-report-print,.pagination{display:none!important}.report-card,.report-head{box-shadow:none!important;border:1px solid #ddd!important}body{background:#fff!important}}
</style>

<div class="finance-report-page">
  <div class="report-head mb-3 d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
      <div class="h4 fw-black mb-1">گزارش فروش ویزیتورها</div>
      <div class="text-muted small">مبنای گزارش: جدول فاکتورها، تاریخ سند، مبلغ نهایی و تخفیف ثبت‌شده روی فاکتور</div>
    </div>
    <div class="d-flex gap-2 flex-wrap no-report-print">
      <a class="btn btn-outline-success" href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}">خروجی Excel</a>
      <a class="btn btn-outline-success" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">خروجی CSV</a>
      <button type="button" class="btn btn-outline-dark" onclick="window.print()">چاپ گزارش</button>
    </div>
  </div>

  @if(!empty($filterErrors))
    <div class="alert alert-danger no-report-print">@foreach($filterErrors as $error)<div>{{ $error }}</div>@endforeach</div>
  @endif

  <div class="card report-card report-filter-card mb-3 no-report-print">
    <div class="card-body">
      <form id="salesVisitorsFilterForm" class="row g-3 align-items-end" method="GET" action="{{ route('finance.reports.sales-visitors') }}">
        <input type="hidden" name="from_date" id="from_date" value="{{ $filters['from_date'] ?? '' }}">
        <input type="hidden" name="to_date" id="to_date" value="{{ $filters['to_date'] ?? '' }}">
        <div class="col-sm-6 col-xl-2"><label class="form-label">از تاریخ شمسی</label><input type="text" class="form-control" id="from_date_jalali" value="{{ $fromJalali }}" dir="ltr" data-jdp data-jdp-only-date placeholder="1403/03/01"></div>
        <div class="col-sm-6 col-xl-2"><label class="form-label">تا تاریخ شمسی</label><input type="text" class="form-control" id="to_date_jalali" value="{{ $toJalali }}" dir="ltr" data-jdp data-jdp-only-date placeholder="1403/03/31"></div>
        <div class="col-sm-6 col-xl-3"><label class="form-label">ویزیتور/ثبت‌کننده</label><select class="form-select" name="visitor_id"><option value="">همه ویزیتورها</option>@foreach($visitors as $visitor)<option value="{{ $visitor->id }}" @selected(($filters['visitor_id'] ?? '') == $visitor->id)>{{ $visitor->name }}</option>@endforeach</select></div>
        <div class="col-sm-6 col-xl-3"><label class="form-label">وضعیت فاکتور</label><select class="form-select" name="status">@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? 'valid') === (string) $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-sm-6 col-xl-2 d-flex gap-2"><button class="btn btn-primary flex-fill">اعمال فیلتر</button><a href="{{ route('finance.reports.sales-visitors') }}" class="btn btn-outline-secondary">حذف</a></div>
      </form>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><div class="summary-card"><div class="label">تعداد فاکتورها</div><div class="value">{{ number_format($summaryTotals['invoice_count']) }}</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="summary-card"><div class="label">جمع قبل از تخفیف</div><div class="value money-cell">{{ $rial($summaryTotals['subtotal']) }}</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="summary-card"><div class="label">جمع تخفیف</div><div class="value money-cell">{{ $rial($summaryTotals['discount_amount']) }}</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="summary-card"><div class="label">جمع مبلغ نهایی</div><div class="value money-cell">{{ $rial($summaryTotals['total']) }}</div></div></div>
  </div>

  <div class="card report-card mb-3">
    <div class="card-header bg-white fw-bold">خلاصه فروش به تفکیک ویزیتور</div>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>نام ویزیتور</th><th>تعداد فاکتورها</th><th class="text-end">قبل از تخفیف</th><th class="text-end">تخفیف</th><th class="text-end">مبلغ نهایی</th></tr></thead><tbody>@forelse($summary as $row)<tr><td>{{ $row['visitor_name'] }}</td><td>{{ number_format($row['invoice_count']) }}</td><td class="money-cell">{{ $rial($row['subtotal']) }}</td><td class="money-cell">{{ $rial($row['discount_amount']) }}</td><td class="money-cell fw-bold">{{ $rial($row['total']) }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">داده‌ای برای نمایش وجود ندارد.</td></tr>@endforelse</tbody></table></div>
  </div>

  <div class="card report-card">
    <div class="card-header bg-white fw-bold">جزئیات فاکتورها</div>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>شماره فاکتور</th><th>تاریخ</th><th>نام مشتری</th><th>موبایل</th><th>ویزیتور/ثبت‌کننده</th><th class="text-end">مبلغ فاکتور</th><th class="text-end">تخفیف</th><th class="text-end">مبلغ نهایی</th><th>وضعیت</th></tr></thead><tbody>@forelse($details as $invoice)<tr><td><a class="code-cell" href="{{ route('invoices.show', $invoice->uuid) }}">{{ $invoice->uuid }}</a></td><td>{{ $jalali($invoice->display_document_date) }}</td><td>{{ $invoice->customer_name ?: '—' }}</td><td>{{ $invoice->customer_mobile ?: '—' }}</td><td>{{ $invoice->preinvoiceOrder?->creator?->name ?: 'نامشخص' }}</td><td class="money-cell">{{ $rial($invoice->subtotal) }}</td><td class="money-cell">{{ $rial($invoice->discount_amount) }}</td><td class="money-cell fw-bold">{{ $rial($invoice->total) }}</td><td>{{ $statusLabels[$invoice->status] ?? ($invoice->status ?: '—') }}</td></tr>@empty<tr><td colspan="9" class="text-center text-muted py-4">فاکتوری یافت نشد.</td></tr>@endforelse</tbody></table></div>
    <div class="card-footer bg-white no-report-print">{{ $details->links() }}</div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  function div(a,b){return ~~(a/b);} function pad(v){return String(v).padStart(2,'0');}
  function jalaliToGregorian(jy,jm,jd){jy+=1595;let days=-355668+(365*jy)+div(jy,33)*8+div((jy%33)+3,4)+jd+((jm<7)?(jm-1)*31:((jm-7)*30)+186);let gy=400*div(days,146097);days%=146097;if(days>36524){gy+=100*div(--days,36524);days%=36524;if(days>=365)days++;}gy+=4*div(days,1461);days%=1461;if(days>365){gy+=div(days-1,365);days=(days-1)%365;}let gd=days+1;const sal=[0,31,((gy%4===0&&gy%100!==0)||(gy%400===0))?29:28,31,30,31,30,31,31,30,31,30,31];let gm=1;for(;gm<=12&&gd>sal[gm];gm++)gd-=sal[gm];return [gy,gm,gd];}
  function normalizeDigits(value){const fa='۰۱۲۳۴۵۶۷۸۹', ar='٠١٢٣٤٥٦٧٨٩';return (value||'').replace(/[۰-۹]/g,d=>fa.indexOf(d)).replace(/[٠-٩]/g,d=>ar.indexOf(d));}
  function toGregorian(value){const m=normalizeDigits(value).trim().match(/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})$/); if(!m) return ''; const g=jalaliToGregorian(Number(m[1]),Number(m[2]),Number(m[3])); return `${g[0]}-${pad(g[1])}-${pad(g[2])}`;}
  document.getElementById('salesVisitorsFilterForm')?.addEventListener('submit', function(){
    document.getElementById('from_date').value = toGregorian(document.getElementById('from_date_jalali').value);
    document.getElementById('to_date').value = toGregorian(document.getElementById('to_date_jalali').value);
  });
});
</script>
@endpush
