@php
    $hasVariants = $p->variants && $p->variants->count() > 0;
    $detailsId = 'productDetails' . $p->id . ($mode ?? '');
    $short = $p->short_barcode ?: ((!empty($p->code) && strlen($p->code) >= 6) ? substr($p->code, 2, 4) : null);
    $firstVar = $hasVariants ? $p->variants->sortBy('variant_code')->first() : null;
    $sampleBarcode = $firstVar?->variant_code ?: ($p->sku ?: $p->barcode);
    $isSellable = $p->is_sellable ?? true;
    $reservedQty = $hasVariants ? max(0, (int) $p->variants->sum(fn ($variant) => (int) ($variant->reserved ?? 0))) : max(0, (int) ($p->reserved ?? 0));
    $variantsPayload = $p->variants->sortBy('variant_code')->values()->map(function ($v) use ($p) {
        $variantBreakdown = $p->warehouseStocks->where('product_variant_id', (int) $v->id)->groupBy('warehouse_id')->map(function ($rows) {
            $first = $rows->first();
            return ['warehouse' => $first?->warehouse?->name, 'qty' => (int) $rows->sum('quantity')];
        })->filter(fn ($row) => (int) ($row['qty'] ?? 0) > 0)->values()->all();
        return ['id' => (int) $v->id, 'name' => $v->variant_name, 'stock' => (int) $v->stock, 'reserved' => max(0, (int) ($v->reserved ?? 0)), 'is_active' => (bool) $v->is_active, 'warehouse_breakdown' => $variantBreakdown];
    })->all();
    $stockBreakdownPayload = $p->warehouseStocks->groupBy('warehouse_id')->map(function ($rows) {
        $first = $rows->first();
        $variantRows = $rows->whereNotNull('product_variant_id');
        $aggregateRows = $rows->whereNull('product_variant_id');
        $qty = $variantRows->isNotEmpty() ? (int) $variantRows->sum('quantity') : (int) $aggregateRows->sum('quantity');
        return ['warehouse' => $first?->warehouse?->name, 'qty' => max(0, $qty)];
    })->filter(fn ($row) => (int) ($row['qty'] ?? 0) > 0)->values()->all();
    $checkboxAttrs = [
        'data-edit-url' => route('products.edit', ['product' => $p, 'return_to' => request()->fullUrl()]),
        'data-delete-url' => route('products.destroy', $p),
        'data-sales-ledger-url' => route('products.sales-ledger', $p),
        'data-purchase-ledger-url' => route('products.purchase-ledger', $p),
        'data-deactivate-url' => route('product-deactivation-documents.create', ['product_id' => $p->id]),
        'data-deactivation-history-url' => route('product-deactivation-documents.index', ['product_name' => $p->name]),
        'data-is-sellable' => $isSellable ? '1' : '0',
        'data-product-name' => $p->name,
        'data-reserved-qty' => $reservedQty,
        'data-variants' => json_encode($variantsPayload, JSON_UNESCAPED_UNICODE),
        'data-stock-breakdown' => json_encode($stockBreakdownPayload, JSON_UNESCAPED_UNICODE),
    ];
@endphp

@if(($mode ?? 'desktop') === 'desktop')
<tr>
    <td class="text-center"><input type="checkbox" class="form-check-input product-checkbox" value="{{ $p->id }}" @foreach($checkboxAttrs as $attr => $value) {{ $attr }}='{{ $value }}' @endforeach></td>
    <td>@if($p->image_path)<a href="{{ route('products.image', $p) }}" target="_blank" class="product-thumb" title="نمایش عکس کالا"><img src="{{ route('products.image', $p) }}" alt="عکس {{ $p->name }}"></a>@else<span class="product-thumb-placeholder" title="بدون عکس">📷</span>@endif</td>
    <td><span class="truncate fw-bold" title="{{ $p->name }}">{{ $p->name }}</span><div class="small text-muted truncate" title="{{ $p->category?->name ?? 'بدون دسته‌بندی' }}">{{ $p->category?->name ?? 'بدون دسته‌بندی' }}</div></td>
    <td class="mono"><span class="pill pill-gray">{{ $short ?: '—' }}</span></td>
    <td class="mono"><span class="truncate safe-break" title="{{ $sampleBarcode ?: '—' }}">{{ $sampleBarcode ?: '—' }}</span></td>
    <td><span class="pill {{ ((int) $p->stock) === 0 ? 'pill-danger' : 'pill-success' }}">{{ $toFa($p->stock ?? 0) }}</span></td>
    <td>@if($isSellable)<span class="sellable-badge active">قابل فروش</span>@else<span class="sellable-badge inactive">غیرفعال</span>@endif</td>
    <td><span class="price-inline">{{ $money($p->price) }}</span></td>
    <td>
        <div class="dropdown"><button class="btn btn-outline-secondary btn-sm btn-mini dropdown-toggle" type="button" data-bs-toggle="dropdown">عملیات</button><ul class="dropdown-menu dropdown-menu-end">
            <li><button class="dropdown-item" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $detailsId }}">جزئیات</button></li>
            <li><a class="dropdown-item" href="{{ route('products.edit', ['product' => $p, 'return_to' => request()->fullUrl()]) }}">ویرایش</a></li>
            <li><button class="dropdown-item row-action-stock" type="button" data-product-id="{{ $p->id }}">موجودی انبار</button></li>
            <li><a class="dropdown-item" href="{{ route('products.sales-ledger', $p) }}">کارتکس فروش</a></li>
            <li><a class="dropdown-item" href="{{ route('products.purchase-ledger', $p) }}">کارتکس خرید</a></li>
            <li><a class="dropdown-item" href="{{ $isSellable ? route('product-deactivation-documents.create', ['product_id' => $p->id]) : route('product-deactivation-documents.index', ['product_name' => $p->name]) }}">{{ $isSellable ? 'غیرفعال‌سازی' : 'سوابق غیرفعال‌سازی' }}</a></li>
            @if($canDeleteProducts)<li><hr class="dropdown-divider"></li><li><button class="dropdown-item text-danger" type="button" onclick="if(confirm('کالای «{{ addslashes($p->name) }}» حذف شود؟')){document.getElementById('bulkDeleteForm').action='{{ route('products.destroy', $p) }}';document.getElementById('bulkDeleteForm').submit();}">حذف</button></li>@endif
        </ul></div>
    </td>
</tr>
<tr class="collapse" id="{{ $detailsId }}"><td colspan="9"><div class="details-box"><div class="row g-2"><div class="col-md-3"><b>دسته‌بندی:</b> {{ $p->category?->name ?? '—' }}</div><div class="col-md-3"><b>تعداد تنوع:</b> {{ $toFa($p->variants->count()) }}</div><div class="col-md-3"><b>رزرو:</b> {{ $toFa($reservedQty) }}</div><div class="col-md-3"><b>بارکد / SKU:</b> <span class="mono safe-break">{{ $sampleBarcode ?: '—' }}</span></div></div>@if($hasVariants)<div class="variant-list mt-2">@foreach($p->variants->sortBy('variant_code') as $v)<div class="variant-row"><b>{{ $v->variant_name }}</b> | <span class="mono">{{ $v->variant_code }}</span> | موجودی: {{ $toFa($v->stock) }} | فروش: {{ $money($v->sell_price) }} | {{ $v->is_active ? 'فعال' : 'غیرفعال' }}</div>@endforeach</div>@endif</div></td></tr>
@else
<div class="mobile-card">
    <div class="mobile-card-top">
        <input type="checkbox" class="form-check-input product-checkbox" value="{{ $p->id }}" @foreach($checkboxAttrs as $attr => $value) {{ $attr }}='{{ $value }}' @endforeach>
        @if($p->image_path)<a href="{{ route('products.image', $p) }}" target="_blank" class="product-thumb"><img src="{{ route('products.image', $p) }}" alt="عکس {{ $p->name }}"></a>@else<span class="product-thumb-placeholder">📷</span>@endif
        <div class="mobile-title truncate" title="{{ $p->name }}">{{ $p->name }}</div>
        @if($isSellable)<span class="sellable-badge active">قابل فروش</span>@else<span class="sellable-badge inactive">غیرفعال</span>@endif
    </div>
    <div class="mobile-meta"><span>کد: <span class="mono">{{ $short ?: '—' }}</span></span><span class="safe-break">بارکد: <span class="mono">{{ $sampleBarcode ?: '—' }}</span></span></div>
    <div class="mobile-values"><span>موجودی: <b>{{ $toFa($p->stock ?? 0) }}</b></span><span>قیمت فروش: <b>{{ $money($p->price) }}</b></span></div>
    <div class="mobile-actions"><button class="btn btn-outline-primary btn-mini" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $detailsId }}">جزئیات</button><div class="dropdown"><button class="btn btn-outline-secondary btn-mini dropdown-toggle" type="button" data-bs-toggle="dropdown">عملیات</button><ul class="dropdown-menu dropdown-menu-end w-100"><li><a class="dropdown-item" href="{{ route('products.edit', ['product' => $p, 'return_to' => request()->fullUrl()]) }}">ویرایش</a></li><li><button class="dropdown-item row-action-stock" type="button" data-product-id="{{ $p->id }}">موجودی انبار</button></li><li><a class="dropdown-item" href="{{ route('products.sales-ledger', $p) }}">کارتکس فروش</a></li><li><a class="dropdown-item" href="{{ route('products.purchase-ledger', $p) }}">کارتکس خرید</a></li><li><a class="dropdown-item" href="{{ $isSellable ? route('product-deactivation-documents.create', ['product_id' => $p->id]) : route('product-deactivation-documents.index', ['product_name' => $p->name]) }}">{{ $isSellable ? 'غیرفعال‌سازی' : 'سوابق غیرفعال‌سازی' }}</a></li>@if($canDeleteProducts)<li><hr class="dropdown-divider"></li><li><button class="dropdown-item text-danger" type="button" onclick="if(confirm('کالای «{{ addslashes($p->name) }}» حذف شود؟')){document.getElementById('bulkDeleteForm').action='{{ route('products.destroy', $p) }}';document.getElementById('bulkDeleteForm').submit();}">حذف</button></li>@endif</ul></div></div>
    <div class="collapse" id="{{ $detailsId }}"><div class="details-box"><div>دسته‌بندی: {{ $p->category?->name ?? '—' }}</div><div>تعداد تنوع: {{ $toFa($p->variants->count()) }}</div><div>رزرو: {{ $toFa($reservedQty) }}</div>@if($hasVariants)<div class="variant-list mt-2">@foreach($p->variants->sortBy('variant_code') as $v)<div class="variant-row"><b>{{ $v->variant_name }}</b><br><span class="mono safe-break">{{ $v->variant_code }}</span><br>موجودی: {{ $toFa($v->stock) }} | فروش: {{ $money($v->sell_price) }} | {{ $v->is_active ? 'فعال' : 'غیرفعال' }}</div>@endforeach</div>@endif</div></div>
</div>
@endif
