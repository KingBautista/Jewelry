<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'payment_term_id',
        'tax_id',
        'fee_id',
        'discount_id',
        'shipping_address',
        'issue_date',
        'due_date',
        'status',
        'subtotal',
        'tax_amount',
        'fee_amount',
        'discount_amount',
        'total_amount',
        'notes',
        'active',
        'payment_status',
        'total_paid_amount',
        'remaining_balance',
        'next_payment_due_date',
        'payment_plan_created',
        'item_status',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'active' => 'boolean',
        'total_paid_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'next_payment_due_date' => 'date',
        'payment_plan_created' => 'boolean',
    ];

    protected $appends = [
        'formatted_subtotal',
        'formatted_tax_amount',
        'formatted_fee_amount',
        'formatted_discount_amount',
        'formatted_total_amount',
        'status_text',
        'customer_name',
        'payment_term_name',
        'tax_name',
        'fee_name',
        'discount_name',
        'payment_status_text',
        'formatted_total_paid_amount',
        'formatted_remaining_balance',
        'item_status_text',
    ];

    /**
     * Get the customer that owns the invoice.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the payment term for the invoice.
     */
    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    /**
     * Get the tax for the invoice.
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Get the fee for the invoice.
     */
    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }

    /**
     * Get the discount for the invoice.
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * Get the payments for the invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the payment submissions for the invoice.
     */
    public function paymentSubmissions(): HasMany
    {
        return $this->hasMany(PaymentSubmission::class);
    }

    /**
     * Get the payment schedules for the invoice.
     */
    public function paymentSchedules(): HasMany
    {
        return $this->hasMany(InvoicePaymentSchedule::class);
    }

    /**
     * Get the items for the invoice.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the item statuses for the invoice.
     */
    public function itemStatuses(): HasMany
    {
        return $this->hasMany(InvoiceItemStatus::class);
    }


    /**
     * Get formatted subtotal attribute.
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return '₱' . number_format($this->subtotal, 2);
    }

    /**
     * Get formatted tax amount attribute.
     */
    public function getFormattedTaxAmountAttribute(): string
    {
        return '₱' . number_format($this->tax_amount, 2);
    }

    /**
     * Get formatted fee amount attribute.
     */
    public function getFormattedFeeAmountAttribute(): string
    {
        return '₱' . number_format($this->fee_amount, 2);
    }

    /**
     * Get formatted discount amount attribute.
     */
    public function getFormattedDiscountAmountAttribute(): string
    {
        return '₱' . number_format($this->discount_amount, 2);
    }

    /**
     * Get formatted total amount attribute.
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        return '₱' . number_format($this->total_amount, 2);
    }

    /**
     * Get status text attribute.
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'sent' => 'Sent',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
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
     * Get payment term name attribute.
     */
    public function getPaymentTermNameAttribute(): ?string
    {
        return $this->paymentTerm?->name;
    }

    /**
     * Get tax name attribute.
     */
    public function getTaxNameAttribute(): ?string
    {
        return $this->tax?->name;
    }

    /**
     * Get fee name attribute.
     */
    public function getFeeNameAttribute(): ?string
    {
        return $this->fee?->name;
    }

    /**
     * Get discount name attribute.
     */
    public function getDiscountNameAttribute(): ?string
    {
        return $this->discount?->name;
    }

    /**
     * Get payment status text attribute.
     */
    public function getPaymentStatusTextAttribute(): string
    {
        return match($this->payment_status) {
            'unpaid' => 'Unpaid',
            'partially_paid' => 'Partially Paid',
            'fully_paid' => 'Fully Paid',
            'overdue' => 'Overdue',
            default => 'Unknown',
        };
    }

    /**
     * Get formatted total paid amount attribute.
     */
    public function getFormattedTotalPaidAmountAttribute(): string
    {
        return '₱' . number_format($this->total_paid_amount, 2);
    }

    /**
     * Get formatted remaining balance attribute.
     */
    public function getFormattedRemainingBalanceAttribute(): string
    {
        return '₱' . number_format($this->remaining_balance, 2);
    }

    /**
     * Get item status text attribute.
     */
    public function getItemStatusTextAttribute(): string
    {
        return match($this->item_status) {
            'pending' => 'Pending',
            'packed' => 'Packed',
            'for_delivery' => 'For Delivery',
            'delivered' => 'Delivered',
            'returned' => 'Returned',
            default => 'Unknown',
        };
    }

    /**
     * Scope a query to only include active invoices.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include inactive invoices.
     */
    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    /**
     * Scope a query to search invoices.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('invoice_number', 'LIKE', "%{$search}%")
              ->orWhereHas('items', function ($itemQuery) use ($search) {
                  $itemQuery->where('product_name', 'LIKE', "%{$search}%")
                           ->orWhere('description', 'LIKE', "%{$search}%");
              })
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
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by payment status.
     */
    public function scopeByPaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    /**
     * Scope a query to filter by item status.
     */
    public function scopeByItemStatus($query, $itemStatus)
    {
        return $query->where('item_status', $itemStatus);
    }

    /**
     * Generate unique invoice number.
     */
    public static function generateInvoiceNumber()
    {
        $prefix = 'INV';
        $lastInvoice = static::orderBy('id', 'desc')->first();
        
        $number = $lastInvoice ? $lastInvoice->id + 1 : 1;
        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate invoice totals.
     */
    public function calculateTotals()
    {
        // Calculate subtotal from all items
        $this->subtotal = $this->items()->sum('price');
        
        // Calculate tax amount
        if ($this->tax) {
            if ($this->tax->type === 'percentage') {
                $this->tax_amount = ($this->subtotal * $this->tax->rate) / 100;
            } else {
                $this->tax_amount = $this->tax->rate;
            }
        } else {
            $this->tax_amount = 0;
        }
        
        // Calculate fee amount
        if ($this->fee) {
            if ($this->fee->type === 'percentage') {
                $this->fee_amount = ($this->subtotal * $this->fee->amount) / 100;
            } else {
                $this->fee_amount = $this->fee->amount;
            }
        } else {
            $this->fee_amount = 0;
        }
        
        // Calculate discount amount
        if ($this->discount) {
            if ($this->discount->type === 'percentage') {
                $this->discount_amount = ($this->subtotal * $this->discount->amount) / 100;
            } else {
                $this->discount_amount = $this->discount->amount;
            }
        } else {
            $this->discount_amount = 0;
        }
        
        // Calculate total amount
        $this->total_amount = $this->subtotal + $this->tax_amount + $this->fee_amount - $this->discount_amount;
        
        return $this;
    }

    /**
     * Generate payment schedules based on payment terms.
     */
    public function generatePaymentSchedules()
    {
        if (!$this->payment_term_id) {
            return $this;
        }

        $paymentTerm = PaymentTerm::with('schedules')->find($this->payment_term_id);
        if (!$paymentTerm) {
            return $this;
        }

        // Create down payment schedule
        InvoicePaymentSchedule::create([
            'invoice_id' => $this->id,
            'payment_type' => 'downpayment',
            'due_date' => $this->issue_date,
            'expected_amount' => $this->total_amount * ($paymentTerm->down_payment_percentage / 100),
            'payment_order' => 1,
            'is_auto_generated' => true,
            'status' => 'pending'
        ]);

        // Create monthly schedules
        $remainingAmount = $this->total_amount * ($paymentTerm->remaining_percentage / 100);
        foreach ($paymentTerm->schedules as $schedule) {
            InvoicePaymentSchedule::create([
                'invoice_id' => $this->id,
                'payment_type' => 'monthly',
                'due_date' => $this->issue_date->addMonths($schedule->month_number),
                'expected_amount' => $remainingAmount * ($schedule->percentage / 100),
                'payment_order' => $schedule->month_number + 1,
                'is_auto_generated' => true,
                'status' => 'pending'
            ]);
        }

        // Update invoice
        $this->update([
            'payment_plan_created' => true,
            'next_payment_due_date' => $this->issue_date,
            'remaining_balance' => $this->total_amount
        ]);

        return $this;
    }

    /**
     * Update payment status based on payments.
     */
    public function updatePaymentStatus()
    {
        $totalPaid = $this->payments()->where('status', 'confirmed')->sum('amount_paid');
        $this->total_paid_amount = $totalPaid;
        $this->remaining_balance = $this->total_amount - $totalPaid;

        if ($totalPaid >= $this->total_amount) {
            $this->payment_status = 'fully_paid';
        } elseif ($totalPaid > 0) {
            $this->payment_status = 'partially_paid';
        } else {
            $this->payment_status = 'unpaid';
        }

        // Check for overdue
        $overdueSchedule = $this->paymentSchedules()
            ->where('due_date', '<', now()->toDateString())
            ->where('status', '!=', 'paid')
            ->exists();

        if ($overdueSchedule && $this->payment_status !== 'fully_paid') {
            $this->payment_status = 'overdue';
        }

        // Update next payment due date
        $nextSchedule = $this->paymentSchedules()
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->first();

        $this->next_payment_due_date = $nextSchedule?->due_date;

        $this->save();
        return $this;
    }
}