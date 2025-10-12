<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class SecurityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear rate limiter
        RateLimiter::clear('login');
        RateLimiter::clear('api');
    }

    public function test_security_middleware_stack_works_together()
    {
        $user = User::factory()->create([
            'user_email' => 'test@example.com',
            'user_password' => Hash::make('password123')
        ]);

        // Test that all security middleware work together
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        // Check security headers
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Content-Security-Policy');

        // Check that response is successful
        $response->assertStatus(200);
        $response->assertJsonStructure(['token']);
    }

    public function test_input_sanitization_with_audit_trail()
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->atLeast()->once();

        $maliciousData = [
            'user_name' => '<script>alert("xss")</script>John Doe',
            'user_email' => 'john@example.com',
            'user_password' => "'; DROP TABLE users; --",
            'user_password_confirmation' => "'; DROP TABLE users; --"
        ];

        $response = $this->postJson('/api/auth/signup', $maliciousData);

        $response->assertStatus(201);
        
        // Verify user was created with sanitized data
        $user = User::where('user_email', 'john@example.com')->first();
        $this->assertNotNull($user);
        $this->assertStringNotContainsString('<script>', $user->user_name);
        $this->assertStringNotContainsString('DROP TABLE', $user->user_name);
    }

    public function test_rate_limiting_with_audit_trail()
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->atLeast()->times(5); // Multiple failed attempts

        $user = User::factory()->create([
            'user_email' => 'test@example.com',
            'user_password' => Hash::make('password123')
        ]);

        // Make multiple failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ]);
            
            $response->assertStatus(401);
        }

        // The 6th attempt should be rate limited
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(429);
    }

    public function test_cors_with_security_headers()
    {
        $response = $this->options('/api/users', [], [
            'Origin' => 'http://localhost:3000',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'Content-Type, Authorization'
        ]);

        // Check CORS headers
        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
        $response->assertHeader('Access-Control-Allow-Methods');
        $response->assertHeader('Access-Control-Allow-Headers');

        // Check security headers are still present
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_audit_trail_covers_all_security_events()
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->times(4); // Registration, login, failed login, logout

        $userData = [
            'user_name' => 'Test User',
            'user_email' => 'test@example.com',
            'user_password' => 'password123',
            'user_password_confirmation' => 'password123'
        ];

        // Registration
        $response = $this->postJson('/api/auth/signup', $userData);
        $response->assertStatus(201);

        // Successful login
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $response->assertStatus(200);
        $token = $response->json('token');

        // Failed login
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);
        $response->assertStatus(401);

        // Logout
        $response = $this->postJson('/api/auth/logout', [], [
            'Authorization' => 'Bearer ' . $token
        ]);
        $response->assertStatus(200);
    }

    public function test_security_configuration_is_loaded()
    {
        // Test that security configuration is properly loaded
        $this->assertNotNull(config('security'));
        $this->assertNotNull(config('security.headers'));
        $this->assertNotNull(config('security.rate_limiting'));
        $this->assertNotNull(config('security.cors'));
        $this->assertNotNull(config('audit'));
    }

    public function test_malicious_request_handling()
    {
        $maliciousRequests = [
            // XSS attempts
            ['GET', '/api/users?search=<script>alert(1)</script>'],
            ['POST', '/api/users', ['user_name' => '<img src=x onerror=alert(1)>']],
            
            // SQL injection attempts
            ['GET', '/api/users?search=\'; DROP TABLE users; --'],
            ['POST', '/api/users', ['user_name' => '\'; DROP TABLE users; --']],
            
            // Path traversal attempts
            ['GET', '/api/users/../../../etc/passwd'],
            ['GET', '/api/users/..%2F..%2F..%2Fetc%2Fpasswd'],
        ];

        foreach ($maliciousRequests as $request) {
            [$method, $uri, $data] = array_pad($request, 3, []);
            $response = $this->json($method, $uri, $data);
            
            // Should not crash the application
            $this->assertContains($response->getStatusCode(), [200, 201, 400, 401, 403, 404, 422, 429]);
            
            // Should have security headers
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
            $response->assertHeader('X-Frame-Options', 'DENY');
        }
    }

    public function test_large_payload_handling()
    {
        // Test with very large payload
        $largeData = [
            'user_name' => str_repeat('A', 10000),
            'user_email' => 'test@example.com',
            'user_password' => 'password123',
            'user_password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/auth/signup', $largeData);
        
        // Should handle gracefully
        $this->assertContains($response->getStatusCode(), [200, 201, 413, 422]);
        
        // Should still have security headers
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_concurrent_malicious_requests()
    {
        $maliciousData = [
            'user_name' => '<script>alert("xss")</script>',
            'user_email' => 'test@example.com',
            'user_password' => 'password123',
            'user_password_confirmation' => 'password123'
        ];

        // Make multiple concurrent malicious requests
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('/api/auth/signup', $maliciousData);
        }

        // All should be handled gracefully
        foreach ($responses as $response) {
            $this->assertContains($response->getStatusCode(), [200, 201, 422, 429]);
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
        }
    }

    public function test_security_headers_on_all_routes()
    {
        $routes = [
            '/api/users',
            '/api/customers',
            '/api/invoices',
            '/api/payments',
            '/api/dashboard/stats',
            '/api/auth/me'
        ];

        foreach ($routes as $route) {
            $response = $this->getJson($route);
            
            // Should have security headers regardless of auth status
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
            $response->assertHeader('X-Frame-Options', 'DENY');
            $response->assertHeader('X-XSS-Protection', '1; mode=block');
            $response->assertHeader('Content-Security-Policy');
        }
    }

    public function test_audit_log_retention_configuration()
    {
        // Test that audit configuration is properly set
        $this->assertEquals(90, config('audit.retention_days'));
        $this->assertEquals('info', config('audit.log_level'));
        $this->assertTrue(config('audit.enabled'));
    }
}
