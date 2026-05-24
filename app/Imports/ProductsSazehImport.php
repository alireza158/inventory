<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\HeadingRowFormatter;

HeadingRowFormatter::default('none'); // تیترها دقیقاً فارسی باقی بمانند

class ProductsSazehImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // تیترهای سازه حساب (دقیقاً همین‌ها)
            $code = trim((string)($row['كد'] ?? ''));
            $name = trim((string)($row['نام كالا'] ?? ''));

            if ($name === '') continue; // اگر نام کالا خالی بود، رد کن

            $stock = (int)($row['موجودي'] ?? 0);

            // قیمت‌ها: ممکنه تو اکسل با کاما بیاد
            $saleRetail = $this->money($row['ف جزئي'] ?? null);
            $saleWholesale = $this->money($row['ف كلي'] ?? null);
            $buyRetail = $this->money($row['خ جزئي'] ?? null);
            $buyWholesale = $this->money($row['خ كلي'] ?? null);

            $reserved = (int)($row['رزرو'] ?? 0);

            // آپسرت: اگر code موجود بود با code آپدیت کن، اگر نبود با نام
            $key = $code !== '' ? ['code' => $code] : ['name' => $name];

            Product::updateOrCreate($key, [
                'name' => $name,
                'code' => $code !== '' ? $code : null,
                'short_barcode' => $this->clean($row['باركد/اختصاري'] ?? null),
                'stock' => $stock,
                'unit' => $this->clean($row['واحد'] ?? null),

                // price را برابر ف جزئی می‌گذاریم (نمایش اصلی)
                'price' => $saleRetail ?? 0,

                'sale_retail' => $saleRetail,
                'sale_wholesale' => $saleWholesale,
                'buy_retail' => $buyRetail,
                'buy_wholesale' => $buyWholesale,

                'reserved' => $reserved,
                'barcode' => $this->clean($row['بارکد'] ?? null),
                'color' => $this->clean($row['رنگ'] ?? null),
            ]);
        }
    }

    private function clean($v): ?string
    {
        $s = trim((string)$v);
        return $s === '' ? null : $s;
    }

    private function money($v): ?int
    {
        if ($v === null) return null;
        $s = preg_replace('/[^\d]/', '', (string)$v);
        return $s === '' ? null : (int)$s;
    }
}
