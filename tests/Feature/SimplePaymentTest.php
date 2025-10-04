<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\User;

class SimplePaymentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function payment_can_be_created_with_admin_source()
    {
        // Create user manually
        $user = User::create([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => 'password',
            'user_salt' => 'salt',
            'user_status' => 1,
            'user_role_id' => 1,
        ]);

        // Create invoice manually
        $invoice = Invoice::create([
            'customer_id' => $user->id,
            'invoice_number' => 'INV001',
            'total_amount' => 1000.00,
            'remaining_balance' => 1000.00,
            'payment_status' => 'pending',
            'status' => 'active',
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $user->id,
            'payment_type' => 'partial',
            'amount_paid' => 1000.00,
            'expected_amount' => 1000.00,
            'reference_number' => 'ADMIN001',
            'status' => 'confirmed',
            'payment_date' => now()->toDateString(),
            'source' => 'admin_created',
        ]);

        $this->assertDatabaseHas('payments', [
            'reference_number' => 'ADMIN001',
            'source' => 'admin_created',
        ]);

        $this->assertEquals('admin_created', $payment->source);
    }

    /** @test */
    public function payment_can_be_created_with_customer_source()
    {
        // Create user manually
        $user = User::create([
            'user_login' => 'testuser2',
            'user_email' => 'test2@example.com',
            'user_pass' => 'password',
            'user_salt' => 'salt',
            'user_status' => 1,
            'user_role_id' => 1,
        ]);

        // Create invoice manually
        $invoice = Invoice::create([
            'customer_id' => $user->id,
            'invoice_number' => 'INV002',
            'total_amount' => 500.00,
            'remaining_balance' => 500.00,
            'payment_status' => 'pending',
            'status' => 'active',
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $user->id,
            'payment_type' => 'partial',
            'amount_paid' => 500.00,
            'expected_amount' => 500.00,
            'reference_number' => 'CUST001',
            'status' => 'pending',
            'payment_date' => now()->toDateString(),
            'source' => 'customer_submission',
        ]);

        $this->assertDatabaseHas('payments', [
            'reference_number' => 'CUST001',
            'source' => 'customer_submission',
        ]);

        $this->assertEquals('customer_submission', $payment->source);
    }

    /** @test */
    public function payments_can_be_filtered_by_source()
    {
        // Create user manually
        $user = User::create([
            'user_login' => 'testuser3',
            'user_email' => 'test3@example.com',
            'user_pass' => 'password',
            'user_salt' => 'salt',
            'user_status' => 1,
            'user_role_id' => 1,
        ]);

        // Create invoice manually
        $invoice = Invoice::create([
            'customer_id' => $user->id,
            'invoice_number' => 'INV003',
            'total_amount' => 1000.00,
            'remaining_balance' => 1000.00,
            'payment_status' => 'pending',
            'status' => 'active',
        ]);

        // Create admin payment
        Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $user->id,
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
            'invoice_id' => $invoice->id,
            'customer_id' => $user->id,
            'payment_type' => 'partial',
            'amount_paid' => 500.00,
            'expected_amount' => 500.00,
            'reference_number' => 'CUST001',
            'status' => 'pending',
            'payment_date' => now()->toDateString(),
            'source' => 'customer_submission',
        ]);

        // Test filtering by source
        $adminPayments = Payment::where('source', 'admin_created')->get();
        $customerPayments = Payment::where('source', 'customer_submission')->get();

        $this->assertCount(1, $adminPayments);
        $this->assertCount(1, $customerPayments);
        $this->assertEquals('ADMIN001', $adminPayments->first()->reference_number);
        $this->assertEquals('CUST001', $customerPayments->first()->reference_number);
    }
}
