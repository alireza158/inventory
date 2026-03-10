<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">تارگت‌های پورسانت</h2></x-slot>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white p-4 rounded shadow">
            <form method="POST" action="{{ route('commissions.targets.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                @csrf
                <select name="commission_period_id" class="border rounded p-2" required>
                    @foreach($periods as $period)
                        <option value="{{ $period->id }}">{{ $period->title }}</option>
                    @endforeach
                </select>
                <select name="user_id" class="border rounded p-2" required>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                <select name="category_id" class="border rounded p-2" required>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
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
                        <th class="text-right p-2">دوره</th>
                        <th class="text-right p-2">کاربر</th>
                        <th class="text-right p-2">دسته‌بندی</th>
                        <th class="text-right p-2">تارگت مبلغ</th>
                        <th class="text-right p-2">تارگت تعداد</th>
                        <th class="text-right p-2">نوع</th>
                        <th class="text-right p-2">مقدار</th>
                        <th class="text-right p-2">حداقل%</th>
                        <th class="text-right p-2">ذخیره</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($targets as $target)
                    <tr class="border-t">
                        <form method="POST" action="{{ route('commissions.targets.update', $target) }}">
                            @csrf
                            @method('PUT')
                            <td class="p-2">{{ $target->period->title }}<input type="hidden" name="commission_period_id" value="{{ $target->commission_period_id }}"></td>
                            <td class="p-2">{{ $target->user->name }}<input type="hidden" name="user_id" value="{{ $target->user_id }}"></td>
                            <td class="p-2">{{ $target->category->name }}<input type="hidden" name="category_id" value="{{ $target->category_id }}"></td>
                            <td class="p-2"><input name="target_amount" type="number" min="0" value="{{ $target->target_amount }}" class="border rounded p-2 w-32"></td>
                            <td class="p-2"><input name="target_qty" type="number" min="0" value="{{ $target->target_qty }}" class="border rounded p-2 w-24"></td>
                            <td class="p-2">
                                <select name="commission_type" class="border rounded p-2">
                                    <option value="percent" @selected($target->commission_type === 'percent')>percent</option>
                                    <option value="fixed" @selected($target->commission_type === 'fixed')>fixed</option>
                                </select>
                            </td>
                            <td class="p-2"><input name="commission_value" type="number" min="0" step="0.01" value="{{ $target->commission_value }}" class="border rounded p-2 w-24"></td>
                            <td class="p-2"><input name="min_percent_to_activate" type="number" min="0" step="0.01" value="{{ $target->min_percent_to_activate }}" class="border rounded p-2 w-24"></td>
                            <td class="p-2"><button class="text-blue-600">ثبت</button></td>
                        </form>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-3">{{ $targets->links() }}</div>
        </div>
    </div>
</x-app-layout>
