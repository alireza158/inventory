@extends('layouts.app')

@section('content')
<div class="container py-3">
    <h4 class="mb-3">حواله</h4>
    <div class="row g-3">
        <div class="col-md-6 col-lg-3">
            <a class="card p-3 text-decoration-none" href="{{ route('vouchers.section.index', 'return-from-sale') }}">
                <div class="fw-bold">ثبت برگشت از فروش</div>
                <div class="small text-muted">ثبت و مشاهده حواله‌های مرجوعی مشتری</div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a class="card p-3 text-decoration-none" href="{{ route('vouchers.section.index', 'scrap') }}">
                <div class="fw-bold">ثبت انبار ضایعات</div>
                <div class="small text-muted">ثبت و مشاهده حواله‌های ضایعات</div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a class="card p-3 text-decoration-none" href="{{ route('vouchers.section.index', 'personnel') }}">
                <div class="fw-bold">ثبت حواله پرسنل</div>
                <div class="small text-muted">ثبت و مشاهده حواله‌های اموال پرسنل</div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a class="card p-3 text-decoration-none" href="{{ route('vouchers.section.index', 'transfer') }}">
                <div class="fw-bold">ثبت حواله</div>
                <div class="small text-muted">ثبت و مشاهده حواله بین انباری</div>
            </a>
        </div>
    </div>

    <div class="mt-4">
        <a class="btn btn-outline-dark" href="{{ route('warehouse.outputs') }}">خروجی‌های انبار</a>
    </div>
</div>
@endsection
