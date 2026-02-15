@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
    <div>
        <h4 class="mb-0">👤 کاربران</h4>
        <div class="text-muted small">منبع: سرویس خارجی CRM</div>
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

@if($error)
    <div class="alert alert-danger">{{ $error }}</div>
@endif

@php
    $columns = [];
    foreach ($users as $user) {
        $columns = array_values(array_unique(array_merge($columns, array_keys($user))));
    }
@endphp

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        @if(count($columns))
                            @foreach($columns as $column)
                                <th class="text-nowrap">{{ $column }}</th>
                            @endforeach
                        @else
                            <th>کاربری یافت نشد</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            @foreach($columns as $column)
                                <td class="text-nowrap">
                                    @php
                                        $value = $user[$column] ?? null;
                                    @endphp

                                    @if(is_array($value))
                                        {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                    @elseif(is_bool($value))
                                        {{ $value ? 'true' : 'false' }}
                                    @else
                                        {{ $value ?? '-' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center text-muted py-4">لیست کاربران خالی است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
