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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();
        $last30DaysStart = now()->subDays(29)->startOfDay();

        $lowStockThreshold = (int) config('inventory.low_stock_threshold', 5);

        $kpis = [
            'todayPreinvoices' => PreinvoiceOrder::query()->whereDate('created_at', $today)->count(),
            'todayInvoices' => Invoice::query()->whereDate('created_at', $today)->count(),
            'financeQueue' => PreinvoiceOrder::query()->where('status', 'submitted_finance')->count(),
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
                    ->where('status', 'submitted_finance')
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

        $salesByDay = Invoice::query()
            ->selectRaw('DATE(created_at) as day, COALESCE(SUM(total),0) as total')
            ->where('created_at', '>=', $last30DaysStart)
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $warehouseByDay = Invoice::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->where('created_at', '>=', $last30DaysStart)
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $paymentsByMethod = InvoicePayment::query()
            ->select('method', DB::raw('COALESCE(SUM(amount),0) as total'))
            ->where('paid_at', '>=', $last30DaysStart->toDateString())
            ->groupBy('method')
            ->pluck('total', 'method');

        $chartDays = collect(range(0, 29))->map(fn (int $offset) => Carbon::parse($last30DaysStart)->addDays($offset)->toDateString());

        $charts = [
            'sales30Days' => $chartDays->map(fn (string $day) => [
                'label' => Carbon::parse($day)->format('m/d'),
                'value' => (int) ($salesByDay[$day] ?? 0),
            ]),
            'warehouse30Days' => $chartDays->map(fn (string $day) => [
                'label' => Carbon::parse($day)->format('m/d'),
                'value' => (int) ($warehouseByDay[$day] ?? 0),
            ]),
            'paymentComparison' => [
                ['label' => 'دریافت نقدی', 'value' => (int) ($paymentsByMethod['cash'] ?? 0)],
                ['label' => 'دریافت چکی', 'value' => (int) ($paymentsByMethod['cheque'] ?? 0)],
            ],
        ];

        $moduleShortcuts = [
            ['title' => 'کالاها', 'description' => 'مدیریت کالا و موجودی', 'route' => route('products.index'), 'icon' => 'box-seam'],
            ['title' => 'انبارداری', 'description' => 'حواله‌ها و انبارگردانی', 'route' => route('vouchers.index'), 'icon' => 'boxes'],
            ['title' => 'بازرگانی و فروش', 'description' => 'پیش‌فاکتور و مشتریان', 'route' => route('preinvoice.create'), 'icon' => 'cart-check'],
            ['title' => 'مالی', 'description' => 'صف مالی و فاکتورها', 'route' => route('preinvoice.draft.index'), 'icon' => 'cash-coin'],
            ['title' => 'پیکربندی', 'description' => 'تنظیمات پایه سیستم', 'route' => route('users.index'), 'icon' => 'gear'],
        ];

        return view('dashboard.index', [
            'todayDateLabel' => now()->locale('fa')->translatedFormat('l d F Y'),
            'todayDateTimeLabel' => now()->locale('fa')->translatedFormat('Y/m/d H:i'),
            'userName' => auth()->user()?->name,
            'kpis' => $kpis,
            'actionItems' => $actionItems,
            'salesSummary' => $salesSummary,
            'warehouseSummary' => $warehouseSummary,
            'financeSummary' => $financeSummary,
            'charts' => $charts,
            'warnings' => $warnings,
            'recentActivity' => $recentActivity,
            'moduleShortcuts' => $moduleShortcuts,
        ]);
    }
}
