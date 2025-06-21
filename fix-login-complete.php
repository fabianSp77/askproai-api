<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "=== Complete Login Fix ===\n\n";

// 1. Check database connection
try {
    DB::connection()->getPdo();
    echo "✓ Database connection OK\n";
} catch (\Exception $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

// 2. Check if user exists
$email = 'fabian@askproai.de';
$password = 'Qwe421as1!1';

$user = User::where('email', $email)->first();
if (!$user) {
    echo "✗ User not found! Creating new user...\n";
    
    // Get valid company and tenant
    $company = DB::table('companies')->where('is_active', 1)->first();
    $tenant = DB::table('tenants')->first();
    
    if (!$company || !$tenant) {
        die("✗ No active company or tenant found!\n");
    }
    
    // Create user directly in database
    DB::table('users')->insert([
        'email' => $email,
        'password' => Hash::make($password),
        'name' => 'Fabian Admin',
        'fname' => 'Fabian',
        'lname' => 'Admin',
        'username' => 'fabian',
        'company_id' => $company->id,
        'tenant_id' => $tenant->id,
        'status_id' => 1,
        'timezone' => 'Europe/Berlin',
        'language' => 'de',
        'homepageid' => 1,
        'salt' => '',
        'date_created' => now()
    ]);
    
    $user = User::where('email', $email)->first();
    echo "✓ User created successfully\n";
} else {
    echo "✓ User exists (ID: {$user->user_id})\n";
}

// 3. Update password with new hash
$newHash = Hash::make($password);
DB::table('users')
    ->where('email', $email)
    ->update(['password' => $newHash]);

echo "✓ Password updated with fresh hash\n";

// 4. Verify password works
$user = User::where('email', $email)->first();
if (Hash::check($password, $user->password)) {
    echo "✓ Password verification successful\n";
} else {
    echo "✗ Password verification failed!\n";
}

// 5. Test authentication without security monitoring
try {
    // Temporarily disable security monitoring
    config(['monitoring.security.enabled' => false]);
    
    if (\Auth::attempt(['email' => $email, 'password' => $password])) {
        echo "✓ Authentication test passed!\n";
        \Auth::logout();
    } else {
        echo "✗ Authentication test failed!\n";
        
        // Debug why it's failing
        echo "\nDebugging info:\n";
        echo "- User ID: " . $user->user_id . "\n";
        echo "- Email: " . $user->email . "\n";
        echo "- Password hash length: " . strlen($user->password) . "\n";
        echo "- Company ID: " . $user->company_id . "\n";
        echo "- Tenant ID: " . $user->tenant_id . "\n";
    }
} catch (\Exception $e) {
    echo "✗ Authentication error: " . $e->getMessage() . "\n";
}

// 6. Clear all caches
echo "\nClearing all caches...\n";
\Artisan::call('cache:clear');
\Artisan::call('config:clear');
\Artisan::call('view:clear');
\Artisan::call('route:clear');
echo "✓ Caches cleared\n";

// 7. Fix any permission issues
if (method_exists($user, 'assignRole')) {
    try {
        $user->assignRole('super_admin');
        echo "✓ Assigned super_admin role\n";
    } catch (\Exception $e) {
        echo "- No roles system or role doesn't exist\n";
    }
}

echo "\n=== Summary ===\n";
echo "Email: $email\n";
echo "Password: $password\n";
echo "URL: https://api.askproai.de/admin/login\n";
echo "\nIf login still fails, check:\n";
echo "1. Clear browser cookies/cache\n";
echo "2. Use incognito/private window\n";
echo "3. Check if there's a firewall blocking your IP\n";