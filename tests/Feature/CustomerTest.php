<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\UserMeta;
use App\Helpers\PasswordHelper;

class CustomerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create();
    }

    /**
     * Helper method to create customer users with meta data
     */
    protected function createCustomerUsers(int $count = 1, array $overrides = []): array
    {
        $customers = [];
        
        for ($i = 0; $i < $count; $i++) {
            $userData = [
                'user_login' => $this->faker->unique()->userName(),
                'user_email' => $this->faker->unique()->safeEmail(),
                'user_salt' => PasswordHelper::generateSalt(),
                'user_pass' => PasswordHelper::generatePassword(PasswordHelper::generateSalt(), 'password123'),
                'user_status' => 1,
                'user_activation_key' => null,
                'user_role_id' => null,
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

            // Apply any remaining overrides
            $metaData = array_merge($metaData, $overrides);

            $user->saveUserMeta($metaData);
            $customers[] = $user;
        }

        return $customers;
    }

    /** @test */
    public function it_can_list_customers()
    {
        // Create some customer users
        $this->createCustomerUsers(3);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/customer-management/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'customer_code',
                        'first_name',
                        'last_name',
                        'full_name',
                        'user_email',
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
                        'user_status',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'meta'
            ]);
    }

    /** @test */
    public function it_can_create_a_customer()
    {
        $customerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe.test@example.com', // Use unique email
            'user_pass' => 'password123',
            'phone' => '+63 917 123 4567',
            'address' => '123 Main Street',
            'city' => 'Manila',
            'state' => 'Metro Manila',
            'postal_code' => '1000',
            'country' => 'Philippines',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'notes' => 'Test customer',
            'active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/customer-management/customers', $customerData);

        if ($response->status() !== 201) {
            dump($response->json());
        }

        $response->assertStatus(201)
            ->assertJsonFragment([
                'first_name' => 'John',
                'last_name' => 'Doe',
                'user_email' => 'john.doe.test@example.com',
            ]);

        // Check that user was created
        $this->assertDatabaseHas('users', [
            'user_email' => 'john.doe.test@example.com'
        ]);

        // Check that customer meta data was created
        $user = User::where('user_email', 'john.doe.test@example.com')->first();
        $this->assertDatabaseHas('user_meta', [
            'user_id' => $user->id,
            'meta_key' => 'user_type',
            'meta_value' => 'customer'
        ]);

        // Check that customer code was auto-generated
        $this->assertDatabaseHas('user_meta', [
            'user_id' => $user->id,
            'meta_key' => 'customer_code'
        ]);
    }

    /** @test */
    public function it_can_show_a_customer()
    {
        $customers = $this->createCustomerUsers(1);
        $customer = $customers[0];

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/customer-management/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $customer->id,
                'first_name' => $customer->user_details['first_name'],
                'last_name' => $customer->user_details['last_name'],
                'user_email' => $customer->user_email
            ]);
    }

    /** @test */
    public function it_can_update_a_customer()
    {
        $customers = $this->createCustomerUsers(1);
        $customer = $customers[0];

        $updateData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'user_pass' => 'newpassword123',
            'phone' => '+63 918 234 5678',
            'address' => '456 Updated Street',
            'city' => 'Quezon City',
            'state' => 'Metro Manila',
            'postal_code' => '1100',
            'country' => 'Philippines',
            'date_of_birth' => '1992-05-15',
            'gender' => 'female',
            'notes' => 'Updated customer',
            'active' => false,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/customer-management/customers/{$customer->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'user_email' => 'jane.smith@example.com'
            ]);

        // Check that user was updated
        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'user_email' => 'jane.smith@example.com',
            'user_status' => 0
        ]);

        // Check that meta data was updated
        $this->assertDatabaseHas('user_meta', [
            'user_id' => $customer->id,
            'meta_key' => 'first_name',
            'meta_value' => 'Jane'
        ]);
    }

    /** @test */
    public function it_can_delete_a_customer()
    {
        $customers = $this->createCustomerUsers(1);
        $customer = $customers[0];

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/customer-management/customers/{$customer->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('users', [
            'id' => $customer->id
        ]);
    }

    /** @test */
    public function it_can_get_customers_for_dropdown()
    {
        // Create active and inactive customers
        $this->createCustomerUsers(1, ['first_name' => 'Active Customer']);
        $this->createCustomerUsers(1, ['first_name' => 'Inactive Customer']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/options/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'customer_code',
                    'first_name',
                    'last_name',
                    'email'
                ]
            ]);
    }

    /** @test */
    public function it_can_get_customer_statistics()
    {
        // Create customers with different statuses
        $this->createCustomerUsers(5); // Active by default
        $this->createCustomerUsers(2, ['user_status' => 0]); // Inactive

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/customer-management/customers/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_customers',
                'active_customers',
                'inactive_customers',
                'new_customers_this_month',
                'customers_by_gender',
                'customers_by_city'
            ])
            ->assertJson([
                'total_customers' => 7,
                'active_customers' => 6,
                'inactive_customers' => 1
            ]);
    }

    /** @test */
    public function it_validates_customer_creation()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/customer-management/customers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'user_pass']);
    }

    /** @test */
    public function it_validates_email_uniqueness()
    {
        $customers = $this->createCustomerUsers(1);
        $existingCustomer = $customers[0];

        $customerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => $existingCustomer->user_email, // Duplicate email
            'user_pass' => 'password123',
            'active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/customer-management/customers', $customerData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_date_of_birth()
    {
        $customerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'user_pass' => 'password123',
            'date_of_birth' => '2030-01-01', // Future date
            'active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/customer-management/customers', $customerData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_of_birth']);
    }

    /** @test */
    public function it_validates_gender_values()
    {
        $customerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'user_pass' => 'password123',
            'gender' => 'invalid_gender',
            'active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/customer-management/customers', $customerData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gender']);
    }

    /** @test */
    public function it_validates_password_minimum_length()
    {
        $customerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'user_pass' => '123', // Too short
            'active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/customer-management/customers', $customerData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_pass']);
    }

    /** @test */
    public function it_can_search_customers()
    {
        $customer1 = $this->createCustomerUsers(1, ['first_name' => 'John', 'last_name' => 'Doe'])[0];
        $customer2 = $this->createCustomerUsers(1, ['first_name' => 'Jane', 'last_name' => 'Smith'])[0];

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/customer-management/customers?search=John');

        $response->assertStatus(200);
        
        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals($customer1->id, $responseData[0]['id']);
    }

    /** @test */
    public function it_can_filter_customers_by_gender()
    {
        $this->createCustomerUsers(1, ['gender' => 'male']);
        $this->createCustomerUsers(1, ['gender' => 'female']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/customer-management/customers?gender=male');

        $response->assertStatus(200);
        
        $responseData = $response->json('data');
        foreach ($responseData as $customer) {
            $this->assertEquals('male', $customer['gender']);
        }
    }

    /** @test */
    public function it_can_filter_customers_by_city()
    {
        $this->createCustomerUsers(1, ['city' => 'Manila']);
        $this->createCustomerUsers(1, ['city' => 'Quezon City']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/customer-management/customers?city=Manila');

        $response->assertStatus(200);
        
        $responseData = $response->json('data');
        foreach ($responseData as $customer) {
            $this->assertEquals('Manila', $customer['city']);
        }
    }

    /** @test */
    public function it_can_filter_customers_by_status()
    {
        $this->createCustomerUsers(1); // Active by default
        $this->createCustomerUsers(1, ['user_status' => 0]); // Inactive

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/customer-management/customers?user_status=Active');

        $response->assertStatus(200);
        
        $responseData = $response->json('data');
        foreach ($responseData as $customer) {
            $this->assertEquals('Active', $customer['user_status']);
        }
    }

}