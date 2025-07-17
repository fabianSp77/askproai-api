<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "Debug Login Process\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Check Portal User
echo "1. Portal User Check:\n";
$email = 'demo@example.com';
$password = 'demo123';

$user = \App\Models\PortalUser::withoutGlobalScopes()->where('email', $email)->first();

if (!$user) {
    echo "   ✗ User not found\n";
} else {
    echo "   ✓ User found\n";
    echo "   - ID: {$user->id}\n";
    echo "   - Name: {$user->name}\n";
    echo "   - Company ID: {$user->company_id}\n";
    echo "   - Is Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
    echo "   - Has 2FA: " . ($user->two_factor_secret ? 'Yes' : 'No') . "\n";
    
    // Check password
    echo "\n2. Password Check:\n";
    if (Hash::check($password, $user->password)) {
        echo "   ✓ Password is correct\n";
    } else {
        echo "   ✗ Password is incorrect\n";
        echo "   - Stored hash: " . substr($user->password, 0, 30) . "...\n";
        echo "   - New hash would be: " . substr(Hash::make($password), 0, 30) . "...\n";
    }
    
    // Try actual login
    echo "\n3. Login Attempt:\n";
    try {
        $result = Auth::guard('portal')->attempt(['email' => $email, 'password' => $password]);
        if ($result) {
            echo "   ✓ Login successful\n";
            echo "   - Auth check: " . (Auth::guard('portal')->check() ? 'Yes' : 'No') . "\n";
            echo "   - User ID: " . Auth::guard('portal')->id() . "\n";
            Auth::guard('portal')->logout();
        } else {
            echo "   ✗ Login failed\n";
        }
    } catch (\Exception $e) {
        echo "   ✗ Exception: " . $e->getMessage() . "\n";
    }
}

// 4. Check Admin User
echo "\n4. Admin User Check:\n";
$adminEmail = 'admin@askproai.de';
$adminUser = \App\Models\User::where('email', $adminEmail)->first();

if (!$adminUser) {
    echo "   ✗ Admin not found\n";
} else {
    echo "   ✓ Admin found\n";
    echo "   - ID: {$adminUser->id}\n";
    echo "   - Name: {$adminUser->name}\n";
    
    if (Hash::check($password, $adminUser->password)) {
        echo "   ✓ Password is correct\n";
    } else {
        echo "   ✗ Password is incorrect\n";
    }
    
    // Try admin login
    try {
        $result = Auth::guard('web')->attempt(['email' => $adminEmail, 'password' => $password]);
        if ($result) {
            echo "   ✓ Admin login successful\n";
            Auth::guard('web')->logout();
        } else {
            echo "   ✗ Admin login failed\n";
        }
    } catch (\Exception $e) {
        echo "   ✗ Exception: " . $e->getMessage() . "\n";
    }
}

// 5. Check session configuration
echo "\n5. Session Configuration:\n";
echo "   - Driver: " . config('session.driver') . "\n";
echo "   - Table: " . config('session.table') . "\n";
echo "   - Domain: " . config('session.domain') . "\n";
echo "   - Secure: " . (config('session.secure') ? 'Yes' : 'No') . "\n";

// 6. Check auth configuration
echo "\n6. Auth Configuration:\n";
echo "   - Default guard: " . config('auth.defaults.guard') . "\n";
echo "   - Portal guard driver: " . config('auth.guards.portal.driver') . "\n";
echo "   - Portal provider: " . config('auth.guards.portal.provider') . "\n";

echo "\n" . str_repeat("=", 50) . "\n";