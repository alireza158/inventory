<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">دوره‌های پورسانت</h2></x-slot>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-800 border border-emerald-200 rounded p-3 text-sm">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="bg-rose-50 text-rose-800 border border-rose-200 rounded p-3 text-sm">
                <ul class="list-disc pl-5 space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="bg-white p-4 rounded shadow">
            <h3 class="font-semibold mb-3">ایجاد دوره جدید</h3>
            <form method="POST" action="{{ route('commissions.periods.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                @csrf
                <input name="title" placeholder="عنوان دوره" value="{{ old('title') }}" class="border rounded p-2" required>
                <input name="start_date" type="date" value="{{ old('start_date') }}" class="border rounded p-2" required>
                <input name="end_date" type="date" value="{{ old('end_date') }}" class="border rounded p-2" required>
                <select name="status" class="border rounded p-2" required>
                    <option value="draft" @selected(old('status')==='draft')>draft</option>
                    <option value="active" @selected(old('status')==='active')>active</option>
                    <option value="closed" @selected(old('status')==='closed')>closed</option>
                </select>
                <button class="bg-indigo-600 text-white rounded p-2">ایجاد دوره</button>
            </form>
        </div>

        <div class="bg-white p-4 rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr><th>عنوان</th><th>بازه</th><th>وضعیت</th><th>ویرایش</th><th>عملیات</th></tr></thead>
                <tbody>
                @foreach($periods as $period)
                    <tr class="border-t align-top">
                        <td class="py-2">{{ $period->title }}</td>
                        <td class="py-2">{{ $period->start_date?->format('Y-m-d') }} تا {{ $period->end_date?->format('Y-m-d') }}</td>
                        <td class="py-2">{{ $period->status }}</td>
                        <td class="py-2 min-w-[360px]">
                            <form method="POST" action="{{ route('commissions.periods.update', $period) }}" class="grid grid-cols-2 gap-2">
                                @csrf @method('PUT')
                                <input name="title" value="{{ $period->title }}" class="border rounded p-1.5" required>
                                <select name="status" class="border rounded p-1.5" required>
                                    <option value="draft" @selected($period->status==='draft')>draft</option>
                                    <option value="active" @selected($period->status==='active')>active</option>
                                    <option value="closed" @selected($period->status==='closed')>closed</option>
                                </select>
                                <input name="start_date" type="date" value="{{ $period->start_date?->format('Y-m-d') }}" class="border rounded p-1.5" required>
                                <input name="end_date" type="date" value="{{ $period->end_date?->format('Y-m-d') }}" class="border rounded p-1.5" required>
                                <button class="col-span-2 bg-slate-800 text-white rounded p-1.5">ذخیره ویرایش</button>
                            </form>
                        </td>
                        <td class="py-2">
                            <div class="flex flex-col gap-2">
                                <form method="POST" action="{{ route('commissions.reports.calculate', $period) }}">@csrf<button class="text-indigo-600">اجرای محاسبه</button></form>
                                @if($period->status !== 'closed')
                                    <form method="POST" action="{{ route('commissions.periods.close', $period) }}">@csrf<button class="text-red-600">بستن دوره</button></form>
                                @endif
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
