<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSubmission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'customer_id',
        'amount_paid',
        'expected_amount',
        'reference_number',
        'receipt_images',
        'status',
        'rejection_reason',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'receipt_images' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_amount_paid',
        'formatted_expected_amount',
        'status_text',
        'customer_name',
        'reviewed_by_name',
        'receipt_images_count',
    ];

    /**
     * Get the invoice that owns the payment submission.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the customer that owns the payment submission.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the user who reviewed the payment submission.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get formatted amount paid attribute.
     */
    public function getFormattedAmountPaidAttribute(): string
    {
        return '₱' . number_format($this->amount_paid, 2);
    }

    /**
     * Get formatted expected amount attribute.
     */
    public function getFormattedExpectedAmountAttribute(): string
    {
        return '₱' . number_format($this->expected_amount, 2);
    }

    /**
     * Get status text attribute.
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    /**
     * Get customer name attribute.
     */
    public function getCustomerNameAttribute(): string
    {
        return $this->customer ? $this->customer->full_name : 'Unknown Customer';
    }

    /**
     * Get reviewed by name attribute.
     */
    public function getReviewedByNameAttribute(): ?string
    {
        return $this->reviewedBy?->full_name;
    }

    /**
     * Get receipt images count attribute.
     */
    public function getReceiptImagesCountAttribute(): int
    {
        return is_array($this->receipt_images) ? count($this->receipt_images) : 0;
    }

    /**
     * Scope a query to only include pending submissions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved submissions.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected submissions.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope a query to search payment submissions.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('reference_number', 'LIKE', "%{$search}%")
              ->orWhereHas('customer', function ($customerQuery) use ($search) {
                  $customerQuery->where('user_email', 'LIKE', "%{$search}%")
                               ->orWhereHas('getUserMetas', function ($metaQuery) use ($search) {
                                   $metaQuery->where('meta_key', 'first_name')
                                            ->where('meta_value', 'LIKE', "%{$search}%");
                               })
                               ->orWhereHas('getUserMetas', function ($metaQuery) use ($search) {
                                   $metaQuery->where('meta_key', 'last_name')
                                            ->where('meta_value', 'LIKE', "%{$search}%");
                               });
              })
              ->orWhereHas('invoice', function ($invoiceQuery) use ($search) {
                  $invoiceQuery->where('invoice_number', 'LIKE', "%{$search}%")
                              ->orWhere('product_name', 'LIKE', "%{$search}%");
              });
        });
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by customer.
     */
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
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
        return $query->whereBetween('submitted_at', [$startDate, $endDate]);
    }
}