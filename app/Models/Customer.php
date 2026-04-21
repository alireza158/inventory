<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'mobile',
        'address',
        'postal_code',
        'extra_description',
        'province_id',
        'city_id',
        'opening_balance',
    ];

    protected $casts = [
        'province_id' => 'integer',
        'city_id' => 'integer',
        'opening_balance' => 'integer',
    ];

    public function ledgers()
    {
        return $this->hasMany(CustomerLedger::class);
    }

    public function scopeWithBalance($query)
    {
        return $query
            ->withSum(['ledgers as debit_sum' => fn ($q) => $q->where('type', 'debit')], 'amount')
            ->withSum(['ledgers as credit_sum' => fn ($q) => $q->where('type', 'credit')], 'amount');
    }

    public function getBalanceAttribute(): int
    {
        $opening = (int) ($this->opening_balance ?? 0);
        $debit = (int) ($this->debit_sum ?? 0);
        $credit = (int) ($this->credit_sum ?? 0);

        return $opening + $debit - $credit;
    }

    public function getDebtAttribute(): int
    {
        return max($this->balance, 0);
    }

    public function getCreditAttribute(): int
    {
        return max(-$this->balance, 0);
    }

    public function getDisplayNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ])));
    }
}