<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'amount',
        'type',
        'description',
        'active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $table = 'fees';

    /**
     * Scope to get only active fees
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
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
}