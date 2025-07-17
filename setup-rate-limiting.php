<?php
// Setup Rate Limiting for AskProAI
// Created: 2025-01-15

echo "🔒 Setting up Rate Limiting...\n\n";

// Check if RateLimiter middleware exists
$middlewarePath = 'app/Http/Middleware/ThrottleRequests.php';
if (!file_exists($middlewarePath)) {
    echo "Creating ThrottleRequests middleware...\n";
    
    $middlewareContent = <<<'PHP'
<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests as BaseThrottleRequests;
use Illuminate\Http\Request;

class ThrottleRequests extends BaseThrottleRequests
{
    /**
     * Resolve the number of attempts if the user is authenticated or not.
     */
    protected function resolveMaxAttempts($request, $maxAttempts)
    {
        // Different limits for authenticated vs unauthenticated users
        if ($request->user()) {
            return $maxAttempts * 2; // Authenticated users get double the limit
        }

        return $maxAttempts;
    }

    /**
     * Resolve request signature based on route and user
     */
    protected function resolveRequestSignature($request)
    {
        if ($user = $request->user()) {
            return sha1($user->getAuthIdentifier());
        }

        // For API routes, use API key if present
        if ($request->hasHeader('X-API-Key')) {
            return sha1($request->header('X-API-Key'));
        }

        // Fallback to IP
        return sha1($request->ip());
    }
}
PHP;
    
    file_put_contents($middlewarePath, $middlewareContent);
    echo "✅ Created ThrottleRequests middleware\n";
}

// Update Kernel.php to include rate limiting
echo "\nUpdating Kernel.php...\n";
$kernelPath = 'app/Http/Kernel.php';
$kernelContent = file_get_contents($kernelPath);

// Check if throttle is already registered
if (!strpos($kernelContent, "'throttle' =>")) {
    // Find routeMiddleware array
    $pattern = "/'verified' => \\\\Illuminate\\\\Auth\\\\Middleware\\\\EnsureEmailIsVerified::class,/";
    $replacement = "'verified' => \\Illuminate\\Auth\\Middleware\\EnsureEmailIsVerified::class,\n        'throttle' => \\App\\Http\\Middleware\\ThrottleRequests::class,";
    
    $kernelContent = preg_replace($pattern, $replacement, $kernelContent);
    file_put_contents($kernelPath, $kernelContent);
    echo "✅ Added throttle middleware to Kernel\n";
} else {
    echo "ℹ️  Throttle middleware already registered\n";
}

// Create rate limit configuration
echo "\nCreating rate limit configuration...\n";

$rateLimitConfig = <<<'PHP'
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
PHP;

file_put_contents('config/ratelimit.php', $rateLimitConfig);
echo "✅ Created rate limit configuration\n";

// Update routes to use rate limiting
echo "\nUpdating API routes with rate limiting...\n";

// Example of how to apply rate limiting to routes
$routeExample = <<<'TXT'

To apply rate limiting to your routes, add the throttle middleware:

// In routes/api.php:
Route::middleware(['throttle:api'])->group(function () {
    // Your API routes here
});

// For specific limits:
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');

// For custom limits per route:
Route::get('/search', [SearchController::class, 'search'])->middleware('throttle:search');

TXT;

echo $routeExample;

// Create a monitoring script
$monitorScript = <<<'PHP'
<?php
// Monitor rate limit violations
// Run via cron every 5 minutes

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

$violations = [];
$keys = Redis::keys('laravel:throttle:*');

foreach ($keys as $key) {
    $hits = Redis::get($key);
    if ($hits > 50) { // Alert if more than 50 hits
        $violations[] = [
            'key' => $key,
            'hits' => $hits,
            'ttl' => Redis::ttl($key)
        ];
    }
}

if (!empty($violations)) {
    Log::warning('Rate limit violations detected', $violations);
    
    // Send alert email or notification
    foreach ($violations as $violation) {
        echo "⚠️  High rate limit usage: {$violation['key']} - {$violation['hits']} hits\n";
    }
}
PHP;

file_put_contents('monitor-rate-limits.php', $monitorScript);
echo "\n✅ Created rate limit monitoring script\n";

echo "\n🎯 Next Steps:\n";
echo "1. Clear config cache: php artisan config:cache\n";
echo "2. Apply throttle middleware to your routes\n";
echo "3. Test rate limiting: curl -I https://api.askproai.de/api/test\n";
echo "4. Monitor violations: php monitor-rate-limits.php\n";
echo "\n✅ Rate limiting setup complete!\n";