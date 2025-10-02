<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class CustomerPortalSimpleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $customer;
    protected $customerRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create customer role
        $this->customerRole = Role::create([
            'name' => 'customer',
            'active' => 1,
            'is_super_admin' => 0
        ]);
        
        // Create customer user
        $salt = 'testsalt';
        $password = \App\Helpers\PasswordHelper::generatePassword($salt, 'password123');
        
        $this->customer = User::create([
            'user_login' => 'testcustomer',
            'user_email' => 'customer@test.com',
            'user_pass' => $password,
            'user_salt' => $salt,
            'user_status' => 1,
            'user_role_id' => $this->customerRole->id
        ]);
    }

    /** @test */
    public function customer_can_login()
    {
        $response = $this->postJson('/api/customer/login', [
            'email' => 'customer@test.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => [
                        'id',
                        'user_login',
                        'user_email',
                        'user_status'
                    ],
                    'token'
                ]);
    }

    /** @test */
    public function customer_login_fails_with_invalid_credentials()
    {
        $response = $this->postJson('/api/customer/login', [
            'email' => 'customer@test.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure(['errors']);
    }

    /** @test */
    public function customer_can_request_password_reset()
    {
        $response = $this->postJson('/api/customer/forgot-password', [
            'email' => 'customer@test.com'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure(['message']);
    }

    /** @test */
    public function customer_can_get_their_profile()
    {
        $token = $this->customer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/customer/user');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'user_login',
                        'user_email',
                        'user_status'
                    ]
                ]);
    }

    /** @test */
    public function customer_can_update_their_profile()
    {
        $token = $this->customer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson('/api/customer/user', [
            'user_login' => 'updatedcustomer',
            'user_email' => 'updated@test.com',
            'phone' => '1234567890',
            'address' => '123 Test Street'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        'id',
                        'user_login',
                        'user_email'
                    ]
                ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->customer->id,
            'user_login' => 'updatedcustomer',
            'user_email' => 'updated@test.com'
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/customer/dashboard/overview');
        $response->assertStatus(401);

        $response = $this->getJson('/api/customer/invoices');
        $response->assertStatus(401);

        $response = $this->postJson('/api/customer/payment-submission', []);
        $response->assertStatus(401);
    }
}
