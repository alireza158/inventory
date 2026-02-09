@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h4 class="page-title mb-0">داشبورد</h4>
    <div class="text-muted small">آخرین بروزرسانی: {{ now()->format('Y/m/d H:i') }}</div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted">تعداد محصولات</div>
                <div class="fs-3 fw-bold mt-1">{{ number_format($totalProducts ?? 0) }}</div>
                <div class="small text-muted mt-2">کل کالاهای تعریف‌شده</div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted">کالاهای موجود</div>
                <div class="fs-3 fw-bold mt-1 text-success">{{ number_format($inStock ?? 0) }}</div>
                <div class="small text-muted mt-2">موجودی بیشتر از صفر</div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted">کم‌موجودی</div>
                <div class="d-flex align-items-end gap-2 mt-1">
                    <div class="fs-3 fw-bold text-warning">{{ number_format($lowStock ?? 0) }}</div>
                    <div class="small text-muted mb-1">{{ $lowStockRate ?? 0 }}%</div>
                </div>
                <div class="small text-muted mt-2">تا آستانه {{ number_format($lowStockThreshold ?? 0) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted">ناموجود</div>
                <div class="fs-3 fw-bold mt-1 text-danger">{{ number_format($outOfStock ?? 0) }}</div>
                <div class="small text-muted mt-2">موجودی صفر</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
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
                                    <td class="fw-bold">{{ number_format($m->quantity) }}</td>
                                    <td>{{ number_format($m->stock_before) }}</td>
                                    <td>{{ number_format($m->stock_after) }}</td>
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
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="mb-3 fw-bold">خلاصه امروز</h6>
                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-muted">ورودی امروز</span>
                    <span class="fw-semibold text-success">{{ number_format($todayMovements->total_in ?? 0) }}</span>
                </div>
                <div class="d-flex justify-content-between small mb-3">
                    <span class="text-muted">خروجی امروز</span>
                    <span class="fw-semibold text-danger">{{ number_format($todayMovements->total_out ?? 0) }}</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">ارزش موجودی</span>
                    <span class="fw-bold">{{ number_format($totalStockValue ?? 0) }} تومان</span>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="mb-3 fw-bold">اقلام نزدیک به اتمام</h6>
                <ul class="list-group list-group-flush">
                    @forelse(($topLowStockProducts ?? []) as $product)
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">{{ $product->name }}</div>
                                <div class="small text-muted">{{ $product->sku }}</div>
                            </div>
                            <span class="badge text-bg-warning">{{ number_format($product->stock) }}</span>
                        </li>
                    @empty
                        <li class="list-group-item px-0 text-muted small">موردی برای نمایش وجود ندارد.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
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
