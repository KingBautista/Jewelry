<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'amount',
        'type',
        'description',
        'valid_from',
        'valid_until',
        'usage_limit',
        'used_count',
        'active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $table = 'discounts';

    /**
     * Scope to get only active discounts
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get valid discounts (within date range and usage limit)
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_from')
              ->orWhere('valid_from', '<=', now());
        })->where(function ($q) {
            $q->whereNull('valid_until')
              ->orWhere('valid_until', '>=', now());
        })->where(function ($q) {
            $q->whereNull('usage_limit')
              ->orWhereRaw('used_count < usage_limit');
        });
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute()
    {
        if ($this->type === 'percentage') {
            return number_format($this->amount, 2) . '%';
        }
        return '₱' . number_format($this->amount, 2);
    }

    /**
     * Check if discount is valid
     */
    public function isValid()
    {
        if (!$this->active) {
            return false;
        }

        if ($this->valid_from && $this->valid_from > now()) {
            return false;
        }

        if ($this->valid_until && $this->valid_until < now()) {
            return false;
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Increment usage count
     */
    public function incrementUsage()
    {
        $this->increment('used_count');
    }
}