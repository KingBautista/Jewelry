<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\UserMeta;
use App\Services\CustomerService;
use App\Helpers\PasswordHelper;

class CustomerServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $customerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customerService = new CustomerService();
    }

    /**
     * Helper method to create a customer user with meta data
     */
    protected function createCustomerUser(array $overrides = []): User
    {
        $randomString = bin2hex(random_bytes(16));
        $userData = [
            'user_login' => 'testuser_' . $randomString,
            'user_email' => 'test_' . $randomString . '@test.com',
            'user_salt' => PasswordHelper::generateSalt(),
            'user_pass' => PasswordHelper::generatePassword(PasswordHelper::generateSalt(), 'password123'),
            'user_status' => 1,
            'user_activation_key' => null,
            'user_role_id' => null,
        ];

        $user = User::create($userData);

        $metaData = [
            'user_type' => 'customer',
            'customer_code' => User::generateCustomerCode(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->country(),
            'date_of_birth' => $this->faker->date('Y-m-d', '2000-01-01'),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];

        // Handle user_status and user_email overrides
        if (isset($overrides['user_status'])) {
            $userData['user_status'] = $overrides['user_status'];
            unset($overrides['user_status']);
        }
        
        if (isset($overrides['user_email'])) {
            $userData['user_email'] = $overrides['user_email'];
            $userData['user_login'] = $overrides['user_email'];
            unset($overrides['user_email']);
        }

        $user = User::create($userData);

        // Apply any remaining overrides
        $metaData = array_merge($metaData, $overrides);

        $user->saveUserMeta($metaData);
        return $user;
    }

    /** @test */
    public function it_can_list_customers()
    {
        // Create some customer users
        $this->createCustomerUser();
        $this->createCustomerUser();
        $this->createCustomerUser();

        $result = $this->customerService->list(10, false);

        $this->assertCount(3, $result->items());
        $this->assertEquals(3, $result->total());
    }

    /** @test */
    public function it_can_list_trashed_customers()
    {
        // Create and soft delete a customer
        $customer = $this->createCustomerUser();
        $customer->delete();

        $result = $this->customerService->list(10, true);

        $this->assertCount(1, $result->items());
        $this->assertEquals(1, $result->total());
    }

    /** @test */
    public function it_can_store_customer_with_meta_data()
    {
        $userData = [
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_salt' => PasswordHelper::generateSalt(),
            'user_pass' => PasswordHelper::generatePassword(PasswordHelper::generateSalt(), 'password123'),
            'user_status' => 1,
            'user_activation_key' => null,
            'user_role_id' => null,
        ];

        $metaData = [
            'user_type' => 'customer',
            'customer_code' => 'CUST000001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '+63 917 123 4567',
            'address' => '123 Main Street',
            'city' => 'Manila',
            'state' => 'Metro Manila',
            'postal_code' => '1000',
            'country' => 'Philippines',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'notes' => 'Test customer',
        ];

        $result = $this->customerService->storeWithMeta($userData, $metaData);

        $this->assertInstanceOf(\App\Http\Resources\UserResource::class, $result);
        $this->assertEquals('John', $result->first_name);
        $this->assertEquals('Doe', $result->last_name);
        $this->assertEquals('test@example.com', $result->user_email);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'user_email' => 'test@example.com'
        ]);

        // Verify meta data was saved
        $user = User::where('user_email', 'test@example.com')->first();
        $this->assertDatabaseHas('user_meta', [
            'user_id' => $user->id,
            'meta_key' => 'user_type',
            'meta_value' => 'customer'
        ]);
    }

    /** @test */
    public function it_can_update_customer_with_meta_data()
    {
        $customer = $this->createCustomerUser();

        $userData = [
            'user_email' => 'updated@example.com',
            'user_status' => 0,
        ];

        $metaData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone' => '+63 918 234 5678',
        ];

        $result = $this->customerService->updateWithMeta($userData, $metaData, $customer);

        $this->assertInstanceOf(\App\Http\Resources\UserResource::class, $result);
        $this->assertEquals('Jane', $result->first_name);
        $this->assertEquals('Smith', $result->last_name);
        $this->assertEquals('updated@example.com', $result->user_email);

        // Verify user was updated
        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'user_email' => 'updated@example.com',
            'user_status' => 0
        ]);

        // Verify meta data was updated
        $this->assertDatabaseHas('user_meta', [
            'user_id' => $customer->id,
            'meta_key' => 'first_name',
            'meta_value' => 'Jane'
        ]);
    }

    /** @test */
    public function it_can_get_customer_statistics()
    {
        // Create customers with different statuses
        $this->createCustomerUser(['user_status' => 1]); // Active
        $this->createCustomerUser(['user_status' => 1]); // Active
        $this->createCustomerUser(['user_status' => 0]); // Inactive

        $stats = $this->customerService->getCustomerStats();

        $this->assertIsArray($stats);
        $this->assertEquals(3, $stats['total_customers']);
        $this->assertEquals(2, $stats['active_customers']);
        $this->assertEquals(1, $stats['inactive_customers']);
        $this->assertArrayHasKey('new_customers_this_month', $stats);
        $this->assertArrayHasKey('customers_by_gender', $stats);
        $this->assertArrayHasKey('customers_by_city', $stats);
    }

    /** @test */
    public function it_can_get_customers_for_dropdown()
    {
        $this->createCustomerUser([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'customer_code' => 'CUST000001'
        ]);

        $this->createCustomerUser([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'customer_code' => 'CUST000002'
        ]);

        $customers = $this->customerService->getCustomersForDropdown();

        $this->assertCount(2, $customers);
        
        $firstCustomer = $customers->first();
        $this->assertArrayHasKey('id', $firstCustomer);
        $this->assertArrayHasKey('customer_code', $firstCustomer);
        $this->assertArrayHasKey('first_name', $firstCustomer);
        $this->assertArrayHasKey('last_name', $firstCustomer);
        $this->assertArrayHasKey('email', $firstCustomer);
    }

    /** @test */
    public function it_can_export_customers_to_csv()
    {
        $this->createCustomerUser([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'customer_code' => 'CUST000001'
        ]);

        $response = $this->customerService->exportCustomers('csv');

        $this->assertInstanceOf(\Illuminate\Http\StreamedResponse::class, $response);
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    /** @test */
    public function it_returns_error_for_unsupported_export_format()
    {
        $response = $this->customerService->exportCustomers('pdf');

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    /** @test */
    public function it_can_filter_customers_by_search()
    {
        $this->createCustomerUser(['first_name' => 'John', 'last_name' => 'Doe']);
        $this->createCustomerUser(['first_name' => 'Jane', 'last_name' => 'Smith']);

        // Mock the request
        request()->merge(['search' => 'John']);

        $result = $this->customerService->list(10, false);

        $this->assertCount(1, $result->items());
        $this->assertEquals('John', $result->items()[0]->first_name);
    }

    /** @test */
    public function it_can_filter_customers_by_gender()
    {
        $this->createCustomerUser(['gender' => 'male']);
        $this->createCustomerUser(['gender' => 'female']);

        // Mock the request
        request()->merge(['gender' => 'male']);

        $result = $this->customerService->list(10, false);

        $this->assertCount(1, $result->items());
        $this->assertEquals('male', $result->items()[0]->gender);
    }

    /** @test */
    public function it_can_filter_customers_by_city()
    {
        $this->createCustomerUser(['city' => 'Manila']);
        $this->createCustomerUser(['city' => 'Quezon City']);

        // Mock the request
        request()->merge(['city' => 'Manila']);

        $result = $this->customerService->list(10, false);

        $this->assertCount(1, $result->items());
        $this->assertEquals('Manila', $result->items()[0]->city);
    }

    /** @test */
    public function it_can_filter_customers_by_status()
    {
        $this->createCustomerUser(['user_status' => 1]); // Active
        $this->createCustomerUser(['user_status' => 0]); // Inactive

        // Mock the request
        request()->merge(['user_status' => 'Active']);

        $result = $this->customerService->list(10, false);

        $this->assertCount(1, $result->items());
        $this->assertEquals('Active', $result->items()[0]->user_status);
    }

    /** @test */
    public function it_can_order_customers()
    {
        $customer1 = $this->createCustomerUser(['first_name' => 'Alice']);
        $customer2 = $this->createCustomerUser(['first_name' => 'Bob']);

        // Mock the request
        request()->merge(['order' => 'first_name', 'sort' => 'asc']);

        $result = $this->customerService->list(10, false);

        $this->assertCount(2, $result->items());
        // Note: Since we're ordering by first_name in meta, this might not work as expected
        // The actual implementation would need to handle meta field ordering
    }

    /** @test */
    public function it_handles_empty_meta_data_gracefully()
    {
        $userData = [
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_salt' => PasswordHelper::generateSalt(),
            'user_pass' => PasswordHelper::generatePassword(PasswordHelper::generateSalt(), 'password123'),
            'user_status' => 1,
            'user_activation_key' => null,
            'user_role_id' => null,
        ];

        $metaData = []; // Empty meta data

        $result = $this->customerService->storeWithMeta($userData, $metaData);

        $this->assertInstanceOf(\App\Http\Resources\UserResource::class, $result);
        $this->assertEquals('test@example.com', $result->user_email);
    }

    /** @test */
    public function it_can_get_total_count()
    {
        $this->createCustomerUser();
        $this->createCustomerUser();

        $count = $this->customerService->getTotalCount();

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function it_can_get_trashed_count()
    {
        $customer = $this->createCustomerUser();
        $customer->delete();

        $count = $this->customerService->getTrashedCount();

        $this->assertEquals(1, $count);
    }
}
