<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Testing Authentication Workflows\n\n";

use App\Models\User;
use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Test 1: Admin User Authentication
echo "1️⃣ Testing Admin Authentication:\n";
try {
    $adminUser = User::where('email', 'admin@askproai.de')->first();
    if ($adminUser) {
        echo "   ✅ Admin user found: {$adminUser->email}\n";
        echo "   ✅ Company ID: {$adminUser->company_id}\n";
        
        // Test authentication
        Auth::login($adminUser);
        if (Auth::check()) {
            echo "   ✅ Admin authentication successful\n";
            
            // Test company context
            $userCompanyId = Auth::user()->company_id;
            echo "   ✅ User company context: $userCompanyId\n";
            
            // Test accessing data with TenantScope
            $branches = DB::table('branches')->where('company_id', $userCompanyId)->count();
            echo "   ✅ Can access branches: $branches found\n";
        }
        Auth::logout();
    } else {
        echo "   ❌ Admin user not found\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Portal User Authentication
echo "2️⃣ Testing Portal Authentication:\n";
try {
    $portalUser = PortalUser::where('email', 'demo@askproai.de')->first();
    if ($portalUser) {
        echo "   ✅ Portal user found: {$portalUser->email}\n";
        echo "   ✅ Company ID: {$portalUser->company_id}\n";
        
        // Test portal guard
        Auth::guard('portal')->login($portalUser);
        if (Auth::guard('portal')->check()) {
            echo "   ✅ Portal authentication successful\n";
            
            // Test company context
            $portalCompanyId = Auth::guard('portal')->user()->company_id;
            echo "   ✅ Portal company context: $portalCompanyId\n";
            
            // Test accessing customer data
            $customers = DB::table('customers')->where('company_id', $portalCompanyId)->count();
            echo "   ✅ Can access customers: $customers found\n";
        }
        Auth::guard('portal')->logout();
    } else {
        echo "   ❌ Portal user not found\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Rate Limiting
echo "3️⃣ Testing Rate Limiting:\n";
try {
    $key = 'test_auth_' . time();
    $rateLimiter = app(\Illuminate\Cache\RateLimiter::class);
    
    // Test hitting the rate limiter
    for ($i = 1; $i <= 6; $i++) {
        if ($rateLimiter->tooManyAttempts($key, 5)) {
            echo "   ✅ Rate limit triggered after $i attempts\n";
            break;
        }
        $rateLimiter->hit($key, 60);
        echo "   ✅ Attempt $i recorded\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: TenantScope Application
echo "4️⃣ Testing TenantScope:\n";
try {
    // Login as company 1 user
    $user1 = User::where('company_id', 1)->first();
    if ($user1) {
        Auth::login($user1);
        
        // Try to access calls - should only see company 1 calls
        $calls = \App\Models\Call::count();
        $company1Calls = DB::table('calls')->where('company_id', 1)->count();
        
        echo "   ✅ User from Company 1 logged in\n";
        echo "   ✅ Calls visible through model: $calls\n";
        echo "   ✅ Actual Company 1 calls: $company1Calls\n";
        
        if ($calls == $company1Calls) {
            echo "   ✅ TenantScope correctly filtering data!\n";
        } else {
            echo "   ❌ TenantScope not filtering correctly\n";
        }
        
        Auth::logout();
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Cross-Tenant Access Prevention
echo "5️⃣ Testing Cross-Tenant Access Prevention:\n";
try {
    $user1 = User::where('company_id', 1)->first();
    $user2 = User::where('company_id', 2)->first();
    
    if ($user1 && $user2) {
        // Login as user from company 1
        Auth::login($user1);
        
        // Try to access company 2 data directly
        $company2Customers = DB::table('customers')
            ->where('company_id', 2)
            ->count();
            
        // Through model (should be 0 due to scope)
        $visibleCustomers = \App\Models\Customer::where('company_id', 2)->count();
        
        echo "   ✅ Company 2 customers in DB: $company2Customers\n";
        echo "   ✅ Company 2 customers visible to Company 1 user: $visibleCustomers\n";
        
        if ($visibleCustomers == 0 && $company2Customers > 0) {
            echo "   ✅ Cross-tenant access correctly prevented!\n";
        } else {
            echo "   ❌ Cross-tenant access not properly prevented\n";
        }
        
        Auth::logout();
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Authentication workflow tests complete!\n";