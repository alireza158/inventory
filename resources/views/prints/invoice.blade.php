<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $printData['title'] }} {{ $printData['number'] }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        * { box-sizing: border-box; }
        html, body { margin:0; padding:0; direction:rtl; }
        body { font-family: Tahoma, Arial, sans-serif; color:#111827; background:#eef2f7; font-size:11px; line-height:1.65; }
        .no-print { width:190mm; margin:12px auto; display:flex; justify-content:space-between; gap:8px; }
        .btn { border:1px solid #111827; background:#fff; color:#111827; border-radius:8px; padding:7px 12px; text-decoration:none; cursor:pointer; }
        .btn-primary { background:#111827; color:#fff; }
        .invoice-page { width:190mm; margin:0 auto 12px; background:#fff; padding:8mm; border:1px solid #d1d5db; }
        .invoice-header { display:grid; grid-template-columns:1fr auto; gap:12px; align-items:start; border-bottom:2px solid #111827; padding-bottom:8px; margin-bottom:8px; }
        .brand { display:flex; align-items:center; gap:10px; }
        .brand img { width:72px; max-height:72px; object-fit:contain; }
        .brand-name { font-size:16px; font-weight:900; }
        .doc-title { margin:0 0 4px; font-size:20px; font-weight:900; text-align:left; }
        .doc-meta { text-align:left; color:#374151; }
        .info-grid { display:grid; grid-template-columns:1.35fr 1fr; gap:8px; margin-bottom:9px; }
        .info-box, .company-box { border:1px solid #374151; border-radius:8px; padding:7px; page-break-inside:avoid; break-inside:avoid; }
        .info-title { font-weight:900; padding-bottom:4px; margin-bottom:5px; border-bottom:1px dashed #9ca3af; }
        .info-row { display:grid; grid-template-columns:92px 1fr; gap:5px; margin-bottom:2px; }
        .label { color:#374151; font-weight:800; }
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        .items-table th, .items-table td { border:1px solid #374151; padding:5px; vertical-align:middle; }
        .items-table th { background:#f3f4f6; text-align:center; font-weight:900; }
        thead { display:table-header-group; }
        tfoot { display:table-footer-group; }
        tr { page-break-inside:avoid; break-inside:avoid; }
        .col-index { width:8mm; text-align:center; }
        .col-desc { width:auto; }
        .col-code { width:26mm; text-align:center; direction:ltr; unicode-bidi:embed; }
        .col-map { width:39mm; text-align:center; direction:ltr; unicode-bidi:embed; font-size:10px; }
        .col-qty { width:13mm; text-align:center; }
        .col-total { width:29mm; text-align:left; direction:ltr; unicode-bidi:embed; }
        body.customer-mode .warehouse-only { display:none; }
        .summary-section { display:grid; grid-template-columns:1fr 78mm; gap:10px; margin-top:10px; page-break-inside:avoid; break-inside:avoid; }
        .summary-table td { border:1px solid #374151; padding:5px 7px; }
        .summary-table td:last-child { text-align:left; direction:ltr; }
        .final-row td { background:#f3f4f6; font-weight:900; }
        .company-box { margin-top:10px; }
        .signature-section { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:12px; page-break-inside:avoid; break-inside:avoid; }
        .signature-box { height:68px; border:1px solid #374151; border-radius:8px; padding:8px; font-weight:900; }
        @media print {
            body { background:#fff !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .no-print { display:none !important; }
            .invoice-page { width:auto; margin:0; padding:0; border:0; }
            a[href]:after { content:"" !important; }
        }
    </style>
</head>
<body class="{{ $printData['mode'] === 'customer' ? 'customer-mode' : 'warehouse-mode' }}">
@php
    $fmtDate = function ($value, string $format = 'Y/m/d H:i') {
        if (!$value) return '—';
        try { return \Morilog\Jalali\Jalalian::fromDateTime($value)->format($format); } catch (\Throwable $e) { return (string) $value; }
    };
    $money = fn ($value) => \App\Support\Currency::formatRial((int) $value);
    $dash = fn ($value) => filled($value) ? $value : '—';
    $showWarehouseMap = $printData['mode'] !== 'customer';
    $itemColspan = $showWarehouseMap ? 6 : 5;
@endphp
<div class="no-print">
    <a class="btn" href="{{ $printData['backUrl'] }}">بازگشت</a>
    <div>
        <a class="btn" href="?mode=customer">نسخه مشتری</a>
        <a class="btn" href="?mode=warehouse">نسخه انبار</a>
        <button class="btn btn-primary" onclick="window.print()">چاپ</button>
    </div>
</div>
<main class="invoice-page">
    <header class="invoice-header">
        <div class="brand"><img src="{{ $printData['logo'] }}" alt="{{ $printData['company']['name'] }}"><div><div class="brand-name">{{ $printData['company']['name'] }}</div><div>{{ $printData['company']['phone'] }}</div></div></div>
        <div><h1 class="doc-title">{{ $printData['title'] }}</h1><div class="doc-meta">
            <div>{{ $printData['numberLabel'] }}: <strong>{{ $printData['number'] }}</strong></div>
            @if($printData['customerNumber'])<div>شماره فاکتور مشتری: {{ $printData['customerNumber'] }}</div>@endif
            <div>تاریخ ثبت پیش‌فاکتور: {{ $fmtDate($printData['registeredAt']) }}</div>
            @if($printData['issuedAt'])<div>تاریخ صدور فاکتور: {{ $fmtDate($printData['issuedAt']) }}</div>@endif
            @if($printData['status'])<div>وضعیت سند: {{ $printData['status'] }}</div>@endif
        </div></div>
    </header>
    <section class="info-grid">
        <div class="info-box"><div class="info-title">مشخصات مشتری</div>
            <div class="info-row"><span class="label">نام مشتری:</span><strong>{{ $dash($printData['customer']['name']) }}</strong></div>
            <div class="info-row"><span class="label">شماره تماس:</span><span>{{ $dash($printData['customer']['mobile']) }}</span></div>
            <div class="info-row"><span class="label">کد مشتری:</span><span>{{ $dash($printData['customer']['code']) }}</span></div>
            <div class="info-row"><span class="label">آدرس:</span><span>{{ $dash($printData['customer']['address']) }}</span></div>
        </div>
        <div class="info-box"><div class="info-title">اطلاعات ارسال</div>
            <div class="info-row"><span class="label">روش ارسال:</span><span>{{ $dash($printData['shipping']['method']) }}</span></div>
            <div class="info-row"><span class="label">هزینه ارسال:</span><span>{{ $money($printData['shipping']['cost']) }}</span></div>
            @if(filled($printData['shipping']['description']))<div class="info-row"><span class="label">توضیحات:</span><span>{{ $printData['shipping']['description'] }}</span></div>@endif
        </div>
    </section>
    <table class="items-table">
        <thead><tr><th class="col-index">ردیف</th><th class="col-desc">شرح کالا</th><th class="col-code">کد انبار</th><th class="col-map warehouse-only">نقشه انبار</th><th class="col-qty">تعداد</th><th class="col-total">مبلغ کل</th></tr></thead>
        <tbody>
        @forelse($printData['items'] as $item)
            <tr><td class="col-index">{{ $loop->iteration }}</td><td class="col-desc">{{ $item['description'] }}</td><td class="col-code">{{ $item['inventoryCode'] }}</td><td class="col-map warehouse-only">{{ $item['warehouseMap'] }}</td><td class="col-qty">{{ number_format($item['quantity']) }}</td><td class="col-total">{{ number_format($item['lineTotal']) }}</td></tr>
        @empty
            <tr><td colspan="{{ $itemColspan }}" style="text-align:center">آیتمی ثبت نشده است.</td></tr>
        @endforelse
        </tbody>
    </table>
    <section class="summary-section">
        <div></div><table class="summary-table"><tbody>
            <tr><td>جمع کالاها</td><td>{{ $money($printData['totals']['subtotal']) }}</td></tr>
            <tr><td>تخفیف</td><td>{{ $money($printData['totals']['discount']) }}</td></tr>
            <tr><td>هزینه ارسال</td><td>{{ $money($printData['totals']['shipping']) }}</td></tr>
            @if(!is_null($printData['totals']['paid']))<tr><td>مبلغ پرداخت‌شده</td><td>{{ $money($printData['totals']['paid']) }}</td></tr>@endif
            @if(!is_null($printData['totals']['remaining']))<tr><td>مانده</td><td>{{ $money($printData['totals']['remaining']) }}</td></tr>@endif
            <tr class="final-row"><td>مبلغ نهایی</td><td>{{ $money($printData['totals']['total']) }}</td></tr>
        </tbody></table>
    </section>
    <section class="company-box"><div class="info-title">اطلاعات شرکت و پرداخت</div>
        <div><strong>{{ $printData['company']['name'] }}</strong> | تلفن: {{ $printData['company']['phone'] }}</div>
        <div>آدرس: {{ $printData['company']['address'] }}</div>
        @if(filled($printData['company']['bank_account']))<div>شماره حساب شرکت: {{ $printData['company']['bank_account'] }}</div>@endif
        @if(filled($printData['company']['sheba']))<div>شماره شبا شرکت: {{ $printData['company']['sheba'] }}</div>@endif
    </section>
    <section class="signature-section"><div class="signature-box">امضا و مهر فروشنده</div><div class="signature-box">امضای مشتری / تحویل‌گیرنده</div></section>
</main>
</body>
</html>
