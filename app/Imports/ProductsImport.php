<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $name = trim((string)($row['name'] ?? ''));
            $sku  = trim((string)($row['sku'] ?? ''));
            $categoryName = trim((string)($row['category'] ?? ''));

            if ($name === '' || $sku === '' || $categoryName === '') {
                continue; // یا می‌تونی خطا جمع کنی
            }

            $category = Category::firstOrCreate(['name' => $categoryName]);

            $stock = (int)($row['stock'] ?? 0);
            $low   = (int)($row['low_stock_threshold'] ?? 5);
            $price = (int) preg_replace('/[^\d]/', '', (string)($row['price'] ?? 0));

            Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'category_id' => $category->id,
                    'stock' => max(0, $stock),
                    'low_stock_threshold' => max(0, $low),
                    'price' => max(0, $price),
                ]
            );
        }
    }
}
