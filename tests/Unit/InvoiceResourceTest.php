<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\User;
use App\Models\PaymentTerm;
use App\Models\Tax;
use App\Models\Fee;
use App\Models\Discount;

class InvoiceResourceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
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

    public function test_it_can_transform_invoice_to_array()
    {
        $customer = $this->createCustomerUser(['first_name' => 'John', 'last_name' => 'Doe']);
        $invoice = $this->createInvoice([
            'customer_id' => $customer->id,
            'product_name' => 'Test Product',
            'price' => 15000.00,
            'status' => 'draft',
        ]);

        $resource = new InvoiceResource($invoice);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('invoice_number', $array);
        $this->assertArrayHasKey('customer_id', $array);
        $this->assertArrayHasKey('customer_name', $array);
        $this->assertArrayHasKey('product_name', $array);
        $this->assertArrayHasKey('price', $array);
        $this->assertArrayHasKey('formatted_price', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('status_text', $array);
        $this->assertArrayHasKey('total_amount', $array);
        $this->assertArrayHasKey('formatted_total_amount', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);

        $this->assertEquals($invoice->id, $array['id']);
        $this->assertEquals('Test Product', $array['product_name']);
        $this->assertEquals('15000.00', $array['price']);
        $this->assertEquals('₱15,000.00', $array['formatted_price']);
        $this->assertEquals('draft', $array['status']);
        $this->assertEquals('Draft', $array['status_text']);
        $this->assertEquals('John Doe', $array['customer_name']);
    }

    public function test_it_includes_customer_data_when_loaded()
    {
        $customer = $this->createCustomerUser(['first_name' => 'Jane', 'last_name' => 'Smith']);
        $invoice = $this->createInvoice(['customer_id' => $customer->id]);
        $invoice->load('customer');

        $resource = new InvoiceResource($invoice);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('customer', $array);
        $this->assertArrayHasKey('id', $array['customer']);
        $this->assertArrayHasKey('name', $array['customer']);
        $this->assertArrayHasKey('email', $array['customer']);
        $this->assertArrayHasKey('phone', $array['customer']);
        $this->assertArrayHasKey('address', $array['customer']);

        $this->assertEquals($customer->id, $array['customer']['id']);
        $this->assertEquals('Jane Smith', $array['customer']['name']);
        $this->assertEquals($customer->user_email, $array['customer']['email']);
    }

    public function test_it_includes_payment_term_data_when_loaded()
    {
        $paymentTerm = PaymentTerm::first();
        $invoice = $this->createInvoice(['payment_term_id' => $paymentTerm->id]);
        $invoice->load('paymentTerm');

        $resource = new InvoiceResource($invoice);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('payment_term', $array);
        $this->assertArrayHasKey('id', $array['payment_term']);
        $this->assertArrayHasKey('name', $array['payment_term']);
        $this->assertArrayHasKey('code', $array['payment_term']);
        $this->assertArrayHasKey('term_months', $array['payment_term']);

        $this->assertEquals($paymentTerm->id, $array['payment_term']['id']);
        $this->assertEquals($paymentTerm->name, $array['payment_term']['name']);
    }

    public function test_it_includes_tax_data_when_loaded()
    {
        $tax = Tax::first();
        $invoice = $this->createInvoice(['tax_id' => $tax->id]);
        $invoice->load('tax');

        $resource = new InvoiceResource($invoice);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('tax', $array);
        $this->assertArrayHasKey('id', $array['tax']);
        $this->assertArrayHasKey('name', $array['tax']);
        $this->assertArrayHasKey('code', $array['tax']);
        $this->assertArrayHasKey('rate', $array['tax']);
        $this->assertArrayHasKey('type', $array['tax']);

        $this->assertEquals($tax->id, $array['tax']['id']);
        $this->assertEquals($tax->name, $array['tax']['name']);
    }

    public function test_it_includes_fee_data_when_loaded()
    {
        $fee = Fee::first();
        $invoice = $this->createInvoice(['fee_id' => $fee->id]);
        $invoice->load('fee');

        $resource = new InvoiceResource($invoice);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('fee', $array);
        $this->assertArrayHasKey('id', $array['fee']);
        $this->assertArrayHasKey('name', $array['fee']);
        $this->assertArrayHasKey('code', $array['fee']);
        $this->assertArrayHasKey('amount', $array['fee']);
        $this->assertArrayHasKey('type', $array['fee']);

        $this->assertEquals($fee->id, $array['fee']['id']);
        $this->assertEquals($fee->name, $array['fee']['name']);
    }

    public function test_it_includes_discount_data_when_loaded()
    {
        $discount = Discount::first();
        $invoice = $this->createInvoice(['discount_id' => $discount->id]);
        $invoice->load('discount');

        $resource = new InvoiceResource($invoice);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('discount', $array);
        $this->assertArrayHasKey('id', $array['discount']);
        $this->assertArrayHasKey('name', $array['discount']);
        $this->assertArrayHasKey('code', $array['discount']);
        $this->assertArrayHasKey('amount', $array['discount']);
        $this->assertArrayHasKey('type', $array['discount']);

        $this->assertEquals($discount->id, $array['discount']['id']);
        $this->assertEquals($discount->name, $array['discount']['name']);
    }

    public function test_it_handles_null_relationships()
    {
        $invoice = $this->createInvoice([
            'payment_term_id' => null,
            'tax_id' => null,
            'fee_id' => null,
            'discount_id' => null,
        ]);

        $resource = new InvoiceResource($invoice);
        $array = $resource->toArray(request());

        $this->assertNull($array['payment_term_id']);
        $this->assertNull($array['tax_id']);
        $this->assertNull($array['fee_id']);
        $this->assertNull($array['discount_id']);
        $this->assertNull($array['payment_term_name']);
        $this->assertNull($array['tax_name']);
        $this->assertNull($array['fee_name']);
        $this->assertNull($array['discount_name']);
    }

    public function test_it_formats_dates_correctly()
    {
        $issueDate = now()->subDays(5);
        $dueDate = now()->addDays(30);
        
        $invoice = $this->createInvoice([
            'issue_date' => $issueDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
        ]);

        $resource = new InvoiceResource($invoice);
        $array = $resource->toArray(request());

        $this->assertEquals($issueDate->format('Y-m-d'), $array['issue_date']);
        $this->assertEquals($dueDate->format('Y-m-d'), $array['due_date']);
    }

    public function test_it_handles_soft_deleted_invoice()
    {
        $invoice = $this->createInvoice();
        $invoice->delete();

        $resource = new InvoiceResource($invoice);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('deleted_at', $array);
        $this->assertNotNull($array['deleted_at']);
    }
}
