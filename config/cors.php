<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'storage/*', 'docs/*', 'sanctum/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'https://admin.illussso.com',
        'https://customer.illussso.com',
        'http://localhost:4000',
        'http://localhost:3001',
        'http://localhost:3000',
        'http://127.0.0.1:4000',
        'http://127.0.0.1:3001',
        'http://127.0.0.1:3000',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
        'Origin',
        'Access-Control-Request-Method',
        'Access-Control-Request-Headers',
    ],
    'exposed_headers' => [
        'Cache-Control',
        'Content-Language',
        'Content-Type',
        'Expires',
        'Last-Modified',
        'Pragma',
    ],
    'max_age' => 86400,
    'supports_credentials' => true,
];
