<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    protected $fillable = ['invoice_id','method','amount','paid_at','payment_identifier','receipt_image','note'];

    public function invoice(){ return $this->belongsTo(Invoice::class); }
    public function cheque(){ return $this->hasOne(Cheque::class); }
}
