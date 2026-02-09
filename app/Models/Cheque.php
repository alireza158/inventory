<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cheque extends Model
{
    protected $fillable = ['invoice_payment_id','bank_name','cheque_number','due_date','image','status'];

    public function payment(){ return $this->belongsTo(InvoicePayment::class, 'invoice_payment_id'); }
}
