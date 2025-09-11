<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Services\InvoiceService;
use App\Models\Invoice;
use App\Models\User;
use App\Models\PaymentTerm;
use App\Models\Tax;
use App\Models\Fee;
use App\Models\Discount;

class InvoiceServiceTest extends TestCase
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
            'user_login' => bin2hex(random_bytes(16)) . '@test.com',
            'user_email' => bin2hex(random_bytes(16)) . '@test.com',
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

        $service = new InvoiceService();
        $result = $service->list(10);

        $this->assertCount(2, $result->resource);
        $this->assertArrayHasKey('meta', $result->additional);
    }

    public function test_it_can_store_an_invoice()
    {
        $customer = $this->createCustomerUser();
        $paymentTerm = PaymentTerm::first();

        $invoiceData = [
            'invoice_number' => 'INV000001',
            'customer_id' => $customer->id,
            'product_name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 10000.00,
            'payment_term_id' => $paymentTerm->id,
            'shipping_address' => 'Test Address',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'notes' => 'Test Notes',
            'active' => true,
        ];

        $service = new InvoiceService();
        $result = $service->store($invoiceData);

        $this->assertInstanceOf(\App\Http\Resources\InvoiceResource::class, $result);
        $this->assertDatabaseHas('invoices', [
            'product_name' => 'Test Product',
            'customer_id' => $customer->id,
        ]);
    }

    public function test_it_can_show_an_invoice()
    {
        $invoice = $this->createInvoice();

        $service = new InvoiceService();
        $result = $service->show($invoice->id);

        $this->assertInstanceOf(Invoice::class, $result);
        $this->assertEquals($invoice->id, $result->id);
    }

    public function test_it_can_update_an_invoice()
    {
        $invoice = $this->createInvoice();

        $updateData = [
            'product_name' => 'Updated Product',
            'price' => 15000.00,
            'description' => 'Updated Description',
        ];

        $service = new InvoiceService();
        $result = $service->update($updateData, $invoice->id);

        $this->assertInstanceOf(\App\Http\Resources\InvoiceResource::class, $result);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'product_name' => 'Updated Product',
        ]);
    }

    public function test_it_can_destroy_an_invoice()
    {
        $invoice = $this->createInvoice();

        $service = new InvoiceService();
        $result = $service->destroy($invoice->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    public function test_it_can_restore_an_invoice()
    {
        $invoice = $this->createInvoice();
        $invoice->delete(); // Soft delete

        $service = new InvoiceService();
        $result = $service->restore($invoice->id);

        $this->assertInstanceOf(\App\Http\Resources\InvoiceResource::class, $result);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'deleted_at' => null,
        ]);
    }

    public function test_it_can_force_delete_an_invoice()
    {
        $invoice = $this->createInvoice();
        $invoice->delete(); // Soft delete first

        $service = new InvoiceService();
        $result = $service->forceDelete($invoice->id);

        $this->assertInstanceOf(Invoice::class, $result);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    public function test_it_can_bulk_delete_invoices()
    {
        $invoice1 = $this->createInvoice();
        $invoice2 = $this->createInvoice();

        $service = new InvoiceService();
        $result = $service->bulkDelete([$invoice1->id, $invoice2->id]);

        $this->assertTrue($result);
        $this->assertSoftDeleted('invoices', ['id' => $invoice1->id]);
        $this->assertSoftDeleted('invoices', ['id' => $invoice2->id]);
    }

    public function test_it_can_bulk_restore_invoices()
    {
        $invoice1 = $this->createInvoice();
        $invoice2 = $this->createInvoice();
        $invoice1->delete();
        $invoice2->delete();

        $service = new InvoiceService();
        $result = $service->bulkRestore([$invoice1->id, $invoice2->id]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice1->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice2->id,
            'deleted_at' => null,
        ]);
    }

    public function test_it_can_bulk_force_delete_invoices()
    {
        $invoice1 = $this->createInvoice();
        $invoice2 = $this->createInvoice();
        $invoice1->delete();
        $invoice2->delete();

        $service = new InvoiceService();
        $result = $service->bulkForceDelete([$invoice1->id, $invoice2->id]);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice1->id]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice2->id]);
    }

    public function test_it_can_get_invoice_statistics()
    {
        $this->createInvoice(['status' => 'draft']);
        $this->createInvoice(['status' => 'sent']);
        $this->createInvoice(['status' => 'paid']);
        $this->createInvoice(['status' => 'overdue']);

        $service = new InvoiceService();
        $stats = $service->getInvoiceStats();

        $this->assertArrayHasKey('total_invoices', $stats);
        $this->assertArrayHasKey('draft_invoices', $stats);
        $this->assertArrayHasKey('sent_invoices', $stats);
        $this->assertArrayHasKey('paid_invoices', $stats);
        $this->assertArrayHasKey('overdue_invoices', $stats);
        $this->assertArrayHasKey('cancelled_invoices', $stats);
        $this->assertArrayHasKey('total_amount', $stats);
        $this->assertArrayHasKey('paid_amount', $stats);
        $this->assertArrayHasKey('pending_amount', $stats);
        $this->assertArrayHasKey('overdue_amount', $stats);
        $this->assertArrayHasKey('invoices_this_month', $stats);
        $this->assertArrayHasKey('invoices_by_status', $stats);
        $this->assertArrayHasKey('invoices_by_customer', $stats);

        $this->assertEquals(4, $stats['total_invoices']);
        $this->assertEquals(1, $stats['draft_invoices']);
        $this->assertEquals(1, $stats['sent_invoices']);
        $this->assertEquals(1, $stats['paid_invoices']);
        $this->assertEquals(1, $stats['overdue_invoices']);
    }

    public function test_it_can_get_invoices_for_dropdown()
    {
        $this->createInvoice(['product_name' => 'Product A']);
        $this->createInvoice(['product_name' => 'Product B']);

        $service = new InvoiceService();
        $dropdown = $service->getInvoicesForDropdown();

        $this->assertCount(2, $dropdown);
        $this->assertArrayHasKey('id', $dropdown[0]);
        $this->assertArrayHasKey('invoice_number', $dropdown[0]);
        $this->assertArrayHasKey('product_name', $dropdown[0]);
        $this->assertArrayHasKey('total_amount', $dropdown[0]);
    }

    public function test_it_can_export_invoices()
    {
        $this->createInvoice();
        $this->createInvoice();

        $service = new InvoiceService();
        $result = $service->exportInvoices('csv');

        $this->assertInstanceOf(\Illuminate\Http\Response::class, $result);
        $this->assertEquals('text/csv; charset=UTF-8', $result->headers->get('Content-Type'));
    }

    public function test_it_can_export_invoices_with_unsupported_format()
    {
        $service = new InvoiceService();
        $result = $service->exportInvoices('pdf');

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
    }
}
