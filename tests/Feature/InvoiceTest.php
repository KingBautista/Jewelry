<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Invoice;
use App\Models\User;
use App\Models\PaymentTerm;
use App\Models\Tax;
use App\Models\Fee;
use App\Models\Discount;

class InvoiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user for authentication
        $this->user = User::factory()->create();
        
        // Create necessary seed data for tests
        $this->createSeedData();
    }
    
    protected function createSeedData(): void
    {
        // Create payment terms, taxes, fees, and discounts for tests
        \App\Models\PaymentTerm::create([
            'name' => 'Test Payment Term',
            'code' => 'TEST',
            'down_payment_percentage' => 20,
            'remaining_percentage' => 80,
            'term_months' => 6,
            'description' => 'Test payment term',
            'active' => true,
        ]);
        
        \App\Models\Tax::create([
            'name' => 'Test Tax',
            'type' => 'percentage',
            'rate' => 12.0,
            'description' => 'Test tax',
            'active' => true,
        ]);
        
        \App\Models\Fee::create([
            'name' => 'Test Fee',
            'type' => 'fixed',
            'amount' => 100.0,
            'description' => 'Test fee',
            'active' => true,
        ]);
        
        \App\Models\Discount::create([
            'name' => 'Test Discount',
            'type' => 'percentage',
            'amount' => 5.0,
            'description' => 'Test discount',
            'active' => true,
        ]);
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
        $paymentTerm = $overrides['payment_term_id'] ?? PaymentTerm::first();
        $tax = $overrides['tax_id'] ?? Tax::first();
        $fee = $overrides['fee_id'] ?? Fee::first();
        $discount = $overrides['discount_id'] ?? Discount::first();

        $invoiceData = [
            'invoice_number' => $overrides['invoice_number'] ?? Invoice::generateInvoiceNumber(),
            'customer_id' => $customer->id,
            'product_name' => $overrides['product_name'] ?? $this->faker->words(3, true),
            'description' => $overrides['description'] ?? $this->faker->sentence(),
            'price' => $overrides['price'] ?? $this->faker->randomFloat(2, 1000, 100000),
            'product_images' => $overrides['product_images'] ?? null,
            'payment_term_id' => $paymentTerm?->id,
            'tax_id' => $tax?->id,
            'fee_id' => $fee?->id,
            'discount_id' => $discount?->id,
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

    public function test_it_can_list_invoices()
    {
        $this->createInvoice();
        $this->createInvoice();

        $response = $this->actingAs($this->user)->getJson('/api/invoice-management/invoices');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'invoice_number',
                            'customer_id',
                            'product_name',
                            'price',
                            'status',
                            'total_amount',
                            'created_at',
                        ]
                    ],
                    'meta' => [
                        'all',
                        'trashed'
                    ]
                ]);
    }

    public function test_it_can_create_an_invoice()
    {
        $customer = $this->createCustomerUser();
        $paymentTerm = PaymentTerm::first();
        $tax = Tax::first();

        $invoiceData = [
            'customer_id' => $customer->id,
            'product_name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 10000.00,
            'payment_term_id' => $paymentTerm->id,
            'tax_id' => $tax->id,
            'shipping_address' => 'Test Address',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'notes' => 'Test Notes',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/invoice-management/invoices', $invoiceData);

        $response->assertStatus(201)
                ->assertJsonFragment([
                    'product_name' => 'Test Product',
                    'price' => '10000.00',
                    'status' => 'draft',
                ]);

        $this->assertDatabaseHas('invoices', [
            'product_name' => 'Test Product',
            'customer_id' => $customer->id,
        ]);
    }

    public function test_it_can_show_an_invoice()
    {
        $invoice = $this->createInvoice();

        $response = $this->actingAs($this->user)->getJson("/api/invoice-management/invoices/{$invoice->id}");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'product_name' => $invoice->product_name,
                ]);
    }

    public function test_it_can_update_an_invoice()
    {
        $invoice = $this->createInvoice();

        $updateData = [
            'product_name' => 'Updated Product',
            'price' => 15000.00,
            'description' => 'Updated Description',
        ];

        $response = $this->actingAs($this->user)->putJson("/api/invoice-management/invoices/{$invoice->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'product_name' => 'Updated Product',
                    'price' => '15000.00',
                ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'product_name' => 'Updated Product',
        ]);
    }

    public function test_it_can_delete_an_invoice()
    {
        $invoice = $this->createInvoice();

        $response = $this->actingAs($this->user)->deleteJson("/api/invoice-management/invoices/{$invoice->id}");

        $response->assertStatus(200)
                ->assertJsonFragment(['message' => 'Resource has been moved to trash.']);

        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    public function test_it_can_cancel_an_invoice()
    {
        $invoice = $this->createInvoice(['status' => 'sent']);

        $response = $this->actingAs($this->user)->patchJson("/api/invoice-management/invoices/{$invoice->id}/cancel");

        $response->assertStatus(200)
                ->assertJsonFragment(['message' => 'Invoice has been cancelled.']);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_it_cannot_cancel_a_paid_invoice()
    {
        $invoice = $this->createInvoice(['status' => 'paid']);

        $response = $this->actingAs($this->user)->patchJson("/api/invoice-management/invoices/{$invoice->id}/cancel");

        $response->assertStatus(400)
                ->assertJsonFragment(['message' => 'Cannot cancel a paid invoice.']);
    }

    public function test_it_can_send_invoice_email()
    {
        $invoice = $this->createInvoice(['status' => 'draft']);

        $response = $this->actingAs($this->user)->postJson("/api/invoice-management/invoices/{$invoice->id}/send-email");

        $response->assertStatus(200)
                ->assertJsonFragment(['message' => 'Invoice has been sent via email.']);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'sent',
        ]);
    }

    public function test_it_can_get_invoice_statistics()
    {
        $this->createInvoice(['status' => 'draft']);
        $this->createInvoice(['status' => 'sent']);
        $this->createInvoice(['status' => 'paid']);
        $this->createInvoice(['status' => 'overdue']);

        $response = $this->actingAs($this->user)->getJson('/api/invoice-management/invoices/statistics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'total_invoices',
                    'draft_invoices',
                    'sent_invoices',
                    'paid_invoices',
                    'overdue_invoices',
                    'cancelled_invoices',
                    'total_amount',
                    'paid_amount',
                    'pending_amount',
                    'overdue_amount',
                ]);
    }

    public function test_it_can_export_invoices()
    {
        $this->createInvoice();
        $this->createInvoice();

        $response = $this->actingAs($this->user)->getJson('/api/invoice-management/invoices/export?format=csv');

        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_it_can_search_invoices()
    {
        $invoice1 = $this->createInvoice(['product_name' => 'Diamond Ring']);
        $invoice2 = $this->createInvoice(['product_name' => 'Gold Necklace']);

        $response = $this->actingAs($this->user)->getJson('/api/invoice-management/invoices?search=Diamond');

        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals($invoice1->id, $responseData['data'][0]['id']);
    }

    public function test_it_can_filter_invoices_by_status()
    {
        $this->createInvoice(['status' => 'draft']);
        $this->createInvoice(['status' => 'sent']);
        $this->createInvoice(['status' => 'paid']);

        $response = $this->actingAs($this->user)->getJson('/api/invoice-management/invoices?status=draft');

        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals('draft', $responseData['data'][0]['status']);
    }

    public function test_it_can_filter_invoices_by_customer()
    {
        $customer1 = $this->createCustomerUser();
        $customer2 = $this->createCustomerUser();
        
        $this->createInvoice(['customer_id' => $customer1->id]);
        $this->createInvoice(['customer_id' => $customer2->id]);

        $response = $this->actingAs($this->user)->getJson("/api/invoice-management/invoices?customer_id={$customer1->id}");

        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals($customer1->id, $responseData['data'][0]['customer_id']);
    }

    public function test_it_can_create_invoice_with_product_images()
    {
        $customer = $this->createCustomerUser();
        $paymentTerm = PaymentTerm::first();

        // Create fake product image files
        $productImage1 = \Illuminate\Http\UploadedFile::fake()->image('product1.jpg');
        $productImage2 = \Illuminate\Http\UploadedFile::fake()->image('product2.jpg');

        $invoiceData = [
            'customer_id' => $customer->id,
            'product_name' => 'Test Product with Images',
            'description' => 'Test Description',
            'price' => 10000.00,
            'payment_term_id' => $paymentTerm->id,
            'shipping_address' => 'Test Address',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'notes' => 'Test Notes',
            'product_images' => [$productImage1, $productImage2],
        ];

        $response = $this->actingAs($this->user)->postJson('/api/invoice-management/invoices', $invoiceData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('invoices', [
            'product_name' => 'Test Product with Images',
            'customer_id' => $customer->id,
        ]);

        // Verify product images were stored
        $invoice = Invoice::where('product_name', 'Test Product with Images')->first();
        $this->assertNotNull($invoice->product_images);
        $this->assertIsArray($invoice->product_images);
        $this->assertCount(2, $invoice->product_images);
    }

    public function test_it_can_generate_payment_schedules()
    {
        $customer = $this->createCustomerUser();
        $paymentTerm = PaymentTerm::first();

        $invoiceData = [
            'customer_id' => $customer->id,
            'product_name' => 'Test Product with Payment Terms',
            'description' => 'Test Description',
            'price' => 10000.00,
            'payment_term_id' => $paymentTerm->id,
            'shipping_address' => 'Test Address',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'notes' => 'Test Notes',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/invoice-management/invoices', $invoiceData);

        $response->assertStatus(201);

        $invoice = Invoice::where('product_name', 'Test Product with Payment Terms')->first();
        
        // Verify payment schedules were generated
        $schedules = $invoice->paymentSchedules;
        $this->assertGreaterThan(0, $schedules->count());
        
        // Verify down payment schedule exists
        $downPaymentSchedule = $schedules->where('payment_type', 'downpayment')->first();
        $this->assertNotNull($downPaymentSchedule);
        $this->assertEquals('pending', $downPaymentSchedule->status);
    }

    public function test_it_can_calculate_totals()
    {
        $customer = $this->createCustomerUser();
        $tax = Tax::first();
        $fee = Fee::first();
        $discount = Discount::first();

        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'customer_id' => $customer->id,
            'product_name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 10000.00,
            'tax_id' => $tax->id,
            'fee_id' => $fee->id,
            'discount_id' => $discount->id,
            'shipping_address' => 'Test Address',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'notes' => 'Test Notes',
            'active' => true,
        ]);

        $invoice->calculateTotals()->save();

        $this->assertEquals(10000.00, $invoice->subtotal);
        $this->assertGreaterThan(0, $invoice->tax_amount);
        $this->assertGreaterThan(0, $invoice->fee_amount);
        $this->assertGreaterThan(0, $invoice->discount_amount);
        $this->assertGreaterThan(0, $invoice->total_amount);
    }

    public function test_it_can_update_payment_status()
    {
        $invoice = $this->createInvoice();
        
        // Create some payments
        \App\Models\Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_type' => 'downpayment',
            'amount_paid' => 5000.00,
            'expected_amount' => 5000.00,
            'reference_number' => 'PAY123456',
            'status' => 'confirmed',
            'payment_date' => now()->toDateString(),
            'notes' => 'Test payment',
        ]);

        $invoice->updatePaymentStatus();

        $this->assertEquals('partially_paid', $invoice->payment_status);
        $this->assertEquals(5000.00, $invoice->total_paid_amount);
        $this->assertGreaterThan(0, $invoice->remaining_balance);
    }

    public function test_it_can_get_invoices_for_dropdown()
    {
        $this->createInvoice(['product_name' => 'Test Product 1']);
        $this->createInvoice(['product_name' => 'Test Product 2']);

        $response = $this->actingAs($this->user)->getJson('/api/invoice-management/invoices/dropdown');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    '*' => [
                        'id',
                        'invoice_number',
                        'product_name',
                        'total_amount',
                    ]
                ]);
    }

    public function test_it_can_search_invoices_with_details()
    {
        $invoice = $this->createInvoice(['product_name' => 'Diamond Ring']);

        $response = $this->actingAs($this->user)->getJson('/api/invoice-management/invoices/search?search=Diamond');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    '*' => [
                        'id',
                        'invoice_number',
                        'product_name',
                        'total_amount',
                        'customer',
                        'payment_term',
                        'payment_schedules',
                        'payment_status',
                        'remaining_balance',
                        'total_paid_amount',
                    ]
                ]);
    }

    public function test_invoice_model_has_formatted_attributes()
    {
        $invoice = $this->createInvoice(['price' => 10000.00]);

        $this->assertStringContains('₱', $invoice->formatted_price);
        $this->assertStringContains('₱', $invoice->formatted_total_amount);
        $this->assertStringContains('₱', $invoice->formatted_subtotal);
    }

    public function test_invoice_model_casts_product_images_to_array()
    {
        $productImages = ['image1.jpg', 'image2.jpg'];

        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'customer_id' => $this->createCustomerUser()->id,
            'product_name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 10000.00,
            'product_images' => $productImages,
            'shipping_address' => 'Test Address',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'notes' => 'Test Notes',
            'active' => true,
        ]);

        $this->assertIsArray($invoice->product_images);
        $this->assertEquals($productImages, $invoice->product_images);
    }

    public function test_invoice_payment_schedule_model()
    {
        $invoice = $this->createInvoice();
        
        $schedule = \App\Models\InvoicePaymentSchedule::create([
            'invoice_id' => $invoice->id,
            'payment_type' => 'downpayment',
            'due_date' => now()->addDays(30),
            'expected_amount' => 5000.00,
            'status' => 'pending',
            'payment_order' => 1,
        ]);

        $this->assertStringContains('₱', $schedule->formatted_expected_amount);
        $this->assertStringContains('₱', $schedule->formatted_paid_amount);
        $this->assertStringContains('₱', $schedule->formatted_remaining_amount);
        $this->assertEquals('Pending', $schedule->status_text);
        $this->assertFalse($schedule->is_overdue);
    }

    public function test_invoice_payment_schedule_can_update_payment()
    {
        $invoice = $this->createInvoice();
        
        $schedule = \App\Models\InvoicePaymentSchedule::create([
            'invoice_id' => $invoice->id,
            'payment_type' => 'downpayment',
            'due_date' => now()->addDays(30),
            'expected_amount' => 5000.00,
            'status' => 'pending',
            'payment_order' => 1,
        ]);

        $schedule->updatePayment(3000.00);

        $this->assertEquals(3000.00, $schedule->paid_amount);
        $this->assertEquals('partial', $schedule->status);

        $schedule->updatePayment(2000.00);

        $this->assertEquals(5000.00, $schedule->paid_amount);
        $this->assertEquals('paid', $schedule->status);
    }

    public function test_invoice_item_status_model()
    {
        $invoice = $this->createInvoice();
        
        $itemStatus = \App\Models\InvoiceItemStatus::create([
            'invoice_id' => $invoice->id,
            'status' => 'packed',
            'status_date' => now()->toDateString(),
            'notes' => 'Item has been packed',
            'updated_by' => $this->user->id,
        ]);

        $this->assertEquals('Packed', $itemStatus->status_text);
        $this->assertNotNull($itemStatus->updated_by_name);
    }
}
