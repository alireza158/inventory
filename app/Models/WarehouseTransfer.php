<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseTransfer extends Model
{

    public const TYPE_BETWEEN_WAREHOUSES = 'between_warehouses';
    public const TYPE_ORGANIZATION_EXPENSE = 'organization_expense';
    public const TYPE_PERSONNEL_ASSET = 'personnel_asset';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_BETWEEN_WAREHOUSES => 'حواله بین انبار',
            self::TYPE_ORGANIZATION_EXPENSE => 'حواله هزینه سازمانی',
            self::TYPE_PERSONNEL_ASSET => 'حواله اموال پرسنل',
        ];
    }

    protected $fillable = [
        'reference',
        'voucher_type',
        'from_warehouse_id',
        'to_warehouse_id',
        'user_id',
        'transferred_at',
        'total_amount',
        'note',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function items()
    {
        return $this->hasMany(WarehouseTransferItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

