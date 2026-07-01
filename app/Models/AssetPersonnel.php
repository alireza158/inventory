<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetPersonnel extends Model
{
    protected $table = 'asset_personnel';

    protected $fillable = [
        'user_id',
        'user_name_snapshot',
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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
