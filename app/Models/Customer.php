<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_code',
        'first_name',
        'last_name',
        'email',
        'customer_salt',
        'customer_pass',
        'customer_activation_key',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'date_of_birth',
        'gender',
        'notes',
        'active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $table = 'customers';

    /**
     * Boot method to auto-generate customer code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->customer_code)) {
                $customer->customer_code = static::generateCustomerCode();
            }
        });
    }

    /**
     * Generate unique customer code
     */
    public static function generateCustomerCode()
    {
        $prefix = 'CUST';
        $lastCustomer = static::orderBy('id', 'desc')->first();
        $number = $lastCustomer ? $lastCustomer->id + 1 : 1;
        
        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get the customer's full name
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get formatted phone number
     */
    public function getFormattedPhoneAttribute()
    {
        if (!$this->phone) {
            return null;
        }
        
        // Basic phone formatting - can be enhanced based on requirements
        return $this->phone;
    }

    /**
     * Get formatted address
     */
    public function getFormattedAddressAttribute()
    {
        $addressParts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country
        ]);
        
        return implode(', ', $addressParts);
    }

    /**
     * Get customer age
     */
    public function getAgeAttribute()
    {
        if (!$this->date_of_birth) {
            return null;
        }
        
        return $this->date_of_birth->age;
    }

    /**
     * Get customer status text
     */
    public function getCustomerStatusTextAttribute()
    {
        if ($this->active) {
            return 'Active';
        } elseif ($this->customer_activation_key) {
            return 'Pending';
        } else {
            return 'Inactive';
        }
    }

    /**
     * Scope to get only active customers
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get only inactive customers
     */
    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    /**
     * Scope to search customers by name or email
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'LIKE', "%{$search}%")
              ->orWhere('last_name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('customer_code', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope to filter by gender
     */
    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }

    /**
     * Scope to filter by city
     */
    public function scopeByCity($query, $city)
    {
        return $query->where('city', 'LIKE', "%{$city}%");
    }

    /**
     * Get customer's invoices (if invoice system exists)
     */
    public function invoices()
    {
        // This relationship can be implemented when invoice system is created
        // return $this->hasMany(Invoice::class);
        return $this->hasMany('App\Models\Invoice');
    }

    /**
     * Get customer's orders (if order system exists)
     */
    public function orders()
    {
        // This relationship can be implemented when order system is created
        // return $this->hasMany(Order::class);
        return $this->hasMany('App\Models\Order');
    }
}