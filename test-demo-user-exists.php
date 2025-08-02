<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Boot the application
$kernel->bootstrap();

echo "=== Checking Portal Demo User ===\n\n";

// Check if demo user exists
$user = \App\Models\PortalUser::withoutGlobalScopes()
    ->where('email', 'demo@askproai.de')
    ->first();

if ($user) {
    echo "✅ Demo user exists:\n";
    echo "   ID: " . $user->id . "\n";
    echo "   Email: " . $user->email . "\n";
    echo "   Name: " . $user->name . "\n";
    echo "   Company ID: " . $user->company_id . "\n";
    echo "   Is Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
    echo "   Created: " . $user->created_at . "\n";
    
    // Test password
    $testPassword = 'password';
    if (\Hash::check($testPassword, $user->password)) {
        echo "   Password 'password': ✅ Valid\n";
    } else {
        echo "   Password 'password': ❌ Invalid\n";
        
        // Try to set it
        echo "\n   Setting password to 'password'...\n";
        $user->password = \Hash::make('password');
        $user->save();
        echo "   Password updated!\n";
    }
} else {
    echo "❌ Demo user does not exist!\n\n";
    echo "Creating demo user...\n";
    
    // Get first company
    $company = \App\Models\Company::first();
    if (!$company) {
        echo "❌ No company exists! Cannot create user.\n";
        exit(1);
    }
    
    $user = \App\Models\PortalUser::create([
        'name' => 'Demo User',
        'email' => 'demo@askproai.de',
        'password' => \Hash::make('password'),
        'company_id' => $company->id,
        'is_active' => true,
    ]);
    
    echo "✅ Demo user created:\n";
    echo "   ID: " . $user->id . "\n";
    echo "   Company: " . $company->name . " (ID: " . $company->id . ")\n";
}

echo "\n=== Testing Login Process ===\n";

// Create a request
$request = \Illuminate\Http\Request::create(
    'https://api.askproai.de/business/login',
    'POST',
    [
        'email' => 'demo@askproai.de',
        'password' => 'password',
    ]
);

// Configure session for portal
config([
    'session.cookie' => 'askproai_portal_session',
    'session.files' => storage_path('framework/sessions/portal'),
]);

// Start session
session()->start();
$sessionIdBefore = session()->getId();
echo "Session ID before login: $sessionIdBefore\n";

// Attempt login directly
$credentials = ['email' => 'demo@askproai.de', 'password' => 'password'];
if (\Auth::guard('portal')->attempt($credentials)) {
    echo "✅ Direct login successful!\n";
    $authUser = \Auth::guard('portal')->user();
    echo "   User ID: " . $authUser->id . "\n";
    echo "   Session ID after: " . session()->getId() . "\n";
    
    // Check session data
    $sessionKey = 'login_portal_' . sha1(\App\Models\PortalUser::class);
    echo "   Session key: $sessionKey\n";
    echo "   Session has key: " . (session()->has($sessionKey) ? 'Yes' : 'No') . "\n";
    echo "   Session value: " . session($sessionKey) . "\n";
} else {
    echo "❌ Direct login failed!\n";
}

echo "\nDone.\n";