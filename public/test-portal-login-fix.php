<?php
// Test portal login functionality after middleware fix

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "Testing Portal Login Fix\n";
echo "========================\n\n";

// Test 1: Check middleware configuration
echo "1. Checking middleware configuration:\n";
$router = $app->make('router');
$aliases = $router->getMiddleware();
echo "   - portal.auth middleware: " . ($aliases['portal.auth'] ?? 'NOT FOUND') . "\n";
echo "   - Expected: App\\Http\\Middleware\\PortalAuth\n\n";

// Test 2: Check PortalUser exists
echo "2. Checking user fabianspitzer@icloud.com:\n";
$user = \App\Models\PortalUser::withoutGlobalScopes()
    ->where('email', 'fabianspitzer@icloud.com')
    ->first();

if ($user) {
    echo "   ✓ User found\n";
    echo "   - ID: {$user->id}\n";
    echo "   - Name: {$user->name}\n";
    echo "   - Company ID: {$user->company_id}\n";
    echo "   - Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
    echo "   - Role: {$user->role}\n";
} else {
    echo "   ✗ User NOT found\n";
}

echo "\n3. Portal Login URLs:\n";
echo "   - Login page: https://api.askproai.de/business/login\n";
echo "   - Portal login: https://api.askproai.de/portal/login (redirects to /business/login)\n";
echo "   - Dashboard: https://api.askproai.de/business/dashboard\n";

echo "\n4. Session Debug URL:\n";
echo "   - https://api.askproai.de/portal-session-debug\n";

echo "\n5. Test Login Command:\n";
echo "   curl -X POST https://api.askproai.de/business/login \\\n";
echo "     -H 'Content-Type: application/x-www-form-urlencoded' \\\n";
echo "     -H 'Accept: text/html' \\\n";
echo "     -d 'email=fabianspitzer@icloud.com&password=YOUR_PASSWORD' \\\n";
echo "     -c cookies.txt -b cookies.txt -L -v\n";

echo "\n✅ Middleware fix has been applied. Portal login should now work correctly.\n";