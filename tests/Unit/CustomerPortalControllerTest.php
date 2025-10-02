<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Api\CustomerPortalController;
use App\Models\User;
use App\Models\Invoice;
use App\Models\PaymentSubmission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Mockery;

class CustomerPortalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $customer;
    protected $customerRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->controller = new CustomerPortalController();
        
        // Create customer role
        $this->customerRole = Role::create([
            'role_name' => 'customer',
            'role_description' => 'Customer role'
        ]);
        
        // Create customer user
        $this->customer = User::create([
            'user_login' => 'testcustomer',
            'user_email' => 'customer@test.com',
            'user_pass' => Hash::make('password123'),
            'user_salt' => 'testsalt',
            'user_status' => 1,
            'user_role_id' => $this->customerRole->id
        ]);
    }

    /** @test */
    public function login_validates_required_fields()
    {
        $request = Request::create('/api/customer/login', 'POST', []);
        
        $response = $this->controller->login($request);
        
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertArrayHasKey('errors', $response->getData(true));
    }

    /** @test */
    public function login_returns_token_for_valid_credentials()
    {
        $request = Request::create('/api/customer/login', 'POST', [
            'email' => 'customer@test.com',
            'password' => 'password123'
        ]);
        
        $response = $this->controller->login($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('token', $responseData);
        $this->assertArrayHasKey('user', $responseData);
    }

    /** @test */
    public function login_rejects_invalid_credentials()
    {
        $request = Request::create('/api/customer/login', 'POST', [
            'email' => 'customer@test.com',
            'password' => 'wrongpassword'
        ]);
        
        $response = $this->controller->login($request);
        
        $this->assertEquals(422, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('errors', $responseData);
    }

    /** @test */
    public function forgot_password_validates_email()
    {
        $request = Request::create('/api/customer/forgot-password', 'POST', []);
        
        $response = $this->controller->forgotPassword($request);
        
        $this->assertEquals(422, $response->getStatusCode());
    }

    /** @test */
    public function forgot_password_sends_email_for_valid_customer()
    {
        $request = Request::create('/api/customer/forgot-password', 'POST', [
            'email' => 'customer@test.com'
        ]);
        
        $response = $this->controller->forgotPassword($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('message', $responseData);
    }

    /** @test */
    public function dashboard_overview_returns_correct_data()
    {
        // Create test invoice
        Invoice::create([
            'invoice_number' => 'INV-001',
            'customer_id' => $this->customer->id,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'sent',
            'payment_status' => 'unpaid',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'remaining_balance' => 1000.00
        ]);

        $request = Request::create('/api/customer/dashboard/overview', 'GET');
        $request->setUserResolver(function () {
            return $this->customer;
        });
        
        $response = $this->controller->dashboardOverview($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('total_invoices', $responseData);
        $this->assertArrayHasKey('total_paid', $responseData);
        $this->assertArrayHasKey('outstanding_balance', $responseData);
        $this->assertArrayHasKey('overdue_invoices', $responseData);
        $this->assertArrayHasKey('upcoming_dues', $responseData);
    }

    /** @test */
    public function get_invoices_returns_paginated_data()
    {
        // Create test invoices
        Invoice::create([
            'invoice_number' => 'INV-001',
            'customer_id' => $this->customer->id,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'sent',
            'payment_status' => 'unpaid',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'remaining_balance' => 1000.00
        ]);

        $request = Request::create('/api/customer/invoices', 'GET');
        $request->setUserResolver(function () {
            return $this->customer;
        });
        
        $response = $this->controller->getInvoices($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('current_page', $responseData);
        $this->assertArrayHasKey('last_page', $responseData);
        $this->assertArrayHasKey('per_page', $responseData);
        $this->assertArrayHasKey('total', $responseData);
    }

    /** @test */
    public function submit_payment_creates_payment_submission()
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV-001',
            'customer_id' => $this->customer->id,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'sent',
            'payment_status' => 'unpaid',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'remaining_balance' => 1000.00
        ]);

        $request = Request::create('/api/customer/payment-submission', 'POST', [
            'invoice_id' => $invoice->id,
            'amount_paid' => 500.00,
            'expected_amount' => 1000.00,
            'reference_number' => 'PAY-REF-001',
            'payment_method' => 'bank_transfer',
            'notes' => 'Test payment'
        ]);
        $request->setUserResolver(function () {
            return $this->customer;
        });
        
        $response = $this->controller->submitPayment($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('data', $responseData);
        
        $this->assertDatabaseHas('payment_submissions', [
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'amount_paid' => 500.00,
            'reference_number' => 'PAY-REF-001',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function submit_payment_validates_required_fields()
    {
        $request = Request::create('/api/customer/payment-submission', 'POST', []);
        $request->setUserResolver(function () {
            return $this->customer;
        });
        
        $response = $this->controller->submitPayment($request);
        
        $this->assertEquals(422, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('errors', $responseData);
    }

    /** @test */
    public function get_payment_submissions_returns_customer_submissions()
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV-001',
            'customer_id' => $this->customer->id,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'sent',
            'payment_status' => 'unpaid',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'remaining_balance' => 1000.00
        ]);

        PaymentSubmission::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'amount_paid' => 500.00,
            'expected_amount' => 1000.00,
            'reference_number' => 'PAY-REF-001',
            'status' => 'pending',
            'submitted_at' => now()
        ]);

        $request = Request::create('/api/customer/payment-submissions', 'GET');
        $request->setUserResolver(function () {
            return $this->customer;
        });
        
        $response = $this->controller->getPaymentSubmissions($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertCount(1, $responseData['data']);
    }

    /** @test */
    public function get_profile_returns_customer_data()
    {
        $request = Request::create('/api/customer/user', 'GET');
        $request->setUserResolver(function () {
            return $this->customer;
        });
        
        $response = $this->controller->getProfile($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals($this->customer->id, $responseData['data']['id']);
    }

    /** @test */
    public function update_profile_updates_customer_data()
    {
        $request = Request::create('/api/customer/user', 'PUT', [
            'user_login' => 'updatedcustomer',
            'user_email' => 'updated@test.com',
            'phone' => '1234567890',
            'address' => '123 Test Street'
        ]);
        $request->setUserResolver(function () {
            return $this->customer;
        });
        
        $response = $this->controller->updateProfile($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('data', $responseData);
        
        $this->assertDatabaseHas('users', [
            'id' => $this->customer->id,
            'user_login' => 'updatedcustomer',
            'user_email' => 'updated@test.com'
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
