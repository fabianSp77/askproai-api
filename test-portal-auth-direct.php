<?php
// Direct authentication test using Laravel's auth system
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "=== Testing Portal Authentication Directly ===\n\n";

// 1. Test user retrieval
echo "1. Testing user retrieval:\n";
$user = \App\Models\PortalUser::withoutGlobalScope(\App\Scopes\CompanyScope::class)
    ->where('email', 'demo@askproai.de')
    ->first();

if ($user) {
    echo "   ✓ User found: {$user->email} (ID: {$user->id})\n";
    echo "   - Company ID: {$user->company_id}\n";
    echo "   - Active: " . ($user->is_active ? 'YES' : 'NO') . "\n";
    echo "   - Password hash: " . substr($user->password, 0, 30) . "...\n";
} else {
    echo "   ✗ User not found\n";
    exit(1);
}

// 2. Test password
echo "\n2. Testing password verification:\n";
$passwordCorrect = \Illuminate\Support\Facades\Hash::check('password', $user->password);
echo "   Password 'password' is " . ($passwordCorrect ? 'CORRECT' : 'INCORRECT') . "\n";

// 3. Test auth attempt
echo "\n3. Testing authentication attempt:\n";
$credentials = [
    'email' => 'demo@askproai.de',
    'password' => 'password'
];

// Clear any existing sessions
auth()->guard('portal')->logout();

// Try to authenticate
$attempt = auth()->guard('portal')->attempt($credentials);
echo "   Auth attempt result: " . ($attempt ? 'SUCCESS' : 'FAILED') . "\n";

if ($attempt) {
    echo "   - User logged in: " . auth()->guard('portal')->user()->email . "\n";
    echo "   - User ID: " . auth()->guard('portal')->id() . "\n";
} else {
    // Manual authentication test
    echo "\n4. Manual authentication test:\n";
    
    // Get the provider
    $provider = auth()->guard('portal')->getProvider();
    echo "   Provider class: " . get_class($provider) . "\n";
    
    // Retrieve user
    $retrievedUser = $provider->retrieveByCredentials(['email' => 'demo@askproai.de']);
    if ($retrievedUser) {
        echo "   ✓ User retrieved by provider\n";
        
        // Validate credentials
        $valid = $provider->validateCredentials($retrievedUser, ['password' => 'password']);
        echo "   Password validation: " . ($valid ? 'PASS' : 'FAIL') . "\n";
        
        if (!$valid) {
            // Test raw password check
            echo "\n   Debug password check:\n";
            echo "   - Provided password: 'password'\n";
            echo "   - User password hash: " . $retrievedUser->password . "\n";
            echo "   - Hash::check result: " . (Hash::check('password', $retrievedUser->password) ? 'TRUE' : 'FALSE') . "\n";
            
            // Try to manually verify the hash
            $info = password_get_info($retrievedUser->password);
            echo "   - Hash algorithm: " . $info['algoName'] . "\n";
        }
    } else {
        echo "   ✗ Provider could not retrieve user\n";
    }
}

// 5. Check session configuration
echo "\n5. Session configuration:\n";
echo "   - Default guard: " . config('auth.defaults.guard') . "\n";
echo "   - Portal guard driver: " . config('auth.guards.portal.driver') . "\n";
echo "   - Portal guard provider: " . config('auth.guards.portal.provider') . "\n";
echo "   - Portal provider driver: " . config('auth.providers.portal_users.driver') . "\n";
echo "   - Portal provider model: " . config('auth.providers.portal_users.model') . "\n";

$kernel->terminate($request, $response);