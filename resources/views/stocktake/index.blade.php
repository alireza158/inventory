@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="page-title mb-0">انبارگردانی</h4>
    <a href="{{ route('stock-count-documents.create') }}" class="btn btn-primary">ایجاد سند جدید</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('stock-count-documents.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">انبار</label>
                <select name="warehouse_id" class="form-select">
                    <option value="">همه</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected((string) request('warehouse_id') === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">وضعیت</label>
                <select name="status" class="form-select">
                    <option value="">همه</option>
                    <option value="draft" @selected(request('status')==='draft')>پیش‌نویس</option>
                    <option value="finalized" @selected(request('status')==='finalized')>نهایی</option>
                    <option value="cancelled" @selected(request('status')==='cancelled')>لغو شده</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">شماره سند</label>
                <input name="document_number" value="{{ request('document_number') }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">تاریخ سند</label>
                <input type="date" name="document_date" value="{{ request('document_date') }}" class="form-control">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-outline-primary w-100">فیلتر</button>
                <a href="{{ route('stock-count-documents.index') }}" class="btn btn-outline-secondary">حذف</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead>
            <tr>
                <th>شماره سند</th>
                <th>تاریخ</th>
                <th>انبار</th>
                <th>وضعیت</th>
                <th>تعداد ردیف</th>
                <th>ثبت‌کننده</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($documents as $document)
                <tr>
                    <td class="fw-semibold">{{ $document->document_number }}</td>
                    <td>{{ optional($document->document_date)->format('Y-m-d') }}</td>
                    <td>{{ $document->warehouse?->name }}</td>
                    <td>
                        @if($document->status === 'draft')
                            <span class="badge text-bg-secondary">پیش‌نویس</span>
                        @elseif($document->status === 'finalized')
                            <span class="badge text-bg-success">نهایی</span>
                        @else
                            <span class="badge text-bg-danger">لغو شده</span>
                        @endif
                    </td>
                    <td>{{ $document->items_count }}</td>
                    <td>{{ $document->creator?->name ?? '—' }}</td>
                    <td class="d-flex gap-1 flex-wrap">
                        <a href="{{ route('stock-count-documents.view', $document) }}" class="btn btn-sm btn-outline-dark">مشاهده</a>
                        @if($document->status === 'draft')
                            <a href="{{ route('stock-count-documents.edit', $document) }}" class="btn btn-sm btn-outline-primary">ویرایش</a>
                            <form method="POST" action="{{ route('stock-count-documents.finalize', $document) }}" onsubmit="return confirm('سند نهایی شود؟ پس از آن قابل ویرایش نیست.')">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-success">نهایی‌سازی</button>
                            </form>
                            <form method="POST" action="{{ route('stock-count-documents.cancel', $document) }}" onsubmit="return confirm('سند لغو شود؟')">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-danger">لغو</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">سندی ثبت نشده است.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="mt-3">{{ $documents->links() }}</div>
    </div>
</div>
@endsection
