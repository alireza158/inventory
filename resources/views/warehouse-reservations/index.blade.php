@extends('layouts.app')

@section('title', 'ردیابی رزروهای موجودی')

@section('content')
<div class="container-fluid" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">ردیابی رزروهای موجودی</h1>
            <div class="text-muted small">نمایش محل درگیری موجودی‌های رزروشده؛ آزادسازی فقط برای رزرو موقت draft مجاز است.</div>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-3 mb-3">
        @foreach([
            'کل رزروهای فعال' => $stats['total_active'],
            'رزروهای موقت فعال' => $stats['draft_active'],
            'موقت بالای ۲۰ ساعت' => $stats['draft_over_20h'],
            'پیش‌فاکتورهای ثبت‌شده' => $stats['preinvoice_active'],
            'مشکوک قابل آزادسازی' => $stats['suspicious_releasable'],
        ] as $label => $value)
            <div class="col-md"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ number_format($value) }}</div></div></div></div>
        @endforeach
    </div>

    <form class="card border-0 shadow-sm mb-3" method="GET" action="{{ route('warehouse.reservations.index') }}">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">جستجوی کالا / کد / تنوع</label><input name="q" value="{{ $filters['q'] ?? '' }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">ثبت‌کننده</label><select name="user_id" class="form-select"><option value="">همه</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">مشتری</label><select name="customer_id" class="form-select"><option value="">همه</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected(($filters['customer_id'] ?? '') == $customer->id)>{{ $customer->name ?: $customer->phone }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">نوع رزرو</label><select name="type" class="form-select"><option value="">همه</option><option value="draft" @selected(($filters['type'] ?? '')==='draft')>رزرو موقت</option><option value="preinvoice" @selected(($filters['type'] ?? '')==='preinvoice')>پیش‌فاکتور</option><option value="invoice" @selected(($filters['type'] ?? '')==='invoice')>فاکتور</option><option value="transfer" @selected(($filters['type'] ?? '')==='transfer')>حواله</option></select></div>
            <div class="col-md-1"><label class="form-label">از تاریخ</label><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control"></div>
            <div class="col-md-1"><label class="form-label">تا تاریخ</label><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control"></div>
            <div class="col-md-3 form-check mt-4"><input class="form-check-input" type="checkbox" name="older_than_20" value="1" @checked(!empty($filters['older_than_20'])) id="old20"><label class="form-check-label" for="old20">فقط بالای ۲۰ ساعت</label></div>
            <div class="col-md-3 form-check mt-4"><input class="form-check-input" type="checkbox" name="releasable_only" value="1" @checked(!empty($filters['releasable_only'])) id="rel"><label class="form-check-label" for="rel">فقط قابل آزادسازی</label></div>
            <div class="col-md-6 text-end"><button class="btn btn-primary">اعمال فیلتر</button><a href="{{ route('warehouse.reservations.index') }}" class="btn btn-outline-secondary">پاک کردن</a></div>
        </div>
    </form>

    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>کالا</th><th>تنوع / مدل / رنگ</th><th>کد / SKU</th><th>تعداد</th><th>نوع رزرو</th><th>مدت</th><th>ثبت‌کننده</th><th>مشتری</th><th>شماره سند</th><th>وضعیت سند</th><th>زمان ایجاد</th><th>هشدار</th><th>عملیات</th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row['product'] }}</td><td>{{ $row['variant'] }}</td><td dir="ltr">{{ $row['sku'] }}</td><td>{{ $row['quantity'] }}</td><td>{{ $row['type_label'] }}</td><td>{{ $row['age_hours'] }} ساعت</td><td>{{ $row['user'] }}</td><td>{{ $row['customer'] }}</td><td>{{ $row['document_no'] }}</td><td>{{ $row['document_status'] }}</td><td>{{ $row['created_at']->format('Y-m-d H:i') }}</td>
                <td>@if($row['alert']==='red')<span class="badge bg-danger">قرمز</span>@elseif($row['alert']==='yellow')<span class="badge bg-warning text-dark">زرد</span>@else<span class="badge bg-success">عادی</span>@endif @unless($row['releasable'])<span class="badge bg-secondary">غیرقابل آزادسازی</span>@endunless</td>
                <td class="text-nowrap">
                    @if($row['releasable'] && $row['reservation'])
                        <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#details-{{ $row['source_id'] }}">جزئیات</button>
                        @canPermission('warehouse.reservations.release')
                        <button class="btn btn-sm btn-danger" type="button" data-bs-toggle="modal" data-bs-target="#release-{{ $row['source_id'] }}">آزادسازی</button>
                        @endcanPermission
                    @elseif($row['document_url'])
                        <a class="btn btn-sm btn-outline-primary" href="{{ $row['document_url'] }}">مشاهده سند</a>
                    @else — @endif
                </td>
            </tr>
            @if($row['reservation'])
            <tr class="collapse" id="details-{{ $row['source_id'] }}"><td colspan="13" class="bg-light small">توکن: <span dir="ltr">{{ $row['reservation']->token }}</span> | expires_at: {{ $row['reservation']->expires_at?->format('Y-m-d H:i') ?? '—' }} | converted_at: {{ $row['reservation']->converted_at?->format('Y-m-d H:i') ?? '—' }} | released_at: {{ $row['reservation']->released_at?->format('Y-m-d H:i') ?? '—' }}</td></tr>
            <div class="modal fade" id="release-{{ $row['source_id'] }}" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="{{ route('warehouse.reservations.draft.release', $row['reservation']) }}">@csrf<div class="modal-header"><h5 class="modal-title">آزادسازی رزرو موقت</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">دلیل آزادسازی</label><select name="release_reason" class="form-select" required>@foreach(['کاربر پیش‌فاکتور را ثبت نکرده','رزرو اشتباه ایجاد شده','مشتری منصرف شده','اصلاح موجودی','سایر'] as $reason)<option value="{{ $reason }}">{{ $reason }}</option>@endforeach</select><label class="form-label mt-3">توضیحات تکمیلی</label><textarea name="release_note" class="form-control" rows="3"></textarea></div><div class="modal-footer"><button class="btn btn-danger">آزادسازی امن</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button></div></form></div></div>
            @endif
        @empty <tr><td colspan="13" class="text-center text-muted py-4">رزروی یافت نشد.</td></tr>@endforelse
        </tbody>
    </table></div></div>
</div>
@endsection
