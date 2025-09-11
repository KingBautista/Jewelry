<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\User;
use App\Models\PaymentTerm;
use App\Models\Tax;
use App\Models\Fee;
use App\Models\Discount;
use Illuminate\Support\Facades\Validator;

class InvoiceRequestTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function createCustomerUser(): User
    {
        $userData = [
            'user_login' => $this->faker->unique()->userName(),
            'user_email' => $this->faker->unique()->safeEmail(),
            'user_salt' => 'test_salt',
            'user_pass' => 'hashed_password',
            'user_status' => 1,
            'user_activation_key' => 'test_key',
            'user_role_id' => null,
        ];

        $user = User::create($userData);

        $customerMetaData = [
            'user_type' => 'customer',
            'customer_code' => 'CUST' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
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

    public function test_store_invoice_request_authorization()
    {
        $request = new StoreInvoiceRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_store_invoice_request_validation_rules()
    {
        $request = new StoreInvoiceRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('customer_id', $rules);
        $this->assertArrayHasKey('product_name', $rules);
        $this->assertArrayHasKey('price', $rules);
        $this->assertArrayHasKey('invoice_number', $rules);
        $this->assertArrayHasKey('payment_term_id', $rules);
        $this->assertArrayHasKey('tax_id', $rules);
        $this->assertArrayHasKey('fee_id', $rules);
        $this->assertArrayHasKey('discount_id', $rules);
        $this->assertArrayHasKey('issue_date', $rules);
        $this->assertArrayHasKey('due_date', $rules);
        $this->assertArrayHasKey('status', $rules);

        $this->assertContains('required', $rules['customer_id']);
        $this->assertContains('required', $rules['product_name']);
        $this->assertContains('required', $rules['price']);
        $this->assertContains('exists:users,id', $rules['customer_id']);
        $this->assertContains('exists:payment_terms,id', $rules['payment_term_id']);
        $this->assertContains('exists:taxes,id', $rules['tax_id']);
        $this->assertContains('exists:fees,id', $rules['fee_id']);
        $this->assertContains('exists:discounts,id', $rules['discount_id']);
    }

    public function test_store_invoice_request_validation_passes_with_valid_data()
    {
        $customer = $this->createCustomerUser();
        $paymentTerm = PaymentTerm::first();
        $tax = Tax::first();

        $data = [
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

        $request = new StoreInvoiceRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    public function test_store_invoice_request_validation_fails_without_required_fields()
    {
        $data = [];

        $request = new StoreInvoiceRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('customer_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('product_name', $validator->errors()->toArray());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
    }

    public function test_store_invoice_request_validation_fails_with_invalid_customer()
    {
        $data = [
            'customer_id' => 99999, // Non-existent customer
            'product_name' => 'Test Product',
            'price' => 10000.00,
        ];

        $request = new StoreInvoiceRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('customer_id', $validator->errors()->toArray());
    }

    public function test_store_invoice_request_validation_fails_with_negative_price()
    {
        $customer = $this->createCustomerUser();

        $data = [
            'customer_id' => $customer->id,
            'product_name' => 'Test Product',
            'price' => -1000.00, // Negative price
        ];

        $request = new StoreInvoiceRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
    }

    public function test_store_invoice_request_validation_fails_with_invalid_status()
    {
        $customer = $this->createCustomerUser();

        $data = [
            'customer_id' => $customer->id,
            'product_name' => 'Test Product',
            'price' => 10000.00,
            'status' => 'invalid_status',
        ];

        $request = new StoreInvoiceRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    public function test_store_invoice_request_validation_fails_with_invalid_date_range()
    {
        $customer = $this->createCustomerUser();

        $data = [
            'customer_id' => $customer->id,
            'product_name' => 'Test Product',
            'price' => 10000.00,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->subDays(1)->toDateString(), // Due date before issue date
        ];

        $request = new StoreInvoiceRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('due_date', $validator->errors()->toArray());
    }

    public function test_update_invoice_request_authorization()
    {
        $request = new UpdateInvoiceRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_update_invoice_request_validation_rules()
    {
        $request = new UpdateInvoiceRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('customer_id', $rules);
        $this->assertArrayHasKey('product_name', $rules);
        $this->assertArrayHasKey('price', $rules);
        $this->assertArrayHasKey('invoice_number', $rules);
        $this->assertArrayHasKey('payment_term_id', $rules);
        $this->assertArrayHasKey('tax_id', $rules);
        $this->assertArrayHasKey('fee_id', $rules);
        $this->assertArrayHasKey('discount_id', $rules);
        $this->assertArrayHasKey('issue_date', $rules);
        $this->assertArrayHasKey('due_date', $rules);
        $this->assertArrayHasKey('status', $rules);

        $this->assertContains('required', $rules['customer_id']);
        $this->assertContains('required', $rules['product_name']);
        $this->assertContains('required', $rules['price']);
    }

    public function test_update_invoice_request_validation_passes_with_valid_data()
    {
        $customer = $this->createCustomerUser();
        $paymentTerm = PaymentTerm::first();
        $tax = Tax::first();

        $data = [
            'customer_id' => $customer->id,
            'product_name' => 'Updated Product',
            'description' => 'Updated Description',
            'price' => 15000.00,
            'payment_term_id' => $paymentTerm->id,
            'tax_id' => $tax->id,
            'shipping_address' => 'Updated Address',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'notes' => 'Updated Notes',
        ];

        $request = new UpdateInvoiceRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes());
    }

    public function test_update_invoice_request_validation_fails_without_required_fields()
    {
        $data = [];

        $request = new UpdateInvoiceRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('customer_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('product_name', $validator->errors()->toArray());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
    }

    public function test_store_invoice_request_custom_messages()
    {
        $request = new StoreInvoiceRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('customer_id.required', $messages);
        $this->assertArrayHasKey('customer_id.exists', $messages);
        $this->assertArrayHasKey('product_name.required', $messages);
        $this->assertArrayHasKey('price.required', $messages);
        $this->assertArrayHasKey('price.numeric', $messages);
        $this->assertArrayHasKey('price.min', $messages);
        $this->assertArrayHasKey('status.in', $messages);
        $this->assertArrayHasKey('due_date.after_or_equal', $messages);

        $this->assertEquals('Customer selection is required.', $messages['customer_id.required']);
        $this->assertEquals('Selected customer does not exist.', $messages['customer_id.exists']);
        $this->assertEquals('Product name is required.', $messages['product_name.required']);
        $this->assertEquals('Price is required.', $messages['price.required']);
        $this->assertEquals('Price must be a valid number.', $messages['price.numeric']);
        $this->assertEquals('Price must be at least 0.', $messages['price.min']);
        $this->assertEquals('Status must be one of: draft, sent, paid, overdue, cancelled.', $messages['status.in']);
        $this->assertEquals('Due date must be after or equal to issue date.', $messages['due_date.after_or_equal']);
    }

    public function test_update_invoice_request_custom_messages()
    {
        $request = new UpdateInvoiceRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('customer_id.required', $messages);
        $this->assertArrayHasKey('customer_id.exists', $messages);
        $this->assertArrayHasKey('product_name.required', $messages);
        $this->assertArrayHasKey('price.required', $messages);
        $this->assertArrayHasKey('price.numeric', $messages);
        $this->assertArrayHasKey('price.min', $messages);
        $this->assertArrayHasKey('status.in', $messages);
        $this->assertArrayHasKey('due_date.after_or_equal', $messages);

        $this->assertEquals('Customer selection is required.', $messages['customer_id.required']);
        $this->assertEquals('Selected customer does not exist.', $messages['customer_id.exists']);
        $this->assertEquals('Product name is required.', $messages['product_name.required']);
        $this->assertEquals('Price is required.', $messages['price.required']);
        $this->assertEquals('Price must be a valid number.', $messages['price.numeric']);
        $this->assertEquals('Price must be at least 0.', $messages['price.min']);
        $this->assertEquals('Status must be one of: draft, sent, paid, overdue, cancelled.', $messages['status.in']);
        $this->assertEquals('Due date must be after or equal to issue date.', $messages['due_date.after_or_equal']);
    }
}
