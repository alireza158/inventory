<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PreinvoiceOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid','created_by','status',
        'customer_name','customer_mobile','customer_address',
        'province_id','city_id',
        'shipping_id','shipping_price',
        'discount_amount','total_price',
    ];

    protected $casts = [
        'province_id' => 'integer',
        'city_id' => 'integer',
        'shipping_id' => 'integer',
        'shipping_price' => 'integer',
        'discount_amount' => 'integer',
        'total_price' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(PreinvoiceOrderItem::class);
    }
}
