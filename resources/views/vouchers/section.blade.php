@extends('layouts.app')

@section('content')
@php
    $titles = [
        'return-from-sale' => 'برگشت از فروش',
        'scrap' => 'انبار ضایعات',
        'personnel' => 'حواله پرسنل',
        'transfer' => 'حواله بین انباری',
    ];

    $toToman = fn($rial) => number_format((int) floor(((int) $rial) / 10));

    $isCustomerReturn = $voucherType === \App\Models\WarehouseTransfer::TYPE_CUSTOMER_RETURN;

    $pageItems = method_exists($vouchers, 'getCollection') ? $vouchers->getCollection() : collect($vouchers);
    $pageCount = $pageItems->count();
    $pageTotalAmount = (int) $pageItems->sum('total_amount');
@endphp

<style>
    :root{
        --brd:#e8edf3;
        --soft:#f8fafc;
        --soft2:#f3f6fb;
        --text:#0f172a;
        --muted:#64748b;
        --blue:#2563eb;
        --blue-soft:#eff6ff;
        --green-soft:#ecfdf5;
        --shadow:0 12px 28px rgba(15,23,42,.06);
    }

    .page-wrap{
        padding: 6px 0 24px;
    }

    .hero-box{
        border:1px solid var(--brd);
        border-radius:22px;
        background:linear-gradient(135deg,#ffffff,#f8fbff 55%,#eef6ff);
        box-shadow:var(--shadow);
        overflow:hidden;
    }

    .hero-title{
        font-size:28px;
        font-weight:900;
        color:var(--text);
        margin-bottom:6px;
    }

    .hero-sub{
        color:var(--muted);
        font-size:14px;
        line-height:1.9;
        margin-bottom:0;
    }

    .soft-chip{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:8px 12px;
        border-radius:999px;
        border:1px solid var(--brd);
        background:#fff;
        font-size:12px;
        font-weight:700;
        color:var(--text);
    }

    .stat-card{
        border:none;
        border-radius:18px;
        box-shadow:var(--shadow);
        height:100%;
    }

    .stat-card .card-body{
        padding:18px;
    }

    .stat-label{
        color:var(--muted);
        font-size:12px;
        margin-bottom:8px;
    }

    .stat-value{
        color:var(--text);
        font-size:26px;
        font-weight:900;
        line-height:1.2;
    }

    .filter-card,
    .table-card{
        border:none;
        border-radius:20px;
        box-shadow:var(--shadow);
        overflow:hidden;
    }

    .section-head{
        padding:14px 18px;
        border-bottom:1px solid var(--brd);
        background:linear-gradient(0deg,#fff,var(--soft2));
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
    }

    .section-title{
        font-size:15px;
        font-weight:900;
        margin:0;
        color:var(--text);
    }

    .section-sub{
        color:var(--muted);
        font-size:12px;
        margin:4px 0 0;
    }

    .table-clean{
        margin-bottom:0;
    }

    .table-clean thead th{
        white-space:nowrap;
        font-size:12px;
        color:var(--muted);
        font-weight:800;
        background:#fbfcfe;
        border-bottom-width:1px;
    }

    .table-clean tbody td{
        vertical-align:middle;
        font-size:13px;
    }

    .customer-box .name{
        font-weight:800;
        color:var(--text);
    }

    .customer-box .meta{
        font-size:12px;
        color:var(--muted);
        margin-top:2px;
    }

    .money-strong{
        font-weight:900;
        color:#0f172a;
        white-space:nowrap;
    }

    .code-pill{
        display:inline-flex;
        align-items:center;
        padding:6px 10px;
        border-radius:999px;
        background:#f8fafc;
        border:1px solid var(--brd);
        font-size:12px;
        font-weight:700;
    }

    .reason-pill{
        display:inline-flex;
        align-items:center;
        padding:6px 10px;
        border-radius:999px;
        background:var(--blue-soft);
        border:1px solid #dbeafe;
        color:#1d4ed8;
        font-size:12px;
        font-weight:700;
    }

    .action-group{
        display:flex;
        gap:8px;
        justify-content:flex-end;
        flex-wrap:wrap;
    }

    .empty-state{
        text-align:center;
        padding:56px 20px;
        color:var(--muted);
    }

    .mobile-cards{
        display:none;
    }

    .return-mobile-card{
        border:1px solid var(--brd);
        border-radius:18px;
        background:#fff;
        padding:14px;
        box-shadow:0 8px 20px rgba(15,23,42,.04);
        margin-bottom:12px;
    }

    .return-mobile-card .title{
        font-weight:900;
        color:var(--text);
        margin-bottom:10px;
    }

    .return-grid{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px;
    }

    .return-grid .item small{
        display:block;
        color:var(--muted);
        font-size:11px;
        margin-bottom:2px;
    }

    .return-grid .item div{
        font-size:13px;
        font-weight:700;
        color:var(--text);
    }

    @media (max-width: 991.98px){
        .desktop-table{
            display:none;
        }
        .mobile-cards{
            display:block;
        }
    }
</style>

<div class="container page-wrap">
    <div class="hero-box mb-4">
        <div class="p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <div class="hero-title">{{ $titles[$type] ?? 'حواله' }}</div>
                    <p class="hero-sub">
                        @if($isCustomerReturn)
                            در این بخش می‌توانی برگشت‌های ثبت‌شده از فروش را با فیلتر مشتری، علت برگشت و بازه تاریخی بررسی، ویرایش یا حذف کنی.
                        @else
                            لیست حواله‌های ثبت‌شده این بخش را ببین و آن‌ها را مدیریت کن.
                        @endif
                    </p>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="soft-chip">نوع: {{ $titles[$type] ?? 'حواله' }}</span>
                        <span class="soft-chip">تعداد در این صفحه: {{ number_format($pageCount) }}</span>
                        @if($isCustomerReturn)
                            <span class="soft-chip">جمع مبلغ این صفحه: {{ $toToman($pageTotalAmount) }} تومان</span>
                        @endif
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-outline-secondary" href="{{ route('vouchers.index') }}">بازگشت</a>
                    <a class="btn btn-primary" href="{{ route('vouchers.section.create', $type) }}">+ ثبت جدید</a>
                </div>
            </div>
        </div>
    </div>

    @if($voucherType === \App\Models\WarehouseTransfer::TYPE_CUSTOMER_RETURN)
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">مشتری</label>
                        <select name="customer_id" class="form-select form-select-sm">
                            <option value="">همه مشتری‌ها</option>
                            @foreach($customers as $customer)
                                @php
                                    $customerTitle = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: ('مشتری #' . $customer->id);
                                @endphp
                                <option value="{{ $customer->id }}" @selected((int) $requestCustomerId === (int) $customer->id)>
                                    {{ $customerTitle }} | {{ $customer->mobile }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">علت برگشت</label>
                        <select name="return_reason" class="form-select form-select-sm">
                            <option value="">همه علت‌ها</option>
                            @foreach($returnReasons as $reasonKey => $reasonTitle)
                                <option value="{{ $reasonKey }}" @selected($returnReason === $reasonKey)>{{ $reasonTitle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">از تاریخ</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">تا تاریخ</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-sm btn-primary w-100">فیلتر</button>
                        <a href="{{ route('vouchers.section.index', $type) }}" class="btn btn-sm btn-outline-secondary w-100">حذف فیلتر</a>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($voucherType === \App\Models\WarehouseTransfer::TYPE_CUSTOMER_RETURN)
        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>شماره</th>
                        <th>تاریخ</th>
                        <th>مبدا</th>
                        <th>مقصد</th>
                        <th>کاربر</th>
                        <th>فاکتور مرجع</th>
                        <th>علت برگشت</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($vouchers as $voucher)
                        <tr>
                            <td>{{ $voucher->id }}</td>
                            <td>{{ $voucher->reference ?: ('TR-'.$voucher->id) }}</td>
                            <td>{{ $voucher->transferred_at?->format('Y/m/d H:i') }}</td>
                            <td>{{ $voucher->fromWarehouse?->name ?: '—' }}</td>
                            <td>{{ $voucher->toWarehouse?->name ?: '—' }}</td>
                            <td>{{ $voucher->user?->name ?: '—' }}</td>
                            <td>{{ $voucher->relatedInvoice?->uuid ?: '—' }}</td>
                            <td>{{ \App\Models\WarehouseTransfer::returnReasonOptions()[$voucher->return_reason] ?? '—' }}</td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('vouchers.edit', $voucher) }}">ویرایش</a>
                                <form method="POST" action="{{ route('vouchers.destroy', $voucher) }}" class="d-inline" onsubmit="return confirm('از حذف برگشت از فروش مطمئن هستید؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">موردی ثبت نشده است.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
            <div class="table-responsive">
                <table class="table table-clean table-hover mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>شماره</th>
                        <th>تاریخ</th>
                        <th>مبدا</th>
                        <th>مقصد</th>
                        <th>کاربر</th>
                        <th>فاکتور مرجع</th>
                        <th class="text-end">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($vouchers as $voucher)
                        <tr>
                            <td>{{ $voucher->id }}</td>
                            <td>{{ $voucher->reference ?: ('TR-'.$voucher->id) }}</td>
                            <td>{{ $voucher->transferred_at?->format('Y/m/d H:i') }}</td>
                            <td>{{ $voucher->fromWarehouse?->name ?: '—' }}</td>
                            <td>{{ $voucher->toWarehouse?->name ?: '—' }}</td>
                            <td>{{ $voucher->user?->name ?: '—' }}</td>
                            <td>{{ $voucher->relatedInvoice?->uuid ?: '—' }}</td>
                            <td>
                                <div class="action-group">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('vouchers.edit', $voucher) }}">ویرایش</a>
                                    <form method="POST" action="{{ route('vouchers.destroy', $voucher) }}" class="d-inline" onsubmit="return confirm('از حذف حواله مطمئن هستید؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">موردی ثبت نشده است.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
    @endif

    <div class="mt-3">
        {{ $vouchers->links() }}
    </div>
</div>
@endsection
