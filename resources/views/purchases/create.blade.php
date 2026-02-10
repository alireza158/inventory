@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="page-title mb-0">ثبت خرید جدید</h4>
    <a class="btn btn-outline-secondary" href="{{ route('purchases.index') }}">بازگشت</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('purchases.store') }}" id="purchaseForm">
            @csrf

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">تامین‌کننده</label>
                    <div class="d-flex gap-2">
                        <select class="form-select" name="supplier_id" required>
                            <option value="">انتخاب کنید...</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id')==$supplier->id)>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-dark">مدیریت تامین‌کننده‌ها</a>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">توضیحات (اختیاری)</label>
                    <input class="form-control" name="note" value="{{ old('note') }}">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="itemsTable">
                    <thead>
                        <tr>
                            <th>اسم محصول</th>
                            <th>تعداد محصول</th>
                            <th>کد محصول</th>
                            <th>قیمت خرید</th>
                            <th>قیمت فروش</th>
                            <th>جمع</th>
                            <th>حذف</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <button type="button" class="btn btn-outline-primary" id="addRowBtn">+ افزودن محصول</button>

            <div class="d-flex justify-content-end mt-3">
                <div class="fw-bold fs-5">قیمت کل خرید: <span id="totalAmount">0</span> ریال</div>
            </div>

            <div class="mt-4">
                <button class="btn btn-primary">ثبت نهایی خرید</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const tbody = document.querySelector('#itemsTable tbody');
    const addBtn = document.getElementById('addRowBtn');
    const totalEl = document.getElementById('totalAmount');

    function rowTemplate(index) {
        return `
        <tr>
            <td><input class="form-control" name="items[${index}][name]" required></td>
            <td><input type="number" min="1" class="form-control qty" name="items[${index}][quantity]" value="1" required></td>
            <td><input class="form-control" name="items[${index}][code]" required></td>
            <td><input type="number" min="0" class="form-control buy" name="items[${index}][buy_price]" value="0" required></td>
            <td><input type="number" min="0" class="form-control" name="items[${index}][sell_price]" value="0" required></td>
            <td class="line-total">0</td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">حذف</button></td>
        </tr>`;
    }

    function recalc() {
        let total = 0;
        tbody.querySelectorAll('tr').forEach((tr) => {
            const qty = Number(tr.querySelector('.qty')?.value || 0);
            const buy = Number(tr.querySelector('.buy')?.value || 0);
            const line = qty * buy;
            total += line;
            tr.querySelector('.line-total').textContent = line.toLocaleString('fa-IR');
        });
        totalEl.textContent = total.toLocaleString('fa-IR');
    }

    function addRow() {
        const index = tbody.querySelectorAll('tr').length;
        tbody.insertAdjacentHTML('beforeend', rowTemplate(index));
        recalc();
    }

    addBtn.addEventListener('click', addRow);

    tbody.addEventListener('input', (e) => {
        if (e.target.classList.contains('qty') || e.target.classList.contains('buy')) {
            recalc();
        }
    });

    tbody.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('tr').remove();
            recalc();
        }
    });

    addRow();
})();
</script>
@endsection
