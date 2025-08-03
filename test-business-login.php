<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Testing Business Portal Login\n\n";

// Test 1: Check if portal user exists
echo "1️⃣ Checking portal user...\n";
$user = \App\Models\PortalUser::where('email', 'demo@askproai.de')->first();
if (!$user) {
    echo "❌ Portal user not found!\n";
    exit(1);
}
echo "✅ Portal user found: {$user->email}\n";
echo "✅ Company ID: {$user->company_id}\n";
echo "✅ Is Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";

// Test 2: Check password
echo "\n2️⃣ Testing password...\n";
$passwordCorrect = \Illuminate\Support\Facades\Hash::check('password123', $user->password);
echo $passwordCorrect ? "✅ Password is correct\n" : "❌ Password is incorrect\n";

if (!$passwordCorrect) {
    echo "Setting password to 'password123'...\n";
    $user->password = \Illuminate\Support\Facades\Hash::make('password123');
    $user->save();
    echo "✅ Password updated\n";
}

// Test 3: Check login controller
echo "\n3️⃣ Testing login controller...\n";
$controller = new \App\Http\Controllers\Portal\Auth\LoginController();

// Create a mock request
$request = new \Illuminate\Http\Request();
$request->setMethod('POST');
$request->merge([
    'email' => 'demo@askproai.de',
    'password' => 'password123'
]);

// Add session
$session = app('session.store');
$request->setLaravelSession($session);

// Add CSRF token
$token = csrf_token();
$request->merge(['_token' => $token]);
echo "✅ CSRF Token: " . substr($token, 0, 20) . "...\n";

// Try login
try {
    $response = $controller->login($request);
    
    if ($response instanceof \Illuminate\Http\RedirectResponse) {
        $targetUrl = $response->getTargetUrl();
        echo "✅ Login successful! Redirecting to: $targetUrl\n";
        
        // Check if user is authenticated
        if (\Illuminate\Support\Facades\Auth::guard('portal')->check()) {
            echo "✅ User is authenticated in portal guard\n";
        } else {
            echo "❌ User is NOT authenticated in portal guard\n";
        }
    } else {
        echo "❌ Login failed with response type: " . get_class($response) . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Login error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Test 4: Check session configuration
echo "\n4️⃣ Checking session configuration...\n";
echo "Session Driver: " . config('session.driver') . "\n";
echo "Session Cookie: " . config('session.cookie') . "\n";
echo "Portal Session Cookie: " . config('session_portal.cookie') . "\n";
echo "Session Domain: " . config('session.domain') . "\n";

echo "\n✅ Test complete!\n";