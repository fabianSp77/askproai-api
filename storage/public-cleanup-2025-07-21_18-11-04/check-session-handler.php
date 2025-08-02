<?php
/**
 * Check Session Handler Configuration
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

// Get the session manager
$sessionManager = app('session');
$store = $sessionManager->driver();

echo "<h1>Session Handler Check</h1>";
echo "<pre>";

// 1. Check handler
echo "1. Session Handler:\n";
echo "   Driver: " . config('session.driver') . "\n";
echo "   Store Class: " . get_class($store) . "\n";
echo "   Handler Class: " . get_class($store->getHandler()) . "\n";

// 2. Check if handler can write
echo "\n2. Handler Write Test:\n";
$testId = 'test_' . uniqid();
$testData = serialize(['test' => 'data', 'time' => time()]);

try {
    $result = $store->getHandler()->write($testId, $testData);
    echo "   Write Result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Try to read it back
    $readData = $store->getHandler()->read($testId);
    echo "   Read Back: " . ($readData === $testData ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Clean up
    $store->getHandler()->destroy($testId);
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// 3. Check session path
echo "\n3. Session Storage:\n";
$sessionPath = storage_path('framework/sessions');
echo "   Path: " . $sessionPath . "\n";
echo "   Exists: " . (is_dir($sessionPath) ? 'YES' : 'NO') . "\n";
echo "   Writable: " . (is_writable($sessionPath) ? 'YES' : 'NO') . "\n";

// 4. Current session
echo "\n4. Current Session:\n";
echo "   ID: " . session()->getId() . "\n";
echo "   Started: " . (session()->isStarted() ? 'YES' : 'NO') . "\n";
echo "   Data: " . print_r(session()->all(), true);

// 5. Test session persistence
if (isset($_GET['test'])) {
    $counter = session('counter', 0);
    $counter++;
    session(['counter' => $counter]);
    session()->save();
    
    echo "\n5. Session Persistence Test:\n";
    echo "   Counter: " . $counter . "\n";
    echo "   (Refresh with ?test=1 to see if it increments)\n";
}

echo "</pre>";

echo '<p><a href="?test=1">Test Session Persistence</a></p>';
?>