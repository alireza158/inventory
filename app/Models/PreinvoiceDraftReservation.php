<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreinvoiceDraftReservation extends Model
{
    protected $fillable = [
        'token',
        'user_id',
        'preinvoice_order_id',
        'product_id',
        'variant_id',
        'quantity',
        'expires_at',
        'converted_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'preinvoice_order_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'quantity' => 'integer',
        'expires_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(PreinvoiceOrder::class, 'preinvoice_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
