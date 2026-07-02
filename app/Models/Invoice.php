<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    public const STATUS_PENDING_WAREHOUSE_APPROVAL = 'pending_warehouse_approval';
    public const STATUS_PENDING_COLLECTION = 'pending_collection';
    public const STATUS_WAREHOUSE_RECEIVED = 'warehouse_received';
    public const STATUS_COLLECTING = 'collecting';
    public const STATUS_CHECKING_DISCREPANCY = 'checking_discrepancy';
    public const STATUS_FINAL_CHECK = 'final_check';
    public const STATUS_PACKING = 'packing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_NOT_SHIPPED = 'not_shipped';
    public const STATUS_PENDING_FINANCE_REAPPROVAL = 'pending_finance_reapproval';
    public const STATUS_FINANCE_APPROVED = 'finance_approved';
    public const STATUS_READY_TO_SHIP = 'ready_to_ship';

    protected $fillable = [
        'uuid','customer_id','preinvoice_order_id','document_date',
        'customer_name','customer_mobile','customer_address',
        'province_id','city_id','shipping_id','shipping_price',
        'discount_amount','subtotal','total','status','status_changed_at','status_changed_by',
        'warehouse_received_at','warehouse_received_by','collection_started_at','collection_started_by',
        'collection_completed_at','collection_completed_by','shipping_status','shipping_note','shipped_at','shipped_by',
        'external_order_id', 'items_updated_at', 'items_updated_by'
    ];

    protected $casts = [
        'document_date' => 'datetime',
        'status_changed_at' => 'datetime',
        'items_updated_at' => 'datetime',
        'warehouse_received_at' => 'datetime',
        'collection_started_at' => 'datetime',
        'collection_completed_at' => 'datetime',
        'shipped_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $invoice): void {
            if (! $invoice->document_date) {
                $invoice->document_date = $invoice->preinvoiceOrder?->display_document_date ?? $invoice->created_at ?? now();
            }
        });
    }

    public function getDisplayDocumentDateAttribute()
    {
        return $this->document_date ?? $this->preinvoiceOrder?->display_document_date ?? $this->created_at;
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class)
            ->orderByRaw('COALESCE(sort_order, id) ASC')
            ->orderBy('id', 'ASC');
    }
    public function payments() { return $this->hasMany(InvoicePayment::class); }
    public function notes() { return $this->hasMany(InvoiceNote::class)->latest(); }
    public function attachments() { return $this->hasMany(InvoiceAttachment::class)->latest(); }
    public function preinvoiceOrder() { return $this->belongsTo(PreinvoiceOrder::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function shippingMethod() { return $this->belongsTo(ShippingMethod::class, 'shipping_id'); }
    public function warehouseReceivedByUser() { return $this->belongsTo(User::class, 'warehouse_received_by'); }
    public function collectionStartedByUser() { return $this->belongsTo(User::class, 'collection_started_by'); }
    public function collectionCompletedByUser() { return $this->belongsTo(User::class, 'collection_completed_by'); }
    public function shippedByUser() { return $this->belongsTo(User::class, 'shipped_by'); }
    public function statusChangedByUser() { return $this->belongsTo(User::class, 'status_changed_by'); }
    public function histories() { return $this->hasMany(SalesHavalehHistory::class)->latest('done_at'); }
    public function activityLogs() { return $this->morphMany(ActivityLog::class, 'subject')->latest('occurred_at'); }

    public function getPaidAmountAttribute(): int
    {
        return (int) $this->payments()->sum('amount');
    }

    public function getRemainingAmountAttribute(): int
    {
        return max(((int)$this->total) - (int)$this->paid_amount, 0);
    }
}
