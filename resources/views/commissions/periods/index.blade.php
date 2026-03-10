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
                    <option value="draft">draft</option>
                    <option value="active">active</option>
                    <option value="closed">closed</option>
                </select>
                <button class="bg-indigo-600 text-white rounded p-2">ایجاد دوره</button>
            </form>
        </div>

        <div class="bg-white p-4 rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr>
                        <th class="text-right p-2">عنوان</th>
                        <th class="text-right p-2">شروع</th>
                        <th class="text-right p-2">پایان</th>
                        <th class="text-right p-2">وضعیت</th>
                        <th class="text-right p-2">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($periods as $period)
                    <tr class="border-t align-top">
                        <td class="p-2">
                            <form method="POST" action="{{ route('commissions.periods.update', $period) }}" class="space-y-2">
                                @csrf
                                @method('PUT')
                                <input name="title" value="{{ $period->title }}" class="border rounded p-2 w-48" required>
                        </td>
                        <td class="p-2">
                                <input name="start_date" type="date" value="{{ $period->start_date?->format('Y-m-d') }}" class="border rounded p-2" required>
                        </td>
                        <td class="p-2">
                                <input name="end_date" type="date" value="{{ $period->end_date?->format('Y-m-d') }}" class="border rounded p-2" required>
                        </td>
                        <td class="p-2">
                                <select name="status" class="border rounded p-2" required>
                                    <option value="draft" @selected($period->status === 'draft')>draft</option>
                                    <option value="active" @selected($period->status === 'active')>active</option>
                                    <option value="closed" @selected($period->status === 'closed')>closed</option>
                                </select>
                        </td>
                        <td class="p-2">
                                <div class="flex flex-wrap gap-2">
                                    <button class="text-blue-600">ذخیره تغییرات</button>
                                    <a class="text-gray-700" href="{{ route('commissions.reports.index', ['period_id' => $period->id]) }}">مشاهده گزارش</a>
                                </div>
                            </form>

                            <div class="flex flex-wrap gap-3 mt-2">
                                <form method="POST" action="{{ route('commissions.reports.calculate', $period) }}">
                                    @csrf
                                    <button class="text-indigo-600">اجرای محاسبه</button>
                                </form>
                                <form method="POST" action="{{ route('commissions.periods.close', $period) }}">
                                    @csrf
                                    <button class="text-red-600">بستن دوره</button>
                                </form>
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
