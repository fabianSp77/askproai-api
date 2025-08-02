<?php
/**
 * Test Portal User Creation
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\PortalUser;
use App\Models\Company;

// Ensure we have a test company
$company = Company::first();
if (!$company) {
    die('No company found in database');
}

// Create or update test portal user
$user = PortalUser::withoutGlobalScopes()
    ->where('email', 'test@askproai.de')
    ->first();

if (!$user) {
    $user = new PortalUser();
    $user->email = 'test@askproai.de';
    $user->company_id = $company->id;
}

$user->name = 'Test User';
$user->password = bcrypt('test123');
$user->role = 'admin';
$user->is_active = true;
$user->two_factor_enforced = false;
$user->save();

echo "✅ Test Portal User Created/Updated:\n";
echo "Email: test@askproai.de\n";
echo "Password: test123\n";
echo "Company: " . $company->name . " (ID: " . $company->id . ")\n";
echo "Role: admin\n";
echo "Active: Yes\n";
echo "2FA: Disabled\n\n";

// Also show demo user status
$demoUser = PortalUser::withoutGlobalScopes()
    ->where('email', 'demo@askproai.de')
    ->first();

if ($demoUser) {
    echo "✅ Demo User Status:\n";
    echo "Email: demo@askproai.de\n";
    echo "Password: demo123 (updated)\n";
    echo "Company: ID " . $demoUser->company_id . "\n";
    echo "Active: " . ($demoUser->is_active ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ Demo user not found\n";
}
?>