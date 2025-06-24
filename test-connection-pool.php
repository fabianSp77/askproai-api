<?php

use Illuminate\Support\Facades\DB;
use App\Models\Call;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=================================\n";
echo "Connection Pool Load Test\n";
echo "=================================\n\n";

// Function to get current connection stats
function getConnectionStats() {
    $stats = DB::select("
        SELECT 
            (SELECT COUNT(*) FROM information_schema.processlist) as current_connections,
            (SELECT COUNT(*) FROM information_schema.processlist WHERE command = 'Sleep') as idle_connections
    ")[0];
    
    return [
        'current' => $stats->current_connections,
        'idle' => $stats->idle_connections
    ];
}

// Baseline stats
$baseline = getConnectionStats();
echo "Baseline connections: {$baseline['current']} (idle: {$baseline['idle']})\n\n";

// Test 1: Sequential queries
echo "Test 1: Running 50 sequential queries...\n";
$start = microtime(true);

for ($i = 0; $i < 50; $i++) {
    $count = Call::count();
    if ($i % 10 === 0) {
        echo "  Query $i completed\n";
    }
}

$sequential_time = microtime(true) - $start;
$after_sequential = getConnectionStats();
echo "  Time: " . round($sequential_time, 2) . "s\n";
echo "  Connections after: {$after_sequential['current']} (idle: {$after_sequential['idle']})\n\n";

// Test 2: Concurrent queries using curl_multi
echo "Test 2: Running 20 concurrent requests...\n";
$start = microtime(true);

// Create a simple endpoint that queries the database
$testEndpoint = <<<'PHP'
<?php
use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulate a typical request
DB::select('SELECT COUNT(*) as count FROM calls');
usleep(100000); // 100ms to simulate processing
DB::select('SELECT COUNT(*) as count FROM appointments');

echo json_encode(['status' => 'ok', 'pid' => getmypid()]);
PHP;

file_put_contents(__DIR__ . '/test-endpoint.php', $testEndpoint);

// Use multiple processes to simulate concurrent requests
$processes = [];
for ($i = 0; $i < 20; $i++) {
    $processes[] = proc_open(
        'php test-endpoint.php',
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes
    );
}

// Wait for all processes
foreach ($processes as $process) {
    proc_close($process);
}

$concurrent_time = microtime(true) - $start;
$after_concurrent = getConnectionStats();
echo "  Time: " . round($concurrent_time, 2) . "s\n";
echo "  Connections after: {$after_concurrent['current']} (idle: {$after_concurrent['idle']})\n\n";

// Clean up
unlink(__DIR__ . '/test-endpoint.php');

// Test 3: Connection pool behavior
echo "Test 3: Testing connection reuse...\n";
$connection_ids = [];

for ($i = 0; $i < 10; $i++) {
    $result = DB::select('SELECT CONNECTION_ID() as id')[0];
    $connection_ids[] = $result->id;
}

$unique_connections = count(array_unique($connection_ids));
echo "  Used $unique_connections unique connections for 10 queries\n";
echo "  Connection reuse rate: " . round((10 - $unique_connections) / 10 * 100, 2) . "%\n\n";

// Final stats
sleep(2); // Let connections settle
$final = getConnectionStats();
echo "Final connections: {$final['current']} (idle: {$final['idle']})\n";

// Summary
echo "\n=================================\n";
echo "Summary\n";
echo "=================================\n";
echo "Persistent connections: " . (config('database.connections.mysql.options.' . PDO::ATTR_PERSISTENT) ? 'ENABLED' : 'DISABLED') . "\n";
echo "Connection pooling: " . (config('database.pool.enabled') ? 'ENABLED' : 'DISABLED') . "\n";
echo "Max pool size: " . config('database.pool.max_connections', 'N/A') . "\n";

if ($after_concurrent['current'] > $baseline['current'] + 15) {
    echo "\n⚠️  WARNING: Connections increased significantly during concurrent load!\n";
    echo "This indicates connection pooling may not be working properly.\n";
} else {
    echo "\n✅ Connection count remained stable during load test.\n";
}