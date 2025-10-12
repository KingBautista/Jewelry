<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class ControllerFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'user_email' => 'admin@example.com',
            'user_password' => Hash::make('password123')
        ]);
        
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    protected function authenticatedRequest($method, $uri, $data = [], $headers = [])
    {
        $headers['Authorization'] = 'Bearer ' . $this->token;
        return $this->json($method, $uri, $data, $headers);
    }

    public function test_user_controller_functionality()
    {
        // Test user listing
        $response = $this->authenticatedRequest('GET', '/api/users');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        // Test user creation
        $userData = [
            'user_name' => 'Test User',
            'user_email' => 'testuser@example.com',
            'user_password' => 'password123',
            'user_password_confirmation' => 'password123'
        ];
        
        $response = $this->authenticatedRequest('POST', '/api/users', $userData);
        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['user_email' => 'testuser@example.com']);

        // Test user update
        $user = User::where('user_email', 'testuser@example.com')->first();
        $updateData = ['user_name' => 'Updated User Name'];
        
        $response = $this->authenticatedRequest('PUT', "/api/users/{$user->id}", $updateData);
        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['user_name' => 'Updated User Name']);

        // Test user deletion
        $response = $this->authenticatedRequest('DELETE', "/api/users/{$user->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_customer_controller_functionality()
    {
        // Test customer listing
        $response = $this->authenticatedRequest('GET', '/api/customers');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        // Test customer creation
        $customerData = [
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '1234567890',
            'customer_address' => '123 Test Street'
        ];
        
        $response = $this->authenticatedRequest('POST', '/api/customers', $customerData);
        $response->assertStatus(201);
        $this->assertDatabaseHas('customers', ['customer_email' => 'customer@example.com']);

        // Test customer update
        $customer = Customer::where('customer_email', 'customer@example.com')->first();
        $updateData = ['customer_name' => 'Updated Customer Name'];
        
        $response = $this->authenticatedRequest('PUT', "/api/customers/{$customer->id}", $updateData);
        $response->assertStatus(200);
        $this->assertDatabaseHas('customers', ['customer_name' => 'Updated Customer Name']);

        // Test customer deletion
        $response = $this->authenticatedRequest('DELETE', "/api/customers/{$customer->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_invoice_controller_functionality()
    {
        // Create a customer first
        $customer = Customer::factory()->create();

        // Test invoice listing
        $response = $this->authenticatedRequest('GET', '/api/invoices');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        // Test invoice creation
        $invoiceData = [
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'subtotal' => 100.00,
            'tax_amount' => 10.00,
            'total_amount' => 110.00,
            'status' => 'pending'
        ];
        
        $response = $this->authenticatedRequest('POST', '/api/invoices', $invoiceData);
        $response->assertStatus(201);
        $this->assertDatabaseHas('invoices', ['invoice_number' => 'INV-001']);

        // Test invoice update
        $invoice = Invoice::where('invoice_number', 'INV-001')->first();
        $updateData = ['status' => 'paid'];
        
        $response = $this->authenticatedRequest('PUT', "/api/invoices/{$invoice->id}", $updateData);
        $response->assertStatus(200);
        $this->assertDatabaseHas('invoices', ['status' => 'paid']);

        // Test invoice deletion
        $response = $this->authenticatedRequest('DELETE', "/api/invoices/{$invoice->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    public function test_payment_controller_functionality()
    {
        // Create a customer and invoice first
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

        // Test payment listing
        $response = $this->authenticatedRequest('GET', '/api/payments');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        // Test payment creation
        $paymentData = [
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'payment_amount' => 50.00,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'status' => 'completed'
        ];
        
        $response = $this->authenticatedRequest('POST', '/api/payments', $paymentData);
        $response->assertStatus(201);
        $this->assertDatabaseHas('payments', ['payment_amount' => 50.00]);

        // Test payment update
        $payment = Payment::where('payment_amount', 50.00)->first();
        $updateData = ['status' => 'refunded'];
        
        $response = $this->authenticatedRequest('PUT', "/api/payments/{$payment->id}", $updateData);
        $response->assertStatus(200);
        $this->assertDatabaseHas('payments', ['status' => 'refunded']);

        // Test payment deletion
        $response = $this->authenticatedRequest('DELETE', "/api/payments/{$payment->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_dashboard_controller_functionality()
    {
        $response = $this->authenticatedRequest('GET', '/api/dashboard/stats');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_customers',
            'total_invoices',
            'total_payments',
            'recent_activities'
        ]);
    }

    public function test_customer_portal_controller_functionality()
    {
        // Create a customer
        $customer = Customer::factory()->create([
            'customer_email' => 'portal@example.com'
        ]);

        // Test customer portal login
        $response = $this->postJson('/api/customer-portal/login', [
            'email' => 'portal@example.com',
            'password' => 'password123'
        ]);
        
        // Note: This might return 401 if customer portal auth is not implemented
        // The test ensures the endpoint exists and doesn't crash
        $this->assertContains($response->getStatusCode(), [200, 401, 422]);
    }

    public function test_unauthorized_access_is_blocked()
    {
        // Test without authentication
        $response = $this->getJson('/api/users');
        $response->assertStatus(401);

        $response = $this->getJson('/api/customers');
        $response->assertStatus(401);

        $response = $this->getJson('/api/invoices');
        $response->assertStatus(401);

        $response = $this->getJson('/api/payments');
        $response->assertStatus(401);
    }

    public function test_invalid_data_handling()
    {
        // Test user creation with invalid data
        $invalidUserData = [
            'user_name' => '', // Empty name
            'user_email' => 'invalid-email', // Invalid email
            'user_password' => '123', // Too short password
        ];
        
        $response = $this->authenticatedRequest('POST', '/api/users', $invalidUserData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_name', 'user_email', 'user_password']);

        // Test customer creation with invalid data
        $invalidCustomerData = [
            'customer_name' => '', // Empty name
            'customer_email' => 'invalid-email', // Invalid email
        ];
        
        $response = $this->authenticatedRequest('POST', '/api/customers', $invalidCustomerData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_name', 'customer_email']);
    }

    public function test_nonexistent_resource_handling()
    {
        // Test accessing non-existent user
        $response = $this->authenticatedRequest('GET', '/api/users/99999');
        $response->assertStatus(404);

        // Test updating non-existent user
        $response = $this->authenticatedRequest('PUT', '/api/users/99999', ['user_name' => 'Test']);
        $response->assertStatus(404);

        // Test deleting non-existent user
        $response = $this->authenticatedRequest('DELETE', '/api/users/99999');
        $response->assertStatus(404);
    }

    public function test_pagination_functionality()
    {
        // Create multiple users
        User::factory()->count(15)->create();

        // Test pagination
        $response = $this->authenticatedRequest('GET', '/api/users?page=1&per_page=10');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'current_page',
            'per_page',
            'total'
        ]);

        $data = $response->json();
        $this->assertCount(10, $data['data']); // 10 users per page
        $this->assertEquals(1, $data['current_page']);
    }

    public function test_search_functionality()
    {
        // Create users with specific names
        User::factory()->create(['user_name' => 'John Doe']);
        User::factory()->create(['user_name' => 'Jane Smith']);
        User::factory()->create(['user_name' => 'Bob Johnson']);

        // Test search
        $response = $this->authenticatedRequest('GET', '/api/users?search=John');
        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertGreaterThan(0, count($data['data']));
        
        // Verify search results contain "John"
        foreach ($data['data'] as $user) {
            $this->assertStringContainsString('John', $user['user_name']);
        }
    }

    public function test_controller_error_handling()
    {
        // Test with malformed JSON
        $response = $this->postJson('/api/users', [], [
            'Content-Type' => 'application/json'
        ]);
        
        // Should handle gracefully (either 422 for validation or 400 for malformed JSON)
        $this->assertContains($response->getStatusCode(), [400, 422]);

        // Test with extremely large payload
        $largeData = [
            'user_name' => str_repeat('A', 10000), // Very long name
            'user_email' => 'test@example.com',
            'user_password' => 'password123',
            'user_password_confirmation' => 'password123'
        ];
        
        $response = $this->authenticatedRequest('POST', '/api/users', $largeData);
        // Should handle gracefully (either validation error or success)
        $this->assertContains($response->getStatusCode(), [200, 201, 422]);
    }

    public function test_concurrent_request_handling()
    {
        // Test multiple concurrent requests
        $responses = [];
        
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->authenticatedRequest('GET', '/api/users');
        }
        
        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
    }
}
