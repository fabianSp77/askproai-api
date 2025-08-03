<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

// Bootstrap the application
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Testing Login Controller Flow ===\n\n";

// Create a login request
$request = Illuminate\Http\Request::create(
    '/business/login',
    'POST',
    [
        '_token' => 'test-token', // Will be ignored in this test
        'email' => 'demo@askproai.de',
        'password' => 'password'
    ]
);

// Set up the request
$request->setLaravelSession($app['session']->driver());

// Get the controller
$controller = new \App\Http\Controllers\Portal\Auth\LoginController();

// Test user lookup manually
echo "1. Testing user lookup (as LoginController does it):\n";
$user = \App\Models\PortalUser::withoutGlobalScope(\App\Scopes\CompanyScope::class)
    ->where('email', 'demo@askproai.de')
    ->first();

if ($user) {
    echo "   ✅ User found: ID {$user->id}\n";
    echo "   - Active: " . ($user->is_active ? 'YES' : 'NO') . "\n";
    echo "   - Company ID: {$user->company_id}\n";
    
    // Test password
    $passwordValid = \Illuminate\Support\Facades\Hash::check('password', $user->password);
    echo "   - Password valid: " . ($passwordValid ? 'YES' : 'NO') . "\n";
} else {
    echo "   ❌ User NOT found\n";
}

// Test auth attempt
echo "\n2. Testing auth attempt:\n";
$attempt = auth()->guard('portal')->attempt([
    'email' => 'demo@askproai.de',
    'password' => 'password'
]);
echo "   Result: " . ($attempt ? 'SUCCESS' : 'FAILED') . "\n";

// Check what provider is being used
echo "\n3. Auth configuration:\n";
$guard = auth()->guard('portal');
$provider = $guard->getProvider();
echo "   Provider class: " . get_class($provider) . "\n";
echo "   Model class: " . get_class($provider->createModel()) . "\n";

// Test provider directly
echo "\n4. Testing provider directly:\n";
$retrievedUser = $provider->retrieveByCredentials([
    'email' => 'demo@askproai.de'
]);
if ($retrievedUser) {
    echo "   ✅ User retrieved by provider\n";
    echo "   - ID: {$retrievedUser->id}\n";
    echo "   - Email: {$retrievedUser->email}\n";
    
    // Test password validation
    $valid = $provider->validateCredentials($retrievedUser, ['password' => 'password']);
    echo "   - Password validation: " . ($valid ? 'PASS' : 'FAIL') . "\n";
} else {
    echo "   ❌ Provider could not retrieve user\n";
}

// Check if there's a company scope issue
echo "\n5. Checking scopes:\n";
$globalScopes = (new \App\Models\PortalUser)->getGlobalScopes();
echo "   Global scopes on PortalUser: " . json_encode(array_keys($globalScopes)) . "\n";

$kernel->terminate($request, null);