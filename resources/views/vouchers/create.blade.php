@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="page-title mb-0">ثبت حواله</h4>
    <a class="btn btn-outline-secondary" href="{{ route('vouchers.index') }}">بازگشت</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('vouchers.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">محصول</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">انتخاب کنید...</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" @selected(old('product_id')==$p->id)>
                                {{ $p->name }} ({{ $p->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">نوع</label>
                    <select name="type" class="form-select" required>
                        <option value="in" @selected(old('type','in')==='in')>ورود</option>
                        <option value="out" @selected(old('type')==='out')>خروج</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">تعداد</label>
                    <input name="quantity" type="number" min="1" class="form-control" value="{{ old('quantity',1) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">علت</label>
                    <select name="reason" class="form-select" required>
                        <option value="purchase" @selected(old('reason','purchase')==='purchase')>خرید</option>
                        <option value="sale" @selected(old('reason')==='sale')>فروش</option>
                        <option value="return" @selected(old('reason')==='return')>مرجوعی</option>
                        <option value="transfer" @selected(old('reason')==='transfer')>انتقال</option>
                        <option value="adjustment" @selected(old('reason')==='adjustment')>اصلاح</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">شماره حواله (اختیاری)</label>
                    <input name="reference" class="form-control" value="{{ old('reference') }}" placeholder="مثلاً 123">
                </div>

                <div class="col-md-4">
                    <label class="form-label">توضیحات (اختیاری)</label>
                    <input name="note" class="form-control" value="{{ old('note') }}">
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">ثبت حواله</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
