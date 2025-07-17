<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "Resetting Login Systems\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Clear all sessions
echo "1. Clearing all sessions...\n";
DB::table('sessions')->truncate();
DB::table('portal_sessions')->truncate();
echo "   ✓ Sessions cleared\n\n";

// 2. Reset Admin User
echo "2. Resetting Admin User...\n";
$adminUser = \App\Models\User::where('email', 'admin@askproai.de')->first();
if ($adminUser) {
    $adminUser->password = Hash::make('demo123');
    $adminUser->save();
    echo "   ✓ Admin password reset to: demo123\n";
} else {
    echo "   ✗ Admin user not found\n";
}

// 3. Reset Portal User
echo "\n3. Resetting Portal User...\n";
$portalUser = \App\Models\PortalUser::where('email', 'demo@example.com')->first();
if ($portalUser) {
    $portalUser->password = Hash::make('demo123');
    $portalUser->is_active = true;
    $portalUser->save();
    echo "   ✓ Portal user password reset to: demo123\n";
    echo "   ✓ Portal user activated\n";
} else {
    echo "   ✗ Portal user not found - creating new one\n";
    $company = \App\Models\Company::first();
    if ($company) {
        $portalUser = \App\Models\PortalUser::create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => Hash::make('demo123'),
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        echo "   ✓ Portal user created\n";
    } else {
        echo "   ✗ No company found\n";
    }
}

// 4. Clear caches
echo "\n4. Clearing caches...\n";
exec('php artisan optimize:clear', $output);
echo "   ✓ Caches cleared\n";

// 5. Test URLs
echo "\n5. Test URLs:\n";
echo "   Admin Login: https://api.askproai.de/admin/login\n";
echo "     - Email: admin@askproai.de\n";
echo "     - Password: demo123\n\n";
echo "   Portal Login: https://api.askproai.de/business/login\n";
echo "     - Email: demo@example.com\n";
echo "     - Password: demo123\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "Reset completed. Please try logging in now.\n";
echo "Note: The browser errors about 'listener' are from browser extensions.\n";
echo "Try in incognito mode or disable extensions if issues persist.\n";