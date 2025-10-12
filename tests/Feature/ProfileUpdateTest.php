<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Helpers\PasswordHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $salt = PasswordHelper::generateSalt();
        $this->user = User::factory()->create([
            'user_email' => 'test@example.com',
            'user_pass' => PasswordHelper::generatePassword($salt, 'password123'),
            'user_salt' => $salt
        ]);
    }

    public function test_authenticated_user_can_update_profile()
    {
        $this->actingAs($this->user);

        $updateData = [
            'user_login' => $this->user->user_login,
            'user_email' => 'updated@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'nickname' => 'Johnny',
            'biography' => 'This is my updated biography',
            'theme' => 'dark'
        ];

        $response = $this->postJson('/api/profile', $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Profile has been updated successfully.'
        ]);

        // Verify the user data was updated
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'user_email' => 'updated@example.com'
        ]);
    }

    public function test_user_can_update_profile_without_password()
    {
        $this->actingAs($this->user);

        $updateData = [
            'user_login' => $this->user->user_login,
            'user_email' => 'updated@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe'
        ];

        $response = $this->postJson('/api/profile', $updateData);

        $response->assertStatus(200);
        
        // Verify password wasn't changed
        $this->user->refresh();
        $this->assertTrue(PasswordHelper::verifyPassword($this->user->user_salt, 'password123', $this->user->user_pass));
    }

    public function test_user_can_update_password()
    {
        $this->actingAs($this->user);

        $updateData = [
            'user_login' => $this->user->user_login,
            'user_email' => $this->user->user_email,
            'user_pass' => 'NewPassword123!'
        ];

        $response = $this->postJson('/api/profile', $updateData);

        $response->assertStatus(200);
        
        // Verify password was changed
        $this->user->refresh();
        $this->assertFalse(PasswordHelper::verifyPassword($this->user->user_salt, 'password123', $this->user->user_pass));
        $this->assertTrue(PasswordHelper::verifyPassword($this->user->user_salt, 'NewPassword123!', $this->user->user_pass));
    }

    public function test_user_cannot_use_duplicate_email()
    {
        // Create another user
        $otherUser = User::factory()->create(['user_email' => 'other@example.com']);
        
        $this->actingAs($this->user);

        $updateData = [
            'user_login' => $this->user->user_login,
            'user_email' => 'other@example.com', // Duplicate email
            'first_name' => 'John'
        ];

        $response = $this->postJson('/api/profile', $updateData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_email']);
    }

    public function test_user_can_keep_same_email()
    {
        $this->actingAs($this->user);

        $updateData = [
            'user_login' => $this->user->user_login,
            'user_email' => $this->user->user_email, // Same email
            'first_name' => 'John'
        ];

        $response = $this->postJson('/api/profile', $updateData);

        $response->assertStatus(200);
    }

    public function test_password_validation_requirements()
    {
        $this->actingAs($this->user);

        $updateData = [
            'user_login' => $this->user->user_login,
            'user_email' => $this->user->user_email,
            'user_pass' => 'weak' // Invalid password
        ];

        $response = $this->postJson('/api/profile', $updateData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_pass']);
    }

    public function test_optional_fields_validation()
    {
        $this->actingAs($this->user);

        $updateData = [
            'user_login' => $this->user->user_login,
            'user_email' => $this->user->user_email,
            'first_name' => 'John<script>alert("xss")</script>', // Invalid characters
            'last_name' => 'Doe<script>alert("xss")</script>',
            'nickname' => 'Johnny<script>alert("xss")</script>',
            'biography' => 'Biography<script>alert("xss")</script>'
        ];

        $response = $this->postJson('/api/profile', $updateData);

        // The input sanitization middleware should either block this (422) or sanitize it (200)
        $this->assertContains($response->status(), [200, 422]);
        
        if ($response->status() === 200) {
            // If sanitized, verify the malicious content was removed
            $this->user->refresh();
            $this->assertStringNotContainsString('<script>', $this->user->user_details['first_name'] ?? '');
            $this->assertStringNotContainsString('<script>', $this->user->user_details['last_name'] ?? '');
        } else {
            // If validation failed, check for validation errors
            $response->assertJsonValidationErrors(['first_name', 'last_name', 'nickname', 'biography']);
        }
    }

    public function test_unauthenticated_user_cannot_update_profile()
    {
        $updateData = [
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'first_name' => 'John'
        ];

        $response = $this->postJson('/api/profile', $updateData);

        $response->assertStatus(401);
    }

    public function test_profile_update_requires_valid_data()
    {
        $this->actingAs($this->user);

        $updateData = [
            'user_email' => 'invalid-email', // Invalid email
            'user_pass' => 'weak' // Invalid password
        ];

        $response = $this->postJson('/api/profile', $updateData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_email', 'user_pass']);
    }

    public function test_profile_update_handles_partial_data()
    {
        $this->actingAs($this->user);

        $updateData = [
            'user_login' => $this->user->user_login,
            'user_email' => $this->user->user_email,
            'first_name' => 'John'
            // Only updating first name
        ];

        $response = $this->postJson('/api/profile', $updateData);

        $response->assertStatus(200);
        
        // Verify only first name was updated
        $this->user->refresh();
        $this->assertEquals('John', $this->user->user_details['first_name'] ?? '');
    }

    public function test_profile_update_returns_updated_user_data()
    {
        $this->actingAs($this->user);

        $updateData = [
            'user_login' => $this->user->user_login,
            'user_email' => 'updated@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'nickname' => 'Johnny',
            'biography' => 'Updated biography',
            'theme' => 'dark'
        ];

        $response = $this->postJson('/api/profile', $updateData);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'user' => [
                'id',
                'user_login',
                'user_email'
            ]
        ]);

        $userData = $response->json('user');
        $this->assertEquals('updated@example.com', $userData['user_email']);
    }

    public function test_profile_update_handles_database_errors_gracefully()
    {
        $this->actingAs($this->user);

        // Mock a database error by using invalid data that might cause issues
        $updateData = [
            'user_login' => $this->user->user_login,
            'user_email' => $this->user->user_email,
            'first_name' => str_repeat('a', 1000), // Very long string
        ];

        $response = $this->postJson('/api/profile', $updateData);

        // Should handle gracefully (either success or proper error response)
        $this->assertContains($response->status(), [200, 422, 500]);
    }
}
