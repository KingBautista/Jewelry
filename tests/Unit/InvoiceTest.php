<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Invoice;
use App\Models\User;
use App\Models\PaymentTerm;
use App\Models\Tax;
use App\Models\Fee;
use App\Models\Discount;

class InvoiceTest extends TestCase
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

    public function test_it_can_generate_invoice_number()
    {
        $code1 = Invoice::generateInvoiceNumber();
        $code2 = Invoice::generateInvoiceNumber();
        
        // Since we have seeded data, the next invoice numbers should be sequential
        $this->assertStringStartsWith('INV', $code1);
        $this->assertStringStartsWith('INV', $code2);
        $this->assertEquals($code1, $code2); // Both should be the same since they're generated at the same time
    }

    public function test_it_can_get_formatted_price_attribute()
    {
        $invoice = $this->createInvoice(['price' => 15000.50]);
        
        $this->assertEquals('₱15,000.50', $invoice->formatted_price);
    }

    public function test_it_can_get_formatted_total_amount_attribute()
    {
        $invoice = $this->createInvoice(['total_amount' => 25000.75]);
        
        $this->assertEquals('₱25,000.75', $invoice->formatted_total_amount);
    }

    public function test_it_can_get_status_text_attribute()
    {
        $statuses = [
            'draft' => 'Draft',
            'sent' => 'Sent',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
        ];

        foreach ($statuses as $status => $expectedText) {
            $invoice = $this->createInvoice(['status' => $status]);
            $this->assertEquals($expectedText, $invoice->status_text);
        }
    }

    public function test_it_can_get_customer_name_attribute()
    {
        $customer = $this->createCustomerUser(['first_name' => 'John', 'last_name' => 'Doe']);
        $invoice = $this->createInvoice(['customer_id' => $customer->id]);
        
        $this->assertEquals('John Doe', $invoice->customer_name);
    }

    public function test_it_can_get_payment_term_name_attribute()
    {
        $paymentTerm = PaymentTerm::first();
        $invoice = $this->createInvoice(['payment_term_id' => $paymentTerm->id]);
        
        $this->assertEquals($paymentTerm->name, $invoice->payment_term_name);
    }

    public function test_it_can_get_tax_name_attribute()
    {
        $tax = Tax::first();
        $invoice = $this->createInvoice(['tax_id' => $tax->id]);
        
        $this->assertEquals($tax->name, $invoice->tax_name);
    }

    public function test_it_can_get_fee_name_attribute()
    {
        $fee = Fee::first();
        $invoice = $this->createInvoice(['fee_id' => $fee->id]);
        
        $this->assertEquals($fee->name, $invoice->fee_name);
    }

    public function test_it_can_get_discount_name_attribute()
    {
        $discount = Discount::first();
        $invoice = $this->createInvoice(['discount_id' => $discount->id]);
        
        $this->assertEquals($discount->name, $invoice->discount_name);
    }

    public function test_it_can_scope_active_invoices()
    {
        $this->createInvoice(['active' => true]);
        $this->createInvoice(['active' => false]);
        
        $activeInvoices = Invoice::active()->get();
        
        $this->assertCount(1, $activeInvoices);
        $this->assertTrue($activeInvoices->first()->active);
    }

    public function test_it_can_scope_inactive_invoices()
    {
        $this->createInvoice(['active' => true]);
        $this->createInvoice(['active' => false]);
        
        $inactiveInvoices = Invoice::inactive()->get();
        
        $this->assertCount(1, $inactiveInvoices);
        $this->assertFalse($inactiveInvoices->first()->active);
    }

    public function test_it_can_scope_search_invoices()
    {
        $invoice1 = $this->createInvoice(['product_name' => 'Diamond Ring']);
        $invoice2 = $this->createInvoice(['product_name' => 'Gold Necklace']);
        
        $searchResults = Invoice::search('Diamond')->get();
        
        $this->assertCount(1, $searchResults);
        $this->assertEquals($invoice1->id, $searchResults->first()->id);
    }

    public function test_it_can_scope_by_status()
    {
        $this->createInvoice(['status' => 'draft']);
        $this->createInvoice(['status' => 'sent']);
        
        $draftInvoices = Invoice::byStatus('draft')->get();
        
        $this->assertCount(1, $draftInvoices);
        $this->assertEquals('draft', $draftInvoices->first()->status);
    }

    public function test_it_can_scope_by_customer()
    {
        $customer1 = $this->createCustomerUser();
        $customer2 = $this->createCustomerUser();
        
        $this->createInvoice(['customer_id' => $customer1->id]);
        $this->createInvoice(['customer_id' => $customer2->id]);
        
        $customerInvoices = Invoice::byCustomer($customer1->id)->get();
        
        $this->assertCount(1, $customerInvoices);
        $this->assertEquals($customer1->id, $customerInvoices->first()->customer_id);
    }

    public function test_it_can_scope_by_date_range()
    {
        $this->createInvoice(['issue_date' => '2024-01-01']);
        $this->createInvoice(['issue_date' => '2024-01-15']);
        $this->createInvoice(['issue_date' => '2024-02-01']);
        
        $dateRangeInvoices = Invoice::byDateRange('2024-01-01', '2024-01-31')->get();
        
        $this->assertCount(2, $dateRangeInvoices);
    }

    public function test_it_can_calculate_totals_with_percentage_tax()
    {
        $tax = Tax::create([
            'name' => 'VAT',
            'code' => 'VAT001',
            'rate' => 12.0,
            'type' => 'percentage',
            'description' => 'Value Added Tax',
            'active' => true,
        ]);

        $invoice = $this->createInvoice([
            'price' => 10000.00,
            'tax_id' => $tax->id,
        ]);

        $invoice->calculateTotals();

        $this->assertEquals(10000.00, $invoice->subtotal);
        $this->assertEquals(1200.00, $invoice->tax_amount); // 12% of 10000
        $this->assertEquals(11200.00, $invoice->total_amount);
    }

    public function test_it_can_calculate_totals_with_fixed_tax()
    {
        $tax = Tax::create([
            'name' => 'Fixed Tax',
            'code' => 'FIX001',
            'rate' => 500.00,
            'type' => 'fixed',
            'description' => 'Fixed Tax Amount',
            'active' => true,
        ]);

        $invoice = $this->createInvoice([
            'price' => 10000.00,
            'tax_id' => $tax->id,
        ]);

        $invoice->calculateTotals();

        $this->assertEquals(10000.00, $invoice->subtotal);
        $this->assertEquals(500.00, $invoice->tax_amount);
        $this->assertEquals(10500.00, $invoice->total_amount);
    }

    public function test_it_can_calculate_totals_with_percentage_discount()
    {
        $discount = Discount::create([
            'name' => 'Early Bird',
            'code' => 'EARLY001',
            'amount' => 10.0,
            'type' => 'percentage',
            'description' => 'Early Bird Discount',
            'valid_from' => now()->subDays(30),
            'valid_to' => now()->addDays(30),
            'active' => true,
        ]);

        $invoice = $this->createInvoice([
            'price' => 10000.00,
            'discount_id' => $discount->id,
        ]);

        $invoice->calculateTotals();

        $this->assertEquals(10000.00, $invoice->subtotal);
        $this->assertEquals(1000.00, $invoice->discount_amount); // 10% of 10000
        $this->assertEquals(9000.00, $invoice->total_amount);
    }

    public function test_it_can_calculate_totals_with_fixed_discount()
    {
        $discount = Discount::create([
            'name' => 'Fixed Discount',
            'code' => 'FIXDISC001',
            'amount' => 1000.00,
            'type' => 'fixed',
            'description' => 'Fixed Discount Amount',
            'valid_from' => now()->subDays(30),
            'valid_to' => now()->addDays(30),
            'active' => true,
        ]);

        $invoice = $this->createInvoice([
            'price' => 10000.00,
            'discount_id' => $discount->id,
        ]);

        $invoice->calculateTotals();

        $this->assertEquals(10000.00, $invoice->subtotal);
        $this->assertEquals(1000.00, $invoice->discount_amount);
        $this->assertEquals(9000.00, $invoice->total_amount);
    }

    public function test_it_can_calculate_complex_totals()
    {
        $tax = Tax::create([
            'name' => 'VAT',
            'code' => 'VAT001',
            'rate' => 12.0,
            'type' => 'percentage',
            'description' => 'Value Added Tax',
            'active' => true,
        ]);

        $fee = Fee::create([
            'name' => 'Processing Fee',
            'code' => 'PROC001',
            'amount' => 5.0,
            'type' => 'percentage',
            'description' => 'Processing Fee',
            'active' => true,
        ]);

        $discount = Discount::create([
            'name' => 'Early Bird',
            'code' => 'EARLY001',
            'amount' => 1000.00,
            'type' => 'fixed',
            'description' => 'Early Bird Discount',
            'valid_from' => now()->subDays(30),
            'valid_to' => now()->addDays(30),
            'active' => true,
        ]);

        $invoice = $this->createInvoice([
            'price' => 10000.00,
            'tax_id' => $tax->id,
            'fee_id' => $fee->id,
            'discount_id' => $discount->id,
        ]);

        $invoice->calculateTotals();

        $this->assertEquals(10000.00, $invoice->subtotal);
        $this->assertEquals(1200.00, $invoice->tax_amount); // 12% of 10000
        $this->assertEquals(500.00, $invoice->fee_amount); // 5% of 10000
        $this->assertEquals(1000.00, $invoice->discount_amount); // Fixed 1000
        $this->assertEquals(10700.00, $invoice->total_amount); // 10000 + 1200 + 500 - 1000
    }

    public function test_it_belongs_to_customer()
    {
        $customer = $this->createCustomerUser();
        $invoice = $this->createInvoice(['customer_id' => $customer->id]);
        
        $this->assertInstanceOf(User::class, $invoice->customer);
        $this->assertEquals($customer->id, $invoice->customer->id);
    }

    public function test_it_belongs_to_payment_term()
    {
        $paymentTerm = PaymentTerm::first();
        $invoice = $this->createInvoice(['payment_term_id' => $paymentTerm->id]);
        
        $this->assertInstanceOf(PaymentTerm::class, $invoice->paymentTerm);
        $this->assertEquals($paymentTerm->id, $invoice->paymentTerm->id);
    }

    public function test_it_belongs_to_tax()
    {
        $tax = Tax::first();
        $invoice = $this->createInvoice(['tax_id' => $tax->id]);
        
        $this->assertInstanceOf(Tax::class, $invoice->tax);
        $this->assertEquals($tax->id, $invoice->tax->id);
    }

    public function test_it_belongs_to_fee()
    {
        $fee = Fee::first();
        $invoice = $this->createInvoice(['fee_id' => $fee->id]);
        
        $this->assertInstanceOf(Fee::class, $invoice->fee);
        $this->assertEquals($fee->id, $invoice->fee->id);
    }

    public function test_it_belongs_to_discount()
    {
        $discount = Discount::first();
        $invoice = $this->createInvoice(['discount_id' => $discount->id]);
        
        $this->assertInstanceOf(Discount::class, $invoice->discount);
        $this->assertEquals($discount->id, $invoice->discount->id);
    }
}
