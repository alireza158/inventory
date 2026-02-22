@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">دسته‌بندی‌ها</h4>
    <a class="btn btn-primary" href="{{ route('categories.create') }}">+ افزودن دسته‌بندی</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        @if($rootCategories->isEmpty())
            <div class="text-center text-muted py-4">هیچ دسته‌بندی ثبت نشده.</div>
        @else
            <div class="small text-muted mb-3">نمایش ساده دسته‌بندی‌ها و زیر‌دسته‌ها</div>
            @include('categories._manage_tree', ['nodes' => $rootCategories, 'level' => 0])
        @endif
    </div>
</div>
@endsection
