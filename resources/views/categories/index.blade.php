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
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tree-all" type="button" role="tab">درخت کامل</button>
                </li>
                @foreach($rootCategories as $root)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tree-root-{{ $root->id }}" type="button" role="tab">
                            {{ $root->name }}
                        </button>
                    </li>
                @endforeach
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#flat-list" type="button" role="tab">لیست جدولی</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tree-all" role="tabpanel">
                    <div class="small text-muted mb-2">نمای درختی والد/زیر‌دسته</div>
                    @include('categories._manage_tree', ['nodes' => $rootCategories, 'level' => 0])
                </div>

                @foreach($rootCategories as $root)
                    <div class="tab-pane fade" id="tree-root-{{ $root->id }}" role="tabpanel">
                        <div class="small text-muted mb-2">والد: {{ $root->name }}</div>
                        @include('categories._manage_tree', ['nodes' => collect([$root]), 'level' => 0])
                    </div>
                @endforeach

                <div class="tab-pane fade" id="flat-list" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>نام</th>
                                    <th>والد</th>
                                    <th class="text-end">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $cat)
                                    <tr>
                                        <td>{{ $cat->id }}</td>
                                        <td class="fw-semibold">{{ $cat->name }}</td>
                                        <td>{{ $cat->parent?->name ?: '—' }}</td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('categories.edit', $cat) }}">ویرایش</a>
                                            <form class="d-inline" method="POST" action="{{ route('categories.destroy', $cat) }}" onsubmit="return confirm('حذف شود؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">حذف</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
