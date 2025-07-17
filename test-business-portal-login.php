<?php
// Test Business Portal Login

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a fake request
$request = Illuminate\Http\Request::create('/business/login', 'POST', [
    'email' => 'demo@example.com',
    'password' => 'password123',
    '_token' => 'test-token'
]);

$app->instance('request', $request);

// Bootstrap the app
$kernel->bootstrap();

// Test user lookup
echo "=== Testing User Lookup ===\n";
$user = \App\Models\PortalUser::withoutGlobalScopes()
    ->where('email', 'demo@example.com')
    ->first();

if ($user) {
    echo "✓ User found: {$user->email}\n";
    echo "  ID: {$user->id}\n";
    echo "  Company ID: {$user->company_id}\n";
    echo "  Active: " . ($user->is_active ? 'YES' : 'NO') . "\n";
    echo "  Password: " . ($user->password ? 'SET' : 'NOT SET') . "\n";
    
    // Test password
    $testPassword = 'password123';
    $isValid = \Illuminate\Support\Facades\Hash::check($testPassword, $user->password);
    echo "  Password 'password123' valid: " . ($isValid ? 'YES' : 'NO') . "\n";
    
    // Check company
    $company = \App\Models\Company::withoutGlobalScopes()->find($user->company_id);
    if ($company) {
        echo "\n✓ Company found: {$company->name}\n";
        echo "  Active: " . ($company->is_active ? 'YES' : 'NO') . "\n";
    } else {
        echo "\n✗ Company NOT found!\n";
    }
} else {
    echo "✗ User NOT found!\n";
}

// Test session
echo "\n=== Testing Session ===\n";
session()->start();
echo "Session ID: " . session()->getId() . "\n";
echo "Session Cookie: " . config('session.cookie') . "\n";

// Test CSRF
echo "\n=== Testing CSRF ===\n";
session()->regenerateToken();
$token = csrf_token();
echo "CSRF Token generated: " . substr($token, 0, 10) . "...\n";

// Test login route
echo "\n=== Testing Login Route ===\n";
$route = app('router')->getRoutes()->getByName('business.login.post');
if ($route) {
    echo "✓ Login route exists\n";
    echo "  Action: " . $route->getActionName() . "\n";
    echo "  Middleware: " . implode(', ', $route->middleware()) . "\n";
} else {
    echo "✗ Login route NOT found!\n";
}

echo "\n✅ Test complete.\n";