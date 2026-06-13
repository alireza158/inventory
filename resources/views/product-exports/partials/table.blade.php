<div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h2 class="h6 fw-bold mb-0">لیست محصولات</h2>
    <span class="badge bg-light text-dark">{{ number_format($rows->count()) }} محصول</span>
</div>
@if($rows->isEmpty())
    <div class="empty-state">
        <div class="display-6 mb-2">📦</div>
        <h3 class="h5 fw-bold">محصولی برای این دسته‌بندی یافت نشد.</h3>
        <p class="mb-0">فیلترها را تغییر دهید یا دسته‌بندی دیگری انتخاب کنید.</p>
    </div>
@else
    <div class="table-wrap">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>تصویر محصول</th>
                <th>نام محصول</th>
                <th>کد محصول / SKU</th>
                <th>دسته‌بندی</th>
                <th>موجودی فعلی</th>
                <th>قیمت فروش</th>
                <th>وضعیت موجودی</th>
                <th>تاریخ آخرین بروزرسانی</th>
            </tr>
            </thead>
            <tbody>
            @foreach($rows as $row)
                <tr>
                    <td><img class="product-thumb" src="{{ $row['image_url'] }}" alt="{{ $row['name'] }}" onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2256%22 height=%2256%22%3E%3Crect width=%2256%22 height=%2256%22 rx=%2212%22 fill=%22%23eef2ff%22/%3E%3Ctext x=%2228%22 y=%2233%22 font-size=%2218%22 text-anchor=%22middle%22%3E📦%3C/text%3E%3C/svg%3E'"></td>
                    <td class="fw-bold">{{ $row['name'] }}</td>
                    <td><span class="code-ltr">{{ $row['sku'] }}</span></td>
                    <td>{{ $row['category'] }}</td>
                    <td>{{ number_format($row['stock']) }} {{ $row['unit'] }}</td>
                    <td>{{ number_format($row['price']) }} تومان</td>
                    <td><span class="badge bg-{{ $row['stock_status_class'] }} status-badge">{{ $row['stock_status'] }}</span></td>
                    <td>{{ $row['updated_at'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
