<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'first_name','last_name','mobile',
        'address','postal_code','extra_description','province_id','city_id',
        'opening_balance',
    ];

    public function ledgers()
    {
        return $this->hasMany(CustomerLedger::class);
    }

    public function scopeWithBalance($q)
    {
        return $q->withSum(['ledgers as debit_sum' => fn($x)=>$x->where('type','debit')], 'amount')
                 ->withSum(['ledgers as credit_sum' => fn($x)=>$x->where('type','credit')], 'amount');
    }

    public function getBalanceAttribute()
    {
        $debit = (int)($this->debit_sum ?? 0);
        $credit = (int)($this->credit_sum ?? 0);
        return (int)$this->opening_balance + $debit - $credit;
    }

    public function getDebtAttribute()   { return max($this->balance, 0); }
    public function getCreditAttribute() { return max(-$this->balance, 0); }

    public function getDisplayNameAttribute(): string
    {
        return trim((string) ($this->first_name ?? ''));
    }
}
