<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

echo "=== Portal Login Debug ===\n\n";

// Check user
$user = PortalUser::where('email', 'fabianspitzer@icloud.com')->first();

if (!$user) {
    die("ERROR: User not found!\n");
}

echo "✓ User found\n";
echo "  - ID: " . $user->id . "\n";
echo "  - Email: " . $user->email . "\n";
echo "  - Name: " . $user->name . "\n";
echo "  - Role: " . $user->role . "\n";
echo "  - Active: " . ($user->is_active ? 'YES' : 'NO') . "\n";
echo "  - Company ID: " . $user->company_id . "\n";

// Check company
$company = Company::find($user->company_id);
echo "\n✓ Company found\n";
echo "  - ID: " . $company->id . "\n";
echo "  - Name: " . $company->name . "\n";
echo "  - Active: " . ($company->is_active ? 'YES' : 'NO') . "\n";

// Check password
echo "\n✓ Password check\n";
$passwordCorrect = Hash::check('demo123', $user->password);
echo "  - Password 'demo123' correct: " . ($passwordCorrect ? 'YES' : 'NO') . "\n";

// Check 2FA
echo "\n✓ 2FA check\n";
echo "  - 2FA Secret: " . ($user->two_factor_secret ? 'SET' : 'NOT SET') . "\n";
echo "  - 2FA Confirmed: " . ($user->two_factor_confirmed_at ? $user->two_factor_confirmed_at : 'NOT CONFIRMED') . "\n";
echo "  - Requires 2FA: " . ($user->requires2FA() ? 'YES' : 'NO') . "\n";
echo "  - 2FA Enforced: " . ($user->two_factor_enforced ? 'YES' : 'NO') . "\n";

// Check if there's any scope issue
echo "\n✓ Database query test\n";
$testUser = \DB::table('portal_users')->where('email', 'fabianspitzer@icloud.com')->first();
echo "  - Direct DB query found user: " . ($testUser ? 'YES' : 'NO') . "\n";

// Check for any global scopes
echo "\n✓ Model scopes\n";
$scopes = (new \ReflectionClass($user))->getProperty('globalScopes');
$scopes->setAccessible(true);
$globalScopes = $scopes->getValue($user);
echo "  - Global scopes: " . (empty($globalScopes) ? 'NONE' : json_encode(array_keys($globalScopes))) . "\n";

// Test login process step by step
echo "\n✓ Login process simulation\n";
echo "  1. User lookup: " . (PortalUser::where('email', 'fabianspitzer@icloud.com')->first() ? 'FOUND' : 'NOT FOUND') . "\n";
echo "  2. Password check: " . ($passwordCorrect ? 'PASSED' : 'FAILED') . "\n";
echo "  3. Active check: " . ($user->is_active ? 'PASSED' : 'FAILED') . "\n";
echo "  4. 2FA requirement: " . ($user->requires2FA() ? 'REQUIRED' : 'NOT REQUIRED') . "\n";
echo "  5. 2FA secret check: " . ($user->two_factor_secret ? 'HAS SECRET' : 'NO SECRET') . "\n";

echo "\n✓ Summary\n";
if ($user && $passwordCorrect && $user->is_active && !$user->requires2FA() && !$user->two_factor_secret) {
    echo "  ✅ User should be able to login without issues!\n";
} else {
    echo "  ❌ Login will fail due to:\n";
    if (!$user) echo "    - User not found\n";
    if (!$passwordCorrect) echo "    - Incorrect password\n";
    if (!$user->is_active) echo "    - User not active\n";
    if ($user->requires2FA()) echo "    - 2FA required but not set up\n";
    if ($user->two_factor_secret) echo "    - 2FA secret exists, challenge required\n";
}

echo "\n";