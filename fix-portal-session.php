<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PortalUser;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

echo "=== Fixing Portal Session ===\n\n";

// Find the user
$user = PortalUser::withoutGlobalScopes()->where('email', 'fabianspitzer@icloud.com')->first();

if (!$user) {
    die("ERROR: User not found!\n");
}

echo "✓ User found: {$user->email} (ID: {$user->id})\n";
echo "✓ Company ID: {$user->company_id}\n";

// Get company
$company = Company::withoutGlobalScopes()->find($user->company_id);
echo "✓ Company: {$company->name}\n";

// Get branch
$branch = Branch::withoutGlobalScopes()->where('company_id', $company->id)->first();
if ($branch) {
    echo "✓ Branch: {$branch->name} (ID: {$branch->id})\n";
} else {
    echo "✗ No branch found for company\n";
}

// Set up proper session
session_start();
$_SESSION['portal_user_id'] = $user->id;
$_SESSION['portal_login'] = $user->id;
$_SESSION['current_branch_id'] = $branch ? $branch->id : null;

echo "\n✓ Session data set:\n";
echo "  - portal_user_id: {$user->id}\n";
echo "  - portal_login: {$user->id}\n";
echo "  - current_branch_id: " . ($branch ? $branch->id : 'null') . "\n";

// Set company context
app()->instance('current_company_id', $user->company_id);

echo "\n✓ Company context set to: {$user->company_id}\n";

// Login the user
Auth::guard('portal')->login($user);

if (Auth::guard('portal')->check()) {
    echo "\n✅ User successfully logged in!\n";
} else {
    echo "\n❌ Login failed\n";
}

echo "\nThe user should now be able to access:\n";
echo "- Dashboard: https://api.askproai.de/business/dashboard\n";
echo "- API Test: https://api.askproai.de/portal-api-test.html\n";