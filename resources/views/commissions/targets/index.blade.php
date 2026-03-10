<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">تارگت‌های پورسانت</h2></x-slot>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white p-4 rounded shadow">
            <form method="POST" action="{{ route('commissions.targets.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                @csrf
                <select name="commission_period_id" class="border rounded p-2" required>@foreach($periods as $period)<option value="{{ $period->id }}">{{ $period->title }}</option>@endforeach</select>
                <select name="user_id" class="border rounded p-2" required>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select>
                <select name="category_id" class="border rounded p-2" required>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
                <input name="target_amount" type="number" min="0" placeholder="تارگت مبلغ" class="border rounded p-2" required>
                <input name="target_qty" type="number" min="0" placeholder="تارگت تعداد" class="border rounded p-2">
                <select name="commission_type" class="border rounded p-2" required><option value="percent">percent</option><option value="fixed">fixed</option></select>
                <input name="commission_value" type="number" min="0" step="0.01" class="border rounded p-2" placeholder="مقدار قانون" required>
                <input name="min_percent_to_activate" type="number" min="0" step="0.01" class="border rounded p-2" placeholder="حداقل درصد فعال سازی" required>
                <button class="bg-indigo-600 text-white rounded p-2 md:col-span-4">ذخیره</button>
            </form>
        </div>

        <div class="bg-white p-4 rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr><th>دوره</th><th>کاربر</th><th>دسته‌بندی</th><th>تارگت مبلغ</th><th>نوع</th><th>مقدار</th><th>حداقل%</th></tr></thead>
                <tbody>@foreach($targets as $target)
                    <tr class="border-t">
                        <td>{{ $target->period->title }}</td><td>{{ $target->user->name }}</td><td>{{ $target->category->name }}</td>
                        <td>{{ number_format($target->target_amount) }}</td><td>{{ $target->commission_type }}</td><td>{{ $target->commission_value }}</td><td>{{ $target->min_percent_to_activate }}</td>
                    </tr>
                @endforeach</tbody>
            </table>
            <div class="mt-3">{{ $targets->links() }}</div>
        </div>
    </div>
</x-app-layout>
