<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\SalesHavalehStatusService;
use App\Services\SalesHavalehService;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly SalesHavalehStatusService $statusService,
        private readonly SalesHavalehService $salesHavalehService,
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'date' => trim((string) $request->query('date', '')),
            'invoice_number' => trim((string) $request->query('invoice_number', $request->query('q', ''))),
            'customer_code' => trim((string) $request->query('customer_code', '')),
            'customer_name' => trim((string) $request->query('customer_name', '')),
        ];

        $reportDateInput = trim((string) $request->query('export_date', $filters['date']));
        $reportDate = $this->parseInvoiceFilterDate($reportDateInput) ?? now()->startOfDay();
        $filterDate = $this->parseInvoiceFilterDate($filters['date']);

        $baseQuery = Invoice::query()
            ->with(['payments.cheque', 'customer:id,crm_customer_id,first_name,last_name,mobile', 'preinvoiceOrder.creator:id,name'])
            ->withSum('payments as paid_total', 'amount')
            ->when($filters['invoice_number'] !== '', function ($query) use ($filters) {
                $query->where('uuid', 'like', '%' . $filters['invoice_number'] . '%');
            })
            ->when($filters['customer_name'] !== '', function ($query) use ($filters) {
                $name = $filters['customer_name'];
                $query->where(function ($qq) use ($name) {
                    $qq->where('customer_name', 'like', "%{$name}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($name) {
                            $customerQuery->where('first_name', 'like', "%{$name}%")
                                ->orWhere('last_name', 'like', "%{$name}%")
                                ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ["%{$name}%"]);
                        });
                });
            })
            ->when($filters['customer_code'] !== '', function ($query) use ($filters) {
                $code = $this->normalizeDigits($filters['customer_code']);
                $query->where(function ($qq) use ($code) {
                    if (ctype_digit($code)) {
                        $qq->where('customer_id', (int) $code);
                    }

                    $qq->orWhereHas('customer', function ($customerQuery) use ($code) {
                        $customerQuery->where('id', 'like', "%{$code}%")
                            ->orWhere('crm_customer_id', 'like', "%{$code}%")
                            ->orWhere('mobile', 'like', "%{$code}%");
                    });
                });
            });

        if ($filterDate) {
            $baseQuery->whereDate('created_at', $filterDate->toDateString());
        }

        if ($request->input('export') === 'daily_csv') {
            $exportQuery = clone $baseQuery;
            if ($filters['date'] === '') {
                $exportQuery->whereDate('created_at', $reportDate->toDateString());
            }

            return $this->exportDailyCustomerFinanceCsv($exportQuery->orderBy('id')->get(), $reportDate);
        }

        $invoices = $baseQuery
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $statusLabels = $this->statusService->labels();
        $q = $filters['invoice_number'];
        $dateInput = $filters['date'];

        return view('invoices.index', compact('invoices', 'q', 'statusLabels', 'dateInput', 'filters', 'reportDateInput'));
    }

    public function salesVouchers(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $allowedStatuses = $this->statusService->all();

        $invoices = Invoice::query()
            ->with(['items.product', 'items.variant'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('uuid', 'like', "%{$q}%")
                        ->orWhere('customer_name', 'like', "%{$q}%")
                        ->orWhere('customer_mobile', 'like', "%{$q}%");
                });
            })
            ->when(in_array($status, $allowedStatuses, true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->whereIn('status', $allowedStatuses)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $statusLabels = $this->statusService->labels();

        return view('vouchers.sales.index', compact('invoices', 'q', 'status', 'statusLabels', 'allowedStatuses'));
    }

    public function salesVoucherShow(string $uuid)
    {
        $invoice = Invoice::query()
            ->with(['items.product', 'items.variant', 'histories.actor'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $statusLabels = $this->statusService->labels();

        return view('vouchers.sales.show', compact('invoice', 'statusLabels'));
    }

    public function salesVoucherEdit(string $uuid)
    {
        $invoice = Invoice::query()
            ->with(['items.product', 'items.variant'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $statusLabels = $this->statusService->labels();
        $canEditItems = $this->statusService->isEditable($invoice, auth()->user());

        return view('vouchers.sales.edit', compact('invoice', 'statusLabels', 'canEditItems'));
    }

    public function salesVoucherUpdate(string $uuid, Request $request)
    {
        $invoice = Invoice::query()->with('items')->where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:invoice_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|integer|min:0',
        ]);

        $this->salesHavalehService->updateItems($invoice, $data['items'], auth()->id());

        return redirect()->route('vouchers.sales.edit', $invoice->uuid)
            ->with('success', '✅ آیتم‌های حواله فروش با موفقیت بروزرسانی شد.');
    }

    public function salesVoucherHistory(string $uuid)
    {
        $invoice = Invoice::query()->with('histories.actor')->where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'invoice_uuid' => $invoice->uuid,
            'history' => $invoice->histories->map(fn ($h) => [
                'action_type' => $h->action_type,
                'field_name' => $h->field_name,
                'old_value' => $h->old_value,
                'new_value' => $h->new_value,
                'description' => $h->description,
                'done_by' => $h->actor?->name,
                'done_at' => optional($h->done_at)->toDateTimeString(),
            ])->values(),
        ]);
    }

    public function edit(string $uuid)
    {
        $invoice = Invoice::query()
            ->with(['items.product', 'items.variant'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return view('invoices.edit', compact('invoice'));
    }

    public function update(string $uuid, Request $request)
    {
        $invoice = Invoice::query()->with('items')->where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_mobile' => 'required|string|max:50',
            'customer_address' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:invoice_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($invoice, $data) {
            $subtotal = 0;

            foreach ($data['items'] as $row) {
                $item = $invoice->items->firstWhere('id', (int) $row['id']);
                if (!$item) {
                    continue;
                }

                $lineTotal = (int) $row['quantity'] * (int) $row['price'];

                $item->update([
                    'quantity' => (int) $row['quantity'],
                    'price' => (int) $row['price'],
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineTotal;
            }

            $total = max($subtotal + (int) $invoice->shipping_price - (int) $invoice->discount_amount, 0);

            $invoice->update([
                'customer_name' => $data['customer_name'],
                'customer_mobile' => $data['customer_mobile'],
                'customer_address' => $data['customer_address'] ?? '',
                'subtotal' => $subtotal,
                'total' => $total,
            ]);
        });

        return redirect()->route('invoices.show', $invoice->uuid)->with('success', '✅ فاکتور ویرایش شد.');
    }

    public function print(string $uuid)
    {
        $invoice = Invoice::query()
            ->with([
                'items.product',
                'items.variant',
                'preinvoiceOrder.creator',
                'shippingMethod:id,name,price',
            ])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return view('invoices.print', compact('invoice'));
    }

    public function show(string $uuid)
    {
        $invoice = Invoice::query()
            ->with([
                'items.product',
                'items.variant',
                'payments.cheque',
                'payments.creator',
                'notes',
                'preinvoiceOrder.creator:id,name',
            ])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $canFinanceApprove = $this->canHandleFinanceActions();
        $statusLabels = $this->statusService->labels();

        return view('invoices.show', compact('invoice', 'canFinanceApprove', 'statusLabels'));
    }

    private function canHandleFinanceActions(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasAnyRole(['admin', 'finance']) || $user->can('finance.approve'));
    }

    public function updateStatus(string $uuid, Request $request)
    {
        $invoice = Invoice::where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'status' => 'required|string',
            'note' => 'nullable|string|max:1000',
        ]);

        $this->salesHavalehService->changeStatus($invoice, $data['status'], $data['note'] ?? null, auth()->id());

        return back()->with('success', '✅ وضعیت بروزرسانی شد.');
    }

    public function cancel(string $uuid, Request $request)
    {
        $invoice = Invoice::where('uuid', $uuid)->firstOrFail();
        $data = $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);
        $this->salesHavalehService->cancelAndRestore($invoice, $data['note'] ?? null, auth()->id());

        return back()->with('success', '✅ فاکتور کنسل شد و موجودی به انبار برگشت.');
    }

    private function parseInvoiceFilterDate(string $dateInput): ?Carbon
    {
        $dateInput = $this->normalizeDigits($dateInput);

        if ($dateInput === '') {
            return null;
        }

        $dateInput = str_replace(['-', '.', ' '], '/', $dateInput);

        try {
            if (preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $dateInput) === 1) {
                [$year, $month, $day] = array_map('intval', explode('/', $dateInput));

                if ($year >= 1300 && $year <= 1600) {
                    return (new Jalalian($year, $month, $day))->toCarbon()->startOfDay();
                }

                return Carbon::create($year, $month, $day)->startOfDay();
            }

            return Carbon::parse($dateInput)->startOfDay();
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

    private function exportDailyCustomerFinanceCsv($invoices, Carbon $reportDate): StreamedResponse
    {
        $filename = 'daily-customer-finance-' . $reportDate->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($invoices, $reportDate) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'report_date',
                'customer_name',
                'customer_mobile',
                'invoice_number',
                'invoice_date',
                'row_type',
                'amount',
                'payment_method',
                'payment_date',
                'payment_bank_name',
                'payment_identifier',
                'cheque_number',
                'cheque_due_date',
                'cheque_received_at',
                'cheque_bank_name',
                'cheque_branch_name',
                'cheque_account_number',
                'cheque_account_holder',
                'cheque_customer_name',
                'cheque_customer_code',
                'cheque_status',
                'note',
            ]);

            foreach ($invoices as $invoice) {
                fputcsv($handle, [
                    $reportDate->toDateString(),
                    $invoice->customer_name ?? '',
                    $invoice->customer_mobile ?? '',
                    $invoice->uuid,
                    optional($invoice->created_at)->format('Y-m-d'),
                    'invoice',
                    (int) $invoice->total,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                ]);

                foreach ($invoice->payments as $payment) {
                    $cheque = $payment->cheque;
                    fputcsv($handle, [
                        $reportDate->toDateString(),
                        $invoice->customer_name ?? '',
                        $invoice->customer_mobile ?? '',
                        $invoice->uuid,
                        optional($invoice->created_at)->format('Y-m-d'),
                        'payment',
                        (int) $payment->amount,
                        $payment->method,
                        $payment->paid_at,
                        $payment->bank_name ?? '',
                        $payment->payment_identifier ?? '',
                        $cheque?->cheque_number ?? '',
                        $cheque?->due_date ?? '',
                        $cheque?->received_at ?? '',
                        $cheque?->bank_name ?? '',
                        $cheque?->branch_name ?? '',
                        $cheque?->account_number ?? '',
                        $cheque?->account_holder ?? '',
                        $cheque?->customer_name ?? '',
                        $cheque?->customer_code ?? '',
                        $cheque?->status ?? '',
                        $payment->note ?? '',
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
