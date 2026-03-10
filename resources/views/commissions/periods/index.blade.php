<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">دوره‌های پورسانت</h2></x-slot>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white p-4 rounded shadow">
            <form method="POST" action="{{ route('commissions.periods.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                @csrf
                <input name="title" placeholder="عنوان دوره" class="border rounded p-2" required>
                <input name="start_date" type="date" class="border rounded p-2" required>
                <input name="end_date" type="date" class="border rounded p-2" required>
                <select name="status" class="border rounded p-2" required>
                    <option value="draft">draft</option><option value="active">active</option><option value="closed">closed</option>
                </select>
                <button class="bg-indigo-600 text-white rounded p-2">ایجاد دوره</button>
            </form>
        </div>

        <div class="bg-white p-4 rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr><th>عنوان</th><th>بازه</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                <tbody>
                @foreach($periods as $period)
                    <tr class="border-t">
                        <td>{{ $period->title }}</td>
                        <td>{{ $period->start_date?->format('Y-m-d') }} تا {{ $period->end_date?->format('Y-m-d') }}</td>
                        <td>{{ $period->status }}</td>
                        <td class="py-2">
                            <div class="flex gap-3">
                                <form method="POST" action="{{ route('commissions.reports.calculate', $period) }}">@csrf<button class="text-indigo-600">اجرای محاسبه</button></form>
                                <form method="POST" action="{{ route('commissions.periods.close', $period) }}">@csrf<button class="text-red-600">بستن دوره</button></form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="mt-3">{{ $periods->links() }}</div>
        </div>
    </div>
</x-app-layout>
