<?php
/**
 * Debug script to test confirm_booking logic
 *
 * ROOT CAUSE: Test call failure analysis
 * Date: 2025-11-08
 * Call ID: call_f1492ec2623ccf7f59482848dea
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  DEBUG: confirm_booking Failure Analysis\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

// Test data mimicking what should have been cached
$testCallIds = [
    'call_f1492ec2623ccf7f59482848dea',  // Actual call ID
    '1'  // What agent passed in args
];

echo "🔍 Step 1: Check existing cache keys\n";
echo "───────────────────────────────────────────────────────────\n";
foreach ($testCallIds as $callId) {
    $cacheKey = "pending_booking:{$callId}";
    $value = Cache::get($cacheKey);

    echo "Key: {$cacheKey}\n";
    echo "  Status: " . ($value ? "✅ EXISTS" : "❌ NOT FOUND") . "\n";

    if ($value) {
        echo "  Data: " . json_encode($value, JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";
}

echo "🧪 Step 2: Simulate start_booking cache operation\n";
echo "───────────────────────────────────────────────────────────\n";

$testBookingData = [
    'call_id' => 'call_f1492ec2623ccf7f59482848dea',
    'company_id' => 1,
    'branch_id' => 1,
    'service_id' => 438,
    'event_type_id' => 12345,
    'appointment_time' => '2025-11-10T08:50:00+01:00',
    'duration' => 30,
    'customer_name' => 'Test Customer',
    'customer_email' => 'test@example.com',
    'customer_phone' => '+49123456789',
    'notes' => 'Test booking',
    'validated_at' => now()->toIso8601String()
];

foreach ($testCallIds as $callId) {
    $cacheKey = "pending_booking:{$callId}";

    echo "Attempting to cache with key: {$cacheKey}\n";

    try {
        $testData = array_merge($testBookingData, ['call_id' => $callId]);
        Cache::put($cacheKey, $testData, now()->addMinutes(10));

        // Verify immediately
        $retrieved = Cache::get($cacheKey);

        if ($retrieved) {
            echo "  ✅ Cache write successful\n";
            echo "  ✅ Cache read successful\n";
            echo "  TTL: 10 minutes\n";
        } else {
            echo "  ❌ Cache write succeeded but immediate read failed!\n";
        }
    } catch (\Exception $e) {
        echo "  ❌ Cache operation failed: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

echo "🔍 Step 3: Check Redis connection\n";
echo "───────────────────────────────────────────────────────────\n";

try {
    $redis = app('redis')->connection();
    $redis->ping();
    echo "✅ Redis connection: OK\n";

    // Check Redis config
    $config = $redis->config('get', 'maxmemory-policy');
    echo "Redis maxmemory-policy: " . json_encode($config) . "\n";

} catch (\Exception $e) {
    echo "❌ Redis connection failed: " . $e->getMessage() . "\n";
}

echo "\n";
echo "🔍 Step 4: Test cache retrieval (as confirm_booking does)\n";
echo "───────────────────────────────────────────────────────────\n";

foreach ($testCallIds as $callId) {
    $cacheKey = "pending_booking:{$callId}";
    $bookingData = Cache::get($cacheKey);

    echo "Checking key: {$cacheKey}\n";

    if (!$bookingData) {
        echo "  ❌ ERROR: No pending booking found in cache\n";
        echo "  This would trigger: 'Die Buchungsdaten sind abgelaufen'\n";
    } else {
        // Check freshness (max 10 minutes)
        $validatedAt = Carbon::parse($bookingData['validated_at']);
        $ageSeconds = $validatedAt->diffInSeconds(now());

        echo "  ✅ Cache data found\n";
        echo "  Age: {$ageSeconds} seconds\n";

        if ($validatedAt->lt(now()->subMinutes(10))) {
            echo "  ❌ ERROR: Booking expired (>10 minutes old)\n";
            echo "  This would trigger: 'Die Buchung ist abgelaufen'\n";
        } else {
            echo "  ✅ Cache is fresh (< 10 minutes old)\n";
        }
    }

    echo "\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "  ANALYSIS COMPLETE\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

