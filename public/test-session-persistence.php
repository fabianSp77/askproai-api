<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Set up environment
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "🔍 Testing Session Persistence\n";
echo "==============================\n\n";

// Test 1: Check session configuration
echo "1. Session Configuration:\n";
echo "   - Driver: " . config('session.driver') . "\n";
echo "   - Cookie Name: " . config('session.cookie') . "\n";
echo "   - Cookie Path: " . config('session.path') . "\n";
echo "   - Cookie Domain: " . (config('session.domain') ?: 'null') . "\n";
echo "   - Secure Cookie: " . (config('session.secure') ? 'true' : 'false') . "\n";
echo "   - HTTP Only: " . (config('session.http_only') ? 'true' : 'false') . "\n";
echo "   - Same Site: " . config('session.same_site') . "\n";

// Test 2: Check session directories
echo "\n2. Session Directories:\n";
$sessionPaths = [
    'Default' => storage_path('framework/sessions'),
    'Portal' => storage_path('framework/sessions/portal'),
];

foreach ($sessionPaths as $name => $path) {
    if (file_exists($path)) {
        $writable = is_writable($path);
        $fileCount = count(glob($path . '/*'));
        echo "   - $name: " . ($writable ? "✅ Writable" : "❌ Not writable") . " ($fileCount files)\n";
    } else {
        echo "   - $name: ❌ Directory doesn't exist\n";
    }
}

// Test 3: Test session functionality
echo "\n3. Session Test:\n";
try {
    // Start a new session
    session()->start();
    $sessionId = session()->getId();
    
    // Set test data
    session(['test_key' => 'test_value_' . time()]);
    session()->save();
    
    echo "   - Session ID: $sessionId\n";
    echo "   - Test value set: " . session('test_key') . "\n";
    
    // Check if session file exists
    $sessionFile = storage_path('framework/sessions/' . $sessionId);
    if (file_exists($sessionFile)) {
        echo "   - Session file exists: ✅\n";
        $content = file_get_contents($sessionFile);
        echo "   - File size: " . strlen($content) . " bytes\n";
    } else {
        echo "   - Session file exists: ❌\n";
    }
} catch (Exception $e) {
    echo "   - Error: " . $e->getMessage() . "\n";
}

// Test 4: Check middleware groups
echo "\n4. Middleware Configuration:\n";
$middlewareGroups = app()->make('router')->getMiddlewareGroups();
foreach (['web', 'admin', 'business-portal'] as $group) {
    if (isset($middlewareGroups[$group])) {
        $count = count($middlewareGroups[$group]);
        echo "   - $group: $count middleware(s)\n";
        
        // Check for session middleware
        $hasSessionStart = false;
        foreach ($middlewareGroups[$group] as $middleware) {
            if (strpos($middleware, 'StartSession') !== false) {
                $hasSessionStart = true;
                break;
            }
        }
        echo "     " . ($hasSessionStart ? "✅ Has StartSession" : "❌ Missing StartSession") . "\n";
    }
}

// Test 5: Check guards
echo "\n5. Auth Guards:\n";
$guards = ['web', 'portal'];
foreach ($guards as $guard) {
    $provider = config("auth.guards.$guard.provider");
    echo "   - $guard: Provider = $provider\n";
}

echo "\n✅ Test completed!\n";
echo "\nRecommendations:\n";
echo "1. Ensure SESSION_SECURE_COOKIE=true in .env\n";
echo "2. Clear all caches: php artisan optimize:clear\n";
echo "3. Check browser cookies are not blocked\n";
echo "4. Try incognito/private browsing mode\n";