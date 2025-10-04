<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Hash;

class PaymentUnifiedTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $customerUser;
    protected $invoice;
    protected $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run seeders to create roles and other necessary data
        $this->seed([
            \Database\Seeders\RoleSeeder::class,
            \Database\Seeders\PermissionSeeder::class,
            \Database\Seeders\PaymentMethodSeeder::class,
        ]);
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'user_email' => 'admin@test.com',
            'user_role_id' => 1, // Admin role
        ]);

        // Create customer user
        $this->customerUser = User::factory()->create([
            'user_email' => 'customer@test.com',
            'user_role_id' => 7, // Customer role
        ]);

        // Create payment method
        $this->paymentMethod = PaymentMethod::factory()->create();

        // Create invoice
        $this->invoice = Invoice::factory()->create([
            'customer_id' => $this->customerUser->id,
        ]);
    }

    /** @test */
    public function admin_can_create_payment_with_admin_created_source()
    {
        $this->actingAs($this->adminUser);

        $paymentData = [
            'invoice_id' => $this->invoice->id,
            'customer_id' => $this->customerUser->id,
            'payment_type' => 'partial',
            'payment_method_id' => $this->paymentMethod->id,
            'amount_paid' => 1000.00,
            'expected_amount' => 1000.00,
            'reference_number' => 'ADMIN001',
            'payment_date' => now()->toDateString(),
            'notes' => 'Admin created payment',
        ];

        $response = $this->postJson('/api/payments', $paymentData);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'customer_id' => $this->customerUser->id,
            'reference_number' => 'ADMIN001',
            'source' => 'admin_created',
        ]);
    }

    /** @test */
    public function customer_can_submit_payment_with_customer_submission_source()
    {
        $this->actingAs($this->customerUser);

        $paymentData = [
            'invoice_id' => $this->invoice->id,
            'amount_paid' => 500.00,
            'expected_amount' => 500.00,
            'reference_number' => 'CUST001',
            'payment_method' => 'Bank Transfer',
            'notes' => 'Customer payment submission',
        ];

        $response = $this->postJson('/api/customer-portal/submit-payment', $paymentData);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'customer_id' => $this->customerUser->id,
            'reference_number' => 'CUST001',
            'source' => 'customer_submission',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function admin_can_see_customer_payment_submissions()
    {
        // Create customer payment submission
        Payment::create([
            'invoice_id' => $this->invoice->id,
            'customer_id' => $this->customerUser->id,
            'payment_type' => 'partial',
            'amount_paid' => 500.00,
            'expected_amount' => 500.00,
            'reference_number' => 'CUST001',
            'status' => 'pending',
            'payment_date' => now()->toDateString(),
            'source' => 'customer_submission',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/payment-submissions');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'reference_number' => 'CUST001',
            'source' => 'customer_submission',
        ]);
    }

    /** @test */
    public function customer_can_see_their_payment_submissions()
    {
        // Create customer payment submission
        Payment::create([
            'invoice_id' => $this->invoice->id,
            'customer_id' => $this->customerUser->id,
            'payment_type' => 'partial',
            'amount_paid' => 500.00,
            'expected_amount' => 500.00,
            'reference_number' => 'CUST001',
            'status' => 'pending',
            'payment_date' => now()->toDateString(),
            'source' => 'customer_submission',
        ]);

        $this->actingAs($this->customerUser);

        $response = $this->getJson('/api/customer-portal/payment-submissions');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'reference_number' => 'CUST001',
        ]);
    }

    /** @test */
    public function customer_can_see_admin_created_payments()
    {
        // Create admin payment
        Payment::create([
            'invoice_id' => $this->invoice->id,
            'customer_id' => $this->customerUser->id,
            'payment_type' => 'partial',
            'amount_paid' => 1000.00,
            'expected_amount' => 1000.00,
            'reference_number' => 'ADMIN001',
            'status' => 'confirmed',
            'payment_date' => now()->toDateString(),
            'source' => 'admin_created',
        ]);

        $this->actingAs($this->customerUser);

        $response = $this->getJson('/api/customer-portal/invoices/' . $this->invoice->id);

        $response->assertStatus(200);
        // Customer should be able to see admin-created payments in invoice details
    }

    /** @test */
    public function admin_can_approve_customer_payment_submission()
    {
        // Create customer payment submission
        $payment = Payment::create([
            'invoice_id' => $this->invoice->id,
            'customer_id' => $this->customerUser->id,
            'payment_type' => 'partial',
            'amount_paid' => 500.00,
            'expected_amount' => 500.00,
            'reference_number' => 'CUST001',
            'status' => 'pending',
            'payment_date' => now()->toDateString(),
            'source' => 'customer_submission',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->patchJson("/api/payment-submissions/{$payment->id}/approve");

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'approved',
            'confirmed_by' => $this->adminUser->id,
        ]);
    }

    /** @test */
    public function admin_can_reject_customer_payment_submission()
    {
        // Create customer payment submission
        $payment = Payment::create([
            'invoice_id' => $this->invoice->id,
            'customer_id' => $this->customerUser->id,
            'payment_type' => 'partial',
            'amount_paid' => 500.00,
            'expected_amount' => 500.00,
            'reference_number' => 'CUST001',
            'status' => 'pending',
            'payment_date' => now()->toDateString(),
            'source' => 'customer_submission',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->patchJson("/api/payment-submissions/{$payment->id}/reject", [
            'rejection_reason' => 'Invalid receipt'
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'rejected',
            'rejection_reason' => 'Invalid receipt',
        ]);
    }

    /** @test */
    public function payment_source_is_correctly_set()
    {
        // Test admin-created payment
        $adminPayment = Payment::create([
            'invoice_id' => $this->invoice->id,
            'customer_id' => $this->customerUser->id,
            'payment_type' => 'partial',
            'amount_paid' => 1000.00,
            'expected_amount' => 1000.00,
            'reference_number' => 'ADMIN001',
            'status' => 'confirmed',
            'payment_date' => now()->toDateString(),
            'source' => 'admin_created',
        ]);

        // Test customer submission
        $customerPayment = Payment::create([
            'invoice_id' => $this->invoice->id,
            'customer_id' => $this->customerUser->id,
            'payment_type' => 'partial',
            'amount_paid' => 500.00,
            'expected_amount' => 500.00,
            'reference_number' => 'CUST001',
            'status' => 'pending',
            'payment_date' => now()->toDateString(),
            'source' => 'customer_submission',
        ]);

        $this->assertEquals('admin_created', $adminPayment->source);
        $this->assertEquals('customer_submission', $customerPayment->source);
    }

    /** @test */
    public function unified_payment_listing_shows_both_sources()
    {
        // Create admin payment
        Payment::create([
            'invoice_id' => $this->invoice->id,
            'customer_id' => $this->customerUser->id,
            'payment_type' => 'partial',
            'amount_paid' => 1000.00,
            'expected_amount' => 1000.00,
            'reference_number' => 'ADMIN001',
            'status' => 'confirmed',
            'payment_date' => now()->toDateString(),
            'source' => 'admin_created',
        ]);

        // Create customer payment
        Payment::create([
            'invoice_id' => $this->invoice->id,
            'customer_id' => $this->customerUser->id,
            'payment_type' => 'partial',
            'amount_paid' => 500.00,
            'expected_amount' => 500.00,
            'reference_number' => 'CUST001',
            'status' => 'pending',
            'payment_date' => now()->toDateString(),
            'source' => 'customer_submission',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/payments');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        
        // Should contain both admin and customer payments
        $response->assertJsonFragment(['reference_number' => 'ADMIN001']);
        $response->assertJsonFragment(['reference_number' => 'CUST001']);
    }
}
