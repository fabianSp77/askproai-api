<?php
echo "🔧 Comprehensive Portal Login Fix\n";
echo "================================\n\n";

// Step 1: Revert API endpoint changes back to original
echo "1️⃣ Reverting API endpoints to original...\n";

$dashboardFile = '/var/www/api-gateway/resources/js/Pages/Portal/Dashboard/Index.jsx';
if (file_exists($dashboardFile)) {
    $content = file_get_contents($dashboardFile);
    $content = str_replace(
        '/business/api-optional/dashboard',
        '/business/api/dashboard',
        $content
    );
    file_put_contents($dashboardFile, $content);
    echo "✅ Reverted Dashboard API endpoint\n";
}

// Step 2: Fix Session configuration
echo "\n2️⃣ Checking session configuration...\n";

$envFile = '/var/www/api-gateway/.env';
$envContent = file_get_contents($envFile);

// Ensure correct session domain
if (strpos($envContent, 'SESSION_DOMAIN=.askproai.de') === false) {
    if (strpos($envContent, 'SESSION_DOMAIN=') !== false) {
        $envContent = preg_replace('/SESSION_DOMAIN=.*/', 'SESSION_DOMAIN=.askproai.de', $envContent);
    } else {
        $envContent .= "\nSESSION_DOMAIN=.askproai.de\n";
    }
    file_put_contents($envFile, $envContent);
    echo "✅ Fixed SESSION_DOMAIN\n";
} else {
    echo "✅ SESSION_DOMAIN already correct\n";
}

// Step 3: Create a session test endpoint
echo "\n3️⃣ Creating session test endpoint...\n";

$testRoute = '
// Session Test Route
Route::get("/business/test/session", function() {
    return response()->json([
        "session_active" => session()->isStarted(),
        "session_id" => session()->getId(),
        "portal_user_id" => session("portal_user_id"),
        "portal_login" => session("portal_login"),
        "auth_check" => Auth::guard("portal")->check(),
        "auth_user" => Auth::guard("portal")->user() ? [
            "id" => Auth::guard("portal")->user()->id,
            "email" => Auth::guard("portal")->user()->email
        ] : null,
        "all_session_data" => session()->all()
    ]);
})->name("test.session");
';

$routesFile = '/var/www/api-gateway/routes/business-portal.php';
$routesContent = file_get_contents($routesFile);

if (strpos($routesContent, 'test/session') === false) {
    // Add test route after the first Route::prefix line
    $routesContent = preg_replace(
        '/(Route::prefix\(\'business\'\)->name\(\'business\.\'\)->group\(function \(\) \{)/',
        "$1\n$testRoute",
        $routesContent,
        1
    );
    file_put_contents($routesFile, $routesContent);
    echo "✅ Added session test route\n";
} else {
    echo "ℹ️  Session test route already exists\n";
}

// Step 4: Create login test script
echo "\n4️⃣ Creating login test script...\n";

$loginTestScript = '<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;

echo "\n🔐 Testing Portal Login\n";
echo "======================\n\n";

// Test user
$email = "fabianspitzer@icloud.com";
$password = "demo123";

$user = PortalUser::where("email", $email)->first();

if (!$user) {
    echo "❌ User not found: $email\n";
    exit(1);
}

echo "✅ User found: {$user->email} (ID: {$user->id})\n";
echo "✅ User is " . ($user->is_active ? "active" : "INACTIVE") . "\n";
echo "✅ Company: {$user->company->name} (ID: {$user->company_id})\n";

// Test password
if (Hash::check($password, $user->password)) {
    echo "✅ Password is correct\n";
} else {
    echo "❌ Password is incorrect\n";
    echo "   Setting password to: $password\n";
    $user->password = Hash::make($password);
    $user->save();
    echo "✅ Password updated\n";
}

// Test authentication
if (Auth::guard("portal")->attempt(["email" => $email, "password" => $password])) {
    echo "✅ Authentication successful!\n";
    $authUser = Auth::guard("portal")->user();
    echo "   Authenticated as: {$authUser->email}\n";
} else {
    echo "❌ Authentication failed\n";
}

echo "\n✅ Login test complete\n";
';

file_put_contents('/var/www/api-gateway/test-portal-login-fix.php', $loginTestScript);
echo "✅ Created login test script\n";

// Step 5: Clear all caches
echo "\n5️⃣ Clearing all caches...\n";
exec('php artisan optimize:clear 2>&1', $output);
echo implode("\n", $output) . "\n";

echo "\n✅ Fix complete!\n\n";
echo "📋 Next steps:\n";
echo "1. Run: php test-portal-login-fix.php\n";
echo "2. Run: npm run build\n";
echo "3. Test login at: https://api.askproai.de/business/login\n";
echo "4. Check session at: https://api.askproai.de/business/test/session\n";