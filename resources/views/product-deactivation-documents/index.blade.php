@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">اسناد غیرفعال‌سازی کالا</h4>
    <a href="{{ route('product-deactivation-documents.create') }}" class="btn btn-primary">ثبت سند جدید</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-2"><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" placeholder="از تاریخ"></div>
            <div class="col-md-2"><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" placeholder="تا تاریخ"></div>
            <div class="col-md-2"><input type="text" name="product_name" value="{{ request('product_name') }}" class="form-control" placeholder="نام محصول"></div>
            <div class="col-md-2"><input type="text" name="variant_name" value="{{ request('variant_name') }}" class="form-control" placeholder="نام تنوع"></div>
            <div class="col-md-2">
                <select name="deactivation_type" class="form-select">
                    <option value="">نوع عملیات</option>
                    @foreach($typeLabels as $key => $label)
                        <option value="{{ $key }}" @selected(request('deactivation_type')===$key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="reason_type" class="form-select">
                    <option value="">علت</option>
                    @foreach($reasonLabels as $key => $label)
                        <option value="{{ $key }}" @selected(request('reason_type')===$key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="created_by" class="form-select">
                    <option value="">ثبت‌کننده</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected((string)request('created_by')===(string)$u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="current_status" class="form-select">
                    <option value="">وضعیت فعلی</option>
                    <option value="active" @selected(request('current_status')==='active')>فعال</option>
                    <option value="inactive" @selected(request('current_status')==='inactive')>غیرفعال</option>
                </select>
            </div>
            <div class="col-md-8 d-flex gap-2">
                <button class="btn btn-outline-primary">جستجو</button>
                <a href="{{ route('product-deactivation-documents.index') }}" class="btn btn-outline-secondary">حذف فیلتر</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead><tr><th>شماره سند</th><th>تاریخ</th><th>نوع</th><th>محصول</th><th>تنوع</th><th>علت</th><th>ثبت‌کننده</th><th>وضعیت فعلی</th><th></th></tr></thead>
            <tbody>
            @forelse($documents as $doc)
                @php
                    $isActive = $doc->deactivation_type === \App\Models\ProductDeactivationDocument::TYPE_PRODUCT
                        ? (bool) ($doc->product?->is_sellable)
                        : (bool) ($doc->variant?->is_active);
                @endphp
                <tr>
                    <td class="fw-bold">{{ $doc->document_number }}</td>
                    <td>{{ verta($doc->created_at)->format('Y/m/d H:i') }}</td>
                    <td>{{ $typeLabels[$doc->deactivation_type] ?? $doc->deactivation_type }}</td>
                    <td>{{ $doc->product_name_snapshot ?: ($doc->product?->name ?? '-') }}</td>
                    <td>{{ $doc->variant_name_snapshot ?: ($doc->variant?->variant_name ?? '-') }}</td>
                    <td>{{ $reasonLabels[$doc->reason_type] ?? $doc->reason_type }}</td>
                    <td>{{ $doc->creator?->name ?? '-' }}</td>
                    <td><span class="badge {{ $isActive ? 'bg-success' : 'bg-secondary' }}">{{ $isActive ? 'فعال' : 'غیرفعال' }}</span></td>
                    <td><a href="{{ route('product-deactivation-documents.show', $doc) }}" class="btn btn-sm btn-outline-dark">مشاهده</a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center py-4 text-muted">سندی ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $documents->links() }}</div>
</div>
@endsection
