<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Middleware\AuditTrailMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class AuditTrailMiddlewareSimpleTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;

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

    public function test_middleware_handles_basic_api_request()
    {
        $request = Request::create('/api/users', 'GET');
        
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

    public function test_middleware_handles_post_request()
    {
        $request = Request::create('/api/users', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->zeroOrMoreTimes();
        
        Log::shouldReceive('error')
            ->zeroOrMoreTimes()
            ->andReturnSelf();

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('Created', 201);
        });

        $this->assertEquals('Created', $response->getContent());
    }

    public function test_middleware_handles_put_request()
    {
        $request = Request::create('/api/users/1', 'PUT', [
            'name' => 'Jane Doe'
        ]);
        
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->zeroOrMoreTimes();
        
        Log::shouldReceive('error')
            ->zeroOrMoreTimes()
            ->andReturnSelf();

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('Updated', 200);
        });

        $this->assertEquals('Updated', $response->getContent());
    }

    public function test_middleware_handles_delete_request()
    {
        $request = Request::create('/api/users/1', 'DELETE');
        
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->zeroOrMoreTimes();
        
        Log::shouldReceive('error')
            ->zeroOrMoreTimes()
            ->andReturnSelf();

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('Deleted', 200);
        });

        $this->assertEquals('Deleted', $response->getContent());
    }

    public function test_middleware_handles_exceptions_gracefully()
    {
        $request = Request::create('/api/test', 'GET');
        
        Log::shouldReceive('channel')
            ->with('audit')
            ->andThrow(new \Exception('Logging failed'));
        
        Log::shouldReceive('error')
            ->zeroOrMoreTimes()
            ->andReturnSelf();
        
        // Should not throw exception, should continue processing
        $response = $this->middleware->handle($request, function ($request) {
            return new Response('OK', 200);
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_middleware_handles_different_route_patterns()
    {
        $routes = [
            '/api/users',
            '/api/customers',
            '/api/invoices',
            '/api/payments',
            '/api/users/123',
            '/api/customers/456/edit'
        ];

        foreach ($routes as $route) {
            $request = Request::create($route, 'GET');
            
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
    }
}
