<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryWebhookLog extends Model
{
    protected $fillable = [
        'setting_id',
        'event',
        'status',
        'response_code',
        'error_message',
        'payload',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
    ];
}
