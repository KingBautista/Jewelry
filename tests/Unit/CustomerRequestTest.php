<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\User;
use App\Helpers\PasswordHelper;

class CustomerRequestTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function store_customer_request_validates_required_fields()
    {
        $request = new StoreCustomerRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('first_name', $rules);
        $this->assertArrayHasKey('last_name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('user_pass', $rules);

        $this->assertStringContainsString('required', $rules['first_name']);
        $this->assertStringContainsString('required', $rules['last_name']);
        $this->assertStringContainsString('required', $rules['email']);
        $this->assertStringContainsString('required', $rules['user_pass']);
    }

    /** @test */
    public function store_customer_request_validates_email_format()
    {
        $request = new StoreCustomerRequest();
        $rules = $request->rules();

        $this->assertStringContainsString('email', $rules['email']);
    }

    /** @test */
    public function store_customer_request_validates_email_uniqueness()
    {
        $request = new StoreCustomerRequest();
        $rules = $request->rules();

        $this->assertStringContainsString('unique:users,user_email', $rules['email']);
    }

    /** @test */
    public function store_customer_request_validates_password_minimum_length()
    {
        $request = new StoreCustomerRequest();
        $rules = $request->rules();

        $this->assertStringContainsString('min:6', $rules['user_pass']);
    }

    /** @test */
    public function store_customer_request_validates_optional_fields()
    {
        $request = new StoreCustomerRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('phone', $rules);
        $this->assertArrayHasKey('address', $rules);
        $this->assertArrayHasKey('city', $rules);
        $this->assertArrayHasKey('state', $rules);
        $this->assertArrayHasKey('postal_code', $rules);
        $this->assertArrayHasKey('country', $rules);
        $this->assertArrayHasKey('date_of_birth', $rules);
        $this->assertArrayHasKey('gender', $rules);
        $this->assertArrayHasKey('notes', $rules);
        $this->assertArrayHasKey('active', $rules);

        $this->assertStringContainsString('nullable', $rules['phone']);
        $this->assertStringContainsString('nullable', $rules['address']);
        $this->assertStringContainsString('nullable', $rules['city']);
        $this->assertStringContainsString('nullable', $rules['state']);
        $this->assertStringContainsString('nullable', $rules['postal_code']);
        $this->assertStringContainsString('nullable', $rules['country']);
        $this->assertStringContainsString('nullable', $rules['date_of_birth']);
        $this->assertStringContainsString('nullable', $rules['gender']);
        $this->assertStringContainsString('nullable', $rules['notes']);
    }

    /** @test */
    public function store_customer_request_validates_date_of_birth()
    {
        $request = new StoreCustomerRequest();
        $rules = $request->rules();

        $this->assertStringContainsString('date', $rules['date_of_birth']);
        $this->assertStringContainsString('before:today', $rules['date_of_birth']);
    }

    /** @test */
    public function store_customer_request_validates_gender_values()
    {
        $request = new StoreCustomerRequest();
        $rules = $request->rules();

        $this->assertStringContainsString('in:male,female,other', $rules['gender']);
    }

    /** @test */
    public function store_customer_request_validates_field_lengths()
    {
        $request = new StoreCustomerRequest();
        $rules = $request->rules();

        $this->assertStringContainsString('max:255', $rules['first_name']);
        $this->assertStringContainsString('max:255', $rules['last_name']);
        $this->assertStringContainsString('max:20', $rules['phone']);
        $this->assertStringContainsString('max:100', $rules['city']);
        $this->assertStringContainsString('max:100', $rules['state']);
        $this->assertStringContainsString('max:20', $rules['postal_code']);
        $this->assertStringContainsString('max:100', $rules['country']);
    }

    /** @test */
    public function store_customer_request_validates_boolean_fields()
    {
        $request = new StoreCustomerRequest();
        $rules = $request->rules();

        $this->assertStringContainsString('boolean', $rules['active']);
    }

    /** @test */
    public function store_customer_request_has_custom_messages()
    {
        $request = new StoreCustomerRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('first_name.required', $messages);
        $this->assertArrayHasKey('last_name.required', $messages);
        $this->assertArrayHasKey('email.required', $messages);
        $this->assertArrayHasKey('email.email', $messages);
        $this->assertArrayHasKey('email.unique', $messages);
        $this->assertArrayHasKey('user_pass.required', $messages);
        $this->assertArrayHasKey('user_pass.min', $messages);
        $this->assertArrayHasKey('date_of_birth.date', $messages);
        $this->assertArrayHasKey('date_of_birth.before', $messages);
        $this->assertArrayHasKey('gender.in', $messages);
        $this->assertArrayHasKey('active.boolean', $messages);
    }

    /** @test */
    public function update_customer_request_validates_required_fields()
    {
        $request = new UpdateCustomerRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('first_name', $rules);
        $this->assertArrayHasKey('last_name', $rules);
        $this->assertArrayHasKey('email', $rules);

        $this->assertStringContainsString('required', $rules['first_name']);
        $this->assertStringContainsString('required', $rules['last_name']);
        $this->assertStringContainsString('required', $rules['email']);
    }

    /** @test */
    public function update_customer_request_makes_password_optional()
    {
        $request = new UpdateCustomerRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('user_pass', $rules);
        $this->assertStringContainsString('nullable', $rules['user_pass']);
    }

    /** @test */
    public function update_customer_request_validates_email_uniqueness_with_ignore()
    {
        $request = new UpdateCustomerRequest();
        $rules = $request->rules();

        $this->assertIsArray($rules['email']);
        $this->assertContains('required', $rules['email']);
        $this->assertContains('email', $rules['email']);
        
        // Check for unique rule with ignore
        $uniqueRule = collect($rules['email'])->first(function ($rule) {
            return is_object($rule) && method_exists($rule, 'getTable');
        });
        
        $this->assertNotNull($uniqueRule);
    }

    /** @test */
    public function update_customer_request_has_same_optional_field_validation()
    {
        $storeRequest = new StoreCustomerRequest();
        $updateRequest = new UpdateCustomerRequest();
        
        $storeRules = $storeRequest->rules();
        $updateRules = $updateRequest->rules();

        // Optional fields should have same validation
        $optionalFields = ['phone', 'address', 'city', 'state', 'postal_code', 'country', 'date_of_birth', 'gender', 'notes', 'active'];
        
        foreach ($optionalFields as $field) {
            $this->assertEquals($storeRules[$field], $updateRules[$field], "Field {$field} should have same validation rules");
        }
    }

    /** @test */
    public function update_customer_request_has_custom_messages()
    {
        $request = new UpdateCustomerRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('first_name.required', $messages);
        $this->assertArrayHasKey('last_name.required', $messages);
        $this->assertArrayHasKey('email.required', $messages);
        $this->assertArrayHasKey('email.email', $messages);
        $this->assertArrayHasKey('email.unique', $messages);
        $this->assertArrayHasKey('user_pass.string', $messages);
        $this->assertArrayHasKey('user_pass.min', $messages);
    }

    /** @test */
    public function both_requests_authorize_access()
    {
        $storeRequest = new StoreCustomerRequest();
        $updateRequest = new UpdateCustomerRequest();

        $this->assertTrue($storeRequest->authorize());
        $this->assertTrue($updateRequest->authorize());
    }

    /** @test */
    public function store_customer_request_validates_string_fields()
    {
        $request = new StoreCustomerRequest();
        $rules = $request->rules();

        $stringFields = ['first_name', 'last_name', 'phone', 'address', 'city', 'state', 'postal_code', 'country', 'notes'];
        
        foreach ($stringFields as $field) {
            $this->assertStringContainsString('string', $rules[$field]);
        }
    }

    /** @test */
    public function update_customer_request_validates_string_fields()
    {
        $request = new UpdateCustomerRequest();
        $rules = $request->rules();

        $stringFields = ['first_name', 'last_name', 'phone', 'address', 'city', 'state', 'postal_code', 'country', 'notes'];
        
        foreach ($stringFields as $field) {
            $this->assertStringContainsString('string', $rules[$field]);
        }
    }
}
