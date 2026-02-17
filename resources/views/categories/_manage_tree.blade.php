@php
    $level = $level ?? 0;
@endphp

<ul class="list-unstyled mb-0">
    @foreach($nodes as $cat)
        @php
            $hasChildren = $cat->children && $cat->children->count();
            $indent = $level * 18;
        @endphp

        <li class="mb-2" style="margin-right: {{ $indent }}px;">
            <div class="d-flex align-items-center justify-content-between border rounded px-2 py-2 bg-white">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge text-bg-light border">#{{ $cat->id }}</span>
                    <span class="fw-semibold">{{ $cat->name }}</span>
                    @if($cat->parent_id)
                        <span class="badge text-bg-secondary">زیر‌دسته</span>
                    @else
                        <span class="badge text-bg-primary">والد</span>
                    @endif
                </div>

                <div class="d-flex gap-1">
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('categories.edit', $cat) }}">ویرایش</a>
                    <form method="POST" action="{{ route('categories.destroy', $cat) }}" onsubmit="return confirm('حذف شود؟')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">حذف</button>
                    </form>
                </div>
            </div>

            @if($hasChildren)
                @include('categories._manage_tree', ['nodes' => $cat->children, 'level' => $level + 1])
            @endif
        </li>
    @endforeach
</ul>
