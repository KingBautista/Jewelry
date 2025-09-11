<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\UserMeta;
use App\Http\Resources\UserResource;
use App\Helpers\PasswordHelper;

class UserResourceTest extends TestCase
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
    public function it_returns_base_user_data_for_regular_users()
    {
        $user = User::factory()->create([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_status' => 1,
        ]);

        $user->saveUserMeta([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'nickname' => 'Johnny',
            'mobile_number' => '+63 917 123 4567',
            'contact_number' => '+63 2 123 4567',
            'biography' => 'Test biography',
            'attachment_file' => 'test.jpg',
            'attachment_metadata' => '{"size": 1024}',
            'theme' => 'dark',
        ]);

        $resource = new UserResource($user);
        $data = $resource->toArray(request());

        $this->assertEquals($user->id, $data['id']);
        $this->assertEquals('testuser', $data['user_login']);
        $this->assertEquals('test@example.com', $data['user_email']);
        $this->assertEquals('John', $data['first_name']);
        $this->assertEquals('Doe', $data['last_name']);
        $this->assertEquals('Johnny', $data['nickname']);
        $this->assertEquals('+63 917 123 4567', $data['mobile_number']);
        $this->assertEquals('+63 2 123 4567', $data['contact_number']);
        $this->assertEquals('Test biography', $data['biography']);
        $this->assertEquals('test.jpg', $data['attachment_file']);
        $this->assertEquals('{"size": 1024}', $data['attachment_metadata']);
        $this->assertEquals('Active', $data['user_status']);
        $this->assertEquals('dark', $data['theme']);
        $this->assertArrayHasKey('updated_at', $data);
        $this->assertArrayHasKey('deleted_at', $data);

        // Should not have customer-specific fields
        $this->assertArrayNotHasKey('customer_code', $data);
        $this->assertArrayNotHasKey('full_name', $data);
        $this->assertArrayNotHasKey('phone', $data);
        $this->assertArrayNotHasKey('address', $data);
    }

    /** @test */
    public function it_returns_customer_data_for_customer_users()
    {
        $user = $this->createCustomerUser([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'customer_code' => 'CUST000001',
            'phone' => '+63 917 123 4567',
            'address' => '123 Main Street',
            'city' => 'Manila',
            'state' => 'Metro Manila',
            'postal_code' => '1000',
            'country' => 'Philippines',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'notes' => 'Test customer',
        ]);

        $resource = new UserResource($user);
        $data = $resource->toArray(request());

        // Base user data
        $this->assertEquals($user->id, $data['id']);
        $this->assertEquals($user->user_email, $data['user_email']);
        $this->assertEquals('Active', $data['user_status']);

        // Customer-specific data
        $this->assertEquals('CUST000001', $data['customer_code']);
        $this->assertEquals('John Doe', $data['full_name']);
        $this->assertEquals('+63 917 123 4567', $data['phone']);
        $this->assertEquals('123 Main Street', $data['address']);
        $this->assertEquals('Manila', $data['city']);
        $this->assertEquals('Metro Manila', $data['state']);
        $this->assertEquals('1000', $data['postal_code']);
        $this->assertEquals('Philippines', $data['country']);
        $this->assertEquals('1990-01-01', $data['date_of_birth']);
        $this->assertEquals('male', $data['gender']);
        $this->assertEquals('Test customer', $data['notes']);
        $this->assertEquals('+63 917 123 4567', $data['formatted_phone']);
        $this->assertEquals('123 Main Street, Manila, Metro Manila, 1000, Philippines', $data['formatted_address']);
        $this->assertIsInt($data['age']);
        $this->assertEquals('Active', $data['customer_status_text']);
        $this->assertTrue($data['active']);
        $this->assertEquals($user->user_email, $data['email']); // For compatibility
        $this->assertArrayHasKey('created_at', $data);
    }

    /** @test */
    public function it_handles_missing_customer_meta_data_gracefully()
    {
        $user = User::factory()->create([
            'user_email' => 'test@example.com',
            'user_status' => 1,
        ]);

        // Only set user_type as customer, no other meta data
        $user->saveUserMeta(['user_type' => 'customer']);

        $resource = new UserResource($user);
        $data = $resource->toArray(request());

        $this->assertEquals('test@example.com', $data['user_email']);
        $this->assertEquals('Active', $data['user_status']);
        
        // Customer fields should be present but empty/null
        $this->assertArrayHasKey('customer_code', $data);
        $this->assertArrayHasKey('full_name', $data);
        $this->assertArrayHasKey('phone', $data);
        $this->assertArrayHasKey('address', $data);
        $this->assertArrayHasKey('city', $data);
        $this->assertArrayHasKey('state', $data);
        $this->assertArrayHasKey('postal_code', $data);
        $this->assertArrayHasKey('country', $data);
        $this->assertArrayHasKey('date_of_birth', $data);
        $this->assertArrayHasKey('gender', $data);
        $this->assertArrayHasKey('notes', $data);
        $this->assertArrayHasKey('formatted_phone', $data);
        $this->assertArrayHasKey('formatted_address', $data);
        $this->assertArrayHasKey('age', $data);
        $this->assertArrayHasKey('customer_status_text', $data);
        $this->assertArrayHasKey('active', $data);
        $this->assertArrayHasKey('email', $data);
    }

    /** @test */
    public function it_handles_inactive_customer_status()
    {
        $user = $this->createCustomerUser([
            'user_status' => 0,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $resource = new UserResource($user);
        $data = $resource->toArray(request());

        $this->assertEquals('Inactive', $data['user_status']);
        $this->assertEquals('Inactive', $data['customer_status_text']);
        $this->assertFalse($data['active']);
    }

    /** @test */
    public function it_handles_pending_customer_status()
    {
        $user = User::factory()->create([
            'user_email' => 'test@example.com',
            'user_status' => 0,
            'user_activation_key' => 'some-activation-key',
        ]);

        $user->saveUserMeta([
            'user_type' => 'customer',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $resource = new UserResource($user);
        $data = $resource->toArray(request());

        $this->assertEquals('Inactive', $data['user_status']);
        $this->assertEquals('Pending', $data['customer_status_text']);
        $this->assertFalse($data['active']);
    }

    /** @test */
    public function it_calculates_age_correctly()
    {
        $birthYear = now()->year - 25;
        $user = $this->createCustomerUser([
            'date_of_birth' => $birthYear . '-01-01',
        ]);

        $resource = new UserResource($user);
        $data = $resource->toArray(request());

        $this->assertEquals(25, $data['age']);
    }

    /** @test */
    public function it_formats_address_correctly()
    {
        $user = $this->createCustomerUser([
            'address' => '123 Main Street',
            'city' => 'Manila',
            'state' => 'Metro Manila',
            'postal_code' => '1000',
            'country' => 'Philippines',
        ]);

        $resource = new UserResource($user);
        $data = $resource->toArray(request());

        $expected = '123 Main Street, Manila, Metro Manila, 1000, Philippines';
        $this->assertEquals($expected, $data['formatted_address']);
    }

    /** @test */
    public function it_handles_partial_address_data()
    {
        $user = $this->createCustomerUser([
            'address' => '123 Main Street',
            'city' => 'Manila',
            // Missing state, postal_code, country
        ]);

        $resource = new UserResource($user);
        $data = $resource->toArray(request());

        $expected = '123 Main Street, Manila, , , ';
        $this->assertEquals($expected, $data['formatted_address']);
    }

    /** @test */
    public function it_handles_empty_address_data()
    {
        $user = $this->createCustomerUser([
            // No address data
        ]);

        $resource = new UserResource($user);
        $data = $resource->toArray(request());

        $expected = ', , , , ';
        $this->assertEquals($expected, $data['formatted_address']);
    }

    /** @test */
    public function it_returns_user_role_information()
    {
        $user = User::factory()->create();
        $user->saveUserMeta([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $resource = new UserResource($user);
        $data = $resource->toArray(request());

        $this->assertArrayHasKey('user_role', $data);
        $this->assertEquals('Unassigned', $data['user_role']); // Default when no role
    }

    /** @test */
    public function it_handles_deleted_users()
    {
        $user = $this->createCustomerUser();
        $user->delete();

        $resource = new UserResource($user);
        $data = $resource->toArray(request());

        $this->assertArrayHasKey('deleted_at', $data);
        $this->assertNotNull($data['deleted_at']);
    }
}
