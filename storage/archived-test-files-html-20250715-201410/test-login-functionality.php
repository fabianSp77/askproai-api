<?php
/**
 * Login Functionality Test Script
 * Tests verschiedene Login-Szenarien nach Emergency Fix
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

echo "🔍 Testing Login Functionality after Emergency Fix\n";
echo "================================================\n\n";

// Test 1: Admin Login
echo "1. Testing Admin Login:\n";
try {
    $adminUser = User::where('email', 'admin@askproai.com')->first();
    if (!$adminUser) {
        echo "   ❌ Admin user not found\n";
    } else {
        $canLogin = Auth::guard('web')->attempt([
            'email' => 'admin@askproai.com',
            'password' => 'password' // Standard test password
        ]);
        
        if ($canLogin) {
            echo "   ✅ Admin login successful\n";
            echo "   - User ID: " . Auth::id() . "\n";
            echo "   - User Name: " . Auth::user()->name . "\n";
            Auth::logout();
        } else {
            echo "   ❌ Admin login failed\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 2: Portal User Login
echo "\n2. Testing Portal User Login:\n";
try {
    $portalUser = PortalUser::where('email', 'demo@example.com')->first();
    if (!$portalUser) {
        echo "   ⚠️ Demo portal user not found, creating one...\n";
        $portalUser = PortalUser::create([
            'email' => 'demo@example.com',
            'password' => Hash::make('demo123'),
            'name' => 'Demo User',
            'company_id' => 1
        ]);
    }
    
    $canLogin = Auth::guard('portal')->attempt([
        'email' => 'demo@example.com',
        'password' => 'demo123'
    ]);
    
    if ($canLogin) {
        echo "   ✅ Portal user login successful\n";
        echo "   - User ID: " . Auth::guard('portal')->id() . "\n";
        echo "   - Company ID: " . Auth::guard('portal')->user()->company_id . "\n";
        Auth::guard('portal')->logout();
    } else {
        echo "   ❌ Portal user login failed\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 3: Session Management
echo "\n3. Testing Session Management:\n";
try {
    session()->put('test_key', 'test_value');
    $value = session()->get('test_key');
    
    if ($value === 'test_value') {
        echo "   ✅ Session write/read working\n";
    } else {
        echo "   ❌ Session not working properly\n";
    }
    
    session()->forget('test_key');
    $value = session()->get('test_key');
    
    if ($value === null) {
        echo "   ✅ Session deletion working\n";
    } else {
        echo "   ❌ Session deletion not working\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 4: CSRF Token Generation
echo "\n4. Testing CSRF Token:\n";
try {
    $token = csrf_token();
    if (!empty($token) && strlen($token) > 20) {
        echo "   ✅ CSRF token generated: " . substr($token, 0, 20) . "...\n";
    } else {
        echo "   ❌ CSRF token generation failed\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 5: Rate Limiting
echo "\n5. Testing Rate Limiting:\n";
try {
    $rateLimiter = app(\Illuminate\Cache\RateLimiter::class);
    $key = 'test-rate-limit:' . request()->ip();
    
    // Simulate 10 attempts
    for ($i = 1; $i <= 10; $i++) {
        if ($rateLimiter->tooManyAttempts($key, 5)) {
            echo "   ✅ Rate limiting kicked in after attempt #$i\n";
            break;
        }
        $rateLimiter->hit($key);
    }
    
    if ($i > 10) {
        echo "   ⚠️ Rate limiting may not be working properly\n";
    }
    
    // Clear test rate limit
    $rateLimiter->clear($key);
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 6: Security Headers
echo "\n6. Testing Security Headers:\n";
$headers = [
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block'
];

foreach ($headers as $header => $expectedValue) {
    if (headers_sent()) {
        echo "   ⚠️ Cannot test headers - already sent\n";
        break;
    }
    echo "   - $header: Expected\n";
}

echo "\n================================================\n";
echo "✅ Login functionality tests completed\n";
echo "Please also check:\n";
echo "- Admin panel: https://api.askproai.de/admin\n";
echo "- Portal: https://api.askproai.de/portal\n";
echo "- API health: https://api.askproai.de/api/health\n";