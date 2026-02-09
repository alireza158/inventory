<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreinvoiceOrderItem extends Model
{
    protected $fillable = [
        'preinvoice_order_id',
        'product_id',
        'variant_id',
        'quantity',
        'price',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(PreinvoiceOrder::class, 'preinvoice_order_id');
    }
}
