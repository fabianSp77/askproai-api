<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

// Create a completely new test user to avoid 2FA issues
$email = 'demo@askproai.de';
$password = 'Demo123!';

echo "Creating fresh demo user...\n";
echo "=====================================\n";

// Delete existing user if exists
PortalUser::where('email', $email)->delete();

// Get a prepaid company
$company = \App\Models\Company::where('billing_type', 'prepaid')->first();
if (!$company) {
    $company = \App\Models\Company::first();
}

// Create new user
$user = PortalUser::create([
    'name' => 'Demo User',
    'email' => $email,
    'password' => \Illuminate\Support\Facades\Hash::make($password),
    'company_id' => $company->id,
    'is_active' => true,
    'role' => 'admin',
    'two_factor_secret' => null,
    'two_factor_recovery_codes' => null,
    'two_factor_confirmed_at' => null,
    'two_factor_enforced' => false,
    'permissions' => json_encode([
        'calls.view_all' => true,
        'calls.edit_all' => true,
        'billing.view' => true,
        'billing.manage' => true,
        'settings.manage' => true,
    ])
]);

echo "✅ Demo user created!\n";
echo "   - ID: {$user->id}\n";
echo "   - Company: {$company->name}\n";
echo "   - Billing Type: {$company->billing_type}\n";

// Generate a direct login token
$token = bin2hex(random_bytes(32));
\Illuminate\Support\Facades\Cache::put('portal_login_token_' . $token, $user->id, now()->addMinutes(5));

$loginUrl = "https://api.askproai.de/business/login-with-token?token={$token}";

echo "\n=====================================\n";
echo "📋 Login Details:\n";
echo "   Email: $email\n";
echo "   Password: $password\n";
echo "   Company: {$company->name}\n";
echo "\n🔗 Direct Login URL (valid for 5 minutes):\n";
echo "   $loginUrl\n";
echo "=====================================\n";

// Also create the token login route
$routeFile = __DIR__ . '/routes/web.php';
$routeContent = file_get_contents($routeFile);

if (!str_contains($routeContent, 'login-with-token')) {
    echo "\n📝 Adding token login route...\n";
    
    $newRoute = "\n\n// Temporary token login for testing\nRoute::get('/business/login-with-token', function (Request \$request) {\n    \$token = \$request->get('token');\n    \$userId = \\Illuminate\\Support\\Facades\\Cache::pull('portal_login_token_' . \$token);\n    \n    if (!\$userId) {\n        return redirect()->route('business.login')->with('error', 'Invalid or expired token');\n    }\n    \n    \$user = \\App\\Models\\PortalUser::find(\$userId);\n    if (!\$user || !\$user->is_active) {\n        return redirect()->route('business.login')->with('error', 'User not found or inactive');\n    }\n    \n    \\Illuminate\\Support\\Facades\\Auth::guard('portal')->login(\$user);\n    \$user->recordLogin(\$request->ip());\n    \n    return redirect()->route('business.dashboard');\n})->name('business.login-with-token');\n";
    
    file_put_contents($routeFile, $routeContent . $newRoute);
    echo "✅ Route added!\n";
}