<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Customer;
use App\Models\User;

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

    /** @test */
    public function it_can_list_customers()
    {
        // Create some customers
        Customer::factory(3)->create();

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
                        'email',
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
                        'status',
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
            'email' => 'john.doe@example.com',
            'customer_pass' => 'password123',
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

        $response->assertStatus(201)
            ->assertJsonFragment([
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
            ]);

        $this->assertDatabaseHas('customers', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com'
        ]);

        // Check that customer code was auto-generated
        $customer = Customer::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($customer->customer_code);
        $this->assertStringStartsWith('CUST', $customer->customer_code);
    }

    /** @test */
    public function it_can_show_a_customer()
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/customer-management/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $customer->id,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email
            ]);
    }

    /** @test */
    public function it_can_update_a_customer()
    {
        $customer = Customer::factory()->create();

        $updateData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'customer_pass' => 'newpassword123',
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
                'email' => 'jane.smith@example.com'
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com'
        ]);
    }

    /** @test */
    public function it_can_delete_a_customer()
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/customer-management/customers/{$customer->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('customers', [
            'id' => $customer->id
        ]);
    }

    /** @test */
    public function it_can_get_customers_for_dropdown()
    {
        // Create active and inactive customers
        Customer::factory()->active()->create();
        Customer::factory()->inactive()->create();

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
        Customer::factory(5)->active()->create();
        Customer::factory(2)->inactive()->create();

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
                'active_customers' => 5,
                'inactive_customers' => 2
            ]);
    }

    /** @test */
    public function it_validates_customer_creation()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/customer-management/customers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'customer_pass']);
    }

    /** @test */
    public function it_validates_email_uniqueness()
    {
        $existingCustomer = Customer::factory()->create();

        $customerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => $existingCustomer->email, // Duplicate email
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
            'customer_pass' => 'password123',
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
            'customer_pass' => '123', // Too short
            'active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/customer-management/customers', $customerData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_pass']);
    }

    /** @test */
    public function it_can_search_customers()
    {
        $customer1 = Customer::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        $customer2 = Customer::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

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
        Customer::factory()->gender('male')->create();
        Customer::factory()->gender('female')->create();

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
        Customer::factory()->fromCity('Manila')->create();
        Customer::factory()->fromCity('Quezon City')->create();

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
        Customer::factory()->active()->create();
        Customer::factory()->inactive()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/customer-management/customers?active=Active');

        $response->assertStatus(200);
        
        $responseData = $response->json('data');
        foreach ($responseData as $customer) {
            $this->assertTrue($customer['active']);
        }
    }

}