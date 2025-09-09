<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'rate',
        'description',
        'active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $table = 'taxes';

    /**
     * Scope to get only active taxes
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get formatted rate as percentage
     */
    public function getFormattedRateAttribute()
    {
        return number_format($this->rate, 2) . '%';
    }
}