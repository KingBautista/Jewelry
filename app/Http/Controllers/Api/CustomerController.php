<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Services\CustomerService;
use App\Services\MessageService;
use App\Models\User;
use App\Helpers\PasswordHelper;

class CustomerController extends BaseController
{
    public function __construct(CustomerService $customerService, MessageService $messageService)
    {
        // Call the parent constructor to initialize services
        parent::__construct($customerService, $messageService);
    }

    public function store(StoreCustomerRequest $request)
    {
        try {
            $data = $request->validated();

            $salt = PasswordHelper::generateSalt();
            $password = PasswordHelper::generatePassword($salt, $data['user_pass']);
            $activation_key = PasswordHelper::generateSalt();

            // Prepare user data
            $userData = [
                'user_login' => $data['email'], // Use email as login
                'user_email' => $data['email'],
                'user_salt' => $salt,
                'user_pass' => $password,
                'user_status' => 1, // Active
                'user_activation_key' => $activation_key,
                'user_role_id' => null, // Will be set based on customer role
            ];

            // Prepare customer meta data
            $customerMetaData = [
                'user_type' => 'customer',
                'customer_code' => $data['customer_code'] ?? User::generateCustomerCode(),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];
            
            $customer = $this->service->storeWithMeta($userData, $customerMetaData);
            
            // Auto-send email with default password
            $this->service->sendWelcomeEmail($customer, $data['user_pass']);
            
            return response($customer, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    public function update(UpdateCustomerRequest $request, Int $id)
    {
        try {
            $data = $request->validated();
            $customer = User::findOrFail($id);
            $oldData = $customer->toArray();

            // Prepare user data updates
            $userData = [
                'user_login' => $data['email'], // Update login if email changed
                'user_email' => $data['email'],
                'user_status' => $data['active'] ?? true ? 1 : 0,
            ];

            // Prepare customer meta data updates
            $customerMetaData = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];

            // Handle password update if provided
            if (isset($data['user_pass'])) {
                $salt = $customer->user_salt;
                $userData['user_pass'] = PasswordHelper::generatePassword($salt, $data['user_pass']);
            }

            $customer = $this->service->updateWithMeta($userData, $customerMetaData, $customer);

            return response($customer, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }


    public function getCustomersForDropdown()
    {
        try {
            $customers = $this->service->getCustomersForDropdown();
            return response()->json($customers);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch customers'], 500);
        }
    }

    public function getCustomerStats()
    {
        try {
            $stats = $this->service->getCustomerStats();
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch customer statistics'], 500);
        }
    }

    public function exportCustomers(Request $request)
    {
        try {
            $format = $request->get('format', 'csv');
            return $this->service->exportCustomers($format);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to export customers'], 500);
        }
    }

    /**
     * Get the next customer code
     */
    public function nextCode()
    {
        try {
            $nextCode = User::generateCustomerCode();
            return response(['next_code' => $nextCode], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }
}