@extends('layouts.app')

@section('content')
@php
    $productsForJs = collect($products ?? [])->map(function ($product) {
        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'is_sellable' => (bool) ($product->is_sellable ?? true),
            'variants' => collect($product->variants ?? [])->map(function ($variant) {
                return [
                    'id' => (int) $variant->id,
                    'name' => (string) ($variant->variant_name ?? ''),
                    'is_active' => (bool) ($variant->is_active ?? true),
                ];
            })->values()->all(),
        ];
    })->values()->all();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">افزودن سند غیرفعال‌سازی</h4>
    <a href="{{ route('product-deactivation-documents.index') }}" class="btn btn-outline-secondary">
        بازگشت
    </a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-bold mb-2">خطاهای فرم:</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('product-deactivation-documents.store') }}" id="deactivationForm">
            @csrf

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="deactivation_type" class="form-label">نوع غیرفعال‌سازی</label>
                    <select name="deactivation_type" id="deactivation_type" class="form-select" required>
                        <option value="">انتخاب کنید</option>
                        @foreach ($typeLabels as $key => $label)
                            <option value="{{ $key }}" @selected(old('deactivation_type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="product_id" class="form-label">محصول</label>
                    <select name="product_id" id="product_id" class="form-select" required>
                        <option value="">انتخاب محصول</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="variant_id" class="form-label">تنوع</label>
                    <select name="variant_id" id="variant_id" class="form-select">
                        <option value="">ابتدا محصول را انتخاب کنید</option>
                    </select>
                    <div class="form-text">
                        فقط در حالت «غیرفعال‌سازی تنوع» الزامی است.
                    </div>
                </div>

                <div class="col-md-4">
                    <label for="reason_type" class="form-label">علت غیرفعال‌سازی</label>
                    <select name="reason_type" id="reason_type" class="form-select" required>
                        <option value="">انتخاب علت</option>
                        @foreach ($reasonLabels as $key => $label)
                            <option value="{{ $key }}" @selected(old('reason_type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-8">
                    <label for="reason_text" class="form-label">توضیح علت</label>
                    <input
                        type="text"
                        name="reason_text"
                        id="reason_text"
                        class="form-control"
                        value="{{ old('reason_text') }}"
                        placeholder="مثلاً توقف فروش، مشکل کیفیت، اتمام همکاری و ..."
                        required
                    >
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">توضیحات تکمیلی</label>
                    <textarea
                        name="description"
                        id="description"
                        class="form-control"
                        rows="4"
                        placeholder="توضیحات تکمیلی اختیاری"
                    >{{ old('description') }}</textarea>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">ثبت سند</button>
                <a href="{{ route('product-deactivation-documents.index') }}" class="btn btn-outline-secondary">انصراف</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const products = @json($productsForJs);

    const typeSelect = document.getElementById('deactivation_type');
    const productSelect = document.getElementById('product_id');
    const variantSelect = document.getElementById('variant_id');

    const oldVariantId = @json(old('variant_id'));
    const oldType = @json(old('deactivation_type'));

    function getSelectedProduct() {
        const productId = Number(productSelect.value || 0);
        return products.find(p => Number(p.id) === productId) || null;
    }

    function renderVariantOptions() {
        const selectedProduct = getSelectedProduct();
        const isVariantMode = typeSelect.value === 'variant';

        variantSelect.innerHTML = '';

        if (!selectedProduct) {
            variantSelect.innerHTML = '<option value="">ابتدا محصول را انتخاب کنید</option>';
            variantSelect.disabled = true;
            variantSelect.required = false;
            return;
        }

        const variants = Array.isArray(selectedProduct.variants) ? selectedProduct.variants : [];

        if (!variants.length) {
            variantSelect.innerHTML = '<option value="">این محصول تنوعی ندارد</option>';
            variantSelect.disabled = true;
            variantSelect.required = false;
            return;
        }

        variantSelect.innerHTML = '<option value="">انتخاب تنوع</option>';

        variants.forEach(function (variant) {
            const option = document.createElement('option');
            option.value = String(variant.id);
            option.textContent = variant.name + (variant.is_active ? '' : ' (غیرفعال)');
            if (String(oldVariantId || '') === String(variant.id)) {
                option.selected = true;
            }
            variantSelect.appendChild(option);
        });

        variantSelect.disabled = !isVariantMode;
        variantSelect.required = isVariantMode;

        if (!isVariantMode) {
            variantSelect.value = '';
        }
    }

    function syncTypeState() {
        const isVariantMode = typeSelect.value === 'variant';
        renderVariantOptions();

        if (!isVariantMode) {
            variantSelect.required = false;
            variantSelect.disabled = true;
            variantSelect.value = '';
        } else {
            const selectedProduct = getSelectedProduct();
            if (selectedProduct && Array.isArray(selectedProduct.variants) && selectedProduct.variants.length > 0) {
                variantSelect.disabled = false;
                variantSelect.required = true;
            }
        }
    }

    typeSelect.addEventListener('change', syncTypeState);
    productSelect.addEventListener('change', function () {
        renderVariantOptions();

        if (typeSelect.value === 'variant') {
            variantSelect.disabled = false;
            variantSelect.required = true;
        }
    });

    if (oldType === 'variant') {
        typeSelect.value = 'variant';
    }

    syncTypeState();
});
</script>
@endsection