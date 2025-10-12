<?php

namespace App\Services;

use App\Models\User;
use App\Models\EmailSetting;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Mail;
use App\Mail\CustomerWelcomeEmail;
use App\Mail\UserWelcomeEmail;
use App\Mail\UserPasswordUpdateEmail;

class CustomerService extends BaseService
{
    public function __construct()
    {
        // Pass the UserResource class to the parent constructor
        parent::__construct(new UserResource(new User), new User());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        $allCustomers = $this->getTotalCount();
        $trashedCustomers = $this->getTrashedCount();

        return UserResource::collection(User::query()
            ->customers() // Only get users with customer type
            ->when($trash, function ($query) {
                return $query->onlyTrashed();
            })
            ->when(request('search'), function ($query) {
                return $query->search(request('search'));
            })
            ->when(request('gender'), function ($query) {
                return $query->byGender(request('gender'));
            })
            ->when(request('city'), function ($query) {
                return $query->byCity(request('city'));
            })
            ->when(request('user_status'), function ($query) {
                $status = request('user_status');
                if ($status === 'Active') {
                    $query->active();
                } elseif ($status === 'Inactive') {
                    $query->inactive();
                }
            })
            ->when(request('order'), function ($query) {
                return $query->orderBy(request('order'), request('sort'));
            })
            ->when(!request('order'), function ($query) {
                return $query->orderBy('id', 'desc');
            })
            ->paginate($perPage)->withQueryString()
        )->additional(['meta' => ['all' => $allCustomers, 'trashed' => $trashedCustomers]]);
    }

    /**
     * Store a newly created resource in storage with meta data.
     */
    public function storeWithMeta(array $userData, array $metaData)
    {
        $user = parent::store($userData);
        if(count($metaData))
            $user->saveUserMeta($metaData);

        // Send welcome email with user information and temporary password
        $this->sendWelcomeEmail($user, $userData['user_pass'] ?? null);

        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage with meta data.
     */
    public function updateWithMeta(array $userData, array $metaData, User $user)
    {
        // Check if password is being updated
        $passwordUpdated = isset($userData['user_pass']) && !empty($userData['user_pass']);
        $newPassword = $passwordUpdated ? $userData['user_pass'] : null;
        
        $user->update($userData);
        if(count($metaData))
            $user->saveUserMeta($metaData);

        // Send password update email if password was changed
        if ($passwordUpdated) {
            $this->sendPasswordUpdateEmail($user, $newPassword);
        }

        return new UserResource($user);
    }

    /**
     * Get customer statistics
     */
    public function getCustomerStats()
    {
        return [
            'total_customers' => User::customers()->count(),
            'active_customers' => User::customers()->active()->count(),
            'inactive_customers' => User::customers()->inactive()->count(),
            'new_customers_this_month' => User::customers()->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'customers_by_gender' => User::customers()
                ->join('user_meta', 'users.id', '=', 'user_meta.user_id')
                ->where('user_meta.meta_key', 'gender')
                ->selectRaw('user_meta.meta_value as gender, COUNT(*) as count')
                ->groupBy('user_meta.meta_value')
                ->get(),
            'customers_by_city' => User::customers()
                ->join('user_meta', 'users.id', '=', 'user_meta.user_id')
                ->where('user_meta.meta_key', 'city')
                ->whereNotNull('user_meta.meta_value')
                ->selectRaw('user_meta.meta_value as city, COUNT(*) as count')
                ->groupBy('user_meta.meta_value')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * Get customers for dropdown
     */
    public function getCustomersForDropdown()
    {
        return User::customers()
            ->active()
            ->select('id', 'user_email')
            ->with(['getUserMetas' => function($query) {
                $query->whereIn('meta_key', ['customer_code', 'first_name', 'last_name']);
            }])
            ->get()
            ->map(function($user) {
                $meta = $user->getUserMetas->pluck('meta_value', 'meta_key');
                return [
                    'id' => $user->id,
                    'customer_code' => $meta['customer_code'] ?? null,
                    'first_name' => $meta['first_name'] ?? null,
                    'last_name' => $meta['last_name'] ?? null,
                    'email' => $user->user_email,
                ];
            })
            ->sortBy('first_name')
            ->values();
    }

    /**
     * Send welcome email to customer
     */
    public function sendWelcomeEmail($user, $password = null)
    {
        try {
            $options = [
                'login_url' => env('ADMIN_APP_URL') . "/login",
                'password' => $password
            ];

            // Configure mail settings from database
            $this->configureMailFromDatabase();

            Mail::to($user->user_email)->send(new UserWelcomeEmail($user, $options));
            
            \Log::info("Welcome email sent to customer: {$user->user_email}");
        } catch (\Exception $e) {
            \Log::error("Failed to send welcome email to customer {$user->user_email}: " . $e->getMessage());
        }
    }

    /**
     * Generate temporary password
     */
    private function generateTempPassword($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $password;
    }

    /**
     * Export customers data
     */
    public function exportCustomers($format = 'csv')
    {
        $customers = User::customers()->withTrashed()->with('getUserMetas')->get();
        
        if ($format === 'csv') {
            $filename = 'customers_export_' . date('Y-m-d_H-i-s') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($customers) {
                $file = fopen('php://output', 'w');
                
                // CSV headers
                fputcsv($file, [
                    'ID', 'Customer Code', 'First Name', 'Last Name', 'Email', 
                    'Phone', 'Address', 'City', 'State', 'Postal Code', 'Country',
                    'Date of Birth', 'Gender', 'Notes', 'Active', 'Created At', 'Updated At'
                ]);

                // CSV data
                foreach ($customers as $customer) {
                    $meta = $customer->getUserMetas->pluck('meta_value', 'meta_key');
                    fputcsv($file, [
                        $customer->id,
                        $meta['customer_code'] ?? '',
                        $meta['first_name'] ?? '',
                        $meta['last_name'] ?? '',
                        $customer->user_email,
                        $meta['phone'] ?? '',
                        $meta['address'] ?? '',
                        $meta['city'] ?? '',
                        $meta['state'] ?? '',
                        $meta['postal_code'] ?? '',
                        $meta['country'] ?? '',
                        $meta['date_of_birth'] ?? '',
                        $meta['gender'] ?? '',
                        $meta['notes'] ?? '',
                        $customer->user_status ? 'Yes' : 'No',
                        $customer->created_at->format('Y-m-d H:i:s'),
                        $customer->updated_at->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return response()->json(['error' => 'Unsupported export format'], 400);
    }

    /**
     * Send password update email to customer
     */
    public function sendPasswordUpdateEmail($user, $newPassword = null)
    {
        try {
            $options = [
                'login_url' => env('ADMIN_APP_URL') . "/login",
                'new_password' => $newPassword
            ];

            // Configure mail settings from database
            $this->configureMailFromDatabase();

            Mail::to($user->user_email)->send(new UserPasswordUpdateEmail($user, $options));
            
            \Log::info("Password update email sent to customer: {$user->user_email}");
        } catch (\Exception $e) {
            \Log::error("Failed to send password update email to customer {$user->user_email}: " . $e->getMessage());
        }
    }

    /**
     * Configure mail settings from database
     */
    private function configureMailFromDatabase()
    {
        $mailConfig = EmailSetting::getMailConfig();
        
        // Set mail configuration dynamically
        config([
            'mail.from.address' => $mailConfig['from']['address'],
            'mail.from.name' => $mailConfig['from']['name'],
        ]);
        
        // Set reply-to if configured
        if ($mailConfig['reply_to']['address']) {
            config([
                'mail.reply_to.address' => $mailConfig['reply_to']['address'],
                'mail.reply_to.name' => $mailConfig['reply_to']['name'],
            ]);
        }
    }
}
