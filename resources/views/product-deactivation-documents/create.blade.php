@extends('layouts.app')

@section('content')
@php
    $productsForJs = collect($products ?? [])->map(function ($p) {
        return [
            'id' => (int) $p->id,
            'variants' => collect($p->variants ?? [])->map(function ($v) {
                return [
                    'id' => (int) $v->id,
                    'name' => (string) ($v->variant_name ?? ''),
                ];
            })->values()->all(),
        ];
    })->values()->all();

    $oldVariantId = old('variant_id');
    $oldType = old('deactivation_type');
@endphp

<div class="card">
    <div class="card-body">
        <h4 class="mb-3">ثبت سند غیرفعال‌سازی کالا</h4>

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

        <form method="POST" action="{{ route('product-deactivation-documents.store') }}" class="row g-3">
            @csrf

            <div class="col-md-4">
                <label class="form-label">نوع عملیات</label>
                <select name="deactivation_type" id="deactivationType" class="form-select" required>
                    <option value="">انتخاب</option>
                    @foreach($typeLabels as $key => $label)
                        <option value="{{ $key }}" @selected(old('deactivation_type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-8">
                <label class="form-label">محصول</label>
                <select name="product_id" id="productId" class="form-select" required>
                    <option value="">انتخاب محصول</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-8" id="variantWrap" style="display:none;">
                <label class="form-label">تنوع</label>
                <select name="variant_id" id="variantId" class="form-select">
                    <option value="">انتخاب تنوع</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">علت غیرفعال‌سازی</label>
                <select name="reason_type" class="form-select" required>
                    <option value="">انتخاب علت</option>
                    @foreach($reasonLabels as $key => $label)
                        <option value="{{ $key }}" @selected(old('reason_type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-12">
                <label class="form-label">توضیح علت (اجباری)</label>
                <textarea name="reason_text" class="form-control" rows="3" required>{{ old('reason_text') }}</textarea>
            </div>

            <div class="col-md-12">
                <label class="form-label">توضیحات تکمیلی</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-danger">ثبت سند غیرفعال‌سازی</button>
                <a href="{{ route('product-deactivation-documents.index') }}" class="btn btn-outline-secondary">بازگشت</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const products = @json($productsForJs);
    const oldVariantId = @json($oldVariantId);
    const oldType = @json($oldType);

    const typeEl = document.getElementById('deactivationType');
    const productEl = document.getElementById('productId');
    const variantWrap = document.getElementById('variantWrap');
    const variantEl = document.getElementById('variantId');

    function renderVariants() {
        const productId = Number(productEl.value || 0);
        const selectedType = typeEl.value;

        if (selectedType !== 'variant') {
            variantWrap.style.display = 'none';
            variantEl.removeAttribute('required');
            variantEl.innerHTML = '<option value="">انتخاب تنوع</option>';
            variantEl.value = '';
            return;
        }

        variantWrap.style.display = '';
        variantEl.setAttribute('required', 'required');

        const product = products.find(function (p) {
            return Number(p.id) === productId;
        });

        const variants = product && Array.isArray(product.variants) ? product.variants : [];

        let options = '<option value="">انتخاب تنوع</option>';
        variants.forEach(function (v) {
            options += `<option value="${v.id}">${v.name}</option>`;
        });

        variantEl.innerHTML = options;

        if (oldVariantId) {
            variantEl.value = String(oldVariantId);
        }
    }

    if (oldType) {
        typeEl.value = oldType;
    }

    typeEl.addEventListener('change', renderVariants);
    productEl.addEventListener('change', renderVariants);

    renderVariants();
});
</script>
@endsection