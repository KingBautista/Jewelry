<?php

// Simple CORS test script
header('Access-Control-Allow-Origin: https://admin.illussso.com');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Accept, Authorization, Content-Type, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, Origin, Access-Control-Request-Method, Access-Control-Request-Headers');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'CORS is working correctly',
    'timestamp' => date('Y-m-d H:i:s'),
    'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'not set',
    'method' => $_SERVER['REQUEST_METHOD']
]);
