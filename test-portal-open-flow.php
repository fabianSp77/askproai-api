<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "\n🔍 Testing Portal Open Flow\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Simulate admin login
echo "1. Simulating Admin Login:\n";
$admin = \App\Models\User::where('email', 'admin@askproai.de')->first();

if ($admin) {
    Auth::guard('web')->login($admin);
    echo "   ✓ Admin logged in: {$admin->email}\n";
    
    // 2. Simulate clicking "Portal öffnen"
    echo "\n2. Simulating Portal Open Click:\n";
    
    $company = \App\Models\Company::withoutGlobalScopes()->where('name', 'Krückeberg Servicegruppe')->first();
    
    if ($company) {
        echo "   ✓ Company found: {$company->name} (ID: {$company->id})\n";
        
        // Generate token (like the Filament page does)
        $token = Str::random(32);
        $cacheKey = 'admin_portal_access_' . $token;
        
        cache()->put($cacheKey, [
            'admin_id' => $admin->id,
            'company_id' => $company->id,
            'redirect_to' => '/business',
            'created_at' => now(),
        ], now()->addMinutes(5));
        
        echo "   ✓ Token generated: {$token}\n";
        echo "   ✓ Cache data stored\n";
        
        // 3. Test the AdminAccessController flow
        echo "\n3. Testing AdminAccessController:\n";
        
        // Clear any existing portal session
        Auth::guard('portal')->logout();
        session()->flush();
        session()->regenerate();
        
        // Create a new request
        $request = new \Illuminate\Http\Request();
        $request->merge(['token' => $token]);
        
        // Create controller instance
        $controller = new \App\Http\Controllers\Portal\AdminAccessController();
        
        try {
            // Manually call the access method
            $response = $controller->access($request);
            
            echo "   ✓ Access method executed\n";
            echo "   - Response status: " . (method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 'redirect') . "\n";
            
            // Check portal auth
            if (Auth::guard('portal')->check()) {
                $portalUser = Auth::guard('portal')->user();
                echo "   ✓ Portal user logged in: {$portalUser->email}\n";
                echo "   - Company ID: {$portalUser->company_id}\n";
            } else {
                echo "   ✗ Portal user NOT logged in!\n";
            }
            
            // Check session data
            echo "\n4. Session Data:\n";
            echo "   - is_admin_viewing: " . (session('is_admin_viewing') ? 'Yes' : 'No') . "\n";
            echo "   - admin_viewing_company: " . session('admin_viewing_company') . "\n";
            echo "   - admin_impersonation: " . json_encode(session('admin_impersonation'), JSON_PRETTY_PRINT) . "\n";
            
        } catch (\Exception $e) {
            echo "   ✗ Error: " . $e->getMessage() . "\n";
            echo "   - File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        }
        
    } else {
        echo "   ✗ Company not found\n";
    }
    
    Auth::guard('web')->logout();
} else {
    echo "   ✗ Admin user not found\n";
}

echo "\n" . str_repeat("=", 50) . "\n";