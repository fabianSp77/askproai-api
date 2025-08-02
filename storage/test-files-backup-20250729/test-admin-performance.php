<?php
// Simple admin performance test
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Log performance metrics
$startTime = microtime(true);
$memoryStart = memory_get_usage();

// Test 1: Direct database query
$start = microtime(true);
$calls = \App\Models\Call::limit(10)->get();
$dbTime = microtime(true) - $start;

// Test 2: With relationships
$start = microtime(true);
$callsWithRelations = \App\Models\Call::with(['company', 'branch'])->limit(10)->get();
$dbRelTime = microtime(true) - $start;

// Test 3: Session test
$start = microtime(true);
session()->put('test', 'value');
$sessionValue = session()->get('test');
$sessionTime = microtime(true) - $start;

// Test 4: Auth check
$start = microtime(true);
$authCheck = auth()->check();
$authTime = microtime(true) - $start;

$totalTime = microtime(true) - $startTime;
$memoryUsed = memory_get_usage() - $memoryStart;

header('Content-Type: text/plain');
echo "=== Admin Performance Test ===\n\n";
echo "Database Query (10 calls): " . number_format($dbTime * 1000, 2) . " ms\n";
echo "With Relations: " . number_format($dbRelTime * 1000, 2) . " ms\n";
echo "Session Test: " . number_format($sessionTime * 1000, 2) . " ms\n";
echo "Auth Check: " . number_format($authTime * 1000, 2) . " ms\n";
echo "\nTotal Time: " . number_format($totalTime * 1000, 2) . " ms\n";
echo "Memory Used: " . number_format($memoryUsed / 1024 / 1024, 2) . " MB\n";
echo "\nPHP Version: " . PHP_VERSION . "\n";
echo "Laravel Version: " . app()->version() . "\n";
echo "Loaded Extensions: " . count(get_loaded_extensions()) . "\n";

// Check for locks
echo "\n=== Process Check ===\n";
$processes = shell_exec('ps aux | grep php | wc -l');
echo "PHP Processes: " . trim($processes) . "\n";

// Check MySQL connections
$dbConnections = \DB::select("SHOW STATUS LIKE 'Threads_connected'");
echo "MySQL Connections: " . $dbConnections[0]->Value . "\n";

// Check Redis
try {
    $redis = app('redis')->connection();
    $redisInfo = $redis->info();
    echo "Redis Connected: Yes\n";
    echo "Redis Memory: " . number_format($redisInfo['used_memory'] / 1024 / 1024, 2) . " MB\n";
} catch (\Exception $e) {
    echo "Redis: " . $e->getMessage() . "\n";
}