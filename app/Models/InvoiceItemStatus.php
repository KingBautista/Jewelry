<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItemStatus extends Model
{
    use HasFactory;

    protected $table = 'invoice_item_status';

    protected $fillable = [
        'invoice_id',
        'status',
        'status_date',
        'notes',
        'updated_by',
    ];

    protected $casts = [
        'status_date' => 'date',
    ];

    protected $appends = [
        'status_text',
        'updated_by_name',
    ];

    /**
     * Get the invoice that owns the item status.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the user who updated the status.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get status text attribute.
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'packed' => 'Packed',
            'for_delivery' => 'For Delivery',
            'delivered' => 'Delivered',
            'returned' => 'Returned',
            default => 'Unknown',
        };
    }

    /**
     * Get updated by name attribute.
     */
    public function getUpdatedByNameAttribute(): ?string
    {
        return $this->updatedBy?->full_name;
    }

    /**
     * Scope a query to only include pending statuses.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include packed statuses.
     */
    public function scopePacked($query)
    {
        return $query->where('status', 'packed');
    }

    /**
     * Scope a query to only include for delivery statuses.
     */
    public function scopeForDelivery($query)
    {
        return $query->where('status', 'for_delivery');
    }

    /**
     * Scope a query to only include delivered statuses.
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope a query to only include returned statuses.
     */
    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by invoice.
     */
    public function scopeByInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('status_date', [$startDate, $endDate]);
    }
}