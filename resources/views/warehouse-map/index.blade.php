@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <h4 class="mb-1">نقشه انبار</h4>
      <div class="text-muted">مدیریت جایگاه فیزیکی تنوع کالاها در زون، رک و باکس</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#newLocation">افزودن مکان</button>
      <a class="btn btn-outline-dark" href="{{ route('warehouse-map.index', array_merge(request()->query(), ['map_status' => 'unmapped'])) }}">تنوع‌های بدون نقشه</a>
    </div>
  </div>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if($errors->any()) <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif

  <div class="card border-0 shadow-sm mb-3 collapse" id="newLocation">
    <div class="card-header bg-white fw-bold">افزودن مکان جدید</div>
    <div class="card-body">
      <form method="POST" action="{{ route('warehouse-map.locations.store') }}" class="row g-3">
        @csrf
        <div class="col-md-2"><label class="form-label">انبار</label><select name="warehouse_id" class="form-select" required>@foreach($warehouses as $w)<option value="{{ $w->id }}" @selected($warehouseId===$w->id)>{{ $w->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">زون</label><input name="zone" class="form-control" placeholder="Z01" required></div>
        <div class="col-md-2"><label class="form-label">رک</label><input name="rack" class="form-control" placeholder="R02" required></div>
        <div class="col-md-2"><label class="form-label">باکس</label><input name="box" class="form-control" placeholder="B05" required></div>
        <div class="col-md-3"><label class="form-label">توضیحات</label><input name="description" class="form-control"></div>
        <div class="col-md-1 d-flex align-items-end"><label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked> فعال</label></div>
        <div class="col-12"><button class="btn btn-success">ثبت مکان</button></div>
      </form>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <form class="row g-2">
        <div class="col-md-2"><select name="warehouse_id" class="form-select"><option value="">انبار</option>@foreach($warehouses as $w)<option value="{{ $w->id }}" @selected($warehouseId===$w->id)>{{ $w->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="category_id" class="form-select"><option value="">دسته‌بندی</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><input name="q" class="form-control" value="{{ request('q') }}" placeholder="جستجوی نام، کد، بارکد یا SKU"></div>
        <div class="col-md-2"><select name="map_status" class="form-select"><option value="">همه وضعیت‌ها</option><option value="mapped" @selected(request('map_status')==='mapped')>دارای نقشه</option><option value="unmapped" @selected(request('map_status')==='unmapped')>بدون نقشه</option><option value="multi" @selected(request('map_status')==='multi')>چندمکانه</option></select></div>
        <div class="col-md-1"><input name="zone" class="form-control" value="{{ request('zone') }}" placeholder="زون"></div>
        <div class="col-md-1"><input name="rack" class="form-control" value="{{ request('rack') }}" placeholder="رک"></div>
        <div class="col-md-1"><input name="box" class="form-control" value="{{ request('box') }}" placeholder="باکس"></div>
        <div class="col-12"><button class="btn btn-dark">اعمال فیلتر</button> <a class="btn btn-outline-secondary" href="{{ route('warehouse-map.index') }}">حذف فیلتر</a></div>
      </form>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white fw-bold">مدیریت مکان‌ها</div>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>کد</th><th>انبار</th><th>وضعیت</th><th>ویرایش</th></tr></thead><tbody>
          @forelse($locations as $location)
            <tr><td dir="ltr" class="fw-bold">{{ $location->code }}</td><td>{{ $location->warehouse?->name }}</td><td>{!! $location->is_active ? '<span class="badge text-bg-success">فعال</span>' : '<span class="badge text-bg-secondary">غیرفعال</span>' !!}</td><td>
              <form method="POST" action="{{ route('warehouse-map.locations.update', $location) }}" class="d-flex gap-1">@csrf @method('PUT')<input name="description" class="form-control form-control-sm" value="{{ $location->description }}" placeholder="توضیح"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($location->is_active)></label><button class="btn btn-sm btn-outline-primary">ثبت</button></form>
            </td></tr>
          @empty <tr><td colspan="4" class="text-center text-muted py-3">مکانی ثبت نشده است.</td></tr>@endforelse
        </tbody></table></div>
        <div class="card-footer bg-white">{{ $locations->links() }}</div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold">تخصیص و مدیریت موجودی مکانی تنوع‌ها</div>
        <div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>کالا</th><th>تنوع</th><th>کد</th><th>موجودی کل</th><th>دارای نقشه</th><th>بدون نقشه</th><th>مکان‌ها</th><th>عملیات</th></tr></thead><tbody>
          @forelse($variants as $row)
            @php($variant=$row['variant'])
            <tr>
              <td>{{ $variant->product?->name }}</td><td>{{ $variant->variant_name }}</td><td dir="ltr">{{ $variant->variant_code ?: ($variant->sku ?: $variant->barcode) }}</td>
              <td>{{ number_format($row['total']) }}</td><td>{{ number_format($row['mapped']) }}</td><td class="{{ $row['unmapped'] < 0 ? 'text-danger fw-bold' : '' }}">{{ number_format($row['unmapped']) }} @if($row['unmapped'] < 0)<div class="small">موجودی مکانی این تنوع بیشتر از موجودی کل ثبت‌شده است. لطفاً بررسی شود.</div>@endif</td>
              <td>@forelse($variant->locationStocks->where('quantity','>',0) as $stock)<div dir="ltr"><span class="badge text-bg-light border">{{ $stock->location?->code }}</span> — {{ number_format($stock->quantity) }}</div>@empty <span class="text-muted">بدون نقشه</span>@endforelse</td>
              <td style="min-width:260px">
                <form method="POST" action="{{ route('warehouse-map.assign') }}" class="row g-1 mb-2">@csrf<input type="hidden" name="warehouse_id" value="{{ $warehouseId }}"><input type="hidden" name="product_variant_id" value="{{ $variant->id }}"><div class="col-7"><select name="warehouse_location_id" class="form-select form-select-sm" required><option value="">مکان...</option>@foreach($allLocations as $loc)<option value="{{ $loc->id }}">{{ $loc->code }}</option>@endforeach</select></div><div class="col-3"><input name="quantity" type="number" min="1" class="form-control form-control-sm" placeholder="تعداد"></div><div class="col-2"><button class="btn btn-sm btn-success w-100">+</button></div></form>
                <form method="POST" action="{{ route('warehouse-map.transfer') }}" class="row g-1">@csrf<input type="hidden" name="warehouse_id" value="{{ $warehouseId }}"><input type="hidden" name="product_variant_id" value="{{ $variant->id }}"><div class="col-4"><select name="from_location_id" class="form-select form-select-sm" required><option value="">از...</option>@foreach($variant->locationStocks as $s)<option value="{{ $s->location?->id }}">{{ $s->location?->code }} ({{ $s->quantity }})</option>@endforeach</select></div><div class="col-4"><select name="to_location_id" class="form-select form-select-sm" required><option value="">به...</option>@foreach($allLocations as $loc)<option value="{{ $loc->id }}">{{ $loc->code }}</option>@endforeach</select></div><div class="col-2"><input name="quantity" type="number" min="1" class="form-control form-control-sm"></div><div class="col-2"><button class="btn btn-sm btn-outline-dark w-100">↔</button></div></form>
                <a class="small" href="{{ route('warehouse-map.history', ['variant'=>$variant, 'warehouse_id'=>$warehouseId]) }}">تاریخچه</a>
              </td>
            </tr>
          @empty <tr><td colspan="8" class="text-center text-muted py-4">تنوعی یافت نشد.</td></tr>@endforelse
        </tbody></table></div>
        <div class="card-footer bg-white">{{ $variants->links() }}</div>
      </div>
    </div>
  </div>
</div>
@endsection
