<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">تارگت‌های پورسانت</h2></x-slot>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white p-4 rounded shadow">
            <form method="POST" action="{{ route('commissions.targets.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                @csrf
                <select name="commission_period_id" class="border rounded p-2" required>
                    @foreach($periods as $period)<option value="{{ $period->id }}">{{ $period->title }}</option>@endforeach
                </select>
                <select name="user_id" class="border rounded p-2" required>
                    @foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
                </select>
                <select name="category_id" class="border rounded p-2" required>
                    @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                </select>
                <input name="target_amount" type="number" min="0" placeholder="تارگت مبلغ" class="border rounded p-2" required>
                <input name="target_qty" type="number" min="0" placeholder="تارگت تعداد" class="border rounded p-2">
                <select name="commission_type" class="border rounded p-2" required>
                    <option value="percent">percent</option>
                    <option value="fixed">fixed</option>
                </select>
                <input name="commission_value" type="number" min="0" step="0.01" class="border rounded p-2" placeholder="مقدار قانون" required>
                <input name="min_percent_to_activate" type="number" min="0" step="0.01" class="border rounded p-2" placeholder="حداقل درصد فعال سازی" required>
                <button class="bg-indigo-600 text-white rounded p-2 md:col-span-4">ذخیره</button>
            </form>
        </div>

        <div class="bg-white p-4 rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr>
                        <th>دوره</th>
                        <th>کاربر</th>
                        <th>دسته‌بندی</th>
                        <th>تارگت مبلغ</th>
                        <th>نوع</th>
                        <th>مقدار</th>
                        <th>حداقل%</th>
                        <th>ویرایش</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($targets as $target)
                    <tr class="border-t align-top">
                        <td class="py-2">{{ $target->period->title }}</td>
                        <td class="py-2">{{ $target->user->name }}</td>
                        <td class="py-2">{{ $target->category->name }}</td>
                        <td class="py-2">{{ number_format($target->target_amount) }}</td>
                        <td class="py-2">{{ $target->commission_type }}</td>
                        <td class="py-2">{{ $target->commission_value }}</td>
                        <td class="py-2">{{ $target->min_percent_to_activate }}</td>
                        <td class="py-2 min-w-[370px]">
                            <form method="POST" action="{{ route('commissions.targets.update', $target) }}" class="grid grid-cols-2 gap-2">
                                @csrf
                                @method('PUT')
                                <select name="commission_period_id" class="border rounded p-2" required>
                                    @foreach($periods as $period)
                                        <option value="{{ $period->id }}" @selected($target->commission_period_id===$period->id)>{{ $period->title }}</option>
                                    @endforeach
                                </select>
                                <select name="user_id" class="border rounded p-2" required>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" @selected($target->user_id===$user->id)>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                <select name="category_id" class="border rounded p-2" required>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected($target->category_id===$category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <input name="target_amount" type="number" min="0" class="border rounded p-2" value="{{ $target->target_amount }}" required>
                                <input name="target_qty" type="number" min="0" class="border rounded p-2" value="{{ $target->target_qty }}">
                                <select name="commission_type" class="border rounded p-2" required>
                                    <option value="percent" @selected($target->commission_type==='percent')>percent</option>
                                    <option value="fixed" @selected($target->commission_type==='fixed')>fixed</option>
                                </select>
                                <input name="commission_value" type="number" min="0" step="0.01" class="border rounded p-2" value="{{ $target->commission_value }}" required>
                                <input name="min_percent_to_activate" type="number" min="0" step="0.01" class="border rounded p-2" value="{{ $target->min_percent_to_activate }}" required>
                                <button class="bg-gray-800 text-white rounded p-2 col-span-2">ذخیره تغییرات</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="mt-3">{{ $targets->links() }}</div>
        </div>
    </div>
</x-app-layout>
