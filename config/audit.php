<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Trail Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration settings for the audit trail system.
    | The audit trail logs all user actions and system events for security
    | and compliance purposes.
    |
    */

    'enabled' => env('AUDIT_TRAIL_ENABLED', true),

    'log_channel' => env('AUDIT_LOG_CHANNEL', 'audit'),

    'log_level' => env('AUDIT_LOG_LEVEL', 'info'),

    'retention_days' => env('AUDIT_RETENTION_DAYS', 90),

    'log_queries' => env('AUDIT_LOG_QUERIES', false),

    'log_failed_attempts' => env('AUDIT_LOG_FAILED_ATTEMPTS', true),

    'log_successful_actions' => env('AUDIT_LOG_SUCCESSFUL_ACTIONS', true),

    'exclude_routes' => [
        'api/files/url',
        'api/options/*',
        'api/dashboard/stats',
    ],

    'sensitive_fields' => [
        'password',
        'password_confirmation',
        'user_pass',
        'user_salt',
        'user_activation_key',
        'remember_token',
        'api_token',
        'access_token',
        'refresh_token',
    ],

    'modules' => [
        'USER_MANAGEMENT',
        'CUSTOMER_MANAGEMENT',
        'INVOICE_MANAGEMENT',
        'PAYMENT_MANAGEMENT',
        'FINANCIAL_MANAGEMENT',
        'CONTENT_MANAGEMENT',
        'SYSTEM_SETTINGS',
        'DASHBOARD',
        'FILE_MANAGEMENT',
        'OPTIONS',
    ],

    'actions' => [
        'VIEW',
        'CREATE',
        'UPDATE',
        'DELETE',
        'BULK_DELETE',
        'BULK_RESTORE',
        'IMPORT',
        'EXPORT',
        'VALIDATE',
        'DECLINE',
        'CONFIRM',
        'LOGIN',
        'LOGOUT',
    ],
];
