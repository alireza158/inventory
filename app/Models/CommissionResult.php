<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_period_id',
        'user_id',
        'category_id',
        'sold_amount',
        'sold_qty',
        'target_amount',
        'target_qty',
        'achievement_percent',
        'commission_type',
        'commission_value',
        'commission_amount',
        'calculated_at',
    ];

    protected $casts = [
        'sold_amount' => 'integer',
        'sold_qty' => 'integer',
        'target_amount' => 'integer',
        'target_qty' => 'integer',
        'achievement_percent' => 'float',
        'commission_value' => 'float',
        'commission_amount' => 'integer',
        'calculated_at' => 'datetime',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(CommissionPeriod::class, 'commission_period_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
