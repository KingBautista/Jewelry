<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Payment;
use App\Models\PaymentSubmission;
use App\Models\Invoice;
use App\Models\User;
use App\Models\PaymentMethod;

class PaymentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        
        // Create a test user for authentication
        $this->user = User::factory()->create();
    }

    protected function createCustomerUser(array $overrides = []): User
    {
        $userData = [
            'user_login' => $this->faker->unique()->userName(),
            'user_email' => $this->faker->unique()->safeEmail(),
            'user_salt' => 'test_salt',
            'user_pass' => 'hashed_password',
            'user_status' => $overrides['user_status'] ?? 1,
            'user_activation_key' => 'test_key',
            'user_role_id' => null,
        ];

        $user = User::create($userData);

        $customerMetaData = [
            'user_type' => 'customer',
            'customer_code' => 'CUST' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
            'first_name' => $overrides['first_name'] ?? $this->faker->firstName(),
            'last_name' => $overrides['last_name'] ?? $this->faker->lastName(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->country(),
            'date_of_birth' => $this->faker->date(),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'notes' => $this->faker->sentence(),
        ];

        $user->saveUserMeta($customerMetaData);
        return $user;
    }

    protected function createInvoice(array $overrides = []): Invoice
    {
        $customer = $overrides['customer_id'] ?? $this->createCustomerUser();
        $paymentTerm = $overrides['payment_term_id'] ?? \App\Models\PaymentTerm::first();

        $invoiceData = [
            'invoice_number' => $overrides['invoice_number'] ?? Invoice::generateInvoiceNumber(),
            'customer_id' => $customer->id,
            'product_name' => $overrides['product_name'] ?? $this->faker->words(3, true),
            'description' => $overrides['description'] ?? $this->faker->sentence(),
            'price' => $overrides['price'] ?? $this->faker->randomFloat(2, 1000, 100000),
            'product_image' => $overrides['product_image'] ?? null,
            'payment_term_id' => $paymentTerm?->id,
            'tax_id' => \App\Models\Tax::first()?->id,
            'fee_id' => \App\Models\Fee::first()?->id,
            'discount_id' => \App\Models\Discount::first()?->id,
            'shipping_address' => $overrides['shipping_address'] ?? $this->faker->address(),
            'issue_date' => $overrides['issue_date'] ?? now()->toDateString(),
            'due_date' => $overrides['due_date'] ?? now()->addDays(30)->toDateString(),
            'status' => $overrides['status'] ?? 'draft',
            'notes' => $overrides['notes'] ?? $this->faker->sentence(),
            'active' => $overrides['active'] ?? true,
        ];

        $invoice = Invoice::create($invoiceData);
        $invoice->calculateTotals()->save();
        return $invoice;
    }

    public function test_it_can_list_payments()
    {
        $response = $this->actingAs($this->user)->getJson('/api/payment-management/payments');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'invoice_id',
                            'customer_id',
                            'payment_type',
                            'amount_paid',
                            'status',
                            'payment_date',
                            'created_at',
                        ]
                    ],
                    'meta' => [
                        'all',
                        'trashed'
                    ]
                ]);
    }

    public function test_it_can_create_a_payment()
    {
        $invoice = $this->createInvoice();
        $paymentMethod = PaymentMethod::first();

        $paymentData = [
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_type' => 'downpayment',
            'payment_method_id' => $paymentMethod->id,
            'amount_paid' => 5000.00,
            'expected_amount' => 5000.00,
            'reference_number' => 'PAY123456',
            'payment_date' => now()->toDateString(),
            'status' => 'pending',
            'notes' => 'Test payment',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/payment-management/payments', $paymentData);

        $response->assertStatus(201)
                ->assertJsonFragment([
                    'payment_type' => 'downpayment',
                    'amount_paid' => '5000.00',
                    'status' => 'pending',
                ]);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'payment_type' => 'downpayment',
            'amount_paid' => 5000.00,
            'reference_number' => 'PAY123456',
        ]);
    }

    public function test_it_requires_reference_number()
    {
        $invoice = $this->createInvoice();
        $paymentMethod = PaymentMethod::first();

        $paymentData = [
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_type' => 'downpayment',
            'payment_method_id' => $paymentMethod->id,
            'amount_paid' => 5000.00,
            'expected_amount' => 5000.00,
            // 'reference_number' => 'PAY123456', // Missing required field
            'payment_date' => now()->toDateString(),
            'status' => 'pending',
            'notes' => 'Test payment',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/payment-management/payments', $paymentData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['reference_number']);
    }

    public function test_it_can_create_payment_with_receipt_images()
    {
        $invoice = $this->createInvoice();
        $paymentMethod = PaymentMethod::first();

        // Create fake receipt image files
        $receiptImage1 = \Illuminate\Http\UploadedFile::fake()->image('receipt1.jpg');
        $receiptImage2 = \Illuminate\Http\UploadedFile::fake()->image('receipt2.jpg');

        $paymentData = [
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_type' => 'downpayment',
            'payment_method_id' => $paymentMethod->id,
            'amount_paid' => 5000.00,
            'expected_amount' => 5000.00,
            'reference_number' => 'PAY123456',
            'payment_date' => now()->toDateString(),
            'status' => 'pending',
            'notes' => 'Test payment with receipt images',
            'receipt_images' => [$receiptImage1, $receiptImage2],
        ];

        $response = $this->actingAs($this->user)->postJson('/api/payment-management/payments', $paymentData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'reference_number' => 'PAY123456',
        ]);

        // Verify receipt images were stored
        $payment = Payment::where('reference_number', 'PAY123456')->first();
        $this->assertNotNull($payment->receipt_images);
        $this->assertIsArray($payment->receipt_images);
        $this->assertCount(2, $payment->receipt_images);
    }

    public function test_it_can_create_payment_with_selected_schedules()
    {
        $invoice = $this->createInvoice();
        $paymentMethod = PaymentMethod::first();

        // Create payment schedules for the invoice
        $schedule1 = \App\Models\InvoicePaymentSchedule::create([
            'invoice_id' => $invoice->id,
            'payment_type' => 'downpayment',
            'due_date' => now()->addDays(30),
            'expected_amount' => 2500.00,
            'status' => 'pending',
            'payment_order' => 1,
        ]);

        $schedule2 = \App\Models\InvoicePaymentSchedule::create([
            'invoice_id' => $invoice->id,
            'payment_type' => 'monthly',
            'due_date' => now()->addDays(60),
            'expected_amount' => 2500.00,
            'status' => 'pending',
            'payment_order' => 2,
        ]);

        $paymentData = [
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_type' => 'downpayment',
            'payment_method_id' => $paymentMethod->id,
            'amount_paid' => 5000.00,
            'expected_amount' => 5000.00,
            'reference_number' => 'PAY123456',
            'payment_date' => now()->toDateString(),
            'status' => 'pending',
            'notes' => 'Test payment with selected schedules',
            'selected_schedules' => [$schedule1->id, $schedule2->id],
        ];

        $response = $this->actingAs($this->user)->postJson('/api/payment-management/payments', $paymentData);

        $response->assertStatus(201);

        // Verify schedules were marked as paid
        $schedule1->refresh();
        $schedule2->refresh();
        $this->assertEquals('paid', $schedule1->status);
        $this->assertEquals('paid', $schedule2->status);
    }

    public function test_it_can_approve_a_payment()
    {
        $payment = Payment::first();

        $response = $this->actingAs($this->user)->patchJson("/api/payment-management/payments/{$payment->id}/approve");

        $response->assertStatus(200)
                ->assertJsonFragment(['message' => 'Payment has been approved.']);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'approved',
        ]);
    }

    public function test_it_can_confirm_a_payment()
    {
        $payment = Payment::first();
        $payment->update(['status' => 'approved']);

        $response = $this->actingAs($this->user)->patchJson("/api/payment-management/payments/{$payment->id}/confirm");

        $response->assertStatus(200)
                ->assertJsonFragment(['message' => 'Payment has been confirmed.']);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_it_can_submit_payment()
    {
        $invoice = $this->createInvoice();

        $submissionData = [
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'amount_paid' => 3000.00,
            'expected_amount' => 3000.00,
            'reference_number' => 'SUB123456',
            'receipt_images' => ['receipt1.jpg', 'receipt2.jpg'],
        ];

        $response = $this->actingAs($this->user)->postJson('/api/payment-management/submissions', $submissionData);

        $response->assertStatus(201)
                ->assertJsonFragment([
                    'amount_paid' => '3000.00',
                    'reference_number' => 'SUB123456',
                ]);

        $this->assertDatabaseHas('payment_submissions', [
            'invoice_id' => $invoice->id,
            'amount_paid' => 3000.00,
        ]);
    }

    public function test_it_can_get_payment_statistics()
    {
        $response = $this->actingAs($this->user)->getJson('/api/payment-management/payments/statistics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'total_payments',
                    'pending_payments',
                    'approved_payments',
                    'confirmed_payments',
                    'rejected_payments',
                    'total_amount_paid',
                    'pending_amount',
                    'approved_amount',
                    'payments_this_month',
                    'payments_by_type',
                    'payments_by_status',
                    'payments_by_customer',
                ]);
    }

    public function test_it_can_get_payment_schedules()
    {
        $invoice = Invoice::first();

        $response = $this->actingAs($this->user)->getJson("/api/payment-management/schedules/invoice/{$invoice->id}");

        $response->assertStatus(200);
    }

    public function test_it_can_update_item_status()
    {
        $invoice = Invoice::first();

        $statusData = [
            'status' => 'packed',
            'notes' => 'Item has been packed and ready for delivery',
        ];

        $response = $this->actingAs($this->user)->patchJson("/api/payment-management/item-status/invoice/{$invoice->id}", $statusData);

        $response->assertStatus(200)
                ->assertJsonFragment(['message' => 'Item status has been updated.']);

        $this->assertDatabaseHas('invoice_item_status', [
            'invoice_id' => $invoice->id,
            'status' => 'packed',
        ]);
    }

    public function test_payment_model_has_primary_receipt_image_attribute()
    {
        $invoice = $this->createInvoice();
        $paymentMethod = PaymentMethod::first();

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_type' => 'downpayment',
            'payment_method_id' => $paymentMethod->id,
            'amount_paid' => 5000.00,
            'expected_amount' => 5000.00,
            'reference_number' => 'PAY123456',
            'receipt_images' => ['receipts/receipt1.jpg', 'receipts/receipt2.jpg', 'receipts/receipt3.jpg'],
            'status' => 'pending',
            'payment_date' => now()->toDateString(),
            'notes' => 'Test payment',
        ]);

        $this->assertEquals('receipts/receipt1.jpg', $payment->primary_receipt_image);
    }

    public function test_payment_model_handles_empty_receipt_images()
    {
        $invoice = $this->createInvoice();
        $paymentMethod = PaymentMethod::first();

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_type' => 'downpayment',
            'payment_method_id' => $paymentMethod->id,
            'amount_paid' => 5000.00,
            'expected_amount' => 5000.00,
            'reference_number' => 'PAY123456',
            'receipt_images' => null,
            'status' => 'pending',
            'payment_date' => now()->toDateString(),
            'notes' => 'Test payment',
        ]);

        $this->assertNull($payment->primary_receipt_image);
    }

    public function test_payment_model_casts_receipt_images_to_array()
    {
        $invoice = $this->createInvoice();
        $paymentMethod = PaymentMethod::first();

        $receiptImages = ['receipts/receipt1.jpg', 'receipts/receipt2.jpg'];

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_type' => 'downpayment',
            'payment_method_id' => $paymentMethod->id,
            'amount_paid' => 5000.00,
            'expected_amount' => 5000.00,
            'reference_number' => 'PAY123456',
            'receipt_images' => $receiptImages,
            'status' => 'pending',
            'payment_date' => now()->toDateString(),
            'notes' => 'Test payment',
        ]);

        $this->assertIsArray($payment->receipt_images);
        $this->assertEquals($receiptImages, $payment->receipt_images);
    }

    public function test_payment_service_can_mark_schedules_as_paid()
    {
        $invoice = $this->createInvoice();
        
        // Create payment schedules
        $schedule1 = \App\Models\InvoicePaymentSchedule::create([
            'invoice_id' => $invoice->id,
            'payment_type' => 'downpayment',
            'due_date' => now()->addDays(30),
            'expected_amount' => 3000.00,
            'status' => 'pending',
            'payment_order' => 1,
        ]);

        $schedule2 = \App\Models\InvoicePaymentSchedule::create([
            'invoice_id' => $invoice->id,
            'payment_type' => 'monthly',
            'due_date' => now()->addDays(60),
            'expected_amount' => 2000.00,
            'status' => 'pending',
            'payment_order' => 2,
        ]);

        $paymentService = new \App\Services\PaymentService();
        $paymentService->markSchedulesAsPaid([$schedule1->id, $schedule2->id], 5000.00);

        $schedule1->refresh();
        $schedule2->refresh();

        $this->assertEquals('paid', $schedule1->status);
        $this->assertEquals('paid', $schedule2->status);
        $this->assertEquals(3000.00, $schedule1->paid_amount);
        $this->assertEquals(2000.00, $schedule2->paid_amount);
    }

    public function test_payment_service_can_get_paid_schedules()
    {
        $invoice = $this->createInvoice();
        
        // Create payment schedules
        $schedule1 = \App\Models\InvoicePaymentSchedule::create([
            'invoice_id' => $invoice->id,
            'payment_type' => 'downpayment',
            'due_date' => now()->addDays(30),
            'expected_amount' => 3000.00,
            'status' => 'paid',
            'payment_order' => 1,
        ]);

        $schedule2 = \App\Models\InvoicePaymentSchedule::create([
            'invoice_id' => $invoice->id,
            'payment_type' => 'monthly',
            'due_date' => now()->addDays(60),
            'expected_amount' => 2000.00,
            'status' => 'pending',
            'payment_order' => 2,
        ]);

        $paymentService = new \App\Services\PaymentService();
        $paidSchedules = $paymentService->getPaidSchedules($invoice->id);

        $this->assertCount(1, $paidSchedules);
        $this->assertEquals($schedule1->id, $paidSchedules->first()->id);
    }
}
