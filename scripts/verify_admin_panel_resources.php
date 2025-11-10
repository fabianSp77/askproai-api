<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\BranchResource;

echo "=== FILAMENT RESOURCE VERIFICATION ===\n\n";

// 1. Check CompanyResource
echo "1. CompanyResource\n";
echo str_repeat("=", 50) . "\n";

$companyModel = CompanyResource::getModel();
echo "Model: $companyModel\n";

$companyNavigationIcon = CompanyResource::getNavigationIcon();
echo "Icon: $companyNavigationIcon\n";

$companyNavigationGroup = CompanyResource::getNavigationGroup();
echo "Group: $companyNavigationGroup\n";

$companyNavigationLabel = CompanyResource::getNavigationLabel();
echo "Label: $companyNavigationLabel\n";

// Check if it should register
$companyShouldRegister = true;
if (method_exists(CompanyResource::class, 'shouldRegisterNavigation')) {
    $companyShouldRegister = CompanyResource::shouldRegisterNavigation();
}
echo "Should Register: " . ($companyShouldRegister ? '✅ YES' : '❌ NO') . "\n";

echo "\n";

// 2. Check BranchResource
echo "2. BranchResource\n";
echo str_repeat("=", 50) . "\n";

$branchModel = BranchResource::getModel();
echo "Model: $branchModel\n";

$branchNavigationIcon = BranchResource::getNavigationIcon();
echo "Icon: $branchNavigationIcon\n";

$branchNavigationGroup = BranchResource::getNavigationGroup();
echo "Group: $branchNavigationGroup\n";

$branchNavigationLabel = BranchResource::getNavigationLabel();
echo "Label: $branchNavigationLabel\n";

// Check if it should register
$branchShouldRegister = true;
if (method_exists(BranchResource::class, 'shouldRegisterNavigation')) {
    $branchShouldRegister = BranchResource::shouldRegisterNavigation();
}
echo "Should Register: " . ($branchShouldRegister ? '✅ YES' : '❌ NO') . "\n";

echo "\n";

// 3. Test canViewAny for super_admin
echo "3. Policy Tests (Super Admin)\n";
echo str_repeat("=", 50) . "\n";

$superAdmin = DB::table('users')
    ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
    ->where('roles.name', 'super_admin')
    ->select('users.*')
    ->first();

if ($superAdmin) {
    $user = \App\Models\User::find($superAdmin->id);

    echo "Testing with: {$user->email}\n";
    echo "Has super_admin role: " . ($user->hasRole('super_admin') ? '✅ YES' : '❌ NO') . "\n\n";

    // Login as this user for policy tests
    auth()->guard('admin')->login($user);

    // Test CompanyResource
    try {
        $canViewCompanies = CompanyResource::canViewAny();
        echo "Can view Companies: " . ($canViewCompanies ? '✅ YES' : '❌ NO') . "\n";
    } catch (\Exception $e) {
        echo "Can view Companies: ❌ ERROR - " . $e->getMessage() . "\n";
    }

    // Test BranchResource
    try {
        $canViewBranches = BranchResource::canViewAny();
        echo "Can view Branches: " . ($canViewBranches ? '✅ YES' : '❌ NO') . "\n";
    } catch (\Exception $e) {
        echo "Can view Branches: ❌ ERROR - " . $e->getMessage() . "\n";
    }

    auth()->guard('admin')->logout();
} else {
    echo "⚠️ No super_admin user found!\n";
}

echo "\n";

// 4. Summary
echo str_repeat("=", 50) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 50) . "\n";

$companyStatus = $companyShouldRegister ? '✅ VISIBLE' : '❌ HIDDEN';
$branchStatus = $branchShouldRegister ? '✅ VISIBLE' : '❌ HIDDEN';

echo "CompanyResource (Unternehmen): $companyStatus\n";
echo "BranchResource (Filialen): $branchStatus\n\n";

if ($companyShouldRegister && $branchShouldRegister) {
    echo "✅ Both resources are enabled and should be visible to Super Admin!\n";
    echo "\nNext steps:\n";
    echo "1. Logout from Admin Panel\n";
    echo "2. Login again\n";
    echo "3. Check 'Stammdaten' navigation group\n";
    echo "4. You should see 'Unternehmen' and 'Filialen'\n\n";
    echo "If still not visible:\n";
    echo "  - Clear cache: php artisan cache:clear\n";
    echo "  - Clear browser cache (Ctrl+Shift+R)\n";
} else {
    echo "⚠️ One or more resources are disabled!\n";
}

echo "\n";
