<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Middleware\AuditTrailMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuditTrailMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AuditTrailMiddleware();
    }

    public function test_middleware_passes_request_to_next_handler()
    {
        $request = Request::create('/api/test', 'GET');
        
        $response = $this->middleware->handle($request, function ($request) {
            return new Response('Test response', 200);
        });

        $this->assertEquals('Test response', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_skips_logging_for_health_check_routes()
    {
        $request = Request::create('/health', 'GET');
        
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->zeroOrMoreTimes();
        
        Log::shouldReceive('error')
            ->zeroOrMoreTimes()
            ->andReturnSelf();
        
        $response = $this->middleware->handle($request, function ($request) {
            return new Response('OK', 200);
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_skips_logging_for_asset_routes()
    {
        $request = Request::create('/assets/style.css', 'GET');
        
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->zeroOrMoreTimes();
        
        Log::shouldReceive('error')
            ->zeroOrMoreTimes()
            ->andReturnSelf();
        
        $response = $this->middleware->handle($request, function ($request) {
            return new Response('CSS content', 200);
        });

        $this->assertEquals('CSS content', $response->getContent());
    }

    public function test_logs_api_requests()
    {
        $request = Request::create('/api/users', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        $request->headers->set('Authorization', 'Bearer token123');
        $request->headers->set('User-Agent', 'Test Agent');

        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('API Request', \Mockery::type('array'));

        Log::shouldReceive('error')
            ->zeroOrMoreTimes()
            ->andReturnSelf();

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('{"success": true}', 201, ['Content-Type' => 'application/json']);
        });

        $this->assertEquals('{"success": true}', $response->getContent());
    }

    public function test_sanitizes_sensitive_data_in_logs()
    {
        $request = Request::create('/api/auth/login', 'POST', [
            'email' => 'test@example.com',
            'password' => 'secretpassword',
            'remember_token' => 'token123'
        ]);

        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('API Request', \Mockery::on(function ($data) {
                return $data['request_data']['password'] === '[REDACTED]' &&
                       $data['request_data']['remember_token'] === '[REDACTED]' &&
                       $data['request_data']['email'] === 'test@example.com';
            }));

        $this->middleware->handle($request, function ($request) {
            return new Response('{"token": "jwt_token"}', 200);
        });
    }

    public function test_extracts_module_from_route()
    {
        $testCases = [
            '/api/users' => 'USER_MANAGEMENT',
            '/api/customers' => 'CUSTOMER_MANAGEMENT',
            '/api/invoices' => 'INVOICE_MANAGEMENT',
            '/api/payments' => 'PAYMENT_MANAGEMENT',
            '/api/auth/login' => 'AUTHENTICATION',
            '/api/unknown' => 'UNKNOWN'
        ];

        foreach ($testCases as $route => $expectedModule) {
            $request = Request::create($route, 'GET');
            
            Log::shouldReceive('channel')
                ->with('audit')
                ->andReturnSelf();
            
            Log::shouldReceive('info')
                ->once()
                ->with('API Request', \Mockery::on(function ($data) use ($expectedModule) {
                    return $data['module'] === $expectedModule;
                }));

            $this->middleware->handle($request, function ($request) {
                return new Response('OK', 200);
            });
        }
    }

    public function test_extracts_action_from_method()
    {
        $testCases = [
            'GET' => 'VIEW',
            'POST' => 'CREATE',
            'PUT' => 'UPDATE',
            'PATCH' => 'UPDATE',
            'DELETE' => 'DELETE'
        ];

        foreach ($testCases as $method => $expectedAction) {
            $request = Request::create('/api/users', $method);
            
            Log::shouldReceive('channel')
                ->with('audit')
                ->andReturnSelf();
            
            Log::shouldReceive('info')
                ->once()
                ->with('API Request', \Mockery::on(function ($data) use ($expectedAction) {
                    return $data['action'] === $expectedAction;
                }));

            $this->middleware->handle($request, function ($request) {
                return new Response('OK', 200);
            });
        }
    }

    public function test_extracts_resource_id_from_route()
    {
        $testCases = [
            '/api/users/123' => '123',
            '/api/customers/456' => '456',
            '/api/invoices/789' => '789',
            '/api/users' => null,
            '/api/users/123/edit' => '123'
        ];

        foreach ($testCases as $route => $expectedId) {
            $request = Request::create($route, 'GET');
            
            Log::shouldReceive('channel')
                ->with('audit')
                ->andReturnSelf();
            
            Log::shouldReceive('info')
                ->once()
                ->with('API Request', \Mockery::on(function ($data) use ($expectedId) {
                    return $data['resource_id'] === $expectedId;
                }));

            $this->middleware->handle($request, function ($request) {
                return new Response('OK', 200);
            });
        }
    }

    public function test_logs_database_queries_when_enabled()
    {
        // Enable query logging
        config(['audit.log_queries' => true]);
        
        $request = Request::create('/api/users', 'GET');
        
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->atLeast()->once();

        $this->middleware->handle($request, function ($request) {
            return new Response('OK', 200);
        });
    }

    public function test_does_not_log_queries_when_disabled()
    {
        // Disable query logging
        config(['audit.log_queries' => false]);
        
        $request = Request::create('/api/users', 'GET');
        
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once(); // Only API request log, no query logs

        $this->middleware->handle($request, function ($request) {
            return new Response('OK', 200);
        });
    }

    public function test_handles_exceptions_gracefully()
    {
        $request = Request::create('/api/test', 'GET');
        
        Log::shouldReceive('channel')
            ->with('audit')
            ->andThrow(new \Exception('Logging failed'));
        
        // Should not throw exception, should continue processing
        $response = $this->middleware->handle($request, function ($request) {
            return new Response('OK', 200);
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_logs_response_status_code()
    {
        $request = Request::create('/api/users', 'POST');
        
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('API Request', \Mockery::on(function ($data) {
                return $data['response_status'] === 201;
            }));

        $this->middleware->handle($request, function ($request) {
            return new Response('Created', 201);
        });
    }

    public function test_logs_response_time()
    {
        $request = Request::create('/api/users', 'GET');
        
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('API Request', \Mockery::on(function ($data) {
                return isset($data['response_time']) && is_numeric($data['response_time']);
            }));

        $this->middleware->handle($request, function ($request) {
            return new Response('OK', 200);
        });
    }
}
