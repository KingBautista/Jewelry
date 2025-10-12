<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Requests\ProfileRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'user_email' => 'test@example.com',
            'user_pass' => Hash::make('password123')
        ]);
        
        // Authenticate the user
        Auth::login($this->user);
    }

    public function test_profile_request_validation_rules()
    {
        $request = new ProfileRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('user_email', $rules);
        $this->assertArrayHasKey('user_pass', $rules);
        $this->assertArrayHasKey('first_name', $rules);
        $this->assertArrayHasKey('last_name', $rules);
        $this->assertArrayHasKey('nickname', $rules);
        $this->assertArrayHasKey('biography', $rules);
    }

    public function test_email_is_required()
    {
        $data = [
            'user_email' => '',
            'user_login' => 'testuser'
        ];

        $validator = Validator::make($data, (new ProfileRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('user_email', $validator->errors()->toArray());
    }

    public function test_email_must_be_valid()
    {
        $data = [
            'user_email' => 'invalid-email',
            'user_login' => 'testuser'
        ];

        $validator = Validator::make($data, (new ProfileRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('user_email', $validator->errors()->toArray());
    }

    public function test_email_uniqueness_excludes_current_user()
    {
        $data = [
            'user_email' => 'test@example.com', // Same as current user
            'user_login' => 'testuser'
        ];

        $validator = Validator::make($data, (new ProfileRequest())->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_email_uniqueness_prevents_duplicate()
    {
        // Create another user with different email
        User::factory()->create(['user_email' => 'other@example.com']);

        $data = [
            'user_email' => 'other@example.com', // Different user's email
            'user_login' => 'testuser'
        ];

        $validator = Validator::make($data, (new ProfileRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('user_email', $validator->errors()->toArray());
    }

    public function test_password_is_optional()
    {
        $data = [
            'user_email' => 'test@example.com',
            'user_login' => 'testuser'
            // No password provided
        ];

        $validator = Validator::make($data, (new ProfileRequest())->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_password_validation_when_provided()
    {
        $data = [
            'user_email' => 'test@example.com',
            'user_login' => 'testuser',
            'user_pass' => 'weak' // Too short
        ];

        $validator = Validator::make($data, (new ProfileRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('user_pass', $validator->errors()->toArray());
    }

    public function test_password_must_be_at_least_8_characters()
    {
        $data = [
            'user_email' => 'test@example.com',
            'user_login' => 'testuser',
            'user_pass' => 'short'
        ];

        $validator = Validator::make($data, (new ProfileRequest())->rules());
        $this->assertTrue($validator->fails());
    }

    public function test_password_must_contain_uppercase_lowercase_number_and_special_char()
    {
        $data = [
            'user_email' => 'test@example.com',
            'user_login' => 'testuser',
            'user_pass' => 'Password123!'
        ];

        $validator = Validator::make($data, (new ProfileRequest())->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_optional_fields_are_nullable()
    {
        $data = [
            'user_email' => 'test@example.com',
            'user_login' => 'testuser',
            'first_name' => null,
            'last_name' => null,
            'nickname' => null,
            'biography' => null
        ];

        $validator = Validator::make($data, (new ProfileRequest())->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_optional_fields_accept_valid_values()
    {
        $data = [
            'user_email' => 'test@example.com',
            'user_login' => 'testuser',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'nickname' => 'Johnny',
            'biography' => 'This is a test biography'
        ];

        $validator = Validator::make($data, (new ProfileRequest())->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_optional_fields_reject_invalid_characters()
    {
        $data = [
            'user_email' => 'test@example.com',
            'user_login' => 'testuser',
            'first_name' => 'John<script>alert("xss")</script>',
            'last_name' => 'Doe<script>alert("xss")</script>',
            'nickname' => 'Johnny<script>alert("xss")</script>',
            'biography' => 'Biography<script>alert("xss")</script>'
        ];

        $validator = Validator::make($data, (new ProfileRequest())->rules());
        $this->assertTrue($validator->fails());
    }

    public function test_validation_messages_are_defined()
    {
        $request = new ProfileRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('user_email.required', $messages);
        $this->assertArrayHasKey('user_email.email', $messages);
        $this->assertArrayHasKey('user_email.unique', $messages);
        $this->assertArrayHasKey('user_pass.min', $messages);
        $this->assertArrayHasKey('user_pass.regex', $messages);
    }
}
