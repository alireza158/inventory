<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelList extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand',
        'model_name',
    ];

    protected $appends = [
        'label',
    ];

    public function getLabelAttribute(): string
    {
        return trim($this->brand . ' - ' . $this->model_name);
    }
}
