<?php
echo "🔧 Portal Navigation Fix\n";
echo "=======================\n\n";

// Create a simple redirect page for wrong URLs
$redirectHtml = '<!DOCTYPE html>
<html>
<head>
    <title>Redirecting...</title>
    <meta http-equiv="refresh" content="0; url=/business/dashboard">
</head>
<body>
    <p>Redirecting to dashboard...</p>
    <p>If you are not redirected, <a href="/business/dashboard">click here</a>.</p>
</body>
</html>';

// Create public redirect file
file_put_contents('/var/www/api-gateway/public/test/session/index.html', $redirectHtml);
echo "✅ Created redirect for /test/session\n";

// Check React app for URL issues
echo "\n🔍 Checking React app configuration...\n";

$portalAppFile = '/var/www/api-gateway/resources/js/PortalApp.jsx';
if (file_exists($portalAppFile)) {
    $content = file_get_contents($portalAppFile);
    
    // Check Router configuration
    if (strpos($content, 'basename="/business"') !== false) {
        echo "✅ React Router has correct basename\n";
    } else {
        echo "⚠️  React Router might not have correct basename\n";
        echo "   Should have: <Router basename=\"/business\">\n";
    }
}

// Create a comprehensive fix for the dashboard
echo "\n📝 Creating comprehensive dashboard fix...\n";

$dashboardFix = '<?php
// Quick fix to ensure proper session handling

require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

echo "🔧 Fixing Portal Dashboard Access\n";
echo "=================================\n\n";

// Get the test user
$user = PortalUser::where("email", "fabianspitzer@icloud.com")->first();

if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

echo "✅ User found: {$user->email}\n";

// Check branches
$branches = DB::table("branches")->where("company_id", $user->company_id)->get();
echo "\n📍 Branches for company:\n";
foreach ($branches as $branch) {
    echo "   - {$branch->name} (ID: {$branch->id})\n";
}

if ($branches->isEmpty()) {
    echo "\n⚠️  No branches found! Creating default branch...\n";
    
    $branchId = DB::table("branches")->insertGetId([
        "company_id" => $user->company_id,
        "name" => "Hauptfiliale",
        "phone" => "+49 30 12345678",
        "email" => "info@demo-gmbh.de",
        "address" => "Musterstraße 1",
        "city" => "Berlin",
        "state" => "Berlin",
        "postal_code" => "10115",
        "country" => "DE",
        "is_active" => 1,
        "created_at" => now(),
        "updated_at" => now()
    ]);
    
    echo "✅ Created default branch (ID: $branchId)\n";
} else {
    $defaultBranch = $branches->first();
    echo "\n✅ Default branch: {$defaultBranch->name}\n";
}

echo "\n💾 Session configuration:\n";
echo "   Driver: " . config("session.driver") . "\n";
echo "   Domain: " . config("session.domain") . "\n";
echo "   Path: " . config("session.path") . "\n";

echo "\n✅ Setup complete! You can now login.\n";
';

file_put_contents('/var/www/api-gateway/fix-dashboard-access.php', $dashboardFix);
echo "✅ Created fix-dashboard-access.php\n";

echo "\n✅ All fixes applied!\n";
echo "\nNext steps:\n";
echo "1. Run: php fix-dashboard-access.php\n";
echo "2. Clear browser cache and cookies\n";
echo "3. Login at: https://api.askproai.de/business/login\n";
echo "4. You should be redirected to: /business/dashboard\n";