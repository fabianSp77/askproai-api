<?php
// Create demo user for business portal

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

echo "=== Creating Demo Portal User ===\n\n";

// Check if demo company exists
$company = Company::where('name', 'Demo Company')->first();
if (!$company) {
    echo "Creating demo company...\n";
    $company = Company::create([
        'name' => 'Demo Company',
        'email' => 'demo@company.com',
        'phone' => '+49123456789',
        'is_active' => true,
    ]);
    echo "✅ Demo company created (ID: {$company->id})\n";
} else {
    echo "✅ Demo company exists (ID: {$company->id})\n";
}

// Check if demo user exists
$demoUser = PortalUser::where('email', 'demo@business.portal')->first();
if ($demoUser) {
    echo "\nDemo user already exists. Updating...\n";
    $demoUser->update([
        'password' => Hash::make('demo123'),
        'is_active' => true,
        'company_id' => $company->id,
    ]);
    echo "✅ Demo user updated\n";
} else {
    echo "\nCreating demo user...\n";
    $demoUser = PortalUser::create([
        'name' => 'Demo User',
        'email' => 'demo@business.portal',
        'password' => Hash::make('demo123'),
        'company_id' => $company->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    echo "✅ Demo user created\n";
}

echo "\n=== Demo User Details ===\n";
echo "Email: demo@business.portal\n";
echo "Password: demo123\n";
echo "Company: {$company->name} (ID: {$company->id})\n";
echo "User ID: {$demoUser->id}\n";
echo "Active: " . ($demoUser->is_active ? 'Yes' : 'No') . "\n";

// Create automatic login URL
$loginToken = bin2hex(random_bytes(32));
cache()->put('demo_login_token_' . $loginToken, $demoUser->id, 300); // 5 minutes

echo "\n=== Quick Login Links ===\n";
echo "Normal Login: https://api.askproai.de/business/login\n";
echo "Auto Login: https://api.askproai.de/business/demo-login?token=$loginToken\n";

// Create demo login route file
$demoLoginContent = <<<'PHP'
<?php
// Demo login route
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\PortalUser;

Route::get('/business/demo-login', function () {
    $token = request('token');
    if (!$token) {
        return redirect('/business/login')->with('error', 'No token provided');
    }
    
    $userId = cache('demo_login_token_' . $token);
    if (!$userId) {
        return redirect('/business/login')->with('error', 'Invalid or expired token');
    }
    
    $user = PortalUser::find($userId);
    if (!$user || !$user->is_active) {
        return redirect('/business/login')->with('error', 'User not found or inactive');
    }
    
    Auth::guard('portal')->login($user);
    cache()->forget('demo_login_token_' . $token);
    
    return redirect('/business')->with('success', 'Logged in as demo user');
})->name('demo-login');
PHP;

file_put_contents(__DIR__ . '/routes/demo-login.php', $demoLoginContent);

// Include in web.php if not already
$webContent = file_get_contents(__DIR__ . '/routes/web.php');
if (!str_contains($webContent, 'demo-login.php')) {
    $webContent .= "\n\n// Demo login route\nrequire __DIR__.'/demo-login.php';\n";
    file_put_contents(__DIR__ . '/routes/web.php', $webContent);
    echo "\n✅ Demo login route added to web.php\n";
}

echo "\nDone!\n";