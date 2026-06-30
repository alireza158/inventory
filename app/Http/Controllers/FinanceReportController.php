<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\User;
use App\Support\JalaliDate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceReportController extends Controller
{
    private const INVALID_STATUSES = [
        Invoice::STATUS_NOT_SHIPPED,
        Invoice::STATUS_PENDING_FINANCE_REAPPROVAL,
        'cancelled',
        'canceled',
        'rejected',
        'void',
    ];

    public function index()
    {
        $reports = [
            [
                'title' => 'گزارش فروش ویزیتورها',
                'description' => 'مشاهده، انتخاب موقت، چاپ و خروجی فاکتورهای فروش ویزیتورها',
                'route' => route('finance.reports.sales-visitors'),
                'icon' => '📊',
                'active' => true,
            ],
        ];

        return view('finance.reports.index', compact('reports'));
    }

    public function salesVisitors(Request $request)
    {
        $filters = $this->filters($request);
        $dateFrom = $this->parseDate($filters['from_date']);
        $dateTo = $this->parseDate($filters['to_date']);
        $filterErrors = $this->validateFilters($filters, $dateFrom, $dateTo);

        $baseQuery = $this->baseSalesVisitorsQuery($filters, $dateFrom, $dateTo);

        if (in_array($request->query('export'), ['csv', 'excel'], true)) {
            return $this->exportSalesVisitors((clone $baseQuery)->orderBy('document_date')->orderBy('id')->get(), $request->query('export') === 'excel');
        }

        $details = (clone $baseQuery)
            ->orderByDesc('document_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $summaryRows = (clone $baseQuery)->get();
        $summary = $summaryRows
            ->groupBy(fn (Invoice $invoice) => (string) ($invoice->preinvoiceOrder?->created_by ?: 0))
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'visitor_name' => $first?->preinvoiceOrder?->creator?->name ?: 'نامشخص',
                    'invoice_count' => $rows->count(),
                    'subtotal' => (int) $rows->sum(fn (Invoice $invoice) => (int) $invoice->subtotal),
                    'discount_amount' => (int) $rows->sum(fn (Invoice $invoice) => (int) $invoice->discount_amount),
                    'total' => (int) $rows->sum(fn (Invoice $invoice) => (int) $invoice->total),
                ];
            })
            ->sortBy('visitor_name')
            ->values();

        $summaryTotals = [
            'invoice_count' => (int) $summary->sum('invoice_count'),
            'subtotal' => (int) $summary->sum('subtotal'),
            'discount_amount' => (int) $summary->sum('discount_amount'),
            'total' => (int) $summary->sum('total'),
        ];

        $visitors = $this->visitors();
        $statusLabels = $this->statusLabels();
        $statusOptions = ['valid' => 'همه فاکتورهای معتبر'] + $statusLabels + ['all' => 'همه وضعیت‌ها'];

        return view('finance.reports.sales-visitors', compact('filters', 'filterErrors', 'details', 'summary', 'summaryTotals', 'visitors', 'statusLabels', 'statusOptions'));
    }

    private function baseSalesVisitorsQuery(array $filters, ?Carbon $dateFrom, ?Carbon $dateTo): Builder
    {
        return Invoice::query()
            ->with(['preinvoiceOrder.creator:id,name'])
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('document_date', '>=', $dateFrom->toDateString()))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('document_date', '<=', $dateTo->toDateString()))
            ->when($filters['visitor_id'] !== '', fn (Builder $query) => $query->whereHas('preinvoiceOrder', fn (Builder $orderQuery) => $orderQuery->where('created_by', (int) $filters['visitor_id'])))
            ->when($filters['status'] === 'valid', fn (Builder $query) => $query->whereNotIn('status', self::INVALID_STATUSES))
            ->when(! in_array($filters['status'], ['', 'valid', 'all'], true), fn (Builder $query) => $query->where('status', $filters['status']));
    }

    private function exportSalesVisitors($invoices, bool $excelAlias = false): StreamedResponse
    {
        $filename = 'sales-visitors-report-' . now()->format('Ymd-His') . ($excelAlias ? '.xls' : '.csv');

        return response()->streamDownload(function () use ($invoices) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['invoice_number', 'invoice_date', 'customer_name', 'customer_mobile', 'visitor', 'subtotal', 'discount_amount', 'total', 'status']);

            foreach ($invoices as $invoice) {
                fputcsv($handle, [
                    $invoice->uuid,
                    JalaliDate::date($invoice->display_document_date),
                    $invoice->customer_name,
                    $invoice->customer_mobile,
                    $invoice->preinvoiceOrder?->creator?->name ?: '',
                    (int) $invoice->subtotal,
                    (int) $invoice->discount_amount,
                    (int) $invoice->total,
                    $this->statusLabels()[$invoice->status] ?? $invoice->status,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        return [
            'from_date' => $this->normalizeDigits(trim((string) $request->query('from_date', ''))),
            'to_date' => $this->normalizeDigits(trim((string) $request->query('to_date', ''))),
            'visitor_id' => trim((string) $request->query('visitor_id', '')),
            'status' => trim((string) $request->query('status', 'valid')) ?: 'valid',
        ];
    }

    private function validateFilters(array &$filters, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $errors = [];
        if ($filters['from_date'] !== '' && ! $dateFrom) {
            $errors[] = 'تاریخ شروع معتبر نیست.';
            $filters['from_date'] = '';
        }
        if ($filters['to_date'] !== '' && ! $dateTo) {
            $errors[] = 'تاریخ پایان معتبر نیست.';
            $filters['to_date'] = '';
        }
        if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
            $errors[] = 'تاریخ شروع نباید بعد از تاریخ پایان باشد.';
        }
        if ($filters['visitor_id'] !== '' && ! ctype_digit($filters['visitor_id'])) {
            $errors[] = 'ویزیتور انتخاب‌شده معتبر نیست.';
            $filters['visitor_id'] = '';
        }

        return $errors;
    }

    private function parseDate(string $dateInput): ?Carbon
    {
        if ($dateInput === '') {
            return null;
        }

        try {
            if (preg_match('/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})$/', $dateInput, $matches) !== 1) {
                return Carbon::parse($dateInput)->startOfDay();
            }

            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];

            if ($year >= 1300 && $year <= 1600) {
                return (new Jalalian($year, $month, $day))->toCarbon()->startOfDay();
            }

            return Carbon::create($year, $month, $day)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeDigits(string $value): string
    {
        return trim(strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]));
    }

    private function visitors()
    {
        return User::query()
            ->whereIn('id', function ($query) {
                $query->select('created_by')
                    ->from('preinvoice_orders')
                    ->whereNotNull('created_by');
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function statusLabels(): array
    {
        return [
            Invoice::STATUS_PENDING_WAREHOUSE_APPROVAL => 'در انتظار تایید انبار',
            Invoice::STATUS_COLLECTING => 'در حال جمع‌آوری',
            Invoice::STATUS_CHECKING_DISCREPANCY => 'بررسی مغایرت',
            Invoice::STATUS_FINAL_CHECK => 'کنترل نهایی',
            Invoice::STATUS_PACKING => 'بسته‌بندی',
            Invoice::STATUS_SHIPPED => 'ارسال‌شده',
            Invoice::STATUS_FINANCE_APPROVED => 'تایید مالی‌شده',
            Invoice::STATUS_NOT_SHIPPED => 'ارسال‌نشده/لغوشده',
            Invoice::STATUS_PENDING_FINANCE_REAPPROVAL => 'در انتظار تایید مالی مجدد',
        ];
    }
}
