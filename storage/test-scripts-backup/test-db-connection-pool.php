<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Database\PooledMySqlConnector;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Database Connection Pool\n";
echo "================================\n\n";

// 1. Test initial pool state
echo "1. Initial Pool State:\n";
$stats = PooledMySqlConnector::getStats();
printStats($stats);

// 2. Test multiple connections
echo "\n2. Creating multiple database connections:\n";
$connections = [];
for ($i = 1; $i <= 5; $i++) {
    try {
        // Force a new connection
        DB::reconnect();
        $result = DB::select("SELECT CONNECTION_ID() as id, {$i} as num");
        $connections[] = $result[0]->id;
        echo "   Connection {$i}: ID = {$result[0]->id}\n";
    } catch (\Exception $e) {
        echo "   Connection {$i}: FAILED - {$e->getMessage()}\n";
    }
}

echo "\n3. Pool State After Connections:\n";
$stats = PooledMySqlConnector::getStats();
printStats($stats);

// 3. Test connection release
echo "\n4. Releasing connections:\n";
DB::disconnect();
echo "   Connections disconnected\n";

echo "\n5. Pool State After Release:\n";
$stats = PooledMySqlConnector::getStats();
printStats($stats);

// 4. Test concurrent connections
echo "\n6. Testing concurrent connections:\n";
$start = microtime(true);
$promises = [];

for ($i = 1; $i <= 10; $i++) {
    $promises[] = executeQuery($i);
}

// Wait for all to complete
foreach ($promises as $i => $promise) {
    echo "   Query " . ($i + 1) . ": " . ($promise ? "SUCCESS" : "FAILED") . "\n";
}

$duration = microtime(true) - $start;
echo "   Total time: " . number_format($duration, 3) . " seconds\n";

echo "\n7. Final Pool State:\n";
$stats = PooledMySqlConnector::getStats();
printStats($stats);

// 5. Test pool exhaustion
echo "\n8. Testing pool exhaustion (creating 60 connections):\n";
$exhaustionTest = [];
$failed = 0;
$succeeded = 0;

for ($i = 1; $i <= 60; $i++) {
    try {
        $conn = DB::connection()->getPdo();
        $exhaustionTest[] = $conn;
        $succeeded++;
        if ($i % 10 == 0) {
            echo "   Created {$i} connections...\n";
        }
    } catch (\Exception $e) {
        $failed++;
        if ($failed == 1) {
            echo "   Connection {$i}: FAILED - Pool exhausted\n";
            echo "   Error: {$e->getMessage()}\n";
        }
    }
}

echo "   Succeeded: {$succeeded}, Failed: {$failed}\n";

echo "\n9. Pool State During Exhaustion:\n";
$stats = PooledMySqlConnector::getStats();
printStats($stats);

// Clean up
unset($exhaustionTest);
DB::disconnect();

echo "\n10. Pool State After Cleanup:\n";
$stats = PooledMySqlConnector::getStats();
printStats($stats);

// Helper functions
function printStats($stats) {
    echo "   Active: {$stats['active_connections']}/{$stats['max_connections']}\n";
    echo "   Available: {$stats['available_connections']}\n";
    echo "   Queue: {$stats['wait_queue_size']}\n";
    echo "   Hit Rate: " . sprintf('%.2f%%', $stats['hit_rate'] * 100) . "\n";
    echo "   Total Requests: {$stats['total_requests']}\n";
}

function executeQuery($num) {
    try {
        $result = DB::select("SELECT SLEEP(0.1), ? as num", [$num]);
        return true;
    } catch (\Exception $e) {
        return false;
    }
}

echo "\n✅ Connection pool test completed!\n";