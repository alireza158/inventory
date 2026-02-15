@extends('layouts.app')

@section('content')
@php
    use Morilog\Jalali\Jalalian;
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="page-title mb-0">تامین‌کننده‌ها</h4>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#supplierModal">
            افزودن تامین‌کننده
        </button>
        <a class="btn btn-outline-secondary" href="{{ route('purchases.create') }}">بازگشت به خرید جدید</a>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="supplierModal" tabindex="-1" aria-labelledby="supplierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="supplierModalLabel">افزودن تامین‌کننده</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ route('suppliers.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">نام تامین‌کننده</label>
                            <input name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">شماره تماس</label>
                            <input name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone') }}" required>
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">کد پستی (اختیاری)</label>
                            <input name="postal_code"
                                   class="form-control @error('postal_code') is-invalid @enderror"
                                   value="{{ old('postal_code') }}">
                            @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">استان (اختیاری)</label>
                            <input name="province"
                                   class="form-control @error('province') is-invalid @enderror"
                                   value="{{ old('province') }}">
                            @error('province') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">شهر (اختیاری)</label>
                            <input name="city"
                                   class="form-control @error('city') is-invalid @enderror"
                                   value="{{ old('city') }}">
                            @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">آدرس (اختیاری)</label>
                            <input name="address"
                                   class="form-control @error('address') is-invalid @enderror"
                                   value="{{ old('address') }}">
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">توضیحات اضافی (اختیاری)</label>
                            <textarea name="additional_notes"
                                      class="form-control @error('additional_notes') is-invalid @enderror"
                                      rows="3">{{ old('additional_notes') }}</textarea>
                            @error('additional_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">بستن</button>
                    <button class="btn btn-primary">ثبت</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- List --}}
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
                        <td>{{ $supplier->created_at ? Jalalian::fromDateTime($supplier->created_at)->format('Y/m/d') : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">تامین‌کننده‌ای ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">{{ $suppliers->links() }}</div>
    </div>
</div>

{{-- Auto-open modal if validation errors exist --}}
@if ($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = new bootstrap.Modal(document.getElementById('supplierModal'));
    modal.show();
});
</script>
@endif

@endsection
