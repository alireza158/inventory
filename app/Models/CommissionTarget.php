<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_period_id',
        'user_id',
        'category_id',
        'target_amount',
        'target_qty',
        'commission_type',
        'commission_value',
        'min_percent_to_activate',
    ];

    protected $casts = [
        'target_amount' => 'integer',
        'target_qty' => 'integer',
        'commission_value' => 'float',
        'min_percent_to_activate' => 'float',
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
