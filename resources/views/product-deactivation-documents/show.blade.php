@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        @php
            $isActive = $document->deactivation_type === \App\Models\ProductDeactivationDocument::TYPE_PRODUCT
                ? (bool) ($document->product?->is_sellable)
                : (bool) ($document->variant?->is_active);
        @endphp

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">جزئیات سند غیرفعال‌سازی</h4>
            <a href="{{ route('product-deactivation-documents.index') }}" class="btn btn-outline-secondary">بازگشت</a>
        </div>

        <div class="row g-3">
            <div class="col-md-4"><b>شماره سند:</b> {{ $document->document_number }}</div>
            <div class="col-md-4"><b>تاریخ:</b> {{ verta($document->created_at)->format('Y/m/d H:i') }}</div>
            <div class="col-md-4"><b>نوع:</b> {{ $typeLabels[$document->deactivation_type] ?? $document->deactivation_type }}</div>
            <div class="col-md-6"><b>محصول:</b> {{ $document->product_name_snapshot ?: ($document->product?->name ?? '-') }}</div>
            <div class="col-md-6"><b>تنوع:</b> {{ $document->variant_name_snapshot ?: ($document->variant?->variant_name ?? '-') }}</div>
            <div class="col-md-6"><b>علت:</b> {{ $reasonLabels[$document->reason_type] ?? $document->reason_type }}</div>
            <div class="col-md-6"><b>ثبت‌کننده:</b> {{ $document->creator?->name ?? '-' }}</div>
            <div class="col-md-6"><b>وضعیت فعلی:</b> <span class="badge {{ $isActive ? 'bg-success' : 'bg-secondary' }}">{{ $isActive ? 'فعال' : 'غیرفعال' }}</span></div>
            <div class="col-md-12"><b>توضیح علت:</b><div class="mt-1 border rounded p-2 bg-light">{{ $document->reason_text }}</div></div>
            <div class="col-md-12"><b>توضیحات تکمیلی:</b><div class="mt-1 border rounded p-2 bg-light">{{ $document->description ?: '-' }}</div></div>
        </div>
    </div>
</div>
@endsection
