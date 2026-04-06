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

    $maxSales = max(1, collect($charts['sales30Days'] ?? [])->max('value'));
    $maxWarehouse = max(1, collect($charts['warehouse30Days'] ?? [])->max('value'));
    $maxPayments = max(1, collect($charts['paymentComparison'] ?? [])->max('value'));
@endphp

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h4 class="page-title mb-1">داشبورد مدیریتی</h4>
        <div class="text-muted small">{{ $todayDateLabel }} | خوش آمدید {{ $userName ?? 'کاربر' }} 👋</div>
    </div>
    <div class="text-muted small">آخرین بروزرسانی: {{ $todayDateTimeLabel }}</div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap gap-2">
        <a href="{{ route('preinvoice.create') }}" class="btn btn-primary btn-sm">ثبت پیش‌فاکتور</a>
        <a href="{{ route('vouchers.create') }}" class="btn btn-outline-primary btn-sm">ثبت حواله انبار</a>
        <a href="{{ route('stock-count-documents.create') }}" class="btn btn-outline-secondary btn-sm">ثبت سند انبارگردانی</a>
        <a href="{{ route('preinvoice.draft.index') }}" class="btn btn-outline-dark btn-sm">مشاهده صف مالی</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-2">
        <a href="{{ route('preinvoice.draft.index') }}" class="card h-100 border-0 shadow-sm text-decoration-none">
            <div class="card-body">
                <div class="text-muted small">پیش‌فاکتور امروز</div>
                <div class="fs-4 fw-bold mt-1">{{ number_format($kpis['todayPreinvoices'] ?? 0) }}</div>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-xl-2">
        <a href="{{ route('invoices.index') }}" class="card h-100 border-0 shadow-sm text-decoration-none">
            <div class="card-body">
                <div class="text-muted small">فاکتور نهایی امروز</div>
                <div class="fs-4 fw-bold mt-1">{{ number_format($kpis['todayInvoices'] ?? 0) }}</div>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-xl-2">
        <a href="{{ route('preinvoice.draft.index') }}" class="card h-100 border-0 shadow-sm text-decoration-none">
            <div class="card-body">
                <div class="text-muted small">در انتظار مالی</div>
                <div class="fs-4 fw-bold mt-1 text-warning">{{ number_format($kpis['financeQueue'] ?? 0) }}</div>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-xl-2">
        <a href="{{ route('vouchers.index') }}" class="card h-100 border-0 shadow-sm text-decoration-none">
            <div class="card-body">
                <div class="text-muted small">در انتظار انبار</div>
                <div class="fs-4 fw-bold mt-1 text-info">{{ number_format($kpis['warehousePending'] ?? 0) }}</div>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-xl-2">
        <a href="{{ route('products.index', ['stock_status' => 'low']) }}" class="card h-100 border-0 shadow-sm text-decoration-none">
            <div class="card-body">
                <div class="text-muted small">کالاهای کم‌موجود</div>
                <div class="fs-4 fw-bold mt-1 text-danger">{{ number_format($kpis['lowStock'] ?? 0) }}</div>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-xl-2">
        <a href="{{ route('invoices.index') }}" class="card h-100 border-0 shadow-sm text-decoration-none">
            <div class="card-body">
                <div class="text-muted small">جمع دریافتی امروز</div>
                <div class="fs-5 fw-bold mt-1">{{ number_format($kpis['todayReceipts'] ?? 0) }} <span class="small">تومان</span></div>
            </div>
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">نیازمند رسیدگی</h6>
                    <span class="small text-muted">Action Queue</span>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($actionItems as $item)
                        <a href="{{ $item['route'] }}" class="list-group-item list-group-item-action px-0 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">{{ $item['title'] }}</div>
                                <div class="small text-muted">{{ $item['description'] }}</div>
                            </div>
                            <span class="badge text-bg-{{ $item['variant'] }} fs-6">{{ number_format($item['count']) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">هشدارهای مهم</h6>
                    <span class="small text-muted">Alerts</span>
                </div>
                <div class="d-grid gap-2">
                    @foreach($warnings as $warning)
                        <a href="{{ $warning['route'] }}" class="text-decoration-none border rounded-3 p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="fw-semibold text-dark">{{ $warning['title'] }}</div>
                                <span class="badge text-bg-{{ $warning['variant'] }}">{{ number_format($warning['count']) }}</span>
                            </div>
                            <div class="small text-muted mt-1">{{ $warning['description'] }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">خلاصه فروش</h6>
                <div class="small d-flex justify-content-between mb-2"><span class="text-muted">پیش‌فاکتور این ماه</span><strong>{{ number_format($salesSummary['preinvoicesThisMonth']) }}</strong></div>
                <div class="small d-flex justify-content-between mb-2"><span class="text-muted">فاکتور فروش این ماه</span><strong>{{ number_format($salesSummary['invoicesThisMonth']) }}</strong></div>
                <div class="small d-flex justify-content-between mb-2"><span class="text-muted">مبلغ فروش این ماه</span><strong>{{ number_format($salesSummary['salesAmountThisMonth']) }} تومان</strong></div>
                <div class="small d-flex justify-content-between mb-3"><span class="text-muted">برگشت از فروش</span><strong>{{ number_format($salesSummary['returnFromSaleCount']) }}</strong></div>
                <div class="small text-muted mb-2">آخرین پیش‌فاکتورها</div>
                <ul class="list-group list-group-flush">
                    @forelse($salesSummary['latestPreinvoices'] as $item)
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                            <span>{{ $item->customer_name ?: 'بدون نام' }}</span>
                            <span class="text-muted small">{{ number_format($item->total_price) }}</span>
                        </li>
                    @empty
                        <li class="list-group-item px-0 small text-muted">داده‌ای ثبت نشده است.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">خلاصه انبارداری</h6>
                <div class="small d-flex justify-content-between mb-2"><span class="text-muted">حواله‌های امروز</span><strong>{{ number_format($warehouseSummary['todayHavalehCount']) }}</strong></div>
                <div class="small d-flex justify-content-between mb-2"><span class="text-muted">در انتظار انبار</span><strong>{{ number_format($warehouseSummary['pendingWarehouse']) }}</strong></div>
                <div class="small d-flex justify-content-between mb-2"><span class="text-muted">کالاهای کم‌موجود</span><strong>{{ number_format($warehouseSummary['lowStock']) }}</strong></div>
                <div class="small d-flex justify-content-between mb-3"><span class="text-muted">کالاهای ناموجود</span><strong>{{ number_format($warehouseSummary['outOfStock']) }}</strong></div>
                <div class="small text-muted">آخرین اسناد انبارگردانی</div>
                <ul class="list-group list-group-flush mb-2">
                    @forelse($warehouseSummary['latestStocktakes'] as $doc)
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                            <span class="small">{{ $doc->document_number }}</span>
                            <span class="badge text-bg-{{ $doc->status === 'draft' ? 'warning' : 'success' }}">{{ $doc->status }}</span>
                        </li>
                    @empty
                        <li class="list-group-item px-0 small text-muted">سندی یافت نشد.</li>
                    @endforelse
                </ul>
                <div class="small text-muted">آخرین حواله‌های ضایعات</div>
                <ul class="list-group list-group-flush">
                    @forelse($warehouseSummary['latestScrapVouchers'] as $scrap)
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                            <span class="small">{{ $scrap->reference ?: 'بدون مرجع' }}</span>
                            <strong class="small">{{ number_format($scrap->quantity) }}</strong>
                        </li>
                    @empty
                        <li class="list-group-item px-0 small text-muted">حواله ضایعاتی ثبت نشده.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">خلاصه مالی</h6>
                <div class="small d-flex justify-content-between mb-2"><span class="text-muted">رکوردهای صف مالی</span><strong>{{ number_format($financeSummary['financeQueue']) }}</strong></div>
                <div class="small d-flex justify-content-between mb-2"><span class="text-muted">جمع دریافتی امروز</span><strong>{{ number_format($financeSummary['todayReceipts']) }} تومان</strong></div>
                <div class="small d-flex justify-content-between mb-2"><span class="text-muted">پرداخت‌های نقدی</span><strong>{{ number_format($financeSummary['todayCashPayments']) }}</strong></div>
                <div class="small d-flex justify-content-between mb-3"><span class="text-muted">پرداخت‌های چکی</span><strong>{{ number_format($financeSummary['todayChequePayments']) }}</strong></div>

                <div class="small text-muted">آخرین فاکتورها</div>
                <ul class="list-group list-group-flush mb-2">
                    @forelse($financeSummary['latestInvoices'] as $invoice)
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                            <span class="small">{{ $invoice->customer_name ?: 'بدون نام' }}</span>
                            <span class="small">{{ number_format($invoice->total) }}</span>
                        </li>
                    @empty
                        <li class="list-group-item px-0 small text-muted">فاکتوری ثبت نشده است.</li>
                    @endforelse
                </ul>

                <div class="small text-muted">گردش حساب اشخاص مهم</div>
                <ul class="list-group list-group-flush">
                    @forelse($financeSummary['importantAccounts'] as $account)
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                            <span class="small">{{ trim(($account->first_name ?? '') . ' ' . ($account->last_name ?? '')) ?: 'مشتری' }}</span>
                            <span class="small {{ $account->balance >= 0 ? 'text-danger' : 'text-success' }}">{{ number_format(abs($account->balance)) }}</span>
                        </li>
                    @empty
                        <li class="list-group-item px-0 small text-muted">داده‌ای وجود ندارد.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">نمودار فروش ۳۰ روز اخیر</h6>
                @foreach(($charts['sales30Days'] ?? []) as $point)
                    <div class="d-flex align-items-center gap-2 mb-1 small">
                        <span class="text-muted" style="width:48px">{{ $point['label'] }}</span>
                        <div class="progress flex-grow-1" style="height:8px;">
                            <div class="progress-bar bg-primary" style="width: {{ min(100, ($point['value'] / $maxSales) * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">نمودار حواله‌های ۳۰ روز اخیر</h6>
                @foreach(($charts['warehouse30Days'] ?? []) as $point)
                    <div class="d-flex align-items-center gap-2 mb-1 small">
                        <span class="text-muted" style="width:48px">{{ $point['label'] }}</span>
                        <div class="progress flex-grow-1" style="height:8px;">
                            <div class="progress-bar bg-info" style="width: {{ min(100, ($point['value'] / $maxWarehouse) * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">مقایسه دریافتی نقدی و چکی (۳۰ روز)</h6>
                @foreach(($charts['paymentComparison'] ?? []) as $point)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ $point['label'] }}</span>
                            <strong>{{ number_format($point['value']) }}</strong>
                        </div>
                        <div class="progress" style="height:10px;">
                            <div class="progress-bar bg-success" style="width: {{ min(100, ($point['value'] / $maxPayments) * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">آخرین فعالیت‌ها</h6>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span>آخرین پیش‌فاکتور</span>
                        <span class="small text-muted">{{ $recentActivity['latestPreinvoice']?->customer_name ?? '---' }}</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span>آخرین حواله ثبت‌شده</span>
                        <span class="small text-muted">{{ $recentActivity['latestHavaleh']?->uuid ?? '---' }}</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span>آخرین تغییر وضعیت حواله</span>
                        <span class="small text-muted">{{ $statusLabels[$recentActivity['latestStatusChange']?->new_value ?? ''] ?? '---' }}</span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span>آخرین سند امین اموال</span>
                        <span class="small text-muted">{{ $recentActivity['latestAssetDocument']?->document_number ?? '---' }}</span>
                    </li>
                </ul>
                <hr>
                <div class="small text-muted mb-2">آخرین فعالیت کاربران</div>
                <ul class="list-group list-group-flush">
                    @forelse($recentActivity['latestUserActivities'] as $log)
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start">
                            <div class="small">
                                <div class="fw-semibold">{{ $log->user?->name ?? 'سیستم' }}</div>
                                <div class="text-muted">{{ $log->description }}</div>
                            </div>
                            <span class="small text-muted">{{ Jalalian::fromDateTime($log->occurred_at)->format('m/d H:i') }}</span>
                        </li>
                    @empty
                        <li class="list-group-item px-0 small text-muted">فعالیتی ثبت نشده است.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">میانبر ماژول‌ها</h6>
                <div class="row g-2">
                    @foreach($moduleShortcuts as $module)
                        <div class="col-12">
                            <a href="{{ $module['route'] }}" class="border rounded-3 p-3 text-decoration-none d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold text-dark">{{ $module['title'] }}</div>
                                    <div class="small text-muted">{{ $module['description'] }}</div>
                                </div>
                                <i class="bi bi-{{ $module['icon'] }} text-muted"></i>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
