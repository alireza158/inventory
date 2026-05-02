@extends('layouts.app')

@php
    use Morilog\Jalali\Jalalian;

    $statusLabels = \App\Models\PreinvoiceOrder::statusLabels();

    $centralWarehouseId = \App\Services\WarehouseStockService::centralWarehouseId();

    $productsData = $products->map(function ($product) use ($centralWarehouseId) {
        return [
            'id' => (int) $product->id,
            'name' => $product->name,
            'stock' => (int) \App\Models\WarehouseStock::query()
                ->where('warehouse_id', $centralWarehouseId)
                ->where('product_id', $product->id)
                ->value('quantity'),
            'variants' => $product->variants->map(function ($variant) {
                return [
                    'id' => (int) $variant->id,
                    'name' => $variant->variant_name,
                    'stock' => max(0, ((int) $variant->stock - (int) $variant->reserved)),
                    'price' => (int) ($variant->sell_price ?? 0),
                ];
            })->values()->toArray(),
        ];
    })->values()->toArray();

    $initialItems = $order->items->map(function ($item) {
        return [
            'product_id' => (int) $item->product_id,
            'variant_id' => (int) $item->variant_id,
            'quantity' => (int) $item->quantity,
            'price' => (int) $item->price,
        ];
    })->values()->toArray();

    $reviews = $order->reviews()->with('user:id,name')->latest()->get();
@endphp

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">بررسی انبار پیش‌فاکتور</h4>
        <a href="{{ route('preinvoice.warehouse.index') }}" class="btn btn-outline-secondary">
            بازگشت به صف انبار
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-3">
            <div class="col-md-3">
                <div class="text-muted small">شماره</div>
                <strong>{{ $order->uuid }}</strong>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">تاریخ</div>
                <strong>
                    {{ $order->created_at ? Jalalian::fromDateTime($order->created_at)->format('Y/m/d H:i') : '—' }}
                </strong>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">مشتری</div>
                <strong>{{ $order->customer_name }}</strong>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">وضعیت</div>
                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                    {{ $statusLabels[$order->status] ?? $order->status }}
                </span>
            </div>
        </div>
    </div>

    <form method="POST"
          action="{{ route('preinvoice.warehouse.save', $order->uuid) }}"
          id="warehouseForm"
          class="card shadow-sm border-0 mb-3">
        @csrf
        @method('PUT')
        <input type="hidden" name="_mode" id="warehouseFormMode" value="save">

        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">اقلام پیش‌فاکتور</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addRow">
                افزودن ردیف
            </button>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle" id="itemsTable">
                    <thead>
                        <tr>
                            <th>کالا</th>
                            <th>تنوع</th>
                            <th>موجودی تنوع</th>
                            <th>موجودی کالای انبار مرکزی</th>
                            <th>تعداد</th>
                            <th>قیمت</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="mb-3">
                <label class="form-label">دلیل اصلاح توسط انبار</label>
                <textarea class="form-control"
                          name="warehouse_review_note"
                          rows="3"
                          placeholder="در صورت ویرایش اقلام، دلیل را بنویسید.">{{ old('warehouse_review_note', $order->warehouse_review_note) }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">ذخیره تغییرات</button>
                <button class="btn btn-success" type="button" id="approveBtn">تایید و ارسال به صف مالی</button>
            </div>
        </div>
    </form>

    <div class="card shadow-sm border-0 mt-3">
        <div class="card-header bg-white">
            <h6 class="mb-0">سوابق بازبینی انبار</h6>
        </div>

        <div class="card-body">
            @forelse($reviews as $review)
                <div class="border rounded p-2 mb-2">
                    <div class="small text-muted">
                        {{ $review->created_at ? Jalalian::fromDateTime($review->created_at)->format('Y/m/d H:i') : '—' }}
                        -
                        {{ $review->user?->name ?? 'سیستم' }}
                        -
                        {{ $review->action }}
                    </div>

                    @if($review->reason)
                        <div>{{ $review->reason }}</div>
                    @endif
                </div>
            @empty
                <div class="text-muted">سابقه‌ای ثبت نشده است.</div>
            @endforelse
        </div>
    </div>
</div>

<script>
    const products = @json($productsData);
    const initialItems = @json($initialItems);

    const tbody = document.querySelector('#itemsTable tbody');

    function optionHtml(items, selectedValue = null) {
        return items.map(item => {
            const isSelected = Number(selectedValue) === Number(item.id) ? 'selected' : '';
            return `<option value="${item.id}" ${isSelected}>${item.name}</option>`;
        }).join('');
    }

    function addRow(data = {}) {
        const tr = document.createElement('tr');

        tr.innerHTML = `
            <td>
                <select class="form-select product" required>
                    <option value="">انتخاب...</option>
                </select>
            </td>
            <td>
                <select class="form-select variant" required>
                    <option value="">انتخاب...</option>
                </select>
            </td>
            <td class="variant-stock">0</td>
            <td class="product-stock">0</td>
            <td>
                <input type="number" class="form-control qty" min="1" value="${data.quantity || 1}" required>
            </td>
            <td>
                <input type="number" class="form-control price" min="0" value="${data.price || 0}" required>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger remove">حذف</button>
            </td>
        `;

        tbody.appendChild(tr);

        const productSelect = tr.querySelector('.product');
        const removeBtn = tr.querySelector('.remove');

        productSelect.innerHTML = '<option value="">انتخاب...</option>' + optionHtml(products, data.product_id);
        productSelect.addEventListener('change', function () {
            fillVariants(tr);
        });

        removeBtn.addEventListener('click', function () {
            tr.remove();
        });

        fillVariants(tr, data.variant_id);
    }

    function fillVariants(tr, selectedVariant = null) {
        const productSelect = tr.querySelector('.product');
        const variantSelect = tr.querySelector('.variant');
        const productStockCell = tr.querySelector('.product-stock');
        const variantStockCell = tr.querySelector('.variant-stock');
        const priceInput = tr.querySelector('.price');

        const productId = Number(productSelect.value || 0);
        const product = products.find(p => Number(p.id) === productId);

        if (!product) {
            productStockCell.textContent = '0';
            variantStockCell.textContent = '0';
            variantSelect.innerHTML = '<option value="">انتخاب...</option>';
            return;
        }

        productStockCell.textContent = product.stock ?? 0;
        variantSelect.innerHTML = '<option value="">انتخاب...</option>' + optionHtml(product.variants || [], selectedVariant);

        const onVariantChange = function () {
            const variantId = Number(variantSelect.value || 0);
            const variant = (product.variants || []).find(v => Number(v.id) === variantId);

            variantStockCell.textContent = variant ? (variant.stock ?? 0) : 0;

            if (variant && (!priceInput.value || Number(priceInput.value) === 0)) {
                priceInput.value = variant.price ?? 0;
            }
        };

        variantSelect.onchange = onVariantChange;
        onVariantChange();
    }

    function attachHiddenInputs(form) {
        form.querySelectorAll('input[name^="items["]').forEach(el => el.remove());

        [...tbody.querySelectorAll('tr')].forEach((tr, index) => {
            const productId = tr.querySelector('.product').value;
            const variantId = tr.querySelector('.variant').value;
            const quantity = tr.querySelector('.qty').value;
            const price = tr.querySelector('.price').value;

            const fields = {
                product_id: productId,
                variant_id: variantId,
                quantity: quantity,
                price: price
            };

            Object.entries(fields).forEach(([key, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `items[${index}][${key}]`;
                input.value = value;
                form.appendChild(input);
            });
        });
    }

    document.getElementById('addRow').addEventListener('click', function () {
        addRow();
    });

    if (initialItems.length) {
        initialItems.forEach(item => addRow(item));
    } else {
        addRow();
    }

    document.getElementById('warehouseForm').addEventListener('submit', function () {
        const methodInput = this.querySelector('input[name="_method"]');
        const modeInput = document.getElementById('warehouseFormMode');

        if (methodInput && modeInput && modeInput.value === 'save') {
            methodInput.value = 'PUT';
            methodInput.disabled = false;
        }

        attachHiddenInputs(this);
    });

    document.getElementById('approveBtn').addEventListener('click', function () {
        const form = document.getElementById('warehouseForm');
        attachHiddenInputs(form);

        form.action = "{{ route('preinvoice.warehouse.approve', $order->uuid) }}";
        form.method = 'POST';

        const methodInput = form.querySelector('input[name="_method"]');
        const modeInput = document.getElementById('warehouseFormMode');

        if (methodInput) {
            methodInput.disabled = true;
        }

        if (modeInput) {
            modeInput.value = 'approve';
        }

        form.submit();
    });
</script>
@endsection
