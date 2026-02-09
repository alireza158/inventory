<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'uuid','customer_id','preinvoice_order_id',
        'customer_name','customer_mobile','customer_address',
        'province_id','city_id','shipping_id','shipping_price',
        'discount_amount','subtotal','total','status'
    ];

    public function items() { return $this->hasMany(InvoiceItem::class); }
    public function payments() { return $this->hasMany(InvoicePayment::class); }
    public function notes() { return $this->hasMany(InvoiceNote::class)->latest(); }
    public function attachments() { return $this->hasMany(InvoiceAttachment::class)->latest(); }

    public function getPaidAmountAttribute(): int
    {
        return (int) $this->payments()->sum('amount');
    }

    public function getRemainingAmountAttribute(): int
    {
        return max(((int)$this->total) - (int)$this->paid_amount, 0);
    }
}
