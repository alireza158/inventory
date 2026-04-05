<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockCountDocument extends Model
{
    protected $fillable = [
        'document_number',
        'warehouse_id',
        'document_date',
        'status',
        'description',
        'finalized_by',
        'finalized_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'document_date' => 'date',
        'finalized_at' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(StockCountDocumentItem::class, 'document_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function finalizer()
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function history()
    {
        return $this->hasMany(StockCountDocumentHistory::class, 'document_id');
    }
}
