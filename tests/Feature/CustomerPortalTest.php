<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;
use App\Models\PaymentSubmission;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $customer;
    protected $customerRole;
    protected $invoice;

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
        
        // Create test invoice
        $this->invoice = Invoice::create([
            'invoice_number' => 'INV-' . uniqid(),
            'customer_id' => $this->customer->id,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'sent',
            'payment_status' => 'unpaid',
            'subtotal' => 1000.00,
            'tax_amount' => 100.00,
            'fee_amount' => 50.00,
            'discount_amount' => 0.00,
            'total_amount' => 1150.00,
            'total_paid_amount' => 0.00,
            'remaining_balance' => 1150.00
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
    public function customer_can_get_dashboard_overview()
    {
        $token = $this->customer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/customer/dashboard/overview');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'total_invoices',
                    'total_paid',
                    'outstanding_balance',
                    'overdue_invoices',
                    'upcoming_dues'
                ]);
    }

    /** @test */
    public function customer_can_get_their_invoices()
    {
        $token = $this->customer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/customer/invoices');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'invoice_number',
                            'total_amount',
                            'payment_status'
                        ]
                    ],
                    'current_page',
                    'last_page',
                    'per_page',
                    'total'
                ]);
    }

    /** @test */
    public function customer_can_get_single_invoice()
    {
        $token = $this->customer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson("/api/customer/invoices/{$this->invoice->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'invoice_number',
                        'total_amount',
                        'payment_status'
                    ]
                ]);
    }

    /** @test */
    public function customer_cannot_access_other_customers_invoice()
    {
        // Create another customer and invoice
        $otherCustomer = User::create([
            'user_login' => 'othercustomer',
            'user_email' => 'other@test.com',
            'user_pass' => Hash::make('password123'),
            'user_salt' => 'testsalt',
            'user_status' => 1,
            'user_role_id' => $this->customerRole->id
        ]);

        $otherInvoice = Invoice::create([
            'invoice_number' => 'INV-002',
            'customer_id' => $otherCustomer->id,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'sent',
            'payment_status' => 'unpaid',
            'subtotal' => 500.00,
            'total_amount' => 500.00,
            'remaining_balance' => 500.00
        ]);

        $token = $this->customer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson("/api/customer/invoices/{$otherInvoice->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function customer_can_submit_payment()
    {
        $token = $this->customer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/customer/payment-submission', [
            'invoice_id' => $this->invoice->id,
            'amount_paid' => 500.00,
            'expected_amount' => 1150.00,
            'reference_number' => 'PAY-REF-001',
            'payment_method' => 'bank_transfer',
            'notes' => 'Test payment submission'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        'id',
                        'invoice_id',
                        'amount_paid',
                        'status'
                    ]
                ]);

        $this->assertDatabaseHas('payment_submissions', [
            'invoice_id' => $this->invoice->id,
            'customer_id' => $this->customer->id,
            'amount_paid' => 500.00,
            'reference_number' => 'PAY-REF-001',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function customer_can_get_payment_submissions()
    {
        // Create a payment submission
        PaymentSubmission::create([
            'invoice_id' => $this->invoice->id,
            'customer_id' => $this->customer->id,
            'amount_paid' => 500.00,
            'expected_amount' => 1150.00,
            'reference_number' => 'PAY-REF-001',
            'status' => 'pending',
            'submitted_at' => now()
        ]);

        $token = $this->customer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/customer/payment-submissions');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'invoice_id',
                            'amount_paid',
                            'status'
                        ]
                    ],
                    'current_page',
                    'last_page',
                    'per_page',
                    'total'
                ]);
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
    public function customer_cannot_access_admin_routes()
    {
        $token = $this->customer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/dashboard/overview');

        $response->assertStatus(403);
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
