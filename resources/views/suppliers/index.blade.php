@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="page-title mb-0">تامین‌کننده‌ها</h4>
    <a class="btn btn-outline-secondary" href="{{ route('purchases.create') }}">بازگشت به خرید جدید</a>
</div>

<div class="card mb-3" id="add-supplier-form">
    <div class="card-body">
        <form method="POST" action="{{ route('suppliers.store') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label">نام تامین‌کننده</label>
                <input name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">شماره تماس</label>
                <input name="phone" class="form-control" value="{{ old('phone') }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">استان (اختیاری)</label>
                <input name="province" class="form-control" value="{{ old('province') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">شهر (اختیاری)</label>
                <input name="city" class="form-control" value="{{ old('city') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">آدرس (اختیاری)</label>
                <input name="address" class="form-control" value="{{ old('address') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">کد پستی (اختیاری)</label>
                <input name="postal_code" class="form-control" value="{{ old('postal_code') }}">
            </div>
            <div class="col-md-11">
                <label class="form-label">توضیحات اضافی (اختیاری)</label>
                <textarea name="additional_notes" class="form-control" rows="2">{{ old('additional_notes') }}</textarea>
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary w-100">ثبت</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>شماره تماس</th>
                    <th>استان/شهر</th>
                    <th>آدرس</th>
                    <th>کد پستی</th>
                    <th>توضیحات اضافی</th>
                    <th>تاریخ ثبت</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                    <tr>
                        <td>{{ $supplier->name }}</td>
                        <td>{{ $supplier->phone ?: '-' }}</td>
                        <td>
                            @if($supplier->province || $supplier->city)
                                {{ $supplier->province ?: '-' }} / {{ $supplier->city ?: '-' }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $supplier->address ?: '-' }}</td>
                        <td>{{ $supplier->postal_code ?: '-' }}</td>
                        <td>{{ $supplier->additional_notes ?: '-' }}</td>
                        <td>{{ $supplier->created_at->format('Y/m/d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">تامین‌کننده‌ای ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">{{ $suppliers->links() }}</div>
    </div>
</div>
@endsection
