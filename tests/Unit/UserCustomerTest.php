<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\UserMeta;
use App\Helpers\PasswordHelper;

class UserCustomerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Helper method to create a customer user with meta data
     */
    protected function createCustomerUser(array $overrides = []): User
    {
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

        // Apply any overrides
        $metaData = array_merge($metaData, $overrides);

        $user->saveUserMeta($metaData);
        return $user;
    }

    /** @test */
    public function it_can_generate_customer_code()
    {
        $code1 = User::generateCustomerCode();
        $code2 = User::generateCustomerCode();

        $this->assertStringStartsWith('CUST', $code1);
        $this->assertStringStartsWith('CUST', $code2);
        $this->assertEquals(10, strlen($code1)); // CUST + 6 digits
        
        // In a fresh database, both codes should be CUST000001 since no customers exist yet
        // But the method should still work correctly
        $this->assertEquals('CUST000001', $code1);
        $this->assertEquals('CUST000001', $code2);
    }

    /** @test */
    public function it_can_get_full_name_attribute()
    {
        $user = $this->createCustomerUser([
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        $this->assertEquals('John Doe', $user->full_name);
    }

    /** @test */
    public function it_can_get_customer_code_attribute()
    {
        $user = $this->createCustomerUser([
            'customer_code' => 'CUST000001'
        ]);

        $this->assertEquals('CUST000001', $user->customer_code);
    }

    /** @test */
    public function it_can_get_formatted_phone_attribute()
    {
        $user = $this->createCustomerUser([
            'phone' => '+63 917 123 4567'
        ]);

        $this->assertEquals('+63 917 123 4567', $user->formatted_phone);
    }

    /** @test */
    public function it_can_get_formatted_address_attribute()
    {
        $user = $this->createCustomerUser([
            'address' => '123 Main Street',
            'city' => 'Manila',
            'state' => 'Metro Manila',
            'postal_code' => '1000',
            'country' => 'Philippines'
        ]);

        $expected = '123 Main Street, Manila, Metro Manila, 1000, Philippines';
        $this->assertEquals($expected, $user->formatted_address);
    }

    /** @test */
    public function it_can_get_age_attribute()
    {
        $user = $this->createCustomerUser([
            'date_of_birth' => '1990-01-01'
        ]);

        $expectedAge = now()->year - 1990;
        $this->assertEquals($expectedAge, $user->age);
    }

    /** @test */
    public function it_can_get_customer_status_text_attribute()
    {
        // Active customer
        $activeUser = $this->createCustomerUser(['user_status' => 1]);
        $this->assertEquals('Active', $activeUser->customer_status_text);

        // Inactive customer
        $inactiveUser = $this->createCustomerUser(['user_status' => 0]);
        $this->assertEquals('Inactive', $inactiveUser->customer_status_text);

        // Pending customer
        $pendingUser = $this->createCustomerUser(['user_status' => 0]);
        $pendingUser->update(['user_activation_key' => 'some-key']);
        $this->assertEquals('Pending', $pendingUser->customer_status_text);
    }

    /** @test */
    public function it_can_scope_active_customers()
    {
        $this->createCustomerUser(['user_status' => 1]);
        $this->createCustomerUser(['user_status' => 0]);

        $activeCustomers = User::customers()->active()->get();
        $this->assertCount(1, $activeCustomers);
        $this->assertEquals(1, $activeCustomers->first()->user_status);
    }

    /** @test */
    public function it_can_scope_inactive_customers()
    {
        $this->createCustomerUser(['user_status' => 1]);
        $this->createCustomerUser(['user_status' => 0]);

        $inactiveCustomers = User::customers()->inactive()->get();
        $this->assertCount(1, $inactiveCustomers);
        $this->assertEquals(0, $inactiveCustomers->first()->user_status);
    }

    /** @test */
    public function it_can_scope_customers_only()
    {
        // Create a regular user
        User::factory()->create();
        
        // Create customer users
        $this->createCustomerUser();
        $this->createCustomerUser();

        $customers = User::customers()->get();
        $this->assertCount(2, $customers);
        
        foreach ($customers as $customer) {
            $this->assertEquals('customer', $customer->user_details['user_type']);
        }
    }

    /** @test */
    public function it_can_search_customers_by_name()
    {
        $user1 = $this->createCustomerUser(['first_name' => 'John', 'last_name' => 'Doe']);
        $user2 = $this->createCustomerUser(['first_name' => 'Jane', 'last_name' => 'Smith']);

        $results = User::customers()->search('John')->get();
        $this->assertCount(1, $results);
        $this->assertEquals($user1->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_customers_by_email()
    {
        $user1 = $this->createCustomerUser(['user_email' => 'john@example.com']);
        $user2 = $this->createCustomerUser(['user_email' => 'jane@example.com']);

        $results = User::customers()->search('john@example.com')->get();
        $this->assertCount(1, $results);
        $this->assertEquals($user1->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_customers_by_customer_code()
    {
        $user1 = $this->createCustomerUser(['customer_code' => 'CUST000001']);
        $user2 = $this->createCustomerUser(['customer_code' => 'CUST000002']);

        $results = User::customers()->search('CUST000001')->get();
        $this->assertCount(1, $results);
        $this->assertEquals($user1->id, $results->first()->id);
    }

    /** @test */
    public function it_can_filter_customers_by_gender()
    {
        $this->createCustomerUser(['gender' => 'male']);
        $this->createCustomerUser(['gender' => 'female']);

        $maleCustomers = User::customers()->byGender('male')->get();
        $this->assertCount(1, $maleCustomers);
        $this->assertEquals('male', $maleCustomers->first()->user_details['gender']);
    }

    /** @test */
    public function it_can_filter_customers_by_city()
    {
        $this->createCustomerUser(['city' => 'Manila']);
        $this->createCustomerUser(['city' => 'Quezon City']);

        $manilaCustomers = User::customers()->byCity('Manila')->get();
        $this->assertCount(1, $manilaCustomers);
        $this->assertEquals('Manila', $manilaCustomers->first()->user_details['city']);
    }

    /** @test */
    public function it_can_save_user_meta_data()
    {
        $user = User::factory()->create();
        
        $metaData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '+63 917 123 4567'
        ];

        $user->saveUserMeta($metaData);

        $this->assertDatabaseHas('user_meta', [
            'user_id' => $user->id,
            'meta_key' => 'first_name',
            'meta_value' => 'John'
        ]);

        $this->assertDatabaseHas('user_meta', [
            'user_id' => $user->id,
            'meta_key' => 'last_name',
            'meta_value' => 'Doe'
        ]);

        $this->assertDatabaseHas('user_meta', [
            'user_id' => $user->id,
            'meta_key' => 'phone',
            'meta_value' => '+63 917 123 4567'
        ]);
    }

    /** @test */
    public function it_can_update_existing_user_meta_data()
    {
        $user = User::factory()->create();
        
        // Save initial meta data
        $user->saveUserMeta(['first_name' => 'John']);
        
        // Update the same meta data
        $user->saveUserMeta(['first_name' => 'Jane']);

        $this->assertDatabaseHas('user_meta', [
            'user_id' => $user->id,
            'meta_key' => 'first_name',
            'meta_value' => 'Jane'
        ]);

        // Should only have one record for first_name
        $this->assertDatabaseCount('user_meta', 1);
    }

    /** @test */
    public function it_can_get_user_meta_relationship()
    {
        $user = User::factory()->create();
        $user->saveUserMeta(['first_name' => 'John', 'last_name' => 'Doe']);

        $userMetas = $user->getUserMetas;
        $this->assertCount(2, $userMetas);
        
        $metaKeys = $userMetas->pluck('meta_key')->toArray();
        $this->assertContains('first_name', $metaKeys);
        $this->assertContains('last_name', $metaKeys);
    }

    /** @test */
    public function it_can_get_user_details_attribute()
    {
        $user = User::factory()->create();
        $user->saveUserMeta([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '+63 917 123 4567'
        ]);

        $userDetails = $user->user_details;
        
        $this->assertIsArray($userDetails);
        $this->assertEquals('John', $userDetails['first_name']);
        $this->assertEquals('Doe', $userDetails['last_name']);
        $this->assertEquals('+63 917 123 4567', $userDetails['phone']);
    }

    /** @test */
    public function it_returns_null_for_missing_meta_attributes()
    {
        // Create a user without customer meta data
        $user = User::factory()->create();
        $user->saveUserMeta(['user_type' => 'customer']);

        // Test attributes that don't exist in meta
        $this->assertNull($user->customer_code);
        $this->assertNull($user->formatted_phone);
        $this->assertNull($user->age);
    }

    /** @test */
    public function it_handles_empty_meta_data_gracefully()
    {
        $user = User::factory()->create();
        
        // Don't save any meta data
        $userDetails = $user->user_details;
        
        $this->assertIsArray($userDetails);
        $this->assertEmpty($userDetails);
    }
}
