@php
    $storeName = $meta['store_name'] ?? config('app.name', 'سامانه انبارداری');
    $exportedAt = $meta['exported_at'] ?? now()->format('Y/m/d H:i');
@endphp

<table class="report-header">
    <tr>
        <td>
            <div class="report-title">گزارش محصولات و موجودی انبار</div>
            <div class="report-subtitle">{{ $storeName }}</div>
        </td>
        <td class="report-date">
            تاریخ تهیه گزارش:
            <br>
            <span class="number">{{ $exportedAt }}</span>
        </td>
    </tr>
</table>

<table class="meta-table">
    <tr>
        <td>
            <div class="meta-label">دسته‌بندی</div>
            <div class="meta-value">{{ $meta['category'] ?? 'همه دسته‌بندی‌ها' }}</div>
        </td>
        <td>
            <div class="meta-label">انبار</div>
            <div class="meta-value">{{ $meta['warehouse'] ?? 'همه انبارها' }}</div>
        </td>
        <td>
            <div class="meta-label">وضعیت موجودی</div>
            <div class="meta-value">{{ $meta['stock_status'] ?? 'همه محصولات' }}</div>
        </td>
        <td>
            <div class="meta-label">جستجو</div>
            <div class="meta-value">{{ filled($meta['search'] ?? '') ? $meta['search'] : 'بدون جستجو' }}</div>
        </td>
    </tr>
</table>

<div class="summary">
    تعداد محصولات:
    <strong class="number">{{ number_format(count($rows)) }}</strong>
</div>

<table class="products-table">
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
                    'success' => 'badge-success',
                    'warning' => 'badge-warning',
                    'danger' => 'badge-danger',
                    default => 'badge-secondary',
                };
            @endphp

            <tr>
                <td class="number">{{ number_format($index + 1) }}</td>
                <td>
                    @if(!empty($row['has_image']) && !empty($row['pdf_image_src']))
                        <img class="product-image" src="{{ $row['pdf_image_src'] }}" alt="">
                    @else
                        <span class="muted">بدون تصویر</span>
                    @endif
                </td>
                <td>
                    <div class="product-name">{{ $row['name'] ?? 'محصول بدون نام' }}</div>
                    <div class="product-unit">واحد: {{ $row['unit'] ?? 'عدد' }}</div>
                </td>
                <td class="code">{{ $row['display_code'] ?? '—' }}</td>
                <td>{{ $row['category'] ?? 'بدون دسته‌بندی' }}</td>
                <td>
                    <span class="number">{{ number_format((int) ($row['stock'] ?? 0)) }}</span>
                    {{ $row['unit'] ?? 'عدد' }}
                </td>
                <td class="price">
                    <span class="price-label">{{ $row['price_label'] ?? 'قیمت فروش' }}</span>
                    <span class="number">{{ number_format((int) ($row['price'] ?? 0)) }}</span>
                    تومان
                </td>
                <td>
                    <span class="badge {{ $statusClass }}">{{ $row['stock_status'] ?? 'نامشخص' }}</span>
                </td>
            </tr>

            @foreach(($row['variants'] ?? []) as $variant)
                <tr class="variant-row">
                    <td></td>
                    <td><span class="variant-label">تنوع</span></td>
                    <td class="variant-name">{{ $variant['name'] ?? 'تنوع بدون نام' }}</td>
                    <td class="code">{{ $variant['code'] ?? '—' }}</td>
                    <td>{{ $variant['status'] ?? 'نامشخص' }}</td>
                    <td>
                        <span class="number">{{ number_format((int) ($variant['stock'] ?? 0)) }}</span>
                        {{ $variant['unit'] ?? 'عدد' }}
                    </td>
                    <td class="price">
                        <span class="number">{{ number_format((int) ($variant['price'] ?? 0)) }}</span>
                        تومان
                    </td>
                    <td>{{ $variant['stock_status'] ?? 'نامشخص' }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
