<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "\n🔧 Fixing Admin Portal Session Persistence\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Check session configuration
echo "1. Session Configuration:\n";
echo "   - Driver: " . config('session.driver') . "\n";
echo "   - Domain: " . config('session.domain') . "\n";
echo "   - Same Site: " . config('session.same_site') . "\n";
echo "   - Cookie Name: " . config('session.cookie') . "\n";
echo "   - Path: " . config('session.path') . "\n";

// 2. Check for potential issues
echo "\n2. Checking Portal User:\n";
$portalUser = \App\Models\PortalUser::withoutGlobalScopes()
    ->where('email', 'like', 'admin+%@askproai.de')
    ->where('company_id', 1)
    ->first();

if ($portalUser) {
    echo "   ✓ Admin portal user exists: {$portalUser->email}\n";
    echo "   - ID: {$portalUser->id}\n";
    echo "   - Active: " . ($portalUser->is_active ? 'Yes' : 'No') . "\n";
    echo "   - Company ID: {$portalUser->company_id}\n";
    
    // Ensure it's active
    if (!$portalUser->is_active) {
        $portalUser->is_active = true;
        $portalUser->save();
        echo "   ✓ Activated portal user\n";
    }
} else {
    echo "   ✗ No admin portal user found\n";
}

// 3. Check session table
echo "\n3. Checking Sessions Table:\n";
$sessionCount = \DB::table('sessions')->count();
echo "   - Total sessions: {$sessionCount}\n";

// 4. Test business portal routes
echo "\n4. Business Portal Routes:\n";
$routes = [
    'business.dashboard',
    'business.api.calls.index',
    'business.api.user.permissions',
];

foreach ($routes as $routeName) {
    try {
        $url = route($routeName);
        echo "   ✓ {$routeName}: {$url}\n";
    } catch (\Exception $e) {
        echo "   ✗ {$routeName}: Not found\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Recommendations:\n";
echo "1. Clear browser cookies and cache\n";
echo "2. Try using an incognito/private window\n";
echo "3. Use the 'Als Firma anmelden' button in admin panel\n";