@php
    $pdfText = fn ($value) => \App\Services\ProductExportService::pdfText((string) $value);
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page{margin:22px 24px}body{font-family:DejaVu Sans,sans-serif;color:#111827;font-size:11px}.page{direction:ltr}.header{border-bottom:3px solid #0f766e;padding-bottom:12px;margin-bottom:16px;text-align:right}.store{font-size:20px;font-weight:900;color:#0f766e}.title{font-size:18px;font-weight:900;margin-top:8px}.meta{background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:10px;margin-bottom:14px;line-height:2;text-align:right}.table{width:100%;border-collapse:collapse;table-layout:fixed}.table th{background:#0f766e;color:#fff;font-weight:900}.table th,.table td{border:1px solid #d1d5db;padding:7px;text-align:right;vertical-align:middle}.thumb{width:42px;height:42px;object-fit:cover;border-radius:8px}.status{font-weight:900}.success{color:#15803d}.warning{color:#a16207}.danger{color:#b91c1c}.ltr{direction:ltr;text-align:left}.rtl{text-align:right}.price{white-space:nowrap}.image-col{width:56px}.stock-col{width:72px}.status-col{width:85px}.date-col{width:120px}.sku-col{width:155px}.category-col{width:85px}
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="store">{{ $pdfText($meta['store_name']) }}</div>
        <div class="title">{{ $pdfText('گزارش محصولات') }}</div>
        <div>{{ $pdfText('تاریخ دریافت خروجی') }}: <span class="ltr">{{ $meta['exported_at'] }}</span></div>
    </div>
    <div class="meta">
        {{ $pdfText('دسته‌بندی انتخاب‌شده') }}: <strong>{{ $pdfText($meta['category']) }}</strong>
        &nbsp; | &nbsp; {{ $pdfText('انبار') }}: <strong>{{ $pdfText($meta['warehouse']) }}</strong>
        &nbsp; | &nbsp; {{ $pdfText('وضعیت موجودی') }}: <strong>{{ $pdfText($meta['stock_status']) }}</strong>
        @if($meta['search']) &nbsp; | &nbsp; {{ $pdfText('جستجو') }}: <strong>{{ $pdfText($meta['search']) }}</strong> @endif
    </div>
    <table class="table">
        <thead>
        <tr>
            <th class="image-col">{{ $pdfText('تصویر') }}</th>
            <th>{{ $pdfText('نام محصول') }}</th>
            <th class="sku-col ltr">SKU/{{ $pdfText('کد') }}</th>
            <th class="category-col">{{ $pdfText('دسته‌بندی') }}</th>
            <th class="stock-col">{{ $pdfText('موجودی') }}</th>
            <th>{{ $pdfText('قیمت فروش') }}</th>
            <th class="status-col">{{ $pdfText('وضعیت') }}</th>
            <th class="date-col">{{ $pdfText('آخرین بروزرسانی') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            <tr>
                <td><img class="thumb" src="{{ $row['pdf_image_src'] }}" alt=""></td>
                <td class="rtl">{{ $pdfText($row['name']) }}</td>
                <td class="ltr">{{ $row['sku'] }}</td>
                <td class="rtl">{{ $pdfText($row['category']) }}</td>
                <td>{{ number_format($row['stock']) }} {{ $pdfText($row['unit']) }}</td>
                <td class="price">{{ number_format($row['price']) }} {{ $pdfText('تومان') }}</td>
                <td class="status {{ $row['stock_status_class'] }}">{{ $pdfText($row['stock_status']) }}</td>
                <td class="ltr">{{ $row['updated_at'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
</body>
</html>
