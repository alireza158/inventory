<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PreinvoiceOrder extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED_WAREHOUSE = 'submitted_warehouse';
    public const STATUS_WAREHOUSE_APPROVED = 'warehouse_approved';
    public const STATUS_WAREHOUSE_REJECTED = 'warehouse_rejected';
    public const STATUS_SUBMITTED_FINANCE = 'submitted_finance';
    public const STATUS_FINANCE_APPROVED = 'finance_approved';

    protected $fillable = [
        'uuid',
        'created_by',
        'status',
        'customer_id', // <-- این فیلد اضافه شد تا باگ ذخیره نشدن مشتری رفع شود
        'customer_name',
        'customer_mobile',
        'customer_address',
        'province_id',
        'city_id',
        'shipping_id',
        'shipping_price',
        'discount_amount',
        'total_price',
        'warehouse_review_note',
        'warehouse_reject_reason',
        'warehouse_reviewed_by',
        'warehouse_reviewed_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'province_id' => 'integer',
        'city_id' => 'integer',
        'shipping_id' => 'integer',
        'shipping_price' => 'integer',
        'discount_amount' => 'integer',
        'total_price' => 'integer',
        'warehouse_reviewed_by' => 'integer',
        'warehouse_reviewed_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(PreinvoiceOrderItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function warehouseReviewer()
    {
        return $this->belongsTo(User::class, 'warehouse_reviewed_by');
    }

    public function reviews()
    {
        return $this->hasMany(PreinvoiceOrderReview::class);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'ثبت شده / اولیه',
            self::STATUS_SUBMITTED_WAREHOUSE => 'در انتظار تایید انبار',
            self::STATUS_WAREHOUSE_APPROVED => 'تایید شده توسط انبار',
            self::STATUS_WAREHOUSE_REJECTED => 'رد شده توسط انبار',
            self::STATUS_SUBMITTED_FINANCE => 'در انتظار تایید مالی',
            self::STATUS_FINANCE_APPROVED => 'تایید شده مالی',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }
}
