<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Middleware\InputSanitizationMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InputSanitizationMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new InputSanitizationMiddleware();
    }

    public function test_xss_script_tags_are_sanitized()
    {
        $request = Request::create('/test', 'POST', [
            'name' => '<script>alert("xss")</script>',
            'description' => 'Normal text'
        ]);

        $this->middleware->handle($request, function ($request) {
            return new Response('OK');
        });

        $this->assertStringNotContainsString('<script>', $request->input('name'));
        $this->assertStringNotContainsString('alert', $request->input('name'));
        $this->assertEquals('Normal text', $request->input('description'));
    }

    public function test_sql_injection_patterns_are_blocked()
    {
        $request = Request::create('/test', 'POST', [
            'search' => "'; DROP TABLE users; --",
            'query' => "SELECT * FROM users WHERE id = 1"
        ]);

        $this->middleware->handle($request, function ($request) {
            return new Response('OK');
        });

        $this->assertStringContainsString('[BLOCKED]', $request->input('search'));
        $this->assertStringContainsString('[BLOCKED]', $request->input('query'));
    }

    public function test_null_bytes_are_removed()
    {
        $request = Request::create('/test', 'POST', [
            'filename' => "test.txt\x00.php",
            'content' => "Normal content"
        ]);

        $this->middleware->handle($request, function ($request) {
            return new Response('OK');
        });

        $this->assertStringNotContainsString("\x00", $request->input('filename'));
        $this->assertEquals("Normal content", $request->input('content'));
    }

    public function test_excessive_whitespace_is_normalized()
    {
        $request = Request::create('/test', 'POST', [
            'text' => "  Multiple    spaces   and\ttabs  ",
            'normal' => "Normal text"
        ]);

        $this->middleware->handle($request, function ($request) {
            return new Response('OK');
        });

        $this->assertEquals("Multiple spaces and tabs", $request->input('text'));
        $this->assertEquals("Normal text", $request->input('normal'));
    }

    public function test_nested_arrays_are_sanitized()
    {
        $request = Request::create('/test', 'POST', [
            'user' => [
                'name' => '<script>alert("xss")</script>',
                'email' => 'test@example.com',
                'profile' => [
                    'bio' => "'; DROP TABLE profiles; --",
                    'website' => 'https://example.com'
                ]
            ]
        ]);

        $this->middleware->handle($request, function ($request) {
            return new Response('OK');
        });

        $this->assertStringNotContainsString('<script>', $request->input('user.name'));
        $this->assertEquals('test@example.com', $request->input('user.email'));
        $this->assertStringContainsString('[BLOCKED]', $request->input('user.profile.bio'));
        $this->assertEquals('https://example.com', $request->input('user.profile.website'));
    }

    public function test_html_special_chars_are_encoded()
    {
        $request = Request::create('/test', 'POST', [
            'html' => '<div>Test & "quotes"</div>',
            'normal' => 'Normal text'
        ]);

        $this->middleware->handle($request, function ($request) {
            return new Response('OK');
        });

        $this->assertStringContainsString('&lt;div&gt;', $request->input('html'));
        $this->assertStringContainsString('&amp;', $request->input('html'));
        $this->assertStringContainsString('&quot;quotes&quot;', $request->input('html'));
        $this->assertEquals('Normal text', $request->input('normal'));
    }

    public function test_non_string_values_are_preserved()
    {
        $request = Request::create('/test', 'POST', [
            'number' => 123,
            'boolean' => true,
            'array' => ['item1', 'item2'],
            'null' => null
        ]);

        $this->middleware->handle($request, function ($request) {
            return new Response('OK');
        });

        $this->assertEquals(123, $request->input('number'));
        $this->assertTrue($request->input('boolean'));
        $this->assertEquals(['item1', 'item2'], $request->input('array'));
        $this->assertNull($request->input('null'));
    }

    public function test_middleware_passes_request_to_next_handler()
    {
        $request = Request::create('/test', 'POST', ['test' => 'value']);

        $response = $this->middleware->handle($request, function ($request) {
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_various_malicious_patterns_are_blocked()
    {
        $maliciousInputs = [
            'iframe' => '<iframe src="javascript:alert(1)"></iframe>',
            'object' => '<object data="malicious.swf"></object>',
            'embed' => '<embed src="malicious.swf">',
            'form' => '<form action="http://evil.com"></form>',
            'javascript' => 'javascript:alert(1)',
            'vbscript' => 'vbscript:msgbox(1)',
            'onload' => '<img onload="alert(1)">',
            'onclick' => '<div onclick="alert(1)">Click me</div>'
        ];

        foreach ($maliciousInputs as $key => $value) {
            $request = Request::create('/test', 'POST', [$key => $value]);
            
            $this->middleware->handle($request, function ($request) {
                return new Response('OK');
            });

            $sanitizedValue = $request->input($key);
            
            // Check if the value was blocked or sanitized
            $this->assertTrue(
                $sanitizedValue === '[BLOCKED]' || 
                !str_contains($sanitizedValue, '<') || 
                !str_contains($sanitizedValue, 'javascript:') ||
                !str_contains($sanitizedValue, 'vbscript:') ||
                !str_contains($sanitizedValue, 'onload=') ||
                !str_contains($sanitizedValue, 'onclick='),
                "Failed to properly sanitize malicious pattern: {$key}. Got: {$sanitizedValue}"
            );
        }
    }
}
