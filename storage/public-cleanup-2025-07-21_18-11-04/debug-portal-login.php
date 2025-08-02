<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

echo "=== Portal Login Debug ===\n\n";

// 1. Check auth configuration
echo "1. Auth Configuration:\n";
$portalGuard = config('auth.guards.portal');
echo "Portal Guard:\n";
print_r($portalGuard);
echo "\n";

$portalProvider = config('auth.providers.portal_users');
echo "Portal Provider:\n";
print_r($portalProvider);
echo "\n";

// 2. Check demo user
echo "2. Demo User Check:\n";
$demoUser = PortalUser::where('email', 'demo@askproai.de')->first();
if ($demoUser) {
    echo "✓ User found\n";
    echo "  ID: {$demoUser->id}\n";
    echo "  Email: {$demoUser->email}\n";
    echo "  Active: " . ($demoUser->is_active ? 'Yes' : 'No') . "\n";
    echo "  Company: " . ($demoUser->company ? $demoUser->company->name : 'None') . "\n";
    
    // Check password
    $passwordValid = Hash::check('demo123', $demoUser->password);
    echo "  Password 'demo123' valid: " . ($passwordValid ? 'Yes' : 'No') . "\n";
} else {
    echo "✗ User not found\n";
}

// 3. Test authentication manually
echo "\n3. Manual Authentication Test:\n";
$credentials = [
    'email' => 'demo@askproai.de',
    'password' => 'demo123'
];

// Try to authenticate
$attemptResult = Auth::guard('portal')->attempt($credentials);
echo "Auth attempt result: " . ($attemptResult ? 'SUCCESS' : 'FAILED') . "\n";

if (!$attemptResult) {
    // Debug why it failed
    echo "\n4. Debugging Failed Login:\n";
    
    // Check if user can be retrieved
    $user = PortalUser::where('email', $credentials['email'])->first();
    if ($user) {
        echo "✓ User can be retrieved from database\n";
        
        // Check password manually
        $passwordOk = Hash::check($credentials['password'], $user->password);
        echo "Password check: " . ($passwordOk ? 'PASS' : 'FAIL') . "\n";
        
        // Check if model implements required contracts
        $implements = class_implements($user);
        echo "User model implements:\n";
        foreach ($implements as $interface) {
            echo "  - $interface\n";
        }
        
        // Check if authenticatable
        if ($user instanceof \Illuminate\Contracts\Auth\Authenticatable) {
            echo "✓ User is Authenticatable\n";
        } else {
            echo "✗ User is NOT Authenticatable\n";
        }
        
        // Try manual login
        Auth::guard('portal')->login($user);
        if (Auth::guard('portal')->check()) {
            echo "✓ Manual login successful!\n";
        } else {
            echo "✗ Manual login failed\n";
        }
    } else {
        echo "✗ Cannot retrieve user from database\n";
    }
}

// 5. Check session configuration
echo "\n5. Session Configuration:\n";
echo "Driver: " . config('session.driver') . "\n";
echo "Domain: " . config('session.domain') . "\n";
echo "Path: " . config('session.path') . "\n";
echo "Secure: " . (config('session.secure') ? 'Yes' : 'No') . "\n";

echo "\n=== Debug Complete ===\n";