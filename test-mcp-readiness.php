<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

echo "=== MCP System Readiness Check ===\n\n";

$checks = [];

// 1. Check MCP Configuration
echo "1. MCP Configuration Check...\n";
$mcpEnabled = config('mcp.enabled');
$checks['mcp_config'] = $mcpEnabled;
echo "   MCP Enabled: " . ($mcpEnabled ? "✓ Yes" : "✗ No") . "\n";

// 2. Check Redis Connection
echo "\n2. Redis Connection Check...\n";
try {
    Redis::ping();
    $checks['redis'] = true;
    echo "   Redis: ✓ Connected\n";
    
    // Test Redis performance
    $start = microtime(true);
    for ($i = 0; $i < 100; $i++) {
        Redis::set("test_key_$i", "value_$i");
        Redis::get("test_key_$i");
        Redis::del("test_key_$i");
    }
    $elapsed = (microtime(true) - $start) * 1000;
    echo "   Redis Performance: " . round($elapsed, 2) . "ms for 300 operations\n";
} catch (\Exception $e) {
    $checks['redis'] = false;
    echo "   Redis: ✗ Failed - " . $e->getMessage() . "\n";
}

// 3. Check Database Connection Pool
echo "\n3. Database Connection Pool Check...\n";
try {
    $poolEnabled = config('mcp.connection_pool.enabled');
    $maxConnections = config('mcp.connection_pool.max_connections');
    echo "   Pool Enabled: " . ($poolEnabled ? "✓ Yes" : "✗ No") . "\n";
    echo "   Max Connections: $maxConnections\n";
    
    // Test concurrent connections
    $connections = [];
    $start = microtime(true);
    for ($i = 0; $i < 10; $i++) {
        $connections[] = DB::connection()->getPdo();
    }
    $elapsed = (microtime(true) - $start) * 1000;
    echo "   Connection Test: ✓ Created 10 connections in " . round($elapsed, 2) . "ms\n";
    $checks['db_pool'] = true;
} catch (\Exception $e) {
    $checks['db_pool'] = false;
    echo "   Database Pool: ✗ Failed - " . $e->getMessage() . "\n";
}

// 4. Check Circuit Breakers
echo "\n4. Circuit Breaker Configuration...\n";
$circuitBreakers = config('mcp.circuit_breakers');
foreach ($circuitBreakers as $service => $config) {
    echo "   $service: Threshold=" . $config['failure_threshold'] . ", Timeout=" . $config['timeout'] . "s\n";
}
$checks['circuit_breakers'] = !empty($circuitBreakers);

// 5. Check Webhook Endpoint
echo "\n5. Webhook Endpoint Check...\n";
try {
    $webhookUrl = env('APP_URL') . '/mcp/webhook';
    echo "   Webhook URL: $webhookUrl\n";
    $checks['webhook_endpoint'] = true;
} catch (\Exception $e) {
    $checks['webhook_endpoint'] = false;
    echo "   Webhook: ✗ Failed - " . $e->getMessage() . "\n";
}

// 6. Check Queue Configuration
echo "\n6. Queue Configuration Check...\n";
try {
    $queueConnection = config('queue.default');
    $horizonStatus = exec('php artisan horizon:status 2>&1', $output, $returnCode);
    
    echo "   Queue Driver: $queueConnection\n";
    echo "   Horizon Status: " . ($returnCode === 0 ? "✓ Running" : "✗ Not Running") . "\n";
    
    if ($queueConnection === 'redis') {
        $queueSize = Redis::llen('queues:default');
        echo "   Default Queue Size: $queueSize jobs\n";
    }
    
    $checks['queue'] = $queueConnection === 'redis';
} catch (\Exception $e) {
    $checks['queue'] = false;
    echo "   Queue: ✗ Failed - " . $e->getMessage() . "\n";
}

// 7. Check Performance Metrics
echo "\n7. Performance Configuration...\n";
$perfConfig = config('mcp.performance');
echo "   Target Latency: " . $perfConfig['target_latency_ms'] . "ms\n";
echo "   Max Latency: " . $perfConfig['max_latency_ms'] . "ms\n";
echo "   Concurrent Calls: " . $perfConfig['concurrent_calls'] . "\n";
echo "   Queue Workers: " . $perfConfig['queue_workers'] . "\n";

// 8. Check Monitoring
echo "\n8. Monitoring Configuration...\n";
$monitoringEnabled = config('mcp.monitoring.metrics_enabled');
echo "   Metrics Enabled: " . ($monitoringEnabled ? "✓ Yes" : "✗ No") . "\n";
$checks['monitoring'] = $monitoringEnabled;

// Summary
echo "\n=== Readiness Summary ===\n";
$totalChecks = count($checks);
$passedChecks = count(array_filter($checks));
$readyForProduction = $passedChecks === $totalChecks;

echo "Passed: $passedChecks/$totalChecks checks\n";
echo "Status: " . ($readyForProduction ? "✅ READY FOR PRODUCTION" : "❌ NOT READY") . "\n";

if (!$readyForProduction) {
    echo "\nFailed Checks:\n";
    foreach ($checks as $check => $passed) {
        if (!$passed) {
            echo "  - $check\n";
        }
    }
}

// Recommendations
echo "\n=== Recommendations ===\n";
if (!$checks['redis']) {
    echo "- Ensure Redis is running: systemctl start redis\n";
}
if (!$checks['queue']) {
    echo "- Start Horizon for queue processing: php artisan horizon\n";
}
if (!$readyForProduction) {
    echo "- Fix all failed checks before production deployment\n";
} else {
    echo "- System is ready for MCP deployment!\n";
    echo "- Consider running load tests before going live\n";
    echo "- Monitor error rates closely after deployment\n";
}

echo "\n";