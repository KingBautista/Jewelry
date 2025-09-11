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
            'product_image' => $overrides['product_image'] ?? null,
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
}
