<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetPersonnel extends Model
{
    protected $table = 'asset_personnel';

    protected $fillable = [
        'full_name',
        'personnel_code',
        'national_code',
        'department',
        'position',
        'mobile',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function documents()
    {
        return $this->hasMany(AssetDocument::class, 'personnel_id');
    }
}
