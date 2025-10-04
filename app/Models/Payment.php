<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'customer_id',
        'payment_type',
        'payment_method_id',
        'amount_paid',
        'expected_amount',
        'reference_number',
        'receipt_images',
        'status',
        'rejection_reason',
        'payment_date',
        'confirmed_at',
        'confirmed_by',
        'notes',
        'selected_schedules',
        'source',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'payment_date' => 'date',
        'confirmed_at' => 'datetime',
        'receipt_images' => 'array',
        'selected_schedules' => 'array',
    ];

    protected $appends = [
        'formatted_amount_paid',
        'formatted_expected_amount',
        'status_text',
        'customer_name',
        'payment_method_name',
        'confirmed_by_name',
        'primary_receipt_image',
        'receipt_image_urls',
    ];

    /**
     * Get the invoice that owns the payment.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the customer that owns the payment.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the payment method for the payment.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the user who confirmed the payment.
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
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
        return $this->expected_amount ? '₱' . number_format($this->expected_amount, 2) : 'N/A';
    }

    /**
     * Get status text attribute.
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'confirmed' => 'Confirmed',
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
     * Get payment method name attribute.
     */
    public function getPaymentMethodNameAttribute(): ?string
    {
        return $this->paymentMethod?->bank_name;
    }

    /**
     * Get confirmed by name attribute.
     */
    public function getConfirmedByNameAttribute(): ?string
    {
        return $this->confirmedBy?->full_name;
    }

    /**
     * Get primary receipt image attribute (first image from receipt_images array).
     */
    public function getPrimaryReceiptImageAttribute(): ?string
    {
        if ($this->receipt_images && is_array($this->receipt_images) && count($this->receipt_images) > 0) {
            $imagePath = $this->receipt_images[0];
            // Return full URL for the image
            return asset('storage/' . $imagePath);
        }
        return null;
    }

    /**
     * Get all receipt image URLs.
     */
    public function getReceiptImageUrlsAttribute(): array
    {
        if ($this->receipt_images && is_array($this->receipt_images)) {
            return array_map(function($imagePath) {
                return asset('storage/' . $imagePath);
            }, $this->receipt_images);
        }
        return [];
    }

    /**
     * Scope a query to only include pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved payments.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include confirmed payments.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope a query to only include rejected payments.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope a query to search payments.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('reference_number', 'LIKE', "%{$search}%")
              ->orWhere('payment_type', 'LIKE', "%{$search}%")
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
                  $invoiceQuery->where('invoice_number', 'LIKE', "%{$search}%");
              });
        });
    }

    /**
     * Scope a query to filter by payment type.
     */
    public function scopeByPaymentType($query, $paymentType)
    {
        return $query->where('payment_type', $paymentType);
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
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }
}