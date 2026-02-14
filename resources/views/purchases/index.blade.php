@extends('layouts.app')

@section('content')
@php
    $toToman = fn($rial) => number_format((int) floor(((int) $rial) / 10));
@endphp

<style>
    :root{
        --ink: #0b1220;
        --navy: #071a3a;
        --blue: #0d6efd;
        --blue2:#0a58ca;
        --soft: #f6f9ff;
        --soft2:#eef4ff;
        --border: #dbe6ff;
        --shadow: 0 10px 28px rgba(7, 26, 58, .10);
        --shadow2:0 6px 14px rgba(7, 26, 58, .08);
    }

    .purchase-page-wrap{
        background: #fff;
        border-radius: 18px;
        padding: 14px;
    }

    .purchase-topbar{
        background: linear-gradient(90deg, var(--navy), var(--blue2));
        border-radius: 16px;
        padding: 12px 14px;
        box-shadow: var(--shadow2);
        color: #fff;
        margin-bottom: 14px;
    }
    .purchase-topbar .page-title{ color:#fff; margin:0; }
    .purchase-topbar .btn{ border-radius: 12px; }
    .purchase-topbar .btn-light{
        background: rgba(255,255,255,.92);
        border: none;
        color: var(--navy);
        font-weight: 700;
    }
    .purchase-topbar .btn-light:hover{ filter: brightness(0.98); }

    /* stat cards */
    .stat-card{
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: var(--shadow2);
        overflow: hidden;
        background: #fff;
        position: relative;
    }
    .stat-card::before{
        content:"";
        position:absolute;
        inset:0 auto 0 0;
        width:7px;
        background: linear-gradient(180deg, var(--navy), var(--blue));
    }
    .stat-card .card-body{ padding: 14px; }
    .stat-card .label{
        color: rgba(32, 62, 122, 0.65);
        font-size: .8rem;
    }
    .stat-card .value{
        color: var(--ink);
        font-weight: 700;
        font-size: 1.1rem;
    }

    /* filter card */
    .filter-card{
        border: 1px solid var(--border);
        border-radius: 18px;
        box-shadow: var(--shadow2);
        background: linear-gradient(180deg, var(--soft), #fff);
    }
    .filter-card .card-body{ padding: 14px; }
    .filter-card .form-label{
        color: rgba(33, 61, 117, 0.7);
        font-size: .85rem;
        margin-bottom: .35rem;
    }
    .filter-card .form-control,
    .filter-card .form-select{
        height: 40px;
        font-size: .9rem;
        border-radius: 12px;
        border: 1px solid rgba(44, 70, 122, 0.12);
        background: #fff;
        color: var(--ink);
    }
    .filter-card .form-control:focus,
    .filter-card .form-select:focus{
        outline: none;
        border-color: rgba(13,110,253,.65);
        box-shadow: 0 0 0 .25rem rgba(255, 255, 255, 1);
    }
    .filter-actions .btn{ border-radius: 12px; }
    .filter-actions .btn-primary{
        background: linear-gradient(90deg, var(--blue2), var(--blue));
        border: none;
        box-shadow: 0 10px 20px rgba(13,110,253,.18);
    }

    /* table card */
    .table-card{
        border: 1px solid var(--border);
        border-radius: 18px;
        box-shadow: var(--shadow);
        overflow: hidden;
        background: #fff;
    }
    .table-card .card-body{ padding: 0; }

    .table-card .table{ margin:0; }
    .table-card thead th{
        background: linear-gradient(90deg, rgba(18, 95, 226, 0.95), rgba(10,88,202,.95));
        color: #fff;
        border: none;
        font-weight: 800;
        font-size: .9rem;
        padding: 12px 10px;
        white-space: nowrap;
    }
    .table-card tbody td{
        padding: 12px 10px;
        vertical-align: middle;
        color: rgba(11,18,32,.88);
    }
    .table-card tbody tr:hover{
        background: rgba(13,110,253,.06);
    }

    .badge-items{
        background: rgba(7,26,58,.10);
        color: var(--navy);
        border: 1px solid rgba(7,26,58,.12);
        padding: .35rem .55rem;
        border-radius: 999px;
        font-weight: 800;
    }

    .amount-strong{
        color: var(--ink);
        font-weight: 900;
    }

    .action-btns .btn{ border-radius: 12px; }
    .action-btns .btn-outline-secondary:hover,
    .action-btns .btn-outline-primary:hover{
        background: rgba(13,110,253,.06);
        border-color: rgba(13,110,253,.45);
    }

    /* pagination */
    .pagination{
        margin-bottom: 0;
    }
</style>

<div class="purchase-page-wrap">

    <div class="purchase-topbar d-flex justify-content-between align-items-center">
        <h4 class="page-title mb-0">خرید کالا</h4>
        <a class="btn btn-light" href="{{ route('purchases.create') }}">+ ثبت خرید جدید</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="label">مبلغ کل خریدها تا الان</div>
                    <div class="value">{{ $toToman($totalAllAmount) }} تومان</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="label">مبلغ کل نتیجه فیلتر</div>
                    <div class="value">{{ $toToman($totalFilteredAmount) }} تومان</div>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" class="card filter-card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">از تاریخ</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">تا تاریخ</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">تامین‌کننده</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">همه</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">حداقل مبلغ فاکتور (تومان)</label>
                    <input type="number" min="0" name="min_total" class="form-control" value="{{ request('min_total') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">حداکثر مبلغ فاکتور (تومان)</label>
                    <input type="number" min="0" name="max_total" class="form-control" value="{{ request('max_total') }}">
                </div>

                <div class="col-md-9 d-flex gap-2 filter-actions">
                    <button class="btn btn-primary">جستجو</button>
                    <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">حذف فیلتر</a>
                </div>
            </div>
        </div>
    </form>

    <div class="card table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>تاریخ</th>
                            <th>تامین‌کننده</th>
                            <th>تخفیف سند</th>
                            <th>مبلغ کل فاکتور</th>
                            <th>تعداد آیتم</th>
                            <th class="text-end">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($purchases as $purchase)
                        <tr>
                            <td class="fw-bold">
                                {{ $loop->iteration + (($purchases->currentPage() - 1) * $purchases->perPage()) }}
                            </td>
                            <td>{{ $purchase->purchased_at?->format('Y/m/d H:i') }}</td>
                            <td>{{ $purchase->supplier?->name }}</td>
                            <td>{{ $toToman($purchase->total_discount ?? 0) }} تومان</td>
                            <td class="amount-strong">{{ $toToman($purchase->total_amount) }} تومان</td>
                            <td><span class="badge-items">{{ $purchase->items_count }}</span></td>
                            <td class="text-end action-btns">
                                <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-outline-secondary">مشاهده</a>
                                <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-sm btn-outline-primary">ویرایش</a>
                                <form method="POST" action="{{ route('purchases.destroy', $purchase) }}" class="d-inline"
                                      onsubmit="return confirm('از حذف این سند خرید مطمئن هستید؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                هیچ سند خریدی با این فیلتر ثبت نشده است.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3">
                {{-- Pagination FIX: حفظ فیلترها --}}
                {{ $purchases->appends(request()->except('page'))->links() }}
            </div>
        </div>
    </div>

</div>
@endsection
