<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Services\CustomerService;
use App\Services\MessageService;
use App\Models\Customer;
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
            $password = PasswordHelper::generatePassword($salt, $data['customer_pass']);
            $activation_key = PasswordHelper::generateSalt();

            $customerData = [
                'customer_code' => $data['customer_code'] ?? null,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'customer_salt' => $salt,
                'customer_pass' => $password,
                'customer_activation_key' => $activation_key,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'notes' => $data['notes'] ?? null,
                'active' => $data['active'] ?? true,
            ];
            
            $customer = $this->service->store($customerData);
            
            // Auto-send email with default password
            $this->service->sendWelcomeEmail($customer, $data['customer_pass']);
            
            return response($customer, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    public function update(UpdateCustomerRequest $request, Int $id)
    {
        try {
            $data = $request->validated();
            $customer = Customer::findOrFail($id);
            $oldData = $customer->toArray();

            $upData = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'notes' => $data['notes'] ?? null,
                'active' => $data['active'] ?? true,
            ];

            // Handle password update if provided
            if (isset($data['customer_pass'])) {
                $salt = $customer->customer_salt;
                $upData['customer_pass'] = PasswordHelper::generatePassword($salt, $data['customer_pass']);
            }

            $customer = $this->service->update($upData, $id);

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
}