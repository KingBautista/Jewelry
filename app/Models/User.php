<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UserMeta;

class User extends Authenticatable
{
	use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'user_login',
		'user_email',
		'user_pass',
		'user_salt',
		'user_status',
		'user_activation_key',
		'remember_token',
		'user_role_id',
	];

	/**
	 * The attributes that should be hidden for serialization.
	 *
	 * @var array<int, string>
	 */
	protected $hidden = [
		'user_pass',
		'user_salt',
		'user_activation_key',
		'remember_token',
	];

	/**
	 * The attributes that should be cast.
	 *
	 * @var array<string, string>
	 */
	protected $casts = [
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
		'deleted_at' => 'datetime',
	];

	/**
	 * The table associated with the model.
	 *
	 * @var string
	*/
	protected $table = 'users';

	public function saveUserMeta($metaData) 
	{
		foreach ($metaData as $key => $data) {
			UserMeta::updateOrCreate(
			[
				'user_id' => $this->id,
				'meta_key' => $key
			],
			[
				'meta_value' => $data,
			]);
		}
	}

	/**
	 * Append additiona info to the return data
	 *
	 * @var string
	 */
	public $appends = [
		'user_details',
		'user_role',
		'full_name',
		'customer_code',
		'formatted_phone',
		'formatted_address',
		'age',
		'customer_status_text',
	];

	public function getUserMetas()
	{   
		return $this->hasMany('App\Models\UserMeta', 'user_id', 'id');
	}

	public function getUserRole($role_id)
	{   
		return Role::find($role_id);
	}

	/**
	 * Get the user's role relationship
	 */
	public function userRole()
	{
		return $this->belongsTo(Role::class, 'user_role_id');
	}

	/**
	 * Get the user's cashier transactions
	 */
	public function transactions()
	{
		return $this->hasMany(CashierTransaction::class, 'cashier_id');
	}

	/**
	 * Get the user's cashier sessions
	 */
	public function sessions()
	{
		return $this->hasMany(CashierSession::class, 'cashier_id');
	}

	/****************************************
	*           ATTRIBUTES PARTS            *
	****************************************/
	public function getUserDetailsAttribute()
	{
		return $this->getUserMetas()->pluck('meta_value', 'meta_key')->toArray();
	}

	public function getUserRoleAttribute()
	{
		// First try to get role from direct relationship
		if ($this->user_role_id && $role = $this->getUserRole($this->user_role_id)) {
			return $role;
		}
		
		// Fallback to user meta if direct relationship is not set
		$user_role = json_decode($this->user_details['user_role'] ?? 'null');
		return $user_role && ($role = $this->getUserRole($user_role->id)) ? $role : null;
	}

	/**
	 * Get the user's full name (for customer functionality)
	 */
	public function getFullNameAttribute()
	{
		$first_name = $this->user_details['first_name'] ?? '';
		$last_name = $this->user_details['last_name'] ?? '';
		return trim($first_name . ' ' . $last_name);
	}

	/**
	 * Get customer code (for customer functionality)
	 */
	public function getCustomerCodeAttribute()
	{
		return $this->user_details['customer_code'] ?? null;
	}

	/**
	 * Get formatted phone number (for customer functionality)
	 */
	public function getFormattedPhoneAttribute()
	{
		$phone = $this->user_details['phone'] ?? null;
		if (!$phone) {
			return null;
		}
		return $phone;
	}

	/**
	 * Get formatted address (for customer functionality)
	 */
	public function getFormattedAddressAttribute()
	{
		$addressParts = array_filter([
			$this->user_details['address'] ?? null,
			$this->user_details['city'] ?? null,
			$this->user_details['state'] ?? null,
			$this->user_details['postal_code'] ?? null,
			$this->user_details['country'] ?? null
		]);
		
		return implode(', ', $addressParts);
	}

	/**
	 * Get customer age (for customer functionality)
	 */
	public function getAgeAttribute()
	{
		$date_of_birth = $this->user_details['date_of_birth'] ?? null;
		if (!$date_of_birth) {
			return null;
		}
		
		try {
			$birthDate = new \DateTime($date_of_birth);
			$today = new \DateTime();
			return $today->diff($birthDate)->y;
		} catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * Get customer status text (for customer functionality)
	 */
	public function getCustomerStatusTextAttribute()
	{
		if ($this->user_status == 1) {
			return 'Active';
		} elseif ($this->user_activation_key) {
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
		return $query->where('user_status', 1);
	}

	/**
	 * Scope to get only inactive customers
	 */
	public function scopeInactive($query)
	{
		return $query->where('user_status', 0);
	}

	/**
	 * Scope to search customers by name or email
	 */
	public function scopeSearch($query, $search)
	{
		return $query->where(function ($q) use ($search) {
			$q->where('user_login', 'LIKE', "%{$search}%")
			  ->orWhere('user_email', 'LIKE', "%{$search}%")
			  ->orWhereHas('getUserMetas', function ($metaQuery) use ($search) {
				  $metaQuery->where('meta_key', 'first_name')
						   ->where('meta_value', 'LIKE', "%{$search}%");
			  })
			  ->orWhereHas('getUserMetas', function ($metaQuery) use ($search) {
				  $metaQuery->where('meta_key', 'last_name')
						   ->where('meta_value', 'LIKE', "%{$search}%");
			  })
			  ->orWhereHas('getUserMetas', function ($metaQuery) use ($search) {
				  $metaQuery->where('meta_key', 'customer_code')
						   ->where('meta_value', 'LIKE', "%{$search}%");
			  });
		});
	}

	/**
	 * Scope to filter by gender
	 */
	public function scopeByGender($query, $gender)
	{
		return $query->whereHas('getUserMetas', function ($metaQuery) use ($gender) {
			$metaQuery->where('meta_key', 'gender')
					 ->where('meta_value', $gender);
		});
	}

	/**
	 * Scope to filter by city
	 */
	public function scopeByCity($query, $city)
	{
		return $query->whereHas('getUserMetas', function ($metaQuery) use ($city) {
			$metaQuery->where('meta_key', 'city')
					 ->where('meta_value', 'LIKE', "%{$city}%");
		});
	}

	/**
	 * Scope to get customers only (users with customer role or specific criteria)
	 */
	public function scopeCustomers($query)
	{
		// You can define what makes a user a customer
		// For now, we'll assume customers have specific meta data or role
		return $query->whereHas('getUserMetas', function ($metaQuery) {
			$metaQuery->where('meta_key', 'user_type')
					 ->where('meta_value', 'customer');
		});
	}

	/**
	 * Generate unique customer code
	 */
	public static function generateCustomerCode()
	{
		$prefix = 'CUST';
		$lastCustomer = static::whereHas('getUserMetas', function ($query) {
			$query->where('meta_key', 'user_type')
				  ->where('meta_value', 'customer');
		})->orderBy('id', 'desc')->first();
		
		$number = $lastCustomer ? $lastCustomer->id + 1 : 1;
		return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
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
