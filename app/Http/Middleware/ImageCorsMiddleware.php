<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ImageCorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add CORS headers for image requests with security restrictions
        $allowedOrigins = config('security.cors.allowed_origins', ['http://localhost:3000']);
        $origin = $request->headers->get('Origin');
        
        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        } else {
            $response->headers->set('Access-Control-Allow-Origin', 'null');
        }
        
        $response->headers->set('Access-Control-Allow-Methods', implode(',', config('security.cors.allowed_methods', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'])));
        $response->headers->set('Access-Control-Allow-Headers', implode(',', config('security.cors.allowed_headers', ['Content-Type', 'Authorization', 'X-Requested-With'])));
        $response->headers->set('Access-Control-Max-Age', config('security.cors.max_age', 86400));
        $response->headers->set('Cross-Origin-Resource-Policy', 'cross-origin');
        $response->headers->set('Cross-Origin-Embedder-Policy', 'unsafe-none');

        return $response;
    }
}
