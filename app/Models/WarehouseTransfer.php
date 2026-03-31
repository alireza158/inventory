<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseTransfer extends Model
{
    public const TYPE_SALE = 'sale';
    public const TYPE_BETWEEN_WAREHOUSES = 'between_warehouses';
    public const TYPE_SCRAP = 'scrap';
    public const TYPE_CUSTOMER_RETURN = 'customer_return';
    public const TYPE_SHOWROOM = 'showroom';
    public const TYPE_PERSONNEL_ASSET = 'personnel_asset';
    public const RETURN_REASON_WRONG_PICKING = 'wrong_picking';
    public const RETURN_REASON_WARRANTY = 'warranty';
    public const RETURN_REASON_PACKAGING_DAMAGE = 'packaging_damage';
    public const RETURN_REASON_TRANSIT_DAMAGE = 'transit_damage';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_BETWEEN_WAREHOUSES => 'حواله بین انبار',
            self::TYPE_SCRAP => 'حواله ضایعات',
            self::TYPE_CUSTOMER_RETURN => 'حواله مرجوعی مشتری',
            self::TYPE_SHOWROOM => 'حواله شوروم سالن',
            self::TYPE_PERSONNEL_ASSET => 'حواله اموال پرسنل',
        ];
    }

    public static function returnReasonOptions(): array
    {
        return [
            self::RETURN_REASON_WRONG_PICKING => 'اشتباه در جمع‌آوری سفارش',
            self::RETURN_REASON_WARRANTY => 'برگشت به دلیل گارانتی (خرابی کالا)',
            self::RETURN_REASON_PACKAGING_DAMAGE => 'آسیب‌دیدگی به‌دلیل پکینگ/کارتن',
            self::RETURN_REASON_TRANSIT_DAMAGE => 'شکستگی در مسیر ارسال',
        ];
    }

    protected $fillable = [
        'reference',
        'voucher_type',
        'from_warehouse_id',
        'to_warehouse_id',
        'related_invoice_id',
        'customer_id',
        'beneficiary_name',
        'return_reason',
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

    public function relatedInvoice()
    {
        return $this->belongsTo(Invoice::class, 'related_invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
