<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">

    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 8mm 15mm;
            footer: html_pdf_footer;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            direction: rtl;
        }

        body {
            font-family: vazirmatn, sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 9pt;
            line-height: 1.7;
            color: #172033;
        }

        table,
        tr,
        th,
        td,
        div,
        span {
            font-family: vazirmatn, sans-serif;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            background-color: #1e293b;
            color: #ffffff;
        }

        .header-table td {
            padding: 12px;
            border: none;
            vertical-align: middle;
        }

        .title {
            font-size: 17pt;
            font-weight: bold;
            color: #ffffff;
        }

        .subtitle {
            margin-top: 3px;
            color: #cbd5e1;
            font-size: 8pt;
        }

        .report-date {
            width: 30%;
            text-align: left;
            color: #ffffff;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .meta-table td {
            padding: 7px;
            border: 1px solid #dbe3ec;
            background-color: #f8fafc;
            vertical-align: middle;
        }

        .meta-label {
            color: #64748b;
            font-size: 7.5pt;
        }

        .meta-value {
            margin-top: 2px;
            font-weight: bold;
            color: #1e293b;
        }

        .summary {
            padding: 7px 10px;
            margin-bottom: 10px;
            background-color: #eff6ff;
            border-right: 4px solid #2563eb;
            color: #1e40af;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
        }

        .products-table thead {
            display: table-header-group;
        }

        .products-table tbody {
            page-break-inside: auto;
        }

        .products-table tr {
            page-break-before: auto;
            page-break-after: auto;
        }

        .products-table th {
            padding: 7px 4px;
            border: 1px solid #334155;
            background-color: #334155;
            color: #ffffff;
            text-align: center;
            vertical-align: middle;
            font-size: 8pt;
        }

        .products-table td {
            padding: 6px 4px;
            border: 1px solid #dbe3ec;
            text-align: center;
            vertical-align: middle;
            background-color: #ffffff;
        }

        .products-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .col-number {
            width: 5%;
        }

        .col-image {
            width: 10%;
        }

        .col-name {
            width: 23%;
        }

        .col-code {
            width: 14%;
        }

        .col-category {
            width: 14%;
        }

        .col-stock {
            width: 9%;
        }

        .col-price {
            width: 16%;
        }

        .col-status {
            width: 9%;
        }

        .product-image {
            width: 42px;
            height: 42px;
            border: 1px solid #dbe3ec;
            padding: 2px;
        }

        .product-name {
            text-align: right;
            font-weight: bold;
            color: #0f172a;
        }

        .product-unit {
            margin-top: 2px;
            text-align: right;
            color: #64748b;
            font-size: 7pt;
        }

        .number,
        .code {
            direction: ltr;
            unicode-bidi: embed;
            white-space: nowrap;
        }

        .price {
            font-weight: bold;
            white-space: nowrap;
        }

        .price-label {
            display: block;
            color: #64748b;
            font-size: 7pt;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 7pt;
            font-weight: bold;
            white-space: nowrap;
        }

        .badge-success {
            color: #067647;
            background-color: #dcfae6;
        }

        .badge-warning {
            color: #9a6700;
            background-color: #fef0c7;
        }

        .badge-danger {
            color: #b42318;
            background-color: #fee4e2;
        }

        .variant-row td {
            background-color: #eef2f6 !important;
            font-size: 7.5pt;
        }

        .variant-name {
            text-align: right;
            padding-right: 10px;
        }

        .variant-label {
            color: #2563eb;
            font-weight: bold;
        }

        .footer {
            width: 100%;
            padding-top: 4px;
            border-top: 1px solid #cbd5e1;
            color: #64748b;
            font-size: 7.5pt;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            border: none;
            padding: 0;
        }

        .footer-page {
            text-align: left;
        }
    </style>
</head>

<body>

<htmlpagefooter name="pdf_footer">
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>
                    {{ $meta['store_name'] ?? 'سامانه انبارداری' }}
                </td>

                <td class="footer-page">
                    صفحه {PAGENO} از {nbpg}
                </td>
            </tr>
        </table>
    </div>
</htmlpagefooter>

<table class="header-table">
    <tr>
        <td>
            <div class="title">
                گزارش محصولات و موجودی انبار
            </div>

            <div class="subtitle">
                {{ $meta['store_name'] ?? 'سامانه انبارداری' }}
            </div>
        </td>

        <td class="report-date">
            تاریخ تهیه گزارش:
            <br>
            <span class="number">
                {{ $meta['exported_at'] ?? now()->format('Y/m/d H:i') }}
            </span>
        </td>
    </tr>
</table>

<table class="meta-table">
    <tr>
        <td>
            <div class="meta-label">دسته‌بندی</div>
            <div class="meta-value">
                {{ $meta['category'] ?? 'همه دسته‌بندی‌ها' }}
            </div>
        </td>

        <td>
            <div class="meta-label">انبار</div>
            <div class="meta-value">
                {{ $meta['warehouse'] ?? 'همه انبارها' }}
            </div>
        </td>

        <td>
            <div class="meta-label">وضعیت موجودی</div>
            <div class="meta-value">
                {{ $meta['stock_status'] ?? 'همه محصولات' }}
            </div>
        </td>

        <td>
            <div class="meta-label">جستجو</div>
            <div class="meta-value">
                {{ filled($meta['search'] ?? '') ? $meta['search'] : 'بدون جستجو' }}
            </div>
        </td>
    </tr>
</table>

<div class="summary">
    تعداد محصولات:
    <strong class="number">{{ number_format(count($rows)) }}</strong>
</div>
<table class="header">
    <tr>
        <td>
            <div class="title">
                گزارش محصولات و موجودی انبار
            </div>

            <div class="subtitle">
                {{ $meta['store_name'] ?? 'سامانه انبارداری' }}
            </div>
        </td>

        <td class="date">
            تاریخ گزارش:
            <br>
            {{ $meta['exported_at'] ?? now()->format('Y/m/d H:i') }}
        </td>
    </tr>
</table>

<table class="meta-table">
    <tr>
        <td>
            <div class="meta-label">دسته‌بندی</div>
            <div class="meta-value">
                {{ $meta['category'] ?? 'همه دسته‌بندی‌ها' }}
            </div>
        </td>

        <td>
            <div class="meta-label">انبار</div>
            <div class="meta-value">
                {{ $meta['warehouse'] ?? 'همه انبارها' }}
            </div>
        </td>

        <td>
            <div class="meta-label">وضعیت موجودی</div>
            <div class="meta-value">
                {{ $meta['stock_status'] ?? 'همه محصولات' }}
            </div>
        </td>

        <td>
            <div class="meta-label">جستجو</div>
            <div class="meta-value">
                {{ filled($meta['search'] ?? '') ? $meta['search'] : 'بدون جستجو' }}
            </div>
        </td>
    </tr>
</table>

<div class="summary">
    تعداد محصولات:
    <strong>{{ number_format(count($rows)) }}</strong>
</div>

<table class="products">
    <thead>
        <tr>
            <th width="4%">ردیف</th>
            <th width="7%">تصویر</th>
            <th width="22%">نام محصول</th>
            <th width="13%">کد کالا</th>
            <th width="13%">دسته‌بندی</th>
            <th width="9%">موجودی</th>
            <th width="17%">قیمت</th>
            <th width="9%">وضعیت</th>
        </tr>
    </thead>

    <tbody>
        @foreach($rows as $index => $row)
            @php
                $statusClass = match ($row['stock_status_class'] ?? '') {
                    'success' => 'success',
                    'warning' => 'warning',
                    default => 'danger',
                };
            @endphp

            <tr>
                <td>{{ $index + 1 }}</td>

                <td>
                    @if(!empty($row['has_image']) && !empty($row['pdf_image_src']))
                        <img
                            class="image"
                            src="{{ $row['pdf_image_src'] }}"
                            alt=""
                        >
                    @else
                        <span class="small">بدون تصویر</span>
                    @endif
                </td>

                <td>
                    <div class="product-name">
                        {{ $row['name'] ?? 'محصول بدون نام' }}
                    </div>

                    <div class="small">
                        واحد: {{ $row['unit'] ?? 'عدد' }}
                    </div>
                </td>

                <td class="ltr">
                    {{ $row['display_code'] ?? '—' }}
                </td>

                <td>
                    {{ $row['category'] ?? 'بدون دسته‌بندی' }}
                </td>

                <td>
                    {{ number_format((int) ($row['stock'] ?? 0)) }}
                    {{ $row['unit'] ?? 'عدد' }}
                </td>

                <td>
                    <div class="small">
                        {{ $row['price_label'] ?? 'قیمت فروش' }}
                    </div>

                    <strong>
                        {{ number_format((int) ($row['price'] ?? 0)) }}
                    </strong>

                    تومان
                </td>

                <td>
                    <span class="status {{ $statusClass }}">
                        {{ $row['stock_status'] ?? 'نامشخص' }}
                    </span>
                </td>
            </tr>

            @foreach(($row['variants'] ?? []) as $variant)
                <tr class="variant">
                    <td></td>
                    <td>تنوع</td>

                    <td class="product-name">
                        {{ $variant['name'] ?? 'تنوع بدون نام' }}
                    </td>

                    <td class="ltr">
                        {{ $variant['code'] ?? '—' }}
                    </td>

                    <td>
                        {{ $variant['status'] ?? 'نامشخص' }}
                    </td>

                    <td>
                        {{ number_format((int) ($variant['stock'] ?? 0)) }}
                        {{ $variant['unit'] ?? 'عدد' }}
                    </td>

                    <td>
                        {{ number_format((int) ($variant['price'] ?? 0)) }}
                        تومان
                    </td>

                    <td>
                        {{ $variant['stock_status'] ?? 'نامشخص' }}
                    </td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>

</body>
</html>