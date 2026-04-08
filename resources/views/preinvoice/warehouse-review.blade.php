@extends('layouts.app')

@php
  use Morilog\Jalali\Jalalian;
  $statusLabels = \App\Models\PreinvoiceOrder::statusLabels();
@endphp

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">بررسی انبار پیش‌فاکتور</h4>
    <a href="{{ route('preinvoice.warehouse.index') }}" class="btn btn-outline-secondary">بازگشت به صف انبار</a>
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
  @endif

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body row g-3">
      <div class="col-md-3"><div class="text-muted small">شماره</div><strong>{{ $order->uuid }}</strong></div>
      <div class="col-md-3"><div class="text-muted small">تاریخ</div><strong>{{ $order->created_at ? Jalalian::fromDateTime($order->created_at)->format('Y/m/d H:i') : '—' }}</strong></div>
      <div class="col-md-3"><div class="text-muted small">مشتری</div><strong>{{ $order->customer_name }}</strong></div>
      <div class="col-md-3"><div class="text-muted small">وضعیت</div><span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">{{ $statusLabels[$order->status] ?? $order->status }}</span></div>
    </div>
  </div>

  <form method="POST" action="{{ route('preinvoice.warehouse.save', $order->uuid) }}" id="warehouseForm" class="card shadow-sm border-0 mb-3">
    @csrf
    @method('PUT')
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <h6 class="mb-0">اقلام پیش‌فاکتور</h6>
      <button type="button" class="btn btn-sm btn-outline-primary" id="addRow">افزودن ردیف</button>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table align-middle" id="itemsTable">
          <thead>
            <tr>
              <th>کالا</th><th>تنوع</th><th>موجودی تنوع</th><th>موجودی کالای انبار مرکزی</th><th>تعداد</th><th>قیمت</th><th></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div class="mb-3">
        <label class="form-label">دلیل اصلاح توسط انبار</label>
        <textarea class="form-control" name="warehouse_review_note" rows="3" placeholder="در صورت ویرایش اقلام، دلیل را بنویسید.">{{ old('warehouse_review_note', $order->warehouse_review_note) }}</textarea>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit">ذخیره تغییرات</button>
        <button class="btn btn-success" type="button" id="approveBtn">تایید و ارسال به صف مالی</button>
      </div>
    </div>
  </form>

  <form method="POST" action="{{ route('preinvoice.warehouse.reject', $order->uuid) }}" class="card border-danger shadow-sm">
    @csrf
    <div class="card-body">
      <h6 class="text-danger">رد / برگشت به ثبت‌کننده</h6>
      <textarea class="form-control mb-2" name="warehouse_reject_reason" rows="2" placeholder="دلیل رد/برگشت را بنویسید">{{ old('warehouse_reject_reason', $order->warehouse_reject_reason) }}</textarea>
      <button class="btn btn-outline-danger" onclick="return confirm('پیش‌فاکتور رد شود؟')">ثبت رد پیش‌فاکتور</button>
    </div>
  </form>

  <div class="card shadow-sm border-0 mt-3">
    <div class="card-header bg-white"><h6 class="mb-0">سوابق بازبینی انبار</h6></div>
    <div class="card-body">
      @forelse($order->reviews()->with('user:id,name')->latest()->get() as $review)
        <div class="border rounded p-2 mb-2">
          <div class="small text-muted">{{ $review->created_at?->format('Y-m-d H:i') }} - {{ $review->user?->name ?? 'سیستم' }} - {{ $review->action }}</div>
          @if($review->reason)<div>{{ $review->reason }}</div>@endif
        </div>
      @empty
        <div class="text-muted">سابقه‌ای ثبت نشده است.</div>
      @endforelse
    </div>
  </div>
</div>

<script>
const products = @json($products->map(function($product){
  return [
    'id'=>$product->id,
    'name'=>$product->name,
    'stock'=>(int) \App\Models\WarehouseStock::query()->where('warehouse_id', \App\Services\WarehouseStockService::centralWarehouseId())->where('product_id', $product->id)->value('quantity'),
    'variants'=>$product->variants->map(fn($v)=>[
      'id'=>$v->id,
      'name'=>$v->variant_name,
      'stock'=>max(0, ((int)$v->stock - (int)$v->reserved)),
      'price'=>(int)($v->sell_price ?? 0),
    ])->values(),
  ];
}));
const initialItems = @json($order->items->map(fn($it)=>[
  'product_id'=>(int)$it->product_id,
  'variant_id'=>(int)$it->variant_id,
  'quantity'=>(int)$it->quantity,
  'price'=>(int)$it->price,
])->values());

const tbody = document.querySelector('#itemsTable tbody');

function optionHtml(items, selected){
  return items.map(i=>`<option value="${i.id}" ${Number(selected)===Number(i.id)?'selected':''}>${i.name}</option>`).join('');
}
function addRow(data={}){
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><select class="form-select product" required></select></td>
    <td><select class="form-select variant" required></select></td>
    <td class="variant-stock">0</td>
    <td class="product-stock">0</td>
    <td><input type="number" class="form-control qty" min="1" value="${data.quantity || 1}" required></td>
    <td><input type="number" class="form-control price" min="0" value="${data.price || 0}" required></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger remove">حذف</button></td>`;
  tbody.appendChild(tr);

  const pSel = tr.querySelector('.product');
  pSel.innerHTML = '<option value="">انتخاب...</option>'+optionHtml(products, data.product_id);
  pSel.addEventListener('change', ()=>fillVariants(tr));
  tr.querySelector('.remove').addEventListener('click', ()=>tr.remove());
  fillVariants(tr, data.variant_id);
}
function fillVariants(tr, selectedVariant = null){
  const pId = Number(tr.querySelector('.product').value || 0);
  const product = products.find(p=>p.id===pId);
  const vSel = tr.querySelector('.variant');
  tr.querySelector('.product-stock').textContent = product ? product.stock : 0;
  if(!product){ vSel.innerHTML = '<option value="">انتخاب...</option>'; return; }
  vSel.innerHTML = '<option value="">انتخاب...</option>'+optionHtml(product.variants, selectedVariant);
  const onVarChange = ()=>{
    const v = product.variants.find(x=>x.id===Number(vSel.value||0));
    tr.querySelector('.variant-stock').textContent = v ? v.stock : 0;
    if(v && !tr.querySelector('.price').value){ tr.querySelector('.price').value = v.price; }
  };
  vSel.onchange = onVarChange;
  onVarChange();
}

document.getElementById('addRow').addEventListener('click', ()=>addRow());
initialItems.forEach(row=>addRow(row));
if (!initialItems.length) addRow();

function attachHiddenInputs(form){
  form.querySelectorAll('input[name^="items["]').forEach(el=>el.remove());
  [...tbody.querySelectorAll('tr')].forEach((tr, index)=>{
    const productId = tr.querySelector('.product').value;
    const variantId = tr.querySelector('.variant').value;
    const quantity = tr.querySelector('.qty').value;
    const price = tr.querySelector('.price').value;
    const fields = {product_id: productId, variant_id: variantId, quantity, price};
    Object.entries(fields).forEach(([key, val])=>{
      const input = document.createElement('input');
      input.type='hidden'; input.name=`items[${index}][${key}]`; input.value=val;
      form.appendChild(input);
    });
  });
}

document.getElementById('warehouseForm').addEventListener('submit', function(){ attachHiddenInputs(this); });
document.getElementById('approveBtn').addEventListener('click', function(){
  const form = document.getElementById('warehouseForm');
  attachHiddenInputs(form);
  form.action = "{{ route('preinvoice.warehouse.approve', $order->uuid) }}";
  form.method = 'POST';
  form.submit();
});
</script>
@endsection
