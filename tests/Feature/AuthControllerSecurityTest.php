<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AuthControllerSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear rate limiter
        RateLimiter::clear('login');
        RateLimiter::clear('api');
    }

    public function test_user_registration_logs_audit_trail()
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Audit Trail', \Mockery::on(function ($data) {
                return $data['module'] === 'USER_MANAGEMENT' &&
                       $data['action'] === 'CREATE' &&
                       isset($data['data']['user_id']) &&
                       isset($data['data']['user_email']);
            }));

        $userData = [
            'username' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/signup', $userData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'user_email' => 'john@example.com'
        ]);
    }

    public function test_successful_login_logs_audit_trail()
    {
        $user = User::factory()->create([
            'user_email' => 'test@example.com',
            'user_pass' => Hash::make('password123')
        ]);

        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Audit Trail', \Mockery::on(function ($data) {
                return $data['module'] === 'USER_MANAGEMENT' &&
                       $data['action'] === 'LOGIN' &&
                       $data['user_id'] === $user->id &&
                       isset($data['data']['user_email']);
            }));

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token']);
    }

    public function test_failed_login_logs_audit_trail()
    {
        $user = User::factory()->create([
            'user_email' => 'test@example.com',
            'user_pass' => Hash::make('password123')
        ]);

        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Audit Trail', \Mockery::on(function ($data) {
                return $data['module'] === 'USER_MANAGEMENT' &&
                       $data['action'] === 'LOGIN_FAILED' &&
                       $data['data']['email'] === 'test@example.com' &&
                       isset($data['data']['ip_address']);
            }));

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401);
    }

    public function test_logout_logs_audit_trail()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Audit Trail', \Mockery::on(function ($data) use ($user) {
                return $data['module'] === 'USER_MANAGEMENT' &&
                       $data['action'] === 'LOGOUT' &&
                       $data['user_id'] === $user->id &&
                       isset($data['data']['user_email']);
            }));

        $response = $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200);
    }

    public function test_login_rate_limiting()
    {
        $user = User::factory()->create([
            'user_email' => 'test@example.com',
            'user_pass' => Hash::make('password123')
        ]);

        // Make multiple failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ]);
        }

        // The 6th attempt should be rate limited
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(429);
    }

    public function test_input_sanitization_on_registration()
    {
        $maliciousData = [
            'username' => '<script>alert("xss")</script>John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/signup', $maliciousData);

        $response->assertStatus(201);
        
        // Check that the malicious script was sanitized
        $user = User::where('user_email', 'john@example.com')->first();
        $this->assertStringNotContainsString('<script>', $user->user_login);
        $this->assertStringContainsString('John Doe', $user->user_login);
    }

    public function test_input_sanitization_on_login()
    {
        $user = User::factory()->create([
            'user_email' => 'test@example.com',
            'user_pass' => Hash::make('password123')
        ]);

        $maliciousData = [
            'email' => 'test@example.com',
            'password' => "'; DROP TABLE users; --"
        ];

        $response = $this->postJson('/api/auth/login', $maliciousData);

        $response->assertStatus(401);
        
        // Verify the malicious SQL was blocked and user still exists
        $this->assertDatabaseHas('users', [
            'user_email' => 'test@example.com'
        ]);
    }

    public function test_security_headers_are_present()
    {
        $response = $this->get('/api/auth/me');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_cors_headers_are_configured()
    {
        $response = $this->options('/api/login', [], [
            'Origin' => 'http://localhost:3000',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'Content-Type, Authorization'
        ]);

        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
        $response->assertHeader('Access-Control-Allow-Methods');
        $response->assertHeader('Access-Control-Allow-Headers');
    }

    public function test_unauthorized_cors_origin_is_blocked()
    {
        $response = $this->options('/api/login', [], [
            'Origin' => 'http://malicious-site.com',
            'Access-Control-Request-Method' => 'POST'
        ]);

        $response->assertHeader('Access-Control-Allow-Origin', 'null');
    }

    public function test_audit_trail_includes_ip_address()
    {
        $user = User::factory()->create([
            'user_email' => 'test@example.com',
            'user_pass' => Hash::make('password123')
        ]);

        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Audit Trail', \Mockery::on(function ($data) {
                return isset($data['ip_address']) && 
                       filter_var($data['ip_address'], FILTER_VALIDATE_IP) !== false;
            }));

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
    }

    public function test_audit_trail_includes_user_agent()
    {
        $user = User::factory()->create([
            'user_email' => 'test@example.com',
            'user_pass' => Hash::make('password123')
        ]);

        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Audit Trail', \Mockery::on(function ($data) {
                return isset($data['user_agent']) && 
                       is_string($data['user_agent']);
            }));

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ], [
            'User-Agent' => 'Test Browser/1.0'
        ]);

        $response->assertStatus(200);
    }

    public function test_multiple_failed_logins_from_same_ip_are_tracked()
    {
        $user = User::factory()->create([
            'user_email' => 'test@example.com',
            'user_pass' => Hash::make('password123')
        ]);

        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->times(3); // 3 failed attempts

        // Make 3 failed login attempts
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ]);
            
            $response->assertStatus(401);
        }
    }

    public function test_successful_login_after_failed_attempts()
    {
        $user = User::factory()->create([
            'user_email' => 'test@example.com',
            'user_pass' => Hash::make('password123')
        ]);

        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->times(2); // 1 failed + 1 successful

        // Failed attempt
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);
        $response->assertStatus(401);

        // Successful attempt
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $response->assertStatus(200);
    }
}
