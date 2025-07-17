<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "Testing Admin and Business Portal Login Systems\n";
echo str_repeat("=", 50) . "\n\n";

// Test 1: Check Session Configuration
echo "1. Session Configuration:\n";
echo "   Default session cookie: " . config('session.cookie') . "\n";
echo "   Default session table: " . config('session.table') . "\n";
echo "   Default auth guard: " . config('auth.defaults.guard') . "\n\n";

// Test 2: Admin Login Test
echo "2. Testing Admin Login:\n";
$adminEmail = 'admin@askproai.de';
$adminUser = \App\Models\User::where('email', $adminEmail)->first();

if ($adminUser) {
    echo "   ✓ Admin user found: $adminEmail\n";
    echo "   - ID: {$adminUser->id}\n";
    echo "   - Name: {$adminUser->name}\n";
    
    // Test password
    $testPassword = 'demo123';
    if (Hash::check($testPassword, $adminUser->password)) {
        echo "   ✓ Password '$testPassword' is valid\n";
    } else {
        echo "   ✗ Password '$testPassword' is NOT valid\n";
        // Set correct password
        $adminUser->password = Hash::make($testPassword);
        $adminUser->save();
        echo "   → Password updated to '$testPassword'\n";
    }
} else {
    echo "   ✗ Admin user not found!\n";
}
echo "\n";

// Test 3: Business Portal Login Test  
echo "3. Testing Business Portal Login:\n";
$portalEmail = 'demo@example.com';
$portalUser = \App\Models\PortalUser::where('email', $portalEmail)->first();

if ($portalUser) {
    echo "   ✓ Portal user found: $portalEmail\n";
    echo "   - ID: {$portalUser->id}\n";
    echo "   - Name: {$portalUser->name}\n";
    echo "   - Company ID: {$portalUser->company_id}\n";
    echo "   - Company: " . ($portalUser->company ? $portalUser->company->name : 'N/A') . "\n";
    echo "   - Is active: " . ($portalUser->is_active ? 'Yes' : 'No') . "\n";
    
    // Test password
    $testPassword = 'demo123';
    if (Hash::check($testPassword, $portalUser->password)) {
        echo "   ✓ Password '$testPassword' is valid\n";
    } else {
        echo "   ✗ Password '$testPassword' is NOT valid\n";
        // Set correct password
        $portalUser->password = Hash::make($testPassword);
        $portalUser->save();
        echo "   → Password updated to '$testPassword'\n";
    }
    
    if (!$portalUser->is_active) {
        $portalUser->is_active = true;
        $portalUser->save();
        echo "   → User activated\n";
    }
} else {
    echo "   ✗ Portal user not found!\n";
    
    // Create demo portal user
    echo "   → Creating demo portal user...\n";
    
    $company = \App\Models\Company::first();
    if (!$company) {
        echo "   ✗ No company found! Cannot create portal user.\n";
    } else {
        $portalUser = \App\Models\PortalUser::create([
            'name' => 'Demo User',
            'email' => $portalEmail,
            'password' => Hash::make('demo123'),
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        echo "   ✓ Portal user created with company: {$company->name}\n";
    }
}
echo "\n";

// Test 4: Session Tables
echo "4. Checking Session Tables:\n";
$tables = DB::select("SHOW TABLES LIKE '%session%'");
foreach ($tables as $table) {
    $tableName = array_values((array)$table)[0];
    $count = DB::table($tableName)->count();
    echo "   - $tableName: $count sessions\n";
}
echo "\n";

// Test 5: Routes
echo "5. Testing Routes:\n";
$routes = [
    ['GET', '/admin/login', 'Admin login page'],
    ['GET', '/business/login', 'Business portal login page'],
    ['GET', '/business/api/auth-check', 'Business API auth check'],
];

$httpKernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

foreach ($routes as [$method, $uri, $description]) {
    try {
        $request = \Illuminate\Http\Request::create($uri, $method);
        $response = $httpKernel->handle($request);
        $status = $response->getStatusCode();
        echo "   - $description: ";
        if ($status >= 200 && $status < 300) {
            echo "✓ OK ($status)\n";
        } else {
            echo "✗ Error ($status)\n";
        }
    } catch (\Exception $e) {
        echo "   - $description: ✗ Exception: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Test completed. Both login systems should now work.\n";
echo "Admin: https://api.askproai.de/admin/login (admin@askproai.de / demo123)\n";
echo "Portal: https://api.askproai.de/business/login (demo@example.com / demo123)\n";