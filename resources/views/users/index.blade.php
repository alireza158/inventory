<x-app-layout>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">کاربران</h1>
            <span class="text-muted small">منبع: سرویس خارجی CRM</span>
        </div>

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
    </div>
</x-app-layout>
