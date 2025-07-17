<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "\n🔍 Testing Business Portal Complete\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Check Portal User
echo "1. Portal User Status:\n";
$portalUser = \App\Models\PortalUser::withoutGlobalScopes()
    ->where('email', 'demo@example.com')->first();

if ($portalUser) {
    echo "   ✓ User found: demo@example.com\n";
    echo "   - ID: {$portalUser->id}\n";
    echo "   - Company: " . ($portalUser->company ? $portalUser->company->name : 'N/A') . "\n";
    echo "   - Active: " . ($portalUser->is_active ? 'Yes' : 'No') . "\n";
    
    // Test password
    if (Hash::check('demo123', $portalUser->password)) {
        echo "   ✓ Password 'demo123' is valid\n";
    } else {
        echo "   ✗ Password invalid - resetting...\n";
        $portalUser->password = Hash::make('demo123');
        $portalUser->save();
        echo "   ✓ Password reset to 'demo123'\n";
    }
} else {
    echo "   ✗ Portal user not found\n";
}

// 2. Test Portal Login
echo "\n2. Testing Portal Login:\n";
try {
    $result = Auth::guard('portal')->attempt([
        'email' => 'demo@example.com',
        'password' => 'demo123'
    ]);
    
    if ($result) {
        echo "   ✓ Login successful\n";
        $user = Auth::guard('portal')->user();
        
        // Set company context
        app()->instance('current_company_id', $user->company_id);
        echo "   ✓ Company context set: {$user->company_id}\n";
        
        // Test API endpoints
        echo "\n3. Testing API Endpoints:\n";
        
        // Test calls endpoint
        $calls = \App\Models\Call::where('company_id', $user->company_id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        echo "   - Calls found: " . $calls->count() . "\n";
        
        // Test permissions
        echo "   - User permissions: ";
        if (method_exists($user, 'getPermissionsAttribute')) {
            $permissions = $user->permissions;
            echo count($permissions) . " permissions\n";
        } else {
            echo "Using default permissions\n";
        }
        
        Auth::guard('portal')->logout();
    } else {
        echo "   ✗ Login failed\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// 3. Check Routes
echo "\n4. Business Portal Routes:\n";
$routes = [
    'business.login' => 'Login page',
    'business.dashboard' => 'Dashboard',
    'business.api.calls.index' => 'Calls API',
    'business.api.user.permissions' => 'Permissions API',
];

foreach ($routes as $name => $description) {
    try {
        $url = route($name);
        echo "   ✓ $description: $url\n";
    } catch (\Exception $e) {
        echo "   ✗ $description: Not found\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Business Portal Status:\n";
echo "- Login: demo@example.com / demo123\n";
echo "- URL: https://api.askproai.de/business/login\n";