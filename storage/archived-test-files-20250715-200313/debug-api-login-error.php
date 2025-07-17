<?php
/**
 * Debug API Login Error - Find the exact issue
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

echo "=== API LOGIN ERROR DEBUG ===\n\n";

// 1. Clear the log first
echo "1. Clearing old logs...\n";
file_put_contents(storage_path('logs/laravel.log'), '');

// 2. Make a test API call
echo "2. Making test API call...\n";

$testUser = \App\Models\PortalUser::first();
if (!$testUser) {
    // Create test user
    $testUser = \App\Models\PortalUser::create([
        'name' => 'Debug Test User',
        'email' => 'debug@test.de',
        'password' => bcrypt('password'),
        'company_id' => 1,
        'is_active' => true
    ]);
    echo "Created test user: {$testUser->email}\n";
}

// Make the API request
$request = \Illuminate\Http\Request::create('/api/v2/portal/auth/login', 'POST', [
    'email' => $testUser->email,
    'password' => 'password'
]);

$request->headers->set('Accept', 'application/json');
$request->headers->set('Content-Type', 'application/json');

try {
    $response = $kernel->handle($request);
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content: " . substr($response->getContent(), 0, 500) . "\n";
} catch (\Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

// 3. Check the log file for the exact error
echo "\n3. Checking log file...\n";
$logContent = file_get_contents(storage_path('logs/laravel.log'));
if ($logContent) {
    // Find the most recent error
    $lines = explode("\n", $logContent);
    $errorLines = [];
    foreach ($lines as $line) {
        if (strpos($line, 'ERROR') !== false || strpos($line, 'error') !== false) {
            $errorLines[] = $line;
        }
    }
    
    if (!empty($errorLines)) {
        echo "Found errors:\n";
        foreach (array_slice($errorLines, -5) as $error) {
            echo $error . "\n";
        }
    } else {
        echo "No errors in log file\n";
    }
} else {
    echo "Log file is empty\n";
}

// 4. Test direct authentication
echo "\n4. Testing direct authentication...\n";
$canAuth = \Auth::guard('portal')->attempt([
    'email' => $testUser->email,
    'password' => 'password'
]);

echo "Direct auth result: " . ($canAuth ? 'SUCCESS' : 'FAILED') . "\n";

if ($canAuth) {
    echo "User authenticated successfully\n";
    $user = \Auth::guard('portal')->user();
    
    // Try to create token
    try {
        $token = $user->createToken('test-token')->plainTextToken;
        echo "Token created successfully: " . substr($token, 0, 20) . "...\n";
    } catch (\Exception $e) {
        echo "Token creation failed: " . $e->getMessage() . "\n";
    }
}

// 5. Check route exists
echo "\n5. Checking if route exists...\n";
$router = app('router');
$routes = $router->getRoutes();
$found = false;

foreach ($routes as $route) {
    if ($route->uri() === 'api/v2/portal/auth/login' && in_array('POST', $route->methods())) {
        $found = true;
        echo "✅ Route found: POST /api/v2/portal/auth/login\n";
        echo "   Action: " . $route->getActionName() . "\n";
        break;
    }
}

if (!$found) {
    echo "❌ Route NOT found!\n";
}

echo "\n=== DIAGNOSIS COMPLETE ===\n";