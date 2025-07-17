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