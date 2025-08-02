<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "🔍 Testing Login Functionality\n";
echo "==============================\n\n";

// Test 1: Check if login pages are accessible
echo "1. Testing Login Page Accessibility:\n";
$urls = [
    'Admin' => 'https://api.askproai.de/admin/login',
    'Business' => 'https://api.askproai.de/business/login'
];

foreach ($urls as $name => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "   - $name Login: HTTP $httpCode " . ($httpCode == 200 ? '✅' : '❌') . "\n";
}

// Test 2: Check session configuration
echo "\n2. Session Configuration:\n";
echo "   - Driver: " . config('session.driver') . "\n";
echo "   - Cookie: " . config('session.cookie') . "\n";
echo "   - Secure: " . (config('session.secure') ? 'true ✅' : 'false ❌') . "\n";
echo "   - Path: " . config('session.path') . "\n";
echo "   - Domain: " . (config('session.domain') ?: 'null') . "\n";

// Test 3: Check middleware groups
echo "\n3. Middleware Groups:\n";
$middlewareGroups = [
    'web' => app()->make('router')->getMiddlewareGroups()['web'] ?? [],
    'admin' => app()->make('router')->getMiddlewareGroups()['admin'] ?? [],
    'business-portal' => app()->make('router')->getMiddlewareGroups()['business-portal'] ?? [],
];

foreach ($middlewareGroups as $group => $middlewares) {
    echo "   - $group: " . count($middlewares) . " middleware(s)\n";
    if ($group == 'admin' && in_array('web', $middlewares)) {
        echo "     ✅ Extends web middleware\n";
    }
}

// Test 4: Test users exist
echo "\n4. Test Users:\n";
$adminUser = \App\Models\User::where('email', 'fabian@askproai.de')->first();
$portalUser = \App\Models\PortalUser::where('email', 'demo@askproai.de')->first();

echo "   - Admin User (fabian@askproai.de): " . ($adminUser ? "Found ✅" : "Not found ❌") . "\n";
echo "   - Portal User (demo@askproai.de): " . ($portalUser ? "Found ✅" : "Not found ❌") . "\n";

// Test 5: Check routes
echo "\n5. Routes:\n";
$routes = [
    'Admin Login' => Route::has('filament.admin.auth.login'),
    'Business Login' => Route::has('business.login'),
    'Business Login POST' => Route::has('business.login.post'),
];

foreach ($routes as $name => $exists) {
    echo "   - $name: " . ($exists ? "Exists ✅" : "Missing ❌") . "\n";
}

echo "\n✅ Test completed!\n";
echo "\nNext steps:\n";
echo "1. Try logging in to Admin: https://api.askproai.de/admin/login\n";
echo "2. Try logging in to Business: https://api.askproai.de/business/login\n";