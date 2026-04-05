<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockCountDocumentItem extends Model
{
    protected $fillable = [
        'document_id',
        'product_id',
        'product_variant_id',
        'system_quantity',
        'actual_quantity',
        'difference_quantity',
        'description',
    ];

    public function document()
    {
        return $this->belongsTo(StockCountDocument::class, 'document_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
