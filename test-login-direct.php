<?php
// Test login by calling the controller directly
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;

// Bootstrap the application
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/');
$response = $kernel->handle($request);

echo "=== Direct Login Test ===\n\n";

// Test 1: Check if demo user exists
$user = PortalUser::where('email', 'demo@askproai.de')->first();
if ($user) {
    echo "✅ Demo user found\n";
    echo "   ID: " . $user->id . "\n";
    echo "   Email: " . $user->email . "\n";
    echo "   Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
    echo "   Company ID: " . ($user->company_id ?? 'NULL') . "\n";
    
    // Test password
    $passwordValid = Hash::check('password123', $user->password);
    echo "   Password valid: " . ($passwordValid ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ Demo user NOT found\n";
}

echo "\n=== Testing LoginController Directly ===\n";

try {
    // Create a proper request
    $loginRequest = Illuminate\Http\Request::create(
        '/business/login',
        'POST',
        [
            'email' => 'demo@askproai.de',
            'password' => 'password123',
            '_token' => 'test-token'
        ]
    );
    
    // Set up session
    $loginRequest->setLaravelSession($app['session']->driver());
    
    // Get the controller
    $controller = new \App\Http\Controllers\Portal\Auth\LoginController();
    
    // Call login method
    $loginResponse = $controller->login($loginRequest);
    
    echo "Login response type: " . get_class($loginResponse) . "\n";
    
    if (method_exists($loginResponse, 'getStatusCode')) {
        echo "Status code: " . $loginResponse->getStatusCode() . "\n";
    }
    
    if (method_exists($loginResponse, 'getTargetUrl')) {
        echo "Redirect to: " . $loginResponse->getTargetUrl() . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Exception: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}

$kernel->terminate($request, $response);