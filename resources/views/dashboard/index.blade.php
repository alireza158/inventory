@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="page-title mb-0">داشبورد</h4>
    <div class="text-muted small">آخرین بروزرسانی: {{ now()->format('Y/m/d H:i') }}</div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">تعداد محصولات</div>
                <div class="fs-4 fw-bold mt-1">{{ number_format($totalProducts ?? 0) }}</div>
                <div class="small text-muted mt-2">کل کالاهای تعریف‌شده</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">کم‌موجودی</div>
                <div class="fs-4 fw-bold mt-1">{{ number_format($lowStock ?? 0) }}</div>
                <div class="small text-muted mt-2"><= آستانه موجودی</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">ناموجود</div>
                <div class="fs-4 fw-bold mt-1">{{ number_format($outOfStock ?? 0) }}</div>
                <div class="small text-muted mt-2">موجودی صفر</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">ارزش موجودی</div>
                <div class="fs-5 fw-bold mt-1">{{ number_format($totalStockValue ?? 0) }} تومان</div>
                <div class="small text-muted mt-2">stock × price</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 fw-bold">آخرین گردش‌های انبار</h6>
                    <a class="btn btn-sm btn-outline-dark" href="{{ route('movements.index') }}">مشاهده همه</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>زمان</th>
                                <th>محصول</th>
                                <th>نوع</th>
                                <th>تعداد</th>
                                <th>قبل</th>
                                <th>بعد</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($latestMovements ?? []) as $m)
                                <tr>
                                    <td class="text-muted small">{{ $m->created_at->format('Y/m/d H:i') }}</td>
                                    <td class="fw-semibold">
                                        {{ $m->product?->name }}
                                        <div class="text-muted small">{{ $m->product?->sku }}</div>
                                    </td>
                                    <td>
                                        @if($m->type === 'in')
                                            <span class="badge text-bg-success">ورود</span>
                                        @else
                                            <span class="badge text-bg-danger">خروج</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold">{{ $m->quantity }}</td>
                                    <td>{{ $m->stock_before }}</td>
                                    <td>{{ $m->stock_after }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">هنوز گردش ثبت نشده.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3 fw-bold">دسترسی سریع</h6>

                <div class="d-grid gap-2">
                    <a class="btn btn-outline-primary" href="{{ route('products.index') }}">کالاها</a>
                    <a class="btn btn-outline-dark" href="{{ route('movements.index') }}">گردش انبار</a>
                    <a class="btn btn-outline-secondary" href="{{ route('vouchers.index') }}">حواله‌ها</a>
                    <a class="btn btn-outline-secondary" href="{{ route('stocktake.index') }}">انبارگردانی</a>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
