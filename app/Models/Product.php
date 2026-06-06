<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'category_id',
        'image_path',

        'code',
        'short_barcode',
        'barcode',

        // خلاصه‌ی محاسبه‌شده از variants (برای لیست/فیلتر)
        'stock',
        'reserved',
        'unit',

        // خلاصه‌ی محاسبه‌شده از variants (مثلاً کمترین قیمت فروش)
        'price',

        // اختیاری/قدیمی
        'sale_retail',
        'sale_wholesale',
        'buy_retail',
        'buy_wholesale',
        'color',

        // CRM
        'external_id',
        'synced_at',

        // اگر ستونش هست و نمی‌خوای حذف کنی:
        'models',
        'has_colors',
        'is_sellable',
        'warehouse_zone',
        'warehouse_rows',
        'warehouse_bins',
    ];

    protected $casts = [
        'stock'      => 'integer',
        'reserved'   => 'integer',
        'price'      => 'integer',

        'sale_retail'     => 'integer',
        'sale_wholesale'  => 'integer',
        'buy_retail'      => 'integer',
        'buy_wholesale'   => 'integer',

        'models'     => 'array',
        'has_colors' => 'boolean',
        'is_sellable' => 'boolean',
        'warehouse_zone' => 'integer',
        'warehouse_rows' => 'array',
        'warehouse_bins' => 'array',
        'synced_at'  => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function warehouseStocks()
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function getAvailableStockAttribute(): int
    {
        return max(0, (int) ($this->stock ?? 0));
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock', 0);
    }

    public function scopeSearch($query, $q)
    {
        if (!$q) return $query;

        return $query->where(function ($qq) use ($q) {
            $qq->where('name', 'like', "%{$q}%")
                ->orWhere('sku', 'like', "%{$q}%")
                ->orWhere('code', 'like', "%{$q}%");
        });
    }
}
