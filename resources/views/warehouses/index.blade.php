@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="page-title mb-0">تعریف انبارها</h4>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="POST" action="{{ route('warehouses.store') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label">نام انبار</label>
                <input class="form-control" name="name" required value="{{ old('name') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">نوع انبار</label>
                <select class="form-select" name="type" id="warehouseType" required>
                    <option value="central" @selected(old('type')==='central')>انبار مرکزی</option>
                    <option value="return" @selected(old('type')==='return')>انبار مرجوعی</option>
                    <option value="scrap" @selected(old('type')==='scrap')>انبار ضایعات</option>
                    <option value="personnel" @selected(old('type')==='personnel')>انبار پرسنل</option>
                </select>
            </div>
            <div class="col-md-3" id="personnelNameWrap" style="display:none;">
                <label class="form-label">نام پرسنل</label>
                <input class="form-control" name="personnel_name" value="{{ old('personnel_name') }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">ثبت انبار</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>نوع</th>
                    <th>پرسنل</th>
                    <th>تعداد آیتم موجودی</th>
                </tr>
            </thead>
            <tbody>
                @foreach($warehouses as $warehouse)
                    <tr>
                        <td class="fw-semibold">{{ $warehouse->name }}</td>
                        <td>{{ $warehouse->type }}</td>
                        <td>{{ $warehouse->personnel_name ?: '-' }}</td>
                        <td>{{ $warehouse->stocks_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
    const typeInput = document.getElementById('warehouseType');
    const personnelWrap = document.getElementById('personnelNameWrap');
    const togglePersonnel = () => personnelWrap.style.display = typeInput.value === 'personnel' ? '' : 'none';
    typeInput.addEventListener('change', togglePersonnel);
    togglePersonnel();
</script>
@endsection

