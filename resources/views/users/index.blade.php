@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
    <div>
        <h4 class="mb-0">👤 کاربران</h4>
        <div class="text-muted small">لیست کاربران ثبت‌شده در دیتابیس</div>
    </div>

    <form method="POST" action="{{ route('users.sync') }}">
        @csrf
        <button type="submit" class="btn btn-primary">🔄 سینک کاربران</button>
    </form>
</div>

@if(session('sync_success'))
    <div class="alert alert-success">{{ session('sync_success') }}</div>
@endif

@if(session('sync_error'))
    <div class="alert alert-danger">{{ session('sync_error') }}</div>
@endif

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>شناسه داخلی</th>
                        <th>شناسه CRM</th>
                        <th>نام</th>
                        <th>موبایل</th>
                        <th>ایمیل</th>
                        <th>مدیر</th>
                        <th>نقش‌ها</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->external_crm_id ?? '-' }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->phone ?? '-' }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->manager?->name ?? '-' }}</td>
                            <td>{{ $user->roles->pluck('name')->implode('، ') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">هنوز کاربری سینک نشده است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
