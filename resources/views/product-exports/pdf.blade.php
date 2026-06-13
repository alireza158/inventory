<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:DejaVu Sans,Tahoma,sans-serif;direction:rtl;color:#111827;font-size:12px}.header{border-bottom:3px solid #0f766e;padding-bottom:12px;margin-bottom:16px;display:flex;justify-content:space-between}.store{font-size:20px;font-weight:900;color:#0f766e}.title{font-size:18px;font-weight:900;margin-top:8px}.meta{background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:10px;margin-bottom:14px;line-height:2}.table{width:100%;border-collapse:collapse}.table th{background:#0f766e;color:#fff}.table th,.table td{border:1px solid #d1d5db;padding:7px;text-align:right;vertical-align:middle}.thumb{width:44px;height:44px;object-fit:cover;border-radius:8px}.status{font-weight:900}.success{color:#15803d}.warning{color:#a16207}.danger{color:#b91c1c}.ltr{direction:ltr;unicode-bidi:plaintext}.price{white-space:nowrap}
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="store">{{ $meta['store_name'] }}</div>
            <div class="title">گزارش محصولات</div>
        </div>
        <div>تاریخ دریافت خروجی: {{ $meta['exported_at'] }}</div>
    </div>
    <div class="meta">
        دسته‌بندی انتخاب‌شده: <strong>{{ $meta['category'] }}</strong> | انبار: <strong>{{ $meta['warehouse'] }}</strong> | وضعیت موجودی: <strong>{{ $meta['stock_status'] }}</strong>
        @if($meta['search']) | جستجو: <strong>{{ $meta['search'] }}</strong> @endif
    </div>
    <table class="table">
        <thead><tr><th>تصویر</th><th>نام محصول</th><th>کد/SKU</th><th>دسته‌بندی</th><th>موجودی</th><th>قیمت فروش</th><th>وضعیت</th><th>آخرین بروزرسانی</th></tr></thead>
        <tbody>
        @foreach($rows as $row)
            <tr>
                <td><img class="thumb" src="{{ $row['image_url'] }}" alt=""></td>
                <td>{{ $row['name'] }}</td>
                <td class="ltr">{{ $row['sku'] }}</td>
                <td>{{ $row['category'] }}</td>
                <td>{{ number_format($row['stock']) }} {{ $row['unit'] }}</td>
                <td class="price">{{ number_format($row['price']) }} تومان</td>
                <td class="status {{ $row['stock_status_class'] }}">{{ $row['stock_status'] }}</td>
                <td>{{ $row['updated_at'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
