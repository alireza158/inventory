<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesHavalehHistory extends Model
{
    protected $table = 'sales_havaleh_histories';

    protected $fillable = [
        'invoice_id',
        'action_type',
        'field_name',
        'old_value',
        'new_value',
        'description',
        'done_by',
        'invoice_uuid',
        'invoice_item_id',
        'product_id',
        'variant_id',
        'old_quantity',
        'new_quantity',
        'delta',
        'returned_to_stock_quantity',
        'consumed_from_stock_quantity',
        'reason',
        'note',
        'done_at',
    ];

    protected $casts = [
        'done_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'done_by');
    }
}
