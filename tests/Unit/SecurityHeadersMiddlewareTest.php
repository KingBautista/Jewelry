<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecurityHeadersMiddleware();
        $this->request = Request::create('/test', 'GET');
    }

    public function test_security_headers_are_added()
    {
        $response = $this->middleware->handle($this->request, function ($request) {
            return new Response('Test response');
        });

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
        $this->assertEquals('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertEquals('geolocation=(), microphone=(), camera=()', $response->headers->get('Permissions-Policy'));
    }

    public function test_content_security_policy_is_set()
    {
        $response = $this->middleware->handle($this->request, function ($request) {
            return new Response('Test response');
        });

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline' 'unsafe-eval'", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);
        $this->assertStringContainsString("img-src 'self' data: https:", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }

    public function test_hsts_header_for_https_requests()
    {
        // Mock HTTPS request
        $this->request->server->set('HTTPS', 'on');
        $this->request->server->set('SERVER_PORT', '443');

        $response = $this->middleware->handle($this->request, function ($request) {
            return new Response('Test response');
        });

        $this->assertEquals('max-age=31536000; includeSubDomains', $response->headers->get('Strict-Transport-Security'));
    }

    public function test_hsts_header_not_set_for_http_requests()
    {
        // Mock HTTP request
        $this->request->server->set('HTTPS', 'off');
        $this->request->server->set('SERVER_PORT', '80');

        $response = $this->middleware->handle($this->request, function ($request) {
            return new Response('Test response');
        });

        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_middleware_passes_request_to_next_handler()
    {
        $response = $this->middleware->handle($this->request, function ($request) {
            return new Response('Test response content');
        });

        $this->assertEquals('Test response content', $response->getContent());
    }

    public function test_middleware_does_not_modify_response_content()
    {
        $originalContent = 'Original response content';
        
        $response = $this->middleware->handle($this->request, function ($request) use ($originalContent) {
            return new Response($originalContent);
        });

        $this->assertEquals($originalContent, $response->getContent());
    }
}
