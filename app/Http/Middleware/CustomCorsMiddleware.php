<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomCorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Handle preflight OPTIONS requests
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', $this->getAllowedOrigin($request))
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
                ->header('Access-Control-Allow-Headers', 'Accept, Authorization, Content-Type, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, Origin, Access-Control-Request-Method, Access-Control-Request-Headers')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        // Add CORS headers to the response
        $response->headers->set('Access-Control-Allow-Origin', $this->getAllowedOrigin($request));
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $response->headers->set('Access-Control-Allow-Headers', 'Accept, Authorization, Content-Type, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, Origin, Access-Control-Request-Method, Access-Control-Request-Headers');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Expose-Headers', 'Cache-Control, Content-Language, Content-Type, Expires, Last-Modified, Pragma');

        return $response;
    }

    /**
     * Get the allowed origin for the request
     */
    private function getAllowedOrigin(Request $request): string
    {
        $origin = $request->headers->get('Origin');
        
        // Get allowed origins from security configuration
        $allowedOrigins = config('security.cors.allowed_origins', [
            'https://admin.illussso.com',
            'https://customer.illussso.com',
            'http://localhost:4000',
            'http://localhost:3001',
            'http://localhost:3000',
            'http://127.0.0.1:4000',
            'http://127.0.0.1:3001',
            'http://127.0.0.1:3000',
        ]);

        if (in_array($origin, $allowedOrigins)) {
            return $origin;
        }

        // For development, allow localhost with any port
        if (app()->environment('local', 'development')) {
            if (preg_match('/^https?:\/\/localhost:\d+$/', $origin) || 
                preg_match('/^https?:\/\/127\.0\.0\.1:\d+$/', $origin)) {
                return $origin;
            }
        }

        // Default fallback
        return $allowedOrigins[0] ?? 'https://admin.illussso.com';
    }
}
