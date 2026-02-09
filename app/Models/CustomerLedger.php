<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerLedger extends Model
{
    protected $fillable = [
        'customer_id','type','amount','reference_type','reference_id','note'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
