<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Services\CustomerService;
use App\Services\MessageService;
use App\Models\User;
use App\Helpers\PasswordHelper;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Customers",
 *     description="Customer management endpoints"
 * )
 */
class CustomerController extends BaseController
{
    public function __construct(CustomerService $customerService, MessageService $messageService)
    {
        // Call the parent constructor to initialize services
        parent::__construct($customerService, $messageService);
    }

    /**
     * @OA\Get(
     *     path="/api/customer-management/customers",
     *     summary="Get all customers",
     *     description="Retrieve a paginated list of all customers",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customers retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index()
    {
        return parent::index();
    }

    /**
     * @OA\Get(
     *     path="/api/customer-management/customers/{id}",
     *     summary="Get a specific customer",
     *     description="Retrieve detailed information about a specific customer",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Customer ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="customer", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function show($id, $withOutResource = false)
    {
        return parent::show($id, true);
    }

    /**
     * @OA\Delete(
     *     path="/api/customer-management/customers/{id}",
     *     summary="Delete a customer",
     *     description="Move a customer to trash (soft delete)",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Customer ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer moved to trash successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function destroy($id)
    {
        return parent::destroy($id);
    }

    /**
     * @OA\Post(
     *     path="/api/customer-management/customers",
     *     summary="Create a new customer",
     *     description="Create a new customer account",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","first_name","last_name","user_pass"},
     *             @OA\Property(property="email", type="string", format="email", example="customer@example.com"),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="user_pass", type="string", format="password", example="password123"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="address", type="string", example="123 Main St"),
     *             @OA\Property(property="city", type="string", example="New York"),
     *             @OA\Property(property="state", type="string", example="NY"),
     *             @OA\Property(property="zip", type="string", example="10001")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Customer created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Customer created successfully"),
     *             @OA\Property(property="customer", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
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
                'user_role_id' => 7, // Will be set based on customer role
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

    /**
     * @OA\Put(
     *     path="/api/customer-management/customers/{id}",
     *     summary="Update a customer",
     *     description="Update an existing customer's information",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Customer ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","first_name","last_name"},
     *             @OA\Property(property="email", type="string", format="email", example="customer@example.com"),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="user_pass", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="address", type="string", example="123 Main St"),
     *             @OA\Property(property="city", type="string", example="New York"),
     *             @OA\Property(property="state", type="string", example="NY"),
     *             @OA\Property(property="postal_code", type="string", example="10001"),
     *             @OA\Property(property="country", type="string", example="USA"),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-15"),
     *             @OA\Property(property="gender", type="string", example="male"),
     *             @OA\Property(property="notes", type="string", example="VIP customer"),
     *             @OA\Property(property="active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="customer", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
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
            $originalPassword = null;
            if (isset($data['user_pass']) && !empty($data['user_pass'])) {
                $salt = $customer->user_salt;
                $userData['user_pass'] = PasswordHelper::generatePassword($salt, $data['user_pass']);
                $originalPassword = $data['user_pass']; // Keep original password for email
            }

            $customer = $this->service->updateWithMeta($userData, $customerMetaData, $customer, $originalPassword);

            return response($customer, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Get(
     *     path="/api/options/customers",
     *     summary="Get customers for dropdown",
     *     description="Retrieve a list of customers for dropdown/select options",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Customers retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="customers", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function getCustomersForDropdown()
    {
        try {
            $customers = $this->service->getCustomersForDropdown();
            return response()->json($customers);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch customers'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/customer-management/customers/statistics",
     *     summary="Get customer statistics",
     *     description="Retrieve customer statistics and analytics",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Customer statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_customers", type="integer", example=150),
     *             @OA\Property(property="active_customers", type="integer", example=120),
     *             @OA\Property(property="new_customers_this_month", type="integer", example=25),
     *             @OA\Property(property="total_revenue", type="number", format="float", example=125000.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function getCustomerStats()
    {
        try {
            $stats = $this->service->getCustomerStats();
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch customer statistics'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/customer-management/customers/export",
     *     summary="Export customers",
     *     description="Export customers data in various formats (CSV, Excel, PDF)",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="format",
     *         in="query",
     *         description="Export format",
     *         @OA\Schema(type="string", enum={"csv", "excel", "pdf"}, example="csv")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Export file generated successfully",
     *         @OA\MediaType(
     *             mediaType="application/octet-stream",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
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
     * @OA\Get(
     *     path="/api/customer-management/customers/next-code",
     *     summary="Get next customer code",
     *     description="Generate the next available customer code",
     *     tags={"Customers"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Next customer code generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="next_code", type="string", example="CUST-2024-001")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
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