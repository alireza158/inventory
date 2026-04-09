<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>چاپ فاکتور {{ $invoice->uuid }}</title>
    <style>
        @page { size: A4; margin: 12mm; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Tahoma, "IRANSans", Arial, sans-serif;
            color: #111;
            background: #f3f4f6;
            line-height: 1.6;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .print-toolbar {
            max-width: 210mm;
            margin: 14px auto 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .btn {
            border: 1px solid #111;
            background: #fff;
            padding: 7px 14px;
            border-radius: 8px;
            color: #111;
            text-decoration: none;
            font-size: 13px;
            cursor: pointer;
        }

        .btn-primary {
            background: #111;
            color: #fff;
        }

        .invoice-sheet {
            width: 100%;
            max-width: 210mm;
            min-height: 297mm;
            margin: 10px auto 20px;
            background: #fff;
            border: 1px solid #d4d4d8;
            border-radius: 10px;
            padding: 16mm 14mm;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand img {
            width: 58px;
            height: 58px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 6px;
        }

        .title {
            text-align: left;
        }

        .title h1 {
            margin: 0;
            font-size: 21px;
            letter-spacing: .2px;
        }

        .subtitle {
            color: #555;
            font-size: 12px;
            margin-top: 4px;
        }

        .box-grid {
            margin-top: 8px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .box {
            border: 1px solid #222;
            border-radius: 8px;
            padding: 10px;
            page-break-inside: avoid;
        }

        .box h3 {
            margin: 0 0 8px;
            font-size: 14px;
            border-bottom: 1px dashed #777;
            padding-bottom: 5px;
        }

        .kv {
            display: flex;
            gap: 8px;
            margin-bottom: 4px;
            font-size: 13px;
        }

        .kv .k {
            min-width: 96px;
            color: #444;
        }

        .items {
            margin-top: 12px;
            border: 1px solid #222;
            border-radius: 8px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #222;
            padding: 7px 6px;
            font-size: 12px;
            vertical-align: middle;
        }

        th {
            background: #f3f4f6;
            font-weight: 700;
            text-align: center;
        }

        td.text-center { text-align: center; }
        td.text-left { text-align: left; }

        .summary-wrap {
            margin-top: 10px;
            display: flex;
            justify-content: flex-end;
        }

        .summary {
            width: 320px;
            border: 1px solid #222;
            border-radius: 8px;
            overflow: hidden;
        }

        .summary td {
            font-size: 13px;
            padding: 7px 10px;
        }

        .summary .total-row td {
            font-weight: 800;
            background: #eceff3;
        }

        .notes {
            margin-top: 12px;
            border: 1px solid #222;
            border-radius: 8px;
            padding: 9px 10px;
            min-height: 64px;
            font-size: 12px;
            page-break-inside: avoid;
        }

        .notes h4 {
            margin: 0 0 6px;
            font-size: 13px;
        }

        .signatures {
            margin-top: 16px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
            page-break-inside: avoid;
        }

        .sign-box {
            border: 1px solid #222;
            border-radius: 8px;
            min-height: 92px;
            padding: 8px;
            font-size: 12px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .muted { color: #555; }

        @media print {
            body {
                background: #fff !important;
                margin: 0 !important;
            }

            .print-toolbar {
                display: none !important;
            }

            .invoice-sheet {
                margin: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                max-width: 100% !important;
                min-height: auto !important;
                padding: 0 !important;
            }

            tr, td, th, .box, .notes, .signatures, .summary {
                page-break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>
@php
    use Morilog\Jalali\Jalalian;

    $fmtDate = function($value, string $format = 'Y/m/d H:i') {
        if (!$value) {
            return '—';
        }
        return Jalalian::fromDateTime($value)->format($format);
    };

    $money = fn($value) => number_format((int) ($value ?? 0)) . ' تومان';

    $companyName = config('app.name', 'شرکت');
    $taxAmount = (int) ($invoice->tax_amount ?? 0);
    $finalTotal = (int) $invoice->total + $taxAmount;
    $orderRef = $invoice->preinvoiceOrder?->uuid ? ('PI-' . $invoice->preinvoiceOrder->uuid) : null;
    $sellerName = $invoice->preinvoiceOrder?->creator?->name ?? null;
    $firstNote = $invoice->notes->first()?->body ?? null;
@endphp

<div class="print-toolbar">
    <a class="btn" href="{{ route('invoices.show', $invoice->uuid) }}">بازگشت به فاکتور</a>
    <button type="button" class="btn btn-primary" onclick="window.print()">چاپ</button>
</div>

<main class="invoice-sheet">
    <header class="header">
        <div class="brand">
            <img src="{{ asset('logo.png') }}" alt="{{ $companyName }}">
            <div>
                <div style="font-size:18px;font-weight:800">{{ $companyName }}</div>
                <div class="subtitle">سامانه فروش و انبار</div>
            </div>
        </div>
        <div class="title">
            <h1>فاکتور فروش</h1>
            <div class="subtitle">نسخه قابل چاپ و بایگانی</div>
        </div>
    </header>

    <section class="box-grid">
        <div class="box">
            <h3>مشخصات فاکتور</h3>
            <div class="kv"><div class="k">شماره فاکتور:</div><div><strong>{{ $invoice->uuid }}</strong></div></div>
            <div class="kv"><div class="k">تاریخ فاکتور:</div><div>{{ $fmtDate($invoice->created_at) }}</div></div>
            <div class="kv"><div class="k">شماره سفارش:</div><div>{{ $orderRef ?? '—' }}</div></div>
            <div class="kv"><div class="k">شماره مرجع:</div><div>{{ $invoice->id }}</div></div>
            <div class="kv"><div class="k">ثبت‌کننده:</div><div>{{ $sellerName ?? '—' }}</div></div>
        </div>
        <div class="box">
            <h3>مشخصات مشتری</h3>
            <div class="kv"><div class="k">نام مشتری:</div><div>{{ $invoice->customer_name ?: '—' }}</div></div>
            <div class="kv"><div class="k">شماره تماس:</div><div>{{ $invoice->customer_mobile ?: '—' }}</div></div>
            <div class="kv"><div class="k">آدرس:</div><div>{{ $invoice->customer_address ?: '—' }}</div></div>
            <div class="kv"><div class="k">کد/شناسه:</div><div>{{ $invoice->customer?->national_code ?? '—' }}</div></div>
        </div>
    </section>

    <section class="items">
        <table>
            <thead>
            <tr>
                <th style="width:50px">ردیف</th>
                <th>نام کالا</th>
                <th>تنوع/مدل/مشخصات</th>
                <th style="width:70px">تعداد</th>
                <th style="width:70px">واحد</th>
                <th style="width:130px">قیمت واحد</th>
                <th style="width:150px">مبلغ کل ردیف</th>
            </tr>
            </thead>
            <tbody>
            @forelse($invoice->items as $item)
                @php
                    $lineTotal = (int) ($item->line_total ?: ((int) $item->quantity * (int) $item->price));
                    $variantName = $item->variant?->variant_name ?? '—';
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $item->product?->name ?? ('#' . $item->product_id) }}</td>
                    <td>{{ $variantName }}</td>
                    <td class="text-center">{{ number_format((int) $item->quantity) }}</td>
                    <td class="text-center">عدد</td>
                    <td class="text-left">{{ number_format((int) $item->price) }}</td>
                    <td class="text-left">{{ number_format($lineTotal) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center muted">آیتمی برای این فاکتور ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>

    <section class="summary-wrap">
        <table class="summary">
            <tr>
                <td>جمع جزء</td>
                <td class="text-left">{{ $money($invoice->subtotal) }}</td>
            </tr>
            <tr>
                <td>تخفیف</td>
                <td class="text-left">{{ $money($invoice->discount_amount) }}</td>
            </tr>
            <tr>
                <td>هزینه ارسال</td>
                <td class="text-left">{{ $money($invoice->shipping_price) }}</td>
            </tr>
            @if($taxAmount > 0)
                <tr>
                    <td>مالیات</td>
                    <td class="text-left">{{ $money($taxAmount) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td>مبلغ نهایی قابل پرداخت</td>
                <td class="text-left">{{ $money($finalTotal) }}</td>
            </tr>
        </table>
    </section>

    <section class="notes">
        <h4>توضیحات / شرایط پرداخت و ارسال</h4>
        <div>{{ $firstNote ?: '—' }}</div>
    </section>

    <section class="signatures">
        <div class="sign-box">
            <div><strong>امضای صادرکننده</strong></div>
            <div class="muted">نام و امضا</div>
        </div>
        <div class="sign-box">
            <div><strong>امضای مشتری / تحویل‌گیرنده</strong></div>
            <div class="muted">نام، تاریخ و امضا</div>
        </div>
        <div class="sign-box">
            <div><strong>مهر شرکت</strong></div>
            <div class="muted">محل درج مهر</div>
        </div>
    </section>
</main>
</body>
</html>
