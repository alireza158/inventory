@extends('layouts.app')

@php
    use Morilog\Jalali\Jalalian;

    $statusLabels = [
        'pending_warehouse_approval' => 'در انتظار تایید انبار',
        'collecting' => 'در حال جمع‌آوری',
        'checking_discrepancy' => 'بررسی مغایرت',
        'final_check' => 'بازبینی نهایی',
        'packing' => 'بسته‌بندی',
        'shipped' => 'ارسال‌شده',
        'not_shipped' => 'ارسال‌نشده',
    ];
@endphp

@section('content')
<style>
    .dash-surface { background: #ffffff; border: 1px solid #eef3f9; border-radius: 14px; }
    .dash-soft { background: #f6f9ff; }
    .dash-title { color: #102a43; }
    .dash-muted { color: #6b778c; }
    .dash-kpi-number { color: #0f2745; font-size: 1.6rem; font-weight: 800; }
    .dash-section-title { color: #102a43; font-weight: 700; font-size: 1rem; }
    .dash-link-row { border-bottom: 1px solid #eef3f9; }
    .dash-link-row:last-child { border-bottom: 0; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h4 class="mb-1 dash-title fw-bold">داشبورد مدیریتی</h4>
        <div class="dash-muted small">{{ $todayDateLabel }} | خوش آمدید {{ $userName ?? 'کاربر' }}</div>
    </div>
    <div class="dash-muted small">{{ $todayDateTimeLabel }}</div>
</div>

<div class="dash-surface p-3 mb-4 d-flex flex-wrap gap-2">
    <a href="{{ route('preinvoice.create') }}" class="btn btn-primary btn-sm">ثبت پیش‌فاکتور</a>
    <a href="{{ route('vouchers.create') }}" class="btn btn-outline-primary btn-sm">ثبت حواله انبار</a>
    <a href="{{ route('preinvoice.draft.index') }}" class="btn btn-outline-dark btn-sm">مشاهده صف مالی</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <a href="{{ route('preinvoice.draft.index') }}" class="dash-surface dash-soft p-3 h-100 text-decoration-none d-block">
            <div class="dash-muted small">در انتظار مالی</div>
            <div class="dash-kpi-number mt-1">{{ number_format($kpis['financeQueue']) }}</div>
        </a>
    </div>
    <div class="col-md-6 col-xl-3">
        <a href="{{ route('vouchers.index') }}" class="dash-surface dash-soft p-3 h-100 text-decoration-none d-block">
            <div class="dash-muted small">در انتظار انبار</div>
            <div class="dash-kpi-number mt-1">{{ number_format($kpis['warehousePending']) }}</div>
        </a>
    </div>
    <div class="col-md-6 col-xl-3">
        <a href="{{ route('products.index', ['stock_status' => 'low']) }}" class="dash-surface dash-soft p-3 h-100 text-decoration-none d-block">
            <div class="dash-muted small">کالاهای کم‌موجود</div>
            <div class="dash-kpi-number mt-1">{{ number_format($kpis['lowStock']) }}</div>
        </a>
    </div>
    <div class="col-md-6 col-xl-3">
        <a href="{{ route('invoices.index') }}" class="dash-surface dash-soft p-3 h-100 text-decoration-none d-block">
            <div class="dash-muted small">جمع دریافتی امروز</div>
            <div class="dash-kpi-number mt-1">{{ number_format($kpis['todayReceipts']) }} <span class="fs-6">تومان</span></div>
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="dash-surface p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="dash-section-title">نیازمند رسیدگی</div>
                <span class="dash-muted small">اولویت روزانه</span>
            </div>
            @foreach(collect($actionItems)->take(4) as $item)
                <a href="{{ $item['route'] }}" class="dash-link-row py-2 text-decoration-none d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold text-dark">{{ $item['title'] }}</div>
                        <div class="dash-muted small">{{ $item['description'] }}</div>
                    </div>
                    <span class="badge text-bg-{{ $item['variant'] }}">{{ number_format($item['count']) }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="col-lg-5">
        <div class="dash-surface p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="dash-section-title">هشدارهای مهم</div>
                <span class="dash-muted small">Critical Alerts</span>
            </div>
            @foreach(collect($warnings)->take(4) as $warning)
                <a href="{{ $warning['route'] }}" class="dash-link-row py-2 text-decoration-none d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold text-dark">{{ $warning['title'] }}</div>
                        <div class="dash-muted small">{{ $warning['description'] }}</div>
                    </div>
                    <span class="badge text-bg-{{ $warning['variant'] }}">{{ number_format($warning['count']) }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-4">
        <div class="dash-surface p-3 h-100">
            <div class="dash-section-title mb-3">خلاصه فروش</div>
            <div class="small d-flex justify-content-between mb-2"><span class="dash-muted">پیش‌فاکتور این ماه</span><strong>{{ number_format($salesSummary['preinvoicesThisMonth']) }}</strong></div>
            <div class="small d-flex justify-content-between mb-2"><span class="dash-muted">فاکتور این ماه</span><strong>{{ number_format($salesSummary['invoicesThisMonth']) }}</strong></div>
            <div class="small d-flex justify-content-between mb-2"><span class="dash-muted">مبلغ فروش این ماه</span><strong>{{ number_format($salesSummary['salesAmountThisMonth']) }}</strong></div>
            <div class="small d-flex justify-content-between"><span class="dash-muted">برگشت از فروش</span><strong>{{ number_format($salesSummary['returnFromSaleCount']) }}</strong></div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="dash-surface p-3 h-100">
            <div class="dash-section-title mb-3">خلاصه انبارداری</div>
            <div class="small d-flex justify-content-between mb-2"><span class="dash-muted">حواله‌های امروز</span><strong>{{ number_format($warehouseSummary['todayHavalehCount']) }}</strong></div>
            <div class="small d-flex justify-content-between mb-2"><span class="dash-muted">در انتظار انبار</span><strong>{{ number_format($warehouseSummary['pendingWarehouse']) }}</strong></div>
            <div class="small d-flex justify-content-between mb-2"><span class="dash-muted">کالاهای کم‌موجود</span><strong>{{ number_format($warehouseSummary['lowStock']) }}</strong></div>
            <div class="small d-flex justify-content-between"><span class="dash-muted">کالاهای ناموجود</span><strong>{{ number_format($warehouseSummary['outOfStock']) }}</strong></div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="dash-surface p-3 h-100">
            <div class="dash-section-title mb-3">خلاصه مالی</div>
            <div class="small d-flex justify-content-between mb-2"><span class="dash-muted">صف مالی</span><strong>{{ number_format($financeSummary['financeQueue']) }}</strong></div>
            <div class="small d-flex justify-content-between mb-2"><span class="dash-muted">جمع دریافتی امروز</span><strong>{{ number_format($financeSummary['todayReceipts']) }}</strong></div>
            <div class="small d-flex justify-content-between mb-2"><span class="dash-muted">پرداخت نقدی</span><strong>{{ number_format($financeSummary['todayCashPayments']) }}</strong></div>
            <div class="small d-flex justify-content-between"><span class="dash-muted">پرداخت چکی</span><strong>{{ number_format($financeSummary['todayChequePayments']) }}</strong></div>
        </div>
    </div>
</div>

<div class="dash-surface p-3 mb-4" id="monthlyReportsCard"
     data-endpoint="{{ route('dashboard.monthly-report') }}"
     data-initial='@json($monthlyReport)'>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div>
            <div class="dash-section-title mb-1">تحلیل عملکرد ماهانه</div>
            <div id="monthlyReportRange" class="dash-muted small">بازه: {{ $monthlyReport['range_label'] }}</div>
        </div>
        <div class="d-flex gap-2 align-items-end">
            <div>
                <label for="reportMonthSelect" class="form-label small dash-muted mb-1">ماه</label>
                <select id="reportMonthSelect" class="form-select form-select-sm">
                    @foreach($reportMonths as $monthNumber => $monthLabel)
                        <option value="{{ $monthNumber }}" @selected($selectedReportMonth == $monthNumber)>{{ $monthLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="reportYearSelect" class="form-label small dash-muted mb-1">سال</label>
                <select id="reportYearSelect" class="form-select form-select-sm">
                    @foreach($reportYears as $yearOption)
                        <option value="{{ $yearOption }}" @selected($selectedReportYear == $yearOption)>{{ $yearOption }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-4"><div class="dash-soft rounded-3 p-2"><div class="dash-muted small">پیش‌فاکتور</div><div class="fw-bold" data-summary="preinvoices">{{ number_format($monthlyReport['summary']['preinvoices']) }}</div></div></div>
        <div class="col-md-4"><div class="dash-soft rounded-3 p-2"><div class="dash-muted small">فاکتور</div><div class="fw-bold" data-summary="invoices">{{ number_format($monthlyReport['summary']['invoices']) }}</div></div></div>
        <div class="col-md-4"><div class="dash-soft rounded-3 p-2"><div class="dash-muted small">مبلغ فروش</div><div class="fw-bold" data-summary="sales_amount">{{ number_format($monthlyReport['summary']['sales_amount']) }} تومان</div></div></div>
    </div>

    <div id="monthlyHorizontalChart" class="d-grid gap-2"></div>

    <div class="row g-2 mt-2">
        <div class="col-md-4"><div class="small d-flex justify-content-between dash-muted"><span>فروش ۳۰ روز</span><strong class="text-dark">{{ number_format($rolling30Summary['sales']) }}</strong></div></div>
        <div class="col-md-4"><div class="small d-flex justify-content-between dash-muted"><span>فاکتور ۳۰ روز</span><strong class="text-dark">{{ number_format($rolling30Summary['invoices']) }}</strong></div></div>
        <div class="col-md-4"><div class="small d-flex justify-content-between dash-muted"><span>دریافتی ۳۰ روز</span><strong class="text-dark">{{ number_format($rolling30Summary['receipts']) }}</strong></div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="dash-surface p-3 h-100">
            <div class="dash-section-title mb-2">آخرین فعالیت‌ها</div>
            <div class="dash-link-row py-2 d-flex justify-content-between"><span>آخرین پیش‌فاکتور</span><span class="dash-muted small">{{ $recentActivity['latestPreinvoice']?->customer_name ?? '---' }}</span></div>
            <div class="dash-link-row py-2 d-flex justify-content-between"><span>آخرین حواله</span><span class="dash-muted small">{{ $recentActivity['latestHavaleh']?->uuid ?? '---' }}</span></div>
            <div class="dash-link-row py-2 d-flex justify-content-between"><span>آخرین تغییر وضعیت</span><span class="dash-muted small">{{ $statusLabels[$recentActivity['latestStatusChange']?->new_value ?? ''] ?? '---' }}</span></div>
            <div class="dash-link-row py-2 d-flex justify-content-between"><span>آخرین سند امین اموال</span><span class="dash-muted small">{{ $recentActivity['latestAssetDocument']?->document_number ?? '---' }}</span></div>
            @foreach($recentActivity['latestUserActivities']->take(2) as $log)
                <div class="dash-link-row py-2 d-flex justify-content-between">
                    <span class="small">{{ $log->user?->name ?? 'سیستم' }} - {{ $log->description }}</span>
                    <span class="dash-muted small">{{ Jalalian::fromDateTime($log->occurred_at)->format('m/d H:i') }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="col-lg-5">
        <div class="dash-surface p-3 h-100">
            <div class="dash-section-title mb-2">میانبر ماژول‌ها</div>
            @foreach($moduleShortcuts as $module)
                <a href="{{ $module['route'] }}" class="dash-link-row py-2 text-decoration-none d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold text-dark">{{ $module['title'] }}</div>
                        <div class="dash-muted small">{{ $module['description'] }}</div>
                    </div>
                    <i class="bi bi-{{ $module['icon'] }} dash-muted"></i>
                </a>
            @endforeach
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const card = document.getElementById('monthlyReportsCard');
    if (!card) return;

    const endpoint = card.dataset.endpoint;
    const monthSelect = document.getElementById('reportMonthSelect');
    const yearSelect = document.getElementById('reportYearSelect');
    const rangeLabelEl = document.getElementById('monthlyReportRange');
    const chartEl = document.getElementById('monthlyHorizontalChart');
    const formatNumber = (value) => new Intl.NumberFormat('fa-IR').format(Number(value || 0));

    const summaryEls = {
        preinvoices: document.querySelector('[data-summary="preinvoices"]'),
        invoices: document.querySelector('[data-summary="invoices"]'),
        sales_amount: document.querySelector('[data-summary="sales_amount"]'),
    };

    function renderChart(report) {
        chartEl.innerHTML = '';

        (report.metrics || []).slice(0, 5).forEach((metric) => {
            const row = document.createElement('div');
            row.innerHTML = `
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="fw-semibold">${metric.label}</span>
                    <span class="dash-muted">${formatNumber(metric.value)} ${metric.unit}</span>
                </div>
                <div class="progress" style="height:10px;background:#eaf1fb;">
                    <div class="progress-bar bg-${metric.color}" style="width:${metric.percent}%"></div>
                </div>
            `;
            chartEl.appendChild(row);
        });
    }

    function renderReport(report) {
        rangeLabelEl.textContent = `بازه: ${report.range_label}`;
        if (summaryEls.preinvoices) summaryEls.preinvoices.textContent = formatNumber(report.summary.preinvoices);
        if (summaryEls.invoices) summaryEls.invoices.textContent = formatNumber(report.summary.invoices);
        if (summaryEls.sales_amount) summaryEls.sales_amount.textContent = `${formatNumber(report.summary.sales_amount)} تومان`;
        renderChart(report);
    }

    let initialReport = null;
    try { initialReport = JSON.parse(card.dataset.initial || '{}'); } catch (e) { initialReport = null; }
    if (initialReport && initialReport.metrics) renderReport(initialReport);

    async function fetchReport() {
        const url = `${endpoint}?report_month=${encodeURIComponent(monthSelect.value)}&report_year=${encodeURIComponent(yearSelect.value)}`;
        card.classList.add('opacity-75');
        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            if (!response.ok) throw new Error('failed');
            renderReport(await response.json());
        } catch (err) {
            console.error(err);
        } finally {
            card.classList.remove('opacity-75');
        }
    }

    monthSelect.addEventListener('change', fetchReport);
    yearSelect.addEventListener('change', fetchReport);
});
</script>
@endsection
