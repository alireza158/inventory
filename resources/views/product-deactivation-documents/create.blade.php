@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="mb-3">ثبت سند غیرفعال‌سازی کالا</h4>
        <form method="POST" action="{{ route('product-deactivation-documents.store') }}" class="row g-3">@csrf
            <div class="col-md-4">
                <label class="form-label">نوع عملیات</label>
                <select name="deactivation_type" id="deactivationType" class="form-select" required>
                    <option value="">انتخاب</option>
                    @foreach($typeLabels as $key => $label)
                        <option value="{{ $key }}" @selected(old('deactivation_type')===$key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-8">
                <label class="form-label">محصول</label>
                <select name="product_id" id="productId" class="form-select" required>
                    <option value="">انتخاب محصول</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((string)old('product_id')===(string)$product->id)>{{ $product->name }}</option>
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
                        <option value="{{ $key }}" @selected(old('reason_type')===$key)>{{ $label }}</option>
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
                <button class="btn btn-danger">ثبت سند غیرفعال‌سازی</button>
                <a href="{{ route('product-deactivation-documents.index') }}" class="btn btn-outline-secondary">بازگشت</a>
            </div>
        </form>
    </div>
</div>

<script>
    const products = @json($products->map(fn($p) => [
        'id' => (int) $p->id,
        'variants' => $p->variants->map(fn($v) => ['id' => (int) $v->id, 'name' => $v->variant_name])->values(),
    ])->values());

    const typeEl = document.getElementById('deactivationType');
    const productEl = document.getElementById('productId');
    const variantWrap = document.getElementById('variantWrap');
    const variantEl = document.getElementById('variantId');
    const oldVariantId = @json(old('variant_id'));

    function renderVariants() {
        const productId = Number(productEl.value || 0);
        const selectedType = typeEl.value;

        if (selectedType !== 'variant') {
            variantWrap.style.display = 'none';
            variantEl.removeAttribute('required');
            variantEl.value = '';
            return;
        }

        variantWrap.style.display = '';
        variantEl.setAttribute('required', 'required');
        const product = products.find(p => Number(p.id) === productId);
        const options = (product?.variants || []).map(v => `<option value="${v.id}">${v.name}</option>`).join('');
        variantEl.innerHTML = `<option value="">انتخاب تنوع</option>${options}`;

        if (oldVariantId) {
            variantEl.value = String(oldVariantId);
        }
    }

    typeEl.addEventListener('change', renderVariants);
    productEl.addEventListener('change', renderVariants);
    renderVariants();
</script>
@endsection
