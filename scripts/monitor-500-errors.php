#!/usr/bin/env php
<?php

/**
 * 500 Error Monitoring Script with Telescope Integration
 * Tracks and reports all 500 errors in the system
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "🔍 500 Error Monitor with Telescope\n";
echo "=====================================\n\n";

// 1. Check Laravel Logs
echo "📝 Checking Laravel Logs...\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $last24Hours = date('Y-m-d', strtotime('-1 day'));

    // Count 500 errors in last 24 hours
    $pattern = '/' . preg_quote($last24Hours, '/') . '.*ERROR.*500|' . preg_quote(date('Y-m-d'), '/') . '.*ERROR.*500/';
    preg_match_all($pattern, $logs, $matches);

    $errorCount = count($matches[0]);

    if ($errorCount > 0) {
        echo "  ⚠️ Found $errorCount potential 500 errors in last 24 hours\n";

        // Show last 3 errors
        $errors = array_slice($matches[0], -3);
        foreach ($errors as $error) {
            echo "  → " . substr($error, 0, 100) . "...\n";
        }
    } else {
        echo "  ✅ No 500 errors in last 24 hours\n";
    }
} else {
    echo "  ❌ Log file not found\n";
}

// 2. Check Telescope Data (if available)
echo "\n🔭 Checking Telescope Data...\n";
try {
    // Check if telescope tables exist
    if (Schema::hasTable('telescope_entries')) {
        // Recent exceptions
        $exceptions = DB::table('telescope_entries')
            ->where('type', 'exception')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        echo "  - Exceptions (24h): $exceptions\n";

        // Recent 500 errors
        $failedRequests = DB::table('telescope_entries')
            ->where('type', 'request')
            ->where('created_at', '>=', now()->subDay())
            ->get()
            ->filter(function ($entry) {
                $content = json_decode($entry->content, true);
                return isset($content['response_status']) && $content['response_status'] == 500;
            });

        echo "  - 500 Errors (24h): " . $failedRequests->count() . "\n";

        if ($failedRequests->count() > 0) {
            echo "\n  Recent 500 Error URLs:\n";
            foreach ($failedRequests->take(5) as $request) {
                $content = json_decode($request->content, true);
                echo "    • " . ($content['uri'] ?? 'unknown') . " at " . $request->created_at . "\n";
            }
        }
    } else {
        echo "  ⚠️ Telescope tables not found\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error accessing Telescope: " . $e->getMessage() . "\n";
}

// 3. Test Current Endpoints
echo "\n🌐 Testing Current Endpoints...\n";
$endpoints = [
    '/admin/customers' => 'Customers',
    '/admin/appointments' => 'Appointments',
    '/admin/calls' => 'Calls',
    '/admin/companies' => 'Companies',
    '/admin/branches' => 'Branches',
    '/admin/services' => 'Services',
    '/admin/staff' => 'Staff',
    '/admin/working-hours' => 'Working Hours',
];

$errors = [];

foreach ($endpoints as $endpoint => $name) {
    $url = 'https://api.askproai.de' . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 500) {
        echo "  ❌ $name: 500 ERROR!\n";
        $errors[] = $endpoint;
    } elseif ($httpCode == 302 || $httpCode == 200) {
        echo "  ✅ $name: OK ($httpCode)\n";
    } else {
        echo "  ⚠️ $name: HTTP $httpCode\n";
    }
}

// 4. Check Database Query Performance
echo "\n⚡ Performance Check...\n";
$start = microtime(true);
try {
    DB::connection()->getPdo();
    $dbTime = round((microtime(true) - $start) * 1000, 2);
    echo "  - Database connection: {$dbTime}ms\n";

    // Test a heavy query
    $start = microtime(true);
    DB::table('calls')->count();
    $queryTime = round((microtime(true) - $start) * 1000, 2);
    echo "  - Calls table count: {$queryTime}ms\n";

    if ($queryTime > 1000) {
        echo "  ⚠️ Slow query detected! May cause timeouts.\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Database error: " . $e->getMessage() . "\n";
}

// 5. Summary and Recommendations
echo "\n📊 Summary\n";
echo "=====================================\n";

if (count($errors) > 0) {
    echo "❌ CRITICAL: Found 500 errors on following endpoints:\n";
    foreach ($errors as $endpoint) {
        echo "  • $endpoint\n";
    }
    echo "\nRecommended Actions:\n";
    echo "1. Check Laravel log: tail -f /var/www/api-gateway/storage/logs/laravel.log\n";
    echo "2. Access Telescope: https://api.askproai.de/telescope\n";
    echo "3. Check specific endpoint logs\n";
} else {
    echo "✅ No active 500 errors detected!\n";
    echo "\nTelescope Dashboard: https://api.askproai.de/telescope\n";
    echo "Login with: fabian@askproai.de\n";

    if ($errorCount > 0) {
        echo "\n⚠️ Note: Found $errorCount historical errors in logs (last 24h)\n";
    }
}

echo "\n💡 Monitoring Tips:\n";
echo "• Run this script regularly: */5 * * * * php " . __FILE__ . "\n";
echo "• Check Telescope dashboard for detailed error tracking\n";
echo "• Enable email notifications for critical errors\n";
echo "• Monitor /var/www/api-gateway/storage/logs/laravel.log\n";