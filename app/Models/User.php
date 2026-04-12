<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'external_crm_id',
        'crm_user_id',
        'name',
        'email',
        'phone',
        'username',
        'is_active',
        'sync_source',
        'crm_role_raw',
        'synced_at',
        'last_crm_payload',
        'crm_created_at',
        'crm_updated_at',
        'avatar',
        'department',
        'position',
        'personnel_code',
        'branch',
        'password',
        'manager_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'synced_at' => 'datetime',
            'crm_created_at' => 'datetime',
            'crm_updated_at' => 'datetime',
            'crm_role_raw' => 'array',
            'last_crm_payload' => 'array',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }
}
