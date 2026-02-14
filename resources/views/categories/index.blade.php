@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">دسته‌بندی‌ها</h4>
    <a class="btn btn-primary" href="{{ route('categories.create') }}">+ افزودن دسته‌بندی</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>نام</th>
                        <th class="text-end">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $cat)
                        <tr>
                            <td>{{ $cat->id }}</td>
                            <td class="fw-semibold">{{ $cat->name }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('categories.edit', $cat) }}">ویرایش</a>

                                <form class="d-inline" method="POST" action="{{ route('categories.destroy', $cat) }}"
                                      onsubmit="return confirm('حذف شود؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">هیچ دسته‌بندی ثبت نشده.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($categories, 'links'))
            <div class="mt-3">{{ $categories->links() }}</div>
        @endif
    </div>
</div>
@endsection
