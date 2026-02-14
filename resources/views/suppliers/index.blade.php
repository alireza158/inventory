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
                <input name="name" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">شماره تماس</label>
                <input name="phone" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">آدرس</label>
                <input name="address" class="form-control" required>
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
                    <th>آدرس</th>
                    <th>تاریخ ثبت</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                    <tr>
                        <td>{{ $supplier->name }}</td>
                        <td>{{ $supplier->phone ?: '-' }}</td>
                        <td>{{ $supplier->address ?: '-' }}</td>
                        <td>{{ $supplier->created_at->format('Y/m/d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">تامین‌کننده‌ای ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">{{ $suppliers->links() }}</div>
    </div>
</div>
@endsection
