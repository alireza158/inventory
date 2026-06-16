@php
    $pdfText = fn ($value) => \App\Services\ProductExportService::pdfText((string) $value);
    $money = fn ($value) => number_format((int) $value) . ' ' . $pdfText('تومان');
@endphp
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        @font-face{font-family:'Vazirmatn';src:url('{{ public_path('fonts/vazirmatn/Vazirmatn-Regular.woff2') }}') format('woff2');font-weight:400;font-style:normal}
        @font-face{font-family:'Vazirmatn';src:url('{{ public_path('fonts/vazirmatn/Vazirmatn-Bold.woff2') }}') format('woff2');font-weight:700;font-style:normal}
        @page{margin:16mm 10mm}body{font-family:'Vazirmatn','DejaVu Sans',sans-serif;direction:rtl;text-align:right;color:#111827;font-size:11px;line-height:1.8}.header{border-bottom:2px solid #0f766e;padding-bottom:10px;margin-bottom:12px}.store{font-size:18px;font-weight:bold;color:#0f766e}.title{font-size:16px;font-weight:bold}.filters{background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:8px;margin-bottom:12px;color:#374151}.product-card{border:1px solid #d1d5db;border-radius:10px;margin-bottom:12px;padding:10px;page-break-inside:avoid}.product-main{display:table;width:100%}.product-image-wrap{display:table-cell;width:86px;vertical-align:top}.product-info{display:table-cell;vertical-align:top;padding-right:10px}.product-image{width:70px;height:70px;object-fit:contain;border:1px solid #e5e7eb;border-radius:8px;background:#f8fafc}.no-image{width:70px;height:70px;border:1px solid #e5e7eb;border-radius:8px;background:#f8fafc;text-align:center;color:#6b7280;font-size:10px;line-height:70px}.product-title{font-weight:bold;font-size:14px;margin-bottom:4px}.meta{color:#4b5563;font-size:10px;margin-bottom:3px}.badge{display:inline-block;border-radius:999px;padding:1px 8px;font-size:10px;font-weight:bold}.success{color:#15803d;background:#dcfce7}.warning{color:#a16207;background:#fef3c7}.danger{color:#b91c1c;background:#fee2e2}.muted{color:#6b7280}.variants-table{width:100%;border-collapse:collapse;margin-top:10px;table-layout:fixed}.variants-table th{background:#0f766e;color:#fff;padding:6px;font-weight:bold}.variants-table td{border:1px solid #e5e7eb;padding:6px;vertical-align:top}.text-center{text-align:center}.text-left{text-align:left}.code{direction:ltr;text-align:left;unicode-bidi:embed;font-family:'DejaVu Sans',sans-serif}.price{white-space:nowrap}.empty-variants{border:1px dashed #d1d5db;background:#f9fafb;border-radius:8px;margin-top:10px;padding:8px;text-align:center;color:#6b7280}.warehouse-stock{font-size:9px;color:#374151;margin-top:2px}.summary{margin-top:4px;color:#374151}
    </style>
</head>
<body>
<div class="header">
    <div class="store">{{ $pdfText('سیستم انبارداری') }}</div>
    <div class="title">{{ $pdfText('گزارش محصولات و موجودی') }}</div>
    <div>{{ $pdfText('تاریخ خروجی') }}: <span class="code">{{ $meta['exported_at'] }}</span></div>
</div>
<div class="filters">
    {{ $pdfText('دسته‌بندی') }}: <strong>{{ $pdfText($meta['category']) }}</strong>
    &nbsp; | &nbsp; {{ $pdfText('انبار') }}: <strong>{{ $pdfText($meta['warehouse']) }}</strong>
    &nbsp; | &nbsp; {{ $pdfText('وضعیت موجودی') }}: <strong>{{ $pdfText($meta['stock_status']) }}</strong>
    &nbsp; | &nbsp; {{ $pdfText('آستانه کم‌موجودی') }}: <span class="code">{{ $meta['low_stock_threshold'] }}</span>
    @if($meta['search']) &nbsp; | &nbsp; {{ $pdfText('جستجو') }}: <strong>{{ $pdfText($meta['search']) }}</strong> @endif
</div>
@foreach($rows as $row)
    <section class="product-card">
        <div class="product-main">
            <div class="product-image-wrap">
                @if($row['has_image'])
                    <img class="product-image" src="{{ $row['pdf_image_src'] }}" alt="">
                @else
                    <div class="no-image">{{ $pdfText('بدون تصویر') }}</div>
                @endif
            </div>
            <div class="product-info">
                <div class="product-title">{{ $pdfText($row['name']) }}</div>
                <div class="meta">{{ $pdfText('دسته‌بندی') }}: {{ $pdfText($row['category']) }}</div>
                <div class="meta">{{ $pdfText('کد محصول / SKU') }}: <span class="code">{{ $row['sku'] }}</span>@if($row['barcode']) &nbsp; | &nbsp; {{ $pdfText('بارکد') }}: <span class="code">{{ $row['barcode'] }}</span>@endif</div>
                <div class="meta">{{ $pdfText('قیمت فروش محصول') }}: <span class="price">{{ $money($row['price']) }}</span></div>
                <div class="summary">{{ $pdfText('موجودی کل') }}: <strong>{{ number_format($row['stock']) }} {{ $pdfText($row['unit']) }}</strong> &nbsp; <span class="badge {{ $row['stock_status_class'] }}">{{ $pdfText($row['stock_status']) }}</span> &nbsp; {{ $pdfText('وضعیت فروش') }}: {{ $pdfText($row['is_sellable'] ? 'قابل فروش' : 'غیرفعال') }}</div>
            </div>
        </div>
        @if(empty($row['variants']))
            <div class="empty-variants">{{ $pdfText('بدون تنوع ثبت‌شده') }}</div>
        @else
            <table class="variants-table">
                <thead><tr><th>{{ $pdfText('تنوع') }}</th><th>{{ $pdfText('کد / بارکد') }}</th><th>{{ $pdfText('موجودی') }}</th><th>{{ $pdfText('قیمت فروش تنوع') }}</th><th>{{ $pdfText('وضعیت') }}</th></tr></thead>
                <tbody>
                @foreach($row['variants'] as $variant)
                    <tr>
                        <td>{{ $pdfText($variant['name']) }}</td>
                        <td><div class="code">{{ $variant['code'] }}</div>@if($variant['barcode'])<div class="code">{{ $variant['barcode'] }}</div>@endif</td>
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
