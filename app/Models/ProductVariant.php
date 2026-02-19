<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'variant_name',
        'model_list_id',
        'variety_name',
        'variety_code',
        'variant_code',
        'variety_id',
        'unique_key',
        'sku',
        'barcode',

        'buy_price',
        'sell_price',
        'stock',
        'reserved',

        'synced_at',
    ];

    protected $casts = [
        'buy_price'  => 'integer',
        'sell_price' => 'integer',
        'stock'      => 'integer',
        'reserved'   => 'integer',
        'synced_at'  => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function modelList()
    {
        return $this->belongsTo(ModelList::class);
    }

    public function getAvailableStockAttribute(): int
    {
        return max(0, ($this->stock ?? 0) - ($this->reserved ?? 0));
    }
}
