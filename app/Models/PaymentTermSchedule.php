<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTermSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_term_id',
        'month_number',
        'percentage',
        'description',
    ];

    protected $casts = [
        'payment_term_id' => 'integer',
        'month_number' => 'integer',
        'percentage' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $table = 'payment_term_schedules';

    /**
     * Get the payment term that owns the schedule
     */
    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    /**
     * Get formatted percentage
     */
    public function getFormattedPercentageAttribute()
    {
        return number_format($this->percentage, 2) . '%';
    }
}