@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="page-title mb-0">نمایش سند انبارگردانی</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('stock-count-documents.index') }}" class="btn btn-outline-secondary">بازگشت</a>
        @if($document->status === 'draft')
            <a href="{{ route('stock-count-documents.edit', $document) }}" class="btn btn-outline-primary">ویرایش</a>
        @endif
    </div>
</div>

<div class="card mb-3">
    <div class="card-body row g-3">
        <div class="col-md-3"><strong>شماره سند:</strong> {{ $document->document_number }}</div>
        <div class="col-md-3"><strong>تاریخ سند:</strong> {{ optional($document->document_date)->format('Y-m-d') }}</div>
        <div class="col-md-3"><strong>انبار:</strong> {{ $document->warehouse?->name }}</div>
        <div class="col-md-3"><strong>وضعیت:</strong> {{ $document->status }}</div>
        <div class="col-md-12"><strong>توضیحات:</strong> {{ $document->description ?: '—' }}</div>
        <div class="col-md-3"><strong>ثبت‌کننده:</strong> {{ $document->creator?->name ?? '—' }}</div>
        <div class="col-md-3"><strong>تاریخ ثبت:</strong> {{ optional($document->created_at)->format('Y-m-d H:i') }}</div>
        <div class="col-md-3"><strong>نهایی‌کننده:</strong> {{ $document->finalizer?->name ?? '—' }}</div>
        <div class="col-md-3"><strong>تاریخ نهایی‌سازی:</strong> {{ optional($document->finalized_at)->format('Y-m-d H:i') ?? '—' }}</div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead>
                <tr>
                    <th>کالا</th>
                    <th>موجودی سیستم</th>
                    <th>موجودی واقعی</th>
                    <th>اختلاف</th>
                    <th>نوع تعدیل</th>
                    <th>توضیح ردیف</th>
                </tr>
            </thead>
            <tbody>
            @foreach($document->items as $item)
                @php
                    $diff = (int) $item->difference_quantity;
                    $adjustType = $diff > 0 ? 'ورود تعدیلی (stock_adjustment_in)' : ($diff < 0 ? 'خروج تعدیلی (stock_adjustment_out)' : 'بدون تعدیل');
                @endphp
                <tr>
                    <td>{{ $item->product?->name }}</td>
                    <td>{{ $item->system_quantity }}</td>
                    <td>{{ $item->actual_quantity }}</td>
                    <td>{{ $diff }}</td>
                    <td>{{ $adjustType }}</td>
                    <td>{{ $item->description ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <h6>تاریخچه سند</h6>
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>زمان</th>
                    <th>رویداد</th>
                    <th>کاربر</th>
                    <th>توضیح</th>
                </tr>
            </thead>
            <tbody>
                @forelse($document->history->sortByDesc('done_at') as $row)
                    <tr>
                        <td>{{ optional($row->done_at)->format('Y-m-d H:i') }}</td>
                        <td>{{ $row->action_type }}</td>
                        <td>{{ $row->doer?->name ?? '—' }}</td>
                        <td>{{ $row->description ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center">تاریخچه‌ای ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
