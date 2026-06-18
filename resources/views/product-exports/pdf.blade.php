@php
    $pdfText = fn ($value) => \App\Services\ProductExportService::pdfText((string) $value);
    $money = fn ($value) => number_format((int) $value) . ' ' . $pdfText('تومان');
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        @font-face{font-family:'Vazirmatn';src:url('{{ public_path('fonts/vazirmatn/Vazirmatn-Regular.woff2') }}') format('woff2');font-weight:400;font-style:normal;font-display:swap}
        @font-face{font-family:'Vazirmatn';src:url('{{ public_path('fonts/vazirmatn/Vazirmatn-Bold.woff2') }}') format('woff2');font-weight:700;font-style:normal;font-display:swap}
        @page{margin:12mm 10mm}
        html,body{margin:0;padding:0;direction:rtl;text-align:right;font-family:'Vazirmatn','DejaVu Sans',sans-serif;font-size:11px;line-height:1.75;color:#111827;background:#fff}
        *{box-sizing:border-box}.report-header{border-bottom:2px solid #0f766e;padding-bottom:10px;margin-bottom:12px}.report-title{font-size:20px;font-weight:bold;color:#0f766e;margin-bottom:4px}.report-subtitle{font-size:11px;color:#4b5563;margin-bottom:3px}.filter-box{border:1px solid #e5e7eb;background:#f8fafc;border-radius:8px;padding:8px 10px;margin-bottom:12px;font-size:10px;color:#374151}.product-card{border:1px solid #d1d5db;border-radius:10px;margin-bottom:12px;padding:10px;page-break-inside:avoid}.product-main{display:table;width:100%;margin-bottom:10px}.product-image-cell{display:table-cell;width:86px;vertical-align:top}.product-info-cell{display:table-cell;vertical-align:top;padding-right:10px}.product-image{width:76px;height:76px;object-fit:contain;border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb}.no-image{width:76px;height:76px;border:1px dashed #cbd5e1;border-radius:8px;background:#f8fafc;color:#94a3b8;text-align:center;line-height:76px;font-size:9px}.product-name{font-size:15px;font-weight:bold;color:#111827;margin-bottom:6px}.product-meta{color:#4b5563;font-size:10px;margin-bottom:4px}.price{font-weight:bold;color:#0f766e;white-space:nowrap}.variants-table{width:100%;border-collapse:collapse;margin-top:8px;font-size:10px;table-layout:fixed}.variants-table th{background:#0f766e;color:#fff;padding:6px;border:1px solid #0f766e;font-weight:bold}.variants-table td{border:1px solid #e5e7eb;padding:6px;vertical-align:middle}.text-center{text-align:center}.text-left{text-align:left}.muted{color:#6b7280}.ltr,.code{direction:ltr;unicode-bidi:embed;text-align:left;font-family:'DejaVu Sans',sans-serif}.badge{display:inline-block;border-radius:999px;padding:1px 8px;font-size:10px;font-weight:bold}.success,.stock-ok{color:#047857;background:#dcfce7}.danger,.stock-zero{color:#dc2626;background:#fee2e2}.warning,.stock-low{color:#d97706;background:#fef3c7}.empty-variants{border:1px dashed #d1d5db;background:#f9fafb;border-radius:8px;margin-top:10px;padding:8px;text-align:center;color:#6b7280}.warehouse-stock{font-size:9px;color:#374151;margin-top:2px}.summary{margin-top:4px;color:#374151}.filters-line span{margin-left:10px}
    </style>
</head>
<body>
    <header class="report-header">
        <div class="report-title">{{ $pdfText('گزارش محصولات') }}</div>
        <div class="report-subtitle">{{ $pdfText('سیستم مدیریت موجودی و گردش کالا') }}</div>
        <div class="report-subtitle">{{ $pdfText('تاریخ دریافت خروجی') }}: <span class="ltr">{{ $meta['exported_at'] }}</span></div>
    </header>

    <div class="filter-box filters-line">
        <span>{{ $pdfText('دسته‌بندی') }}: <strong>{{ $pdfText($meta['category']) }}</strong></span>
        <span>{{ $pdfText('انبار') }}: <strong>{{ $pdfText($meta['warehouse']) }}</strong></span>
        <span>{{ $pdfText('وضعیت موجودی') }}: <strong>{{ $pdfText($meta['stock_status']) }}</strong></span>
        <span>{{ $pdfText('آستانه کم‌موجودی') }}: <span class="ltr">{{ $meta['low_stock_threshold'] }}</span></span>
        @if($meta['search'])<span>{{ $pdfText('جستجو') }}: <strong>{{ $pdfText($meta['search']) }}</strong></span>@endif
    </div>

    @foreach($rows as $row)
        <section class="product-card">
            <div class="product-main">
                <div class="product-image-cell">
                    @if($row['has_image'])
                        <img class="product-image" src="{{ $row['pdf_image_src'] }}" alt="">
                    @else
                        <div class="no-image">{{ $pdfText('بدون تصویر') }}</div>
                    @endif
                </div>
                <div class="product-info-cell">
                    <div class="product-name">{{ $pdfText($row['name']) }}</div>
                    <div class="product-meta">{{ $pdfText('دسته‌بندی') }}: {{ $pdfText($row['category']) }}</div>
                    <div class="product-meta">{{ $pdfText($row['price_label']) }}: <span class="price">{{ $money($row['price']) }}</span></div>
                    <div class="product-meta">{{ $pdfText('کد محصول / کد نمایشی') }}: <span class="ltr">{{ $row['display_code'] }}</span>@if($row['barcode']) &nbsp; | &nbsp; {{ $pdfText('بارکد') }}: <span class="ltr">{{ $row['barcode'] }}</span>@endif</div>
                    <div class="summary">{{ $pdfText('موجودی کل') }}: <strong>{{ number_format($row['stock']) }} {{ $pdfText($row['unit']) }}</strong> &nbsp; <span class="badge {{ $row['stock_status_class'] }}">{{ $pdfText($row['stock_status']) }}</span></div>
                </div>
            </div>

            @if(empty($row['variants']))
                <div class="empty-variants">{{ $pdfText('بدون تنوع ثبت‌شده') }}</div>
            @else
                <table class="variants-table">
                    <thead>
                        <tr>
                            <th>{{ $pdfText('تنوع') }}</th>
                            <th>{{ $pdfText('کد / بارکد') }}</th>
                            <th>{{ $pdfText('موجودی') }}</th>
                            <th>{{ $pdfText('قیمت فروش تنوع') }}</th>
                            <th>{{ $pdfText('وضعیت') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($row['variants'] as $variant)
                            <tr>
                                <td>{{ $pdfText($variant['name']) }}</td>
                                <td><div class="ltr">{{ $variant['code'] }}</div>@if($variant['barcode'])<div class="ltr">{{ $variant['barcode'] }}</div>@endif</td>
                                <td class="text-center"><strong>{{ number_format($variant['stock']) }} {{ $pdfText($variant['unit']) }}</strong>@foreach($variant['warehouse_stocks'] as $stock)<div class="warehouse-stock">{{ $pdfText($stock['warehouse']) }}: {{ number_format($stock['quantity']) }}</div>@endforeach</td>
                                <td class="price">{{ $money($variant['price']) }}</td>
                                <td><span class="badge {{ $variant['stock_status_class'] }}">{{ $pdfText($variant['stock_status']) }}</span><div class="muted">{{ $pdfText($variant['status']) }}</div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endforeach
</body>
</html>
