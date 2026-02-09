<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'variant_id',
        'quantity',
        'price',
        'line_total',
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
