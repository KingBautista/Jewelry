<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_name',
        'account_name',
        'account_number',
        'description',
        'qr_code_image',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $table = 'payment_methods';

    /**
     * Scope to get only active payment methods
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get the full QR code image URL
     */
    public function getQrCodeUrlAttribute()
    {
        if ($this->qr_code_image) {
            return asset('storage/' . $this->qr_code_image);
        }
        return null;
    }

    /**
     * Get formatted account number (masked for display)
     */
    public function getMaskedAccountNumberAttribute()
    {
        if (strlen($this->account_number) <= 4) {
            return $this->account_number;
        }
        
        $masked = str_repeat('*', strlen($this->account_number) - 4);
        return $masked . substr($this->account_number, -4);
    }
}