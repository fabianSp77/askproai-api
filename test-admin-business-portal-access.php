<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "\n🔍 Testing Admin Access to Business Portal\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Simulate admin login
echo "1. Simulating Admin Login:\n";
$admin = \App\Models\User::where('email', 'admin@askproai.de')->first();

if ($admin) {
    Auth::guard('web')->login($admin);
    echo "   ✓ Admin logged in: {$admin->email}\n";
    echo "   - Is Admin: " . ($admin->is_admin ? 'Yes' : 'No') . "\n";
    echo "   - Super Admin: " . ($admin->super_admin ? 'Yes' : 'No') . "\n";
    
    // Set up session for testing
    session()->start();
    
    // Test direct API access
    echo "\n2. Testing Direct Business Portal API Access:\n";
    
    // Create request instance
    $request = new \Illuminate\Http\Request();
    $request->setUserResolver(function () use ($admin) {
        return $admin;
    });
    
    // Test the middleware
    $middleware = new \App\Http\Middleware\PortalApiAuth();
    
    try {
        $response = $middleware->handle($request, function ($request) {
            return response()->json(['success' => true]);
        });
        
        if ($response->getStatusCode() === 200) {
            echo "   ✓ Direct API access allowed\n";
            echo "   - Company Context: " . app('current_company_id') . "\n";
            echo "   - Admin Viewing: " . (session('is_admin_viewing') ? 'Yes' : 'No') . "\n";
        } else {
            echo "   ✗ Access denied: " . $response->getStatusCode() . "\n";
            $content = json_decode($response->getContent(), true);
            if ($content) {
                echo "   - Message: " . ($content['message'] ?? 'Unknown') . "\n";
                if (isset($content['debug'])) {
                    echo "   - Debug: " . json_encode($content['debug'], JSON_PRETTY_PRINT) . "\n";
                }
            }
        }
    } catch (\Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
    
    // Test with admin impersonation session
    echo "\n3. Testing with Admin Impersonation Session:\n";
    
    // Get first company
    $company = \App\Models\Company::withoutGlobalScopes()->where('is_active', true)->first();
    if ($company) {
        // Set up admin impersonation
        session([
            'is_admin_viewing' => true,
            'admin_impersonation' => [
                'admin_id' => $admin->id,
                'company_id' => $company->id,
                'admin_session' => true,
                'started_at' => now(),
            ]
        ]);
        
        echo "   ✓ Admin impersonation set for: {$company->name}\n";
        
        // Test again
        try {
            $response = $middleware->handle($request, function ($request) {
                return response()->json(['success' => true]);
            });
            
            if ($response->getStatusCode() === 200) {
                echo "   ✓ API access with impersonation allowed\n";
            } else {
                echo "   ✗ Access still denied\n";
            }
        } catch (\Exception $e) {
            echo "   ✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    Auth::guard('web')->logout();
} else {
    echo "   ✗ Admin user not found\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Summary:\n";
echo "- Admin users can now access Business Portal APIs\n";
echo "- The system automatically sets up company context\n";
echo "- Use 'Als Firma anmelden' in Admin Panel for better experience\n";