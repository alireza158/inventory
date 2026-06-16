@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
    <div>
        <h4 class="mb-0">🔐 مدیریت دسترسی کاربران</h4>
        <div class="text-muted small">برای هر کاربر، دسترسی‌ها را به صورت گروه‌بندی‌شده انتخاب و ذخیره کنید.</div>
    </div>
</div>

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.permissions.index') }}" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label fw-bold">انتخاب کاربر</label>
                <select name="user_id" class="form-select" onchange="this.form.submit()">
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected($selectedUser?->id === $user->id)>
                            {{ $user->name }} — {{ $user->phone ?? $user->email ?? 'بدون اطلاعات تماس' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-grid">
                <button class="btn btn-outline-primary">نمایش دسترسی‌ها</button>
            </div>
        </form>
    </div>
</div>

@if($selectedUser)
    <form method="POST" action="{{ route('admin.permissions.update', $selectedUser) }}">
        @csrf
        @method('PUT')

        <div class="alert alert-info">
            در حال ویرایش دسترسی‌های <strong>{{ $selectedUser->name }}</strong> هستید.
            @if($selectedUser->hasAnyRole(['admin', 'Admin', 'ادمین']))
                <div class="small mt-1">این کاربر نقش ادمین دارد و همیشه به همه بخش‌ها دسترسی کامل خواهد داشت.</div>
            @endif
        </div>

        <div class="row g-3">
            @foreach($permissions as $group => $groupPermissions)
                <div class="col-lg-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-white fw-bold">{{ $group ?: 'سایر' }}</div>
                        <div class="card-body">
                            <div class="row g-2">
                                @foreach($groupPermissions as $permission)
                                    <div class="col-12">
                                        <label class="form-check d-flex align-items-start gap-2 m-0 p-2 rounded border bg-light">
                                            <input class="form-check-input mt-1" type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, $selectedPermissionIds))>
                                            <span>
                                                <span class="d-block fw-semibold">{{ $permission->name }}</span>
                                                <code dir="ltr" class="small">{{ $permission->key }}</code>
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="position-sticky bottom-0 bg-white border rounded shadow-sm p-3 mt-4 d-flex justify-content-end">
            <button class="btn btn-primary px-4">ذخیره دسترسی‌ها</button>
        </div>
    </form>
@else
    <div class="alert alert-warning">هیچ کاربری برای مدیریت دسترسی‌ها یافت نشد.</div>
@endif
@endsection
