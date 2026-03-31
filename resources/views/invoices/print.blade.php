<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>چاپ فاکتور {{ $invoice->uuid }}</title>
  <style>body{font-family:tahoma;padding:24px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:8px}.text-end{text-align:left}</style>
</head>
<body onload="window.print()">
  <h3>فاکتور {{ $invoice->uuid }}</h3>
  <p>مشتری: {{ $invoice->customer_name }} | موبایل: {{ $invoice->customer_mobile }}</p>
  <table>
    <thead><tr><th>محصول</th><th>مدل</th><th>تعداد</th><th>قیمت</th><th>جمع</th></tr></thead>
    <tbody>
      @foreach($invoice->items as $item)
      <tr>
        <td>{{ $item->product?->name ?? ('#'.$item->product_id) }}</td>
        <td>{{ $item->variant?->variant_name ?? '—' }}</td>
        <td>{{ number_format((int)$item->quantity) }}</td>
        <td>{{ number_format((int)$item->price) }}</td>
        <td>{{ number_format((int)$item->line_total) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  <p><b>مبلغ کل: {{ number_format((int)$invoice->total) }}</b></p>
</body>
</html>
