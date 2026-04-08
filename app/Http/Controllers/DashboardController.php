<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AssetDocument;
use App\Models\Cheque;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PreinvoiceOrder;
use App\Models\Product;
use App\Models\SalesHavalehHistory;
use App\Models\StockCountDocument;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();
        $last30DaysStart = now()->subDays(29)->startOfDay();

        $lowStockThreshold = (int) config('inventory.low_stock_threshold', 5);

        $kpis = [
            'todayPreinvoices' => PreinvoiceOrder::query()->whereDate('created_at', $today)->count(),
            'todayInvoices' => Invoice::query()->whereDate('created_at', $today)->count(),
            'financeQueue' => PreinvoiceOrder::query()->where('status', PreinvoiceOrder::STATUS_SUBMITTED_FINANCE)->count(),
            'warehousePending' => Invoice::query()->where('status', Invoice::STATUS_PENDING_WAREHOUSE_APPROVAL)->count(),
            'lowStock' => Product::query()->where('stock', '>', 0)->where('stock', '<=', $lowStockThreshold)->count(),
            'todayReceipts' => (int) InvoicePayment::query()->whereDate('paid_at', $today)->sum('amount'),
        ];

        $actionItems = [
            [
                'title' => 'پیش‌فاکتورهای منتظر تأیید مالی',
                'count' => $kpis['financeQueue'],
                'description' => 'نیازمند بررسی و تبدیل به فاکتور',
                'route' => route('preinvoice.draft.index'),
                'variant' => 'warning',
            ],
            [
                'title' => 'حواله‌های منتظر رسیدگی انبار',
                'count' => $kpis['warehousePending'],
                'description' => 'در انتظار تایید اولیه انبار',
                'route' => route('vouchers.index'),
                'variant' => 'info',
            ],
            [
                'title' => 'حواله‌های در حال جمع‌آوری/بسته‌بندی',
                'count' => Invoice::query()->whereIn('status', [
                    Invoice::STATUS_COLLECTING,
                    Invoice::STATUS_CHECKING_DISCREPANCY,
                    Invoice::STATUS_FINAL_CHECK,
                    Invoice::STATUS_PACKING,
                ])->count(),
                'description' => 'برای پیگیری عملیات ارسال',
                'route' => route('vouchers.sale-delivery.index'),
                'variant' => 'primary',
            ],
            [
                'title' => 'اسناد انبارگردانی نهایی‌نشده',
                'count' => StockCountDocument::query()->where('status', 'draft')->count(),
                'description' => 'اسناد draft باقی‌مانده',
                'route' => route('stocktake.index'),
                'variant' => 'secondary',
            ],
            [
                'title' => 'اسناد امین اموال نهایی‌نشده',
                'count' => AssetDocument::query()->where('status', AssetDocument::STATUS_DRAFT)->count(),
                'description' => 'نیازمند تکمیل یا نهایی‌سازی',
                'route' => route('asset.documents.index'),
                'variant' => 'dark',
            ],
        ];

        $salesSummary = [
            'preinvoicesThisMonth' => PreinvoiceOrder::query()->where('created_at', '>=', $monthStart)->count(),
            'invoicesThisMonth' => Invoice::query()->where('created_at', '>=', $monthStart)->count(),
            'salesAmountThisMonth' => (int) Invoice::query()->where('created_at', '>=', $monthStart)->sum('total'),
            'returnFromSaleCount' => StockMovement::query()
                ->where('type', 'in')
                ->where('reason', 'return_from_sale')
                ->where('created_at', '>=', $monthStart)
                ->count(),
            'latestPreinvoices' => PreinvoiceOrder::query()
                ->latest()
                ->take(5)
                ->get(['uuid', 'customer_name', 'total_price', 'created_at']),
        ];

        $warehouseSummary = [
            'todayHavalehCount' => Invoice::query()->whereDate('created_at', $today)->count(),
            'pendingWarehouse' => $kpis['warehousePending'],
            'lowStock' => $kpis['lowStock'],
            'outOfStock' => Product::query()->where('stock', '<=', 0)->count(),
            'latestStocktakes' => StockCountDocument::query()
                ->with('warehouse:id,name')
                ->latest()
                ->take(5)
                ->get(['id', 'warehouse_id', 'document_number', 'status', 'created_at']),
            'latestScrapVouchers' => StockMovement::query()
                ->where('reason', 'scrap')
                ->latest()
                ->take(5)
                ->get(['reference', 'quantity', 'created_at']),
        ];

        $financeSummary = [
            'financeQueue' => $kpis['financeQueue'],
            'todayReceipts' => $kpis['todayReceipts'],
            'todayCashPayments' => InvoicePayment::query()->whereDate('paid_at', $today)->where('method', 'cash')->count(),
            'todayChequePayments' => InvoicePayment::query()->whereDate('paid_at', $today)->where('method', 'cheque')->count(),
            'latestInvoices' => Invoice::query()
                ->latest()
                ->take(5)
                ->get(['uuid', 'customer_name', 'total', 'status', 'created_at']),
            'importantAccounts' => Customer::query()
                ->withBalance()
                ->get(['id', 'first_name', 'last_name', 'opening_balance'])
                ->sortByDesc(fn (Customer $customer) => abs((int) $customer->balance))
                ->take(5)
                ->values(),
        ];

        $statusHistory = SalesHavalehHistory::query()
            ->with(['invoice:id,uuid', 'actor:id,name'])
            ->where('field_name', 'status')
            ->latest('done_at')
            ->first();

        $recentActivity = [
            'latestPreinvoice' => PreinvoiceOrder::query()->latest()->first(['uuid', 'customer_name', 'created_at']),
            'latestHavaleh' => Invoice::query()->latest()->first(['uuid', 'customer_name', 'created_at']),
            'latestStatusChange' => $statusHistory,
            'latestAssetDocument' => AssetDocument::query()->latest()->first(['id', 'document_number', 'status', 'created_at']),
            'latestUserActivities' => ActivityLog::query()
                ->with('user:id,name')
                ->latest('occurred_at')
                ->take(6)
                ->get(['id', 'user_id', 'description', 'occurred_at']),
        ];

        $warnings = [
            [
                'title' => 'کالاهای کم‌موجود',
                'count' => $kpis['lowStock'],
                'description' => "موجودی کمتر یا مساوی {$lowStockThreshold}",
                'route' => route('products.index', ['stock_status' => 'low']),
                'variant' => 'warning',
            ],
            [
                'title' => 'کالاهای صفر موجودی',
                'count' => Product::query()->where('stock', '<=', 0)->count(),
                'description' => 'نیازمند تامین فوری',
                'route' => route('products.index', ['stock_status' => 'out']),
                'variant' => 'danger',
            ],
            [
                'title' => 'سفارش‌های معطل مالی',
                'count' => PreinvoiceOrder::query()
                    ->where('status', PreinvoiceOrder::STATUS_SUBMITTED_FINANCE)
                    ->where('created_at', '<=', now()->subDays(2))
                    ->count(),
                'description' => 'بیش از ۲ روز در صف مانده‌اند',
                'route' => route('preinvoice.draft.index'),
                'variant' => 'secondary',
            ],
            [
                'title' => 'چک‌های نزدیک سررسید',
                'count' => Cheque::query()
                    ->where('status', 'pending')
                    ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                    ->count(),
                'description' => 'تا ۷ روز آینده',
                'route' => route('invoices.index'),
                'variant' => 'info',
            ],
            [
                'title' => 'اسناد قدیمی نهایی‌نشده',
                'count' => StockCountDocument::query()
                    ->where('status', 'draft')
                    ->where('created_at', '<=', now()->subDays(7))
                    ->count()
                    + AssetDocument::query()
                        ->where('status', AssetDocument::STATUS_DRAFT)
                        ->where('created_at', '<=', now()->subDays(7))
                        ->count(),
                'description' => 'پیش‌نویس‌های قدیمی‌تر از ۷ روز',
                'route' => route('stocktake.index'),
                'variant' => 'dark',
            ],
        ];

        $jalaliNow = Jalalian::fromDateTime(now());
        $selectedYear = (int) $request->integer('report_year', $jalaliNow->getYear());
        $selectedMonth = (int) $request->integer('report_month', $jalaliNow->getMonth());

        $monthlyReport = $this->buildMonthlyReport($selectedYear, $selectedMonth);

        $rolling30Summary = [
            'sales' => (int) Invoice::query()->where('created_at', '>=', $last30DaysStart)->sum('total'),
            'invoices' => Invoice::query()->where('created_at', '>=', $last30DaysStart)->count(),
            'receipts' => (int) InvoicePayment::query()->where('paid_at', '>=', $last30DaysStart->toDateString())->sum('amount'),
        ];

        $moduleShortcuts = [
            ['title' => 'کالاها', 'description' => 'مدیریت کالا و موجودی', 'route' => route('products.index'), 'icon' => 'box-seam'],
            ['title' => 'انبارداری', 'description' => 'حواله‌ها و انبارگردانی', 'route' => route('vouchers.index'), 'icon' => 'boxes'],
            ['title' => 'بازرگانی و فروش', 'description' => 'پیش‌فاکتور و مشتریان', 'route' => route('preinvoice.create'), 'icon' => 'cart-check'],
            ['title' => 'مالی', 'description' => 'صف مالی و فاکتورها', 'route' => route('preinvoice.draft.index'), 'icon' => 'cash-coin'],
            ['title' => 'پیکربندی', 'description' => 'تنظیمات پایه سیستم', 'route' => route('users.index'), 'icon' => 'gear'],
        ];

        $reportMonths = [
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
            7 => 'مهر', 8 => 'آبان', 9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
        ];

        $reportYears = range($jalaliNow->getYear() - 3, $jalaliNow->getYear() + 1);

        return view('dashboard.index', [
            'todayDateLabel' => now()->locale('fa')->translatedFormat('l d F Y'),
            'todayDateTimeLabel' => now()->locale('fa')->translatedFormat('Y/m/d H:i'),
            'userName' => auth()->user()?->name,
            'kpis' => $kpis,
            'actionItems' => $actionItems,
            'salesSummary' => $salesSummary,
            'warehouseSummary' => $warehouseSummary,
            'financeSummary' => $financeSummary,
            'warnings' => $warnings,
            'recentActivity' => $recentActivity,
            'moduleShortcuts' => $moduleShortcuts,
            'monthlyReport' => $monthlyReport,
            'reportMonths' => $reportMonths,
            'reportYears' => $reportYears,
            'selectedReportMonth' => $selectedMonth,
            'selectedReportYear' => $selectedYear,
            'rolling30Summary' => $rolling30Summary,
        ]);
    }

    public function monthlyReport(Request $request): JsonResponse
    {
        $jalaliNow = Jalalian::fromDateTime(now());
        $year = (int) $request->integer('report_year', $jalaliNow->getYear());
        $month = (int) $request->integer('report_month', $jalaliNow->getMonth());

        return response()->json($this->buildMonthlyReport($year, $month));
    }

    private function buildMonthlyReport(int $jalaliYear, int $jalaliMonth): array
    {
        $jalaliMonth = max(1, min(12, $jalaliMonth));

        $startJalali = new Jalalian($jalaliYear, $jalaliMonth, 1);
        $nextMonthJalali = $jalaliMonth === 12
            ? new Jalalian($jalaliYear + 1, 1, 1)
            : new Jalalian($jalaliYear, $jalaliMonth + 1, 1);

        $start = $startJalali->toCarbon()->startOfDay();
        $end = $nextMonthJalali->toCarbon()->subSecond();

        $metrics = [
            ['key' => 'sales', 'label' => 'مبلغ فروش', 'unit' => 'تومان', 'value' => (int) Invoice::query()->whereBetween('created_at', [$start, $end])->sum('total'), 'color' => 'primary'],
            ['key' => 'warehouse_vouchers', 'label' => 'حواله‌های انبار', 'unit' => 'عدد', 'value' => Invoice::query()->whereBetween('created_at', [$start, $end])->count(), 'color' => 'info'],
            ['key' => 'receipts', 'label' => 'دریافتی‌ها', 'unit' => 'تومان', 'value' => (int) InvoicePayment::query()->whereBetween('paid_at', [$start->toDateString(), $end->toDateString()])->sum('amount'), 'color' => 'success'],
            ['key' => 'invoice_count', 'label' => 'تعداد فاکتورها', 'unit' => 'عدد', 'value' => Invoice::query()->whereBetween('created_at', [$start, $end])->count(), 'color' => 'secondary'],
            ['key' => 'pending_orders', 'label' => 'سفارش‌های در انتظار', 'unit' => 'عدد', 'value' => PreinvoiceOrder::query()->where('status', PreinvoiceOrder::STATUS_SUBMITTED_FINANCE)->whereBetween('created_at', [$start, $end])->count(), 'color' => 'warning'],
        ];

        $max = max(1, collect($metrics)->max('value'));
        $metrics = collect($metrics)->map(fn (array $metric) => $metric + [
            'percent' => (float) min(100, round(($metric['value'] / $max) * 100, 2)),
            'display_value' => number_format($metric['value']),
        ])->values()->all();

        $monthNames = [
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
            7 => 'مهر', 8 => 'آبان', 9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
        ];

        return [
            'report_year' => $jalaliYear,
            'report_month' => $jalaliMonth,
            'range_label' => ($monthNames[$jalaliMonth] ?? 'ماه') . " {$jalaliYear}",
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'summary' => [
                'preinvoices' => PreinvoiceOrder::query()->whereBetween('created_at', [$start, $end])->count(),
                'invoices' => Invoice::query()->whereBetween('created_at', [$start, $end])->count(),
                'sales_amount' => (int) Invoice::query()->whereBetween('created_at', [$start, $end])->sum('total'),
            ],
            'metrics' => $metrics,
        ];
    }
}
