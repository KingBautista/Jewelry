<?php

namespace App\Services;

use App\Models\Customer;
use App\Http\Resources\CustomerResource;
use Illuminate\Support\Facades\Mail;
use App\Mail\CustomerWelcomeEmail;

class CustomerService extends BaseService
{
    public function __construct()
    {
        // Pass the CustomerResource class to the parent constructor
        parent::__construct(new CustomerResource(new Customer), new Customer());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        $allCustomers = $this->getTotalCount();
        $trashedCustomers = $this->getTrashedCount();

        return CustomerResource::collection(Customer::query()
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
            ->when(request('active'), function ($query) {
                $active = request('active');
                if ($active === 'Active') {
                    $query->active();
                } elseif ($active === 'Inactive') {
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
     * Store a newly created resource in storage.
     */
    public function store(array $data)
    {
        $customer = parent::store($data);
        
        // Auto-send welcome email with default password
        $this->sendWelcomeEmail($customer);

        return $customer;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(array $data, int $id)
    {
        $customer = $this->model::findOrFail($id);
        $customer->update($data);

        return $this->resource::make($customer);
    }

    /**
     * Get customer statistics
     */
    public function getCustomerStats()
    {
        return [
            'total_customers' => Customer::count(),
            'active_customers' => Customer::active()->count(),
            'inactive_customers' => Customer::inactive()->count(),
            'new_customers_this_month' => Customer::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'customers_by_gender' => Customer::selectRaw('gender, COUNT(*) as count')
                ->groupBy('gender')
                ->get(),
            'customers_by_city' => Customer::selectRaw('city, COUNT(*) as count')
                ->whereNotNull('city')
                ->groupBy('city')
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
        return Customer::select('id', 'customer_code', 'first_name', 'last_name', 'email')
            ->active()
            ->orderBy('first_name')
            ->get();
    }


    /**
     * Send welcome email to customer
     */
    public function sendWelcomeEmail($customer, $password = null)
    {
        try {
            // Use provided password or generate a temporary one
            $tempPassword = $password ?: $this->generateTempPassword();
            
            $options = [
                'customer_name' => $customer->full_name,
                'customer_code' => $customer->customer_code,
                'temp_password' => $tempPassword,
                'login_url' => env('ADMIN_APP_URL') . "/login",
            ];

            // Send welcome email (you can implement this mail class later)
            // Mail::to($customer->email)->send(new CustomerWelcomeEmail($customer, $options));
            
            // For now, just log the password (in production, this should be sent via email)
            \Log::info("Welcome email for customer {$customer->email} with password: {$tempPassword}");
            
        } catch (\Exception $e) {
            \Log::error("Failed to send welcome email to customer {$customer->email}: " . $e->getMessage());
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
        $customers = Customer::withTrashed()->get();
        
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
                    fputcsv($file, [
                        $customer->id,
                        $customer->customer_code,
                        $customer->first_name,
                        $customer->last_name,
                        $customer->email,
                        $customer->phone,
                        $customer->address,
                        $customer->city,
                        $customer->state,
                        $customer->postal_code,
                        $customer->country,
                        $customer->date_of_birth ? $customer->date_of_birth->format('Y-m-d') : '',
                        $customer->gender,
                        $customer->notes,
                        $customer->active ? 'Yes' : 'No',
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
}
