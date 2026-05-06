<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>چاپ فاکتور {{ $invoice->uuid }}</title>
    <style>
        :root { --print-font: 10px; --print-border: #222; --print-muted: #666; }
        @page { size: A4 portrait; margin: 8mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Tahoma, "IRANSans", Arial, sans-serif;
            background: #f3f4f6;
            color: #111;
            direction: rtl;
            font-size: var(--print-font);
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .print-actions{
            width: 190mm; margin: 10px auto; display:flex; justify-content:space-between; gap:8px; align-items:center;
        }
        .print-actions-left { display:flex; gap:6px; align-items:center; }
        .btn {
            border: 1px solid #222; background: #fff; color:#111; text-decoration:none; border-radius:8px;
            padding: 6px 10px; font-size: 10px; cursor:pointer;
        }
        .btn-primary { background:#111; color:#fff; }
        .invoice-page{
            width:190mm; min-height:277mm; margin:0 auto 16px; background:#fff; padding:8mm;
            border:1px solid #d1d5db; border-radius:8px;
        }
        .invoice-header{ display:grid; grid-template-columns: 1fr auto; gap:8px; align-items:start; margin-bottom:8px; }
        .header-logo{ display:flex; align-items:center; gap:6px; font-weight:800; }
        .header-logo img{ width:32px; height:32px; object-fit:contain; }
        .invoice-title{ margin:0 0 4px; font-size:16px; font-weight:900; text-align:left; }
        .subtitle{ color:var(--print-muted); font-size:9px; text-align:left; }
        .info-grid{ display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-bottom:8px; }
        .info-box{ border:1px solid var(--print-border); border-radius:6px; padding:6px; page-break-inside:avoid; }
        .info-box-title{ font-weight:900; border-bottom:1px dashed #777; padding-bottom:3px; margin-bottom:4px; }
        .info-row{ display:grid; grid-template-columns:85px 1fr; gap:4px; margin-bottom:2px; line-height:1.4; }
        .info-label{ color:#333; font-weight:700; }
        .invoice-table{ width:100%; border-collapse:collapse; table-layout:fixed; margin-top:6px; }
        .invoice-table th,.invoice-table td{ border:1px solid var(--print-border); padding:3px 4px; line-height:1.35; vertical-align:middle; }
        .invoice-table th{ font-size:9.5px; font-weight:900; background:#f3f4f6; text-align:center; }
        .invoice-table td{ font-size:9.5px; }
        .col-index{ width:28px; text-align:center; }
        .col-qty{ width:42px; text-align:center; }
        .col-price,.col-total{ width:78px; text-align:left; direction:ltr; }
        .item-name{ font-weight:800; color:#111; }
        .item-variant{ margin-top:2px; font-size:8.8px; color:#555; line-height:1.3; max-height:2.7em; overflow:hidden; }
        .summary-wrap{ display:flex; justify-content:flex-start; margin-top:8px; page-break-inside:avoid; }
        .summary-table{ width:85mm; border-collapse:collapse; }
        .summary-table td{ border:1px solid var(--print-border); padding:4px 6px; font-size:10px; }
        .summary-table .final-row td{ background:#eef2f7; font-weight:900; }
        .notes-box{ border:1px solid var(--print-border); border-radius:6px; padding:6px; min-height:28px; margin-top:8px; page-break-inside:avoid; }
        .signature-section{ display:grid; grid-template-columns:repeat(3,1fr); gap:6px; margin-top:8px; page-break-inside:avoid; }
        .signature-box{ border:1px solid var(--print-border); border-radius:6px; height:50px; padding:6px; font-size:9px; }
        .signature-title{ font-weight:900; margin-bottom:14px; }

        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; }

        @media print{
            body{ background:#fff !important; margin:0; }
            .no-print,.print-actions{ display:none !important; }
            .invoice-page{ width:auto; min-height:auto; margin:0; padding:0; border:0; border-radius:0; box-shadow:none; }
            .invoice-table th,.invoice-table td{ padding:2.5px 3.5px; }
        }

        body.print-a5 .invoice-page, body.print-a5 .print-actions { width:132mm; }
        body.print-a5 .invoice-table th, body.print-a5 .invoice-table td { font-size:8.5px; padding:2px 3px; }
        body.print-a5 .info-grid { grid-template-columns:1fr; }
        body.print-a5 .summary-table { width:100%; }
    </style>
</head>
<body class="print-a4">
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

<div class="print-actions no-print">
    <div class="print-actions-left">
        <a class="btn" href="{{ route('vouchers.sales.show', $invoice->uuid) }}">بازگشت به حواله</a>
        <button type="button" class="btn" onclick="document.body.classList.remove('print-a5');document.body.classList.add('print-a4');">A4</button>
        <button type="button" class="btn" onclick="document.body.classList.remove('print-a4');document.body.classList.add('print-a5');">A5</button>
    </div>
    <button type="button" class="btn btn-primary" onclick="window.print()">چاپ</button>
</div>

<main class="invoice-page">
    <header class="invoice-header">
        <div class="header-logo">
            <img src="{{ asset('logo.png') }}" alt="{{ $companyName }}">
            <div>
                <div style="font-size:12px;font-weight:800">{{ $companyName }}</div>
                <div style="font-size:9px;color:#666">سامانه فروش و انبار</div>
            </div>
        </div>
        <div>
            <h1 class="invoice-title">فاکتور فروش / حواله فروش</h1>
            <div class="subtitle">نسخه چاپی انبار</div>
        </div>
    </header>

    <section class="info-grid">
        <div class="info-box">
            <div class="info-box-title">مشخصات فاکتور</div>
            <div class="info-row"><div class="info-label">شماره فاکتور:</div><div><strong>{{ $invoice->uuid }}</strong></div></div>
            <div class="info-row"><div class="info-label">تاریخ:</div><div>{{ $fmtDate($invoice->created_at) }}</div></div>
            <div class="info-row"><div class="info-label">شماره سفارش:</div><div>{{ $orderRef ?? '—' }}</div></div>
            <div class="info-row"><div class="info-label">ثبت‌کننده:</div><div>{{ $sellerName ?? '—' }}</div></div>
        </div>
        <div class="info-box">
            <div class="info-box-title">مشخصات مشتری</div>
            <div class="info-row"><div class="info-label">نام مشتری:</div><div>{{ $invoice->customer_name ?: '—' }}</div></div>
            <div class="info-row"><div class="info-label">موبایل:</div><div>{{ $invoice->customer_mobile ?: '—' }}</div></div>
            @if(!empty($invoice->customer_address))
                <div class="info-row"><div class="info-label">آدرس:</div><div>{{ $invoice->customer_address }}</div></div>
            @endif
        </div>
    </section>

    <section>
        <table class="invoice-table">
            <thead>
            <tr>
                <th class="col-index">ردیف</th>
                <th>نام کالا / تنوع</th>
                <th class="col-qty">تعداد</th>
                <th class="col-price">قیمت واحد</th>
                <th class="col-total">مبلغ کل</th>
            </tr>
            </thead>
            <tbody>
            @forelse($invoice->items as $item)
                @php
                    $lineTotal = (int) ($item->line_total ?: ((int) $item->quantity * (int) $item->price));
                    $variantName = $item->variant?->variant_name ?? '—';
                @endphp
                <tr>
                    <td class="col-index">{{ $loop->iteration }}</td>
                    <td>
                        <div class="item-name">{{ $item->product?->name ?? ('#' . $item->product_id) }}</div>
                        <div class="item-variant">{{ $variantName }}</div>
                    </td>
                    <td class="col-qty">{{ number_format((int) $item->quantity) }}</td>
                    <td class="col-price">{{ number_format((int) $item->price) }}</td>
                    <td class="col-total">{{ number_format($lineTotal) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="col-index">آیتمی برای این فاکتور ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>

    <section class="summary-wrap">
        <table class="summary-table invoice-summary">
            <tr>
                <td>جمع جزء</td>
                <td style="text-align:left;direction:ltr">{{ $money($invoice->subtotal) }}</td>
            </tr>
            <tr>
                <td>تخفیف</td>
                <td style="text-align:left;direction:ltr">{{ $money($invoice->discount_amount) }}</td>
            </tr>
            <tr>
                <td>هزینه ارسال</td>
                <td style="text-align:left;direction:ltr">{{ $money($invoice->shipping_price) }}</td>
            </tr>
            @if($taxAmount > 0)
                <tr>
                    <td>مالیات</td>
                    <td style="text-align:left;direction:ltr">{{ $money($taxAmount) }}</td>
                </tr>
            @endif
            <tr class="final-row">
                <td>مبلغ نهایی قابل پرداخت</td>
                <td style="text-align:left;direction:ltr">{{ $money($finalTotal) }}</td>
            </tr>
        </table>
    </section>

    <section class="notes-box">
        <div style="font-weight:900;margin-bottom:4px">توضیحات</div>
        <div>{{ $firstNote ?: '—' }}</div>
    </section>

    <section class="signature-section">
        <div class="signature-box">
            <div class="signature-title">امضای صادرکننده</div>
        </div>
        <div class="signature-box">
            <div class="signature-title">امضای مشتری / تحویل‌گیرنده</div>
        </div>
        <div class="signature-box">
            <div class="signature-title">مهر شرکت</div>
        </div>
    </section>
</main>
</body>
</html>
