@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
    <div>
        <h4 class="mb-0">🔐 مدیریت دسترسی کاربران</h4>
        <div class="text-muted small">برای هر کاربر، دسترسی صفحات سایدبار و عملیات هر بخش را تک‌به‌تک انتخاب و ذخیره کنید.</div>
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


        @canPermission('permissions.assign_roles')
        <div class="card shadow-sm border-success mb-4">
            <div class="card-header bg-success text-white">
                <div class="fw-bold">نقش‌های کاربر</div>
                <div class="small opacity-75">برای جلوگیری از قفل شدن پنل، تنها مدیرکل نمی‌تواند نقش super_admin خودش را حذف کند.</div>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($roles as $role)
                        <div class="col-md-4 col-xl-3">
                            <label class="form-check d-flex align-items-center gap-2 m-0 p-2 rounded border bg-light">
                                <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(in_array($role->name, $selectedRoleNames, true))>
                                <code dir="ltr">{{ $role->name }}</code>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endcanPermission

        <div class="card shadow-sm border-primary mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div>
                    <div class="fw-bold">صفحات داخل سایدبار</div>
                    <div class="small opacity-75">دسترسی نمایش هر لینک منوی کناری را جداگانه فعال یا غیرفعال کنید.</div>
                </div>
                <span class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-light js-select-sidebar">انتخاب همه صفحات</button>
                    <button type="button" class="btn btn-sm btn-outline-light js-clear-sidebar">حذف همه صفحات</button>
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3" id="sidebarPermissionCards">
                    @foreach($sidebarPages as $section => $pages)
                        <div class="col-md-6 col-xl-4">
                            <div class="border rounded h-100 p-3 bg-light">
                                <div class="fw-bold mb-2">{{ $section }}</div>
                                <div class="d-grid gap-2">
                                    @foreach($pages as $page)
                                        @php($permission = $page['model'])
                                        <label class="form-check d-flex align-items-start gap-2 m-0 p-2 rounded border bg-white">
                                            <input class="form-check-input mt-1" type="checkbox" name="permissions[]" value="{{ $permission->id }}" data-permission-id="{{ $permission->id }}" @checked(in_array($permission->id, $selectedPermissionIds))>
                                            <span>
                                                <span class="d-block fw-semibold">{{ $page['label'] }}</span>
                                                <code dir="ltr" class="small">{{ $permission->key }}</code>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <h5 class="fw-bold mb-3">همه دسترسی‌های عملیاتی</h5>
        <div class="row g-3">
            @foreach($permissions as $group => $groupPermissions)
                <div class="col-lg-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm permission-group-card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center gap-2 flex-wrap">
                            <span class="fw-bold">{{ $group ?: 'سایر' }}</span>
                            <span class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-success js-select-group">انتخاب همه</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary js-clear-group">حذف همه</button>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                @foreach($groupPermissions as $permission)
                                    <div class="col-12">
                                        <label class="form-check d-flex align-items-start gap-2 m-0 p-2 rounded border bg-light">
                                            <input class="form-check-input mt-1" type="checkbox" name="permissions[]" value="{{ $permission->id }}" data-permission-id="{{ $permission->id }}" @checked(in_array($permission->id, $selectedPermissionIds))>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    function setPermissionChecked(permissionId, checked) {
        document.querySelectorAll('input[data-permission-id="' + permissionId + '"]').forEach(function (checkbox) {
            checkbox.checked = checked;
        });
    }

    document.querySelectorAll('input[data-permission-id]').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            setPermissionChecked(checkbox.dataset.permissionId, checkbox.checked);
        });
    });

    document.querySelector('.js-select-sidebar')?.addEventListener('click', function () {
        document.querySelectorAll('#sidebarPermissionCards input[data-permission-id]').forEach(function (checkbox) {
            setPermissionChecked(checkbox.dataset.permissionId, true);
        });
    });

    document.querySelector('.js-clear-sidebar')?.addEventListener('click', function () {
        document.querySelectorAll('#sidebarPermissionCards input[data-permission-id]').forEach(function (checkbox) {
            setPermissionChecked(checkbox.dataset.permissionId, false);
        });
    });

    document.querySelectorAll('.permission-group-card').forEach(function (card) {
        card.querySelector('.js-select-group')?.addEventListener('click', function () {
            card.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
                setPermissionChecked(checkbox.dataset.permissionId, true);
            });
        });

        card.querySelector('.js-clear-group')?.addEventListener('click', function () {
            card.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
                setPermissionChecked(checkbox.dataset.permissionId, false);
            });
        });
    });
});
</script>
@endsection
