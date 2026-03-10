<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">گزارش پورسانت</h2></x-slot>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white p-4 rounded shadow grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
            <div>مجموع فروش: <strong>{{ number_format($dashboard['total_sold_amount']) }}</strong></div>
            <div>مجموع پورسانت: <strong>{{ number_format($dashboard['total_commission_amount']) }}</strong></div>
            <div>بهترین کاربر: <strong>{{ $dashboard['best_user']?->user?->name ?? '-' }}</strong></div>
            <div>ضعیف‌ترین دسته: <strong>{{ $dashboard['weakest_category']?->category?->name ?? '-' }}</strong></div>
            <div>تحقق کلی: <strong>{{ $dashboard['overall_achievement_percent'] }}%</strong></div>
        </div>

        <div class="bg-white p-4 rounded shadow">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <select name="period_id" class="border rounded p-2"><option value="">همه دوره‌ها</option>@foreach($periods as $period)<option value="{{ $period->id }}" @selected($periodId===$period->id)>{{ $period->title }}</option>@endforeach</select>
                <select name="user_id" class="border rounded p-2"><option value="">همه کاربران</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected($userId===$user->id)>{{ $user->name }}</option>@endforeach</select>
                <select name="category_id" class="border rounded p-2"><option value="">همه دسته‌ها</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($categoryId===$category->id)>{{ $category->name }}</option>@endforeach</select>
                <button class="bg-gray-800 text-white rounded p-2">اعمال فیلتر</button>
            </form>
        </div>

        <div class="bg-white p-4 rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr><th>کاربر</th><th>دسته‌بندی</th><th>تارگت</th><th>فروش</th><th>تحقق%</th><th>نوع</th><th>قانون</th><th>پورسانت</th></tr></thead>
                <tbody>
                    @foreach($results as $row)
                    <tr class="border-t">
                        <td>{{ $row->user->name }}</td><td>{{ $row->category->name }}</td>
                        <td>{{ number_format($row->target_amount) }}</td><td>{{ number_format($row->sold_amount) }}</td>
                        <td>{{ $row->achievement_percent }}</td><td>{{ $row->commission_type }}</td>
                        <td>{{ $row->commission_value }}</td><td>{{ number_format($row->commission_amount) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-3">{{ $results->links() }}</div>
        </div>
    </div>
</x-app-layout>
