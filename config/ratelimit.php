<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    */
    
    'limits' => [
        // API endpoints
        'api' => [
            'default' => '60,1',      // 60 requests per minute
            'search' => '30,1',       // 30 searches per minute
            'webhook' => '100,1',     // 100 webhooks per minute
            'export' => '5,10',       // 5 exports per 10 minutes
        ],
        
        // Web endpoints
        'web' => [
            'default' => '100,1',     // 100 requests per minute
            'login' => '5,1',         // 5 login attempts per minute
            'register' => '3,10',     // 3 registrations per 10 minutes
            'password_reset' => '3,10', // 3 password resets per 10 minutes
        ],
        
        // Admin endpoints
        'admin' => [
            'default' => '200,1',     // 200 requests per minute for admins
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limit Headers
    |--------------------------------------------------------------------------
    */
    
    'headers' => [
        'limit' => 'X-RateLimit-Limit',
        'remaining' => 'X-RateLimit-Remaining',
        'retry_after' => 'X-RateLimit-RetryAfter',
    ],
];