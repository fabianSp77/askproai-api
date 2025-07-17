<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

// Get an admin user
$admin = User::where('email', 'fabian@fabiansprojects.com')
    ->orWhere('email', 'admin@askproai.de')
    ->orWhere('id', 1)
    ->first();

if (!$admin) {
    die("No admin user found!\n");
}

// Get a company
$company = Company::where('name', 'LIKE', '%Krückeberg%')
    ->orWhere('billing_type', 'prepaid')
    ->first();

if (!$company) {
    die("No suitable company found!\n");
}

echo "=== CREATING ADMIN VIEWING SESSION ===\n\n";
echo "Admin: {$admin->email}\n";
echo "Company: {$company->name}\n";
echo "Company ID: {$company->id}\n\n";

// Generate admin viewing URL
$sessionId = bin2hex(random_bytes(16));
$adminViewingData = [
    'admin_id' => $admin->id,
    'company_id' => $company->id,
    'session_id' => $sessionId,
    'expires_at' => now()->addHours(24)->toIso8601String()
];

// Store in cache
\Illuminate\Support\Facades\Cache::put('admin_viewing_' . $sessionId, $adminViewingData, now()->addHours(24));

// Create special route in web.php if not exists
$routeFile = __DIR__ . '/routes/web.php';
$routeContent = file_get_contents($routeFile);

if (!str_contains($routeContent, 'admin-view-portal')) {
    $newRoute = "\n\n// Admin viewing portal route\nRoute::get('/admin-view-portal/{session}', function (\$session) {\n    \$data = \\Illuminate\\Support\\Facades\\Cache::get('admin_viewing_' . \$session);\n    if (!\$data) {\n        return redirect('/business/login')->with('error', 'Invalid session');\n    }\n    \n    // Set admin viewing session\n    session(['is_admin_viewing' => true]);\n    session(['admin_impersonation' => [\n        'user_id' => 0,\n        'company_id' => \$data['company_id'],\n        'company_name' => \\App\\Models\\Company::find(\$data['company_id'])->name,\n        'admin_id' => \$data['admin_id']\n    ]]);\n    \n    return redirect('/business/dashboard');\n})->name('admin.view-portal');\n";
    
    file_put_contents($routeFile, $routeContent . $newRoute);
    echo "✅ Route added!\n\n";
}

$url = "https://api.askproai.de/admin-view-portal/{$sessionId}";

echo "🔗 ADMIN VIEWING URL (24h valid):\n";
echo $url . "\n\n";

echo "📋 This bypasses portal authentication and lets you view as admin!\n";
echo "After clicking, you'll have access to:\n";
echo "- /business/calls → Test all features\n";
echo "- /business/billing → Test Stripe\n";