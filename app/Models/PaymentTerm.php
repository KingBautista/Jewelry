<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentTerm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'down_payment_percentage',
        'remaining_percentage',
        'term_months',
        'description',
        'active',
    ];

    protected $casts = [
        'down_payment_percentage' => 'decimal:2',
        'remaining_percentage' => 'decimal:2',
        'term_months' => 'integer',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $table = 'payment_terms';

    /**
     * Get the payment term schedules
     */
    public function schedules()
    {
        return $this->hasMany(PaymentTermSchedule::class)->orderBy('month_number');
    }

    /**
     * Scope to get only active payment terms
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get formatted down payment percentage
     */
    public function getFormattedDownPaymentAttribute()
    {
        return number_format($this->down_payment_percentage, 2) . '%';
    }

    /**
     * Get formatted remaining percentage
     */
    public function getFormattedRemainingAttribute()
    {
        return number_format($this->remaining_percentage, 2) . '%';
    }

    /**
     * Validate that schedules percentages add up to remaining percentage
     */
    public function validateSchedules()
    {
        $totalPercentage = $this->schedules->sum('percentage');
        return abs($totalPercentage - $this->remaining_percentage) < 0.01; // Allow small floating point differences
    }
}