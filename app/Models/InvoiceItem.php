<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'product_name_snapshot',
        'variant_id',
        'variant_name_snapshot',
        'variant_code_snapshot',
        'quantity',
        'price',
        'line_total',
        'sort_order',
        'line_discount_amount',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    public function product()
{
    return $this->belongsTo(\App\Models\Product::class);
}

public function variant()
{
    return $this->belongsTo(\App\Models\ProductVariant::class, 'variant_id');
}

}
