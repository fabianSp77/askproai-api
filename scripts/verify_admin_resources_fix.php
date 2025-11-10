#!/usr/bin/env php
<?php

/**
 * Verify Admin Panel Resources Visibility Fix
 *
 * Tests if CompanyResource and BranchResource are visible to Super Admin users
 *
 * FIX 2025-11-05: Policy role variants fix
 * - Added 'Super Admin' (with space) variant
 * - Added 'Admin' (capitalized) variant
 * - Both Resources should now be visible to all admin role variants
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\BranchResource;

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║      ADMIN PANEL RESOURCES VISIBILITY TEST - FIX 2025-11-05   ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

// Get all admin users
$adminUsers = User::whereHas('roles', function ($q) {
    $q->whereIn('name', ['super_admin', 'Super Admin', 'admin', 'Admin']);
})->get();

if ($adminUsers->isEmpty()) {
    echo "⚠️ WARNING: No admin users found!\n\n";
    exit(1);
}

echo "Found " . $adminUsers->count() . " admin user(s)\n\n";

foreach ($adminUsers as $user) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Testing user: {$user->name} <{$user->email}>\n";
    echo "Roles: " . $user->getRoleNames()->implode(', ') . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Simulate authentication
    auth()->guard('admin')->setUser($user);

    // Test CompanyResource visibility
    try {
        $canViewCompanies = CompanyResource::canViewAny();

        if ($canViewCompanies) {
            echo "✅ CompanyResource (Unternehmen): VISIBLE\n";
        } else {
            echo "❌ CompanyResource (Unternehmen): NOT VISIBLE\n";
        }

        // Get navigation details
        $companyNav = [
            'label' => CompanyResource::getNavigationLabel(),
            'group' => CompanyResource::getNavigationGroup(),
            'sort' => CompanyResource::getNavigationSort(),
            'icon' => CompanyResource::getNavigationIcon(),
        ];

        echo "   Navigation:\n";
        echo "   - Label: {$companyNav['label']}\n";
        echo "   - Group: {$companyNav['group']}\n";
        echo "   - Sort: {$companyNav['sort']}\n";
        echo "   - Icon: {$companyNav['icon']}\n";

    } catch (\Exception $e) {
        echo "❌ CompanyResource ERROR: " . $e->getMessage() . "\n";
    }

    echo "\n";

    // Test BranchResource visibility
    try {
        $canViewBranches = BranchResource::canViewAny();

        if ($canViewBranches) {
            echo "✅ BranchResource (Filialen): VISIBLE\n";
        } else {
            echo "❌ BranchResource (Filialen): NOT VISIBLE\n";
        }

        // Get navigation details
        $branchNav = [
            'label' => BranchResource::getNavigationLabel(),
            'group' => BranchResource::getNavigationGroup(),
            'sort' => BranchResource::getNavigationSort(),
            'icon' => BranchResource::getNavigationIcon(),
        ];

        echo "   Navigation:\n";
        echo "   - Label: {$branchNav['label']}\n";
        echo "   - Group: {$branchNav['group']}\n";
        echo "   - Sort: {$branchNav['sort']}\n";
        echo "   - Icon: {$branchNav['icon']}\n";

    } catch (\Exception $e) {
        echo "❌ BranchResource ERROR: " . $e->getMessage() . "\n";
    }

    echo "\n";

    // Policy check details
    echo "Policy Checks:\n";

    $companyPolicy = new \App\Policies\CompanyPolicy();
    $branchPolicy = new \App\Policies\BranchPolicy();

    // Test CompanyPolicy
    try {
        $canViewAnyCompanies = $companyPolicy->viewAny($user);
        echo "   - CompanyPolicy::viewAny(): " . ($canViewAnyCompanies ? "✅ true" : "❌ false") . "\n";

        $beforeResultCompany = $companyPolicy->before($user, 'viewAny');
        echo "   - CompanyPolicy::before(): " . ($beforeResultCompany === true ? "✅ true (bypass)" : ($beforeResultCompany === null ? "⏭️ null (continue)" : "❌ false")) . "\n";
    } catch (\Exception $e) {
        echo "   - CompanyPolicy ERROR: " . $e->getMessage() . "\n";
    }

    // Test BranchPolicy
    try {
        $canViewAnyBranches = $branchPolicy->viewAny($user);
        echo "   - BranchPolicy::viewAny(): " . ($canViewAnyBranches ? "✅ true" : "❌ false") . "\n";

        $beforeResultBranch = $branchPolicy->before($user, 'viewAny');
        echo "   - BranchPolicy::before(): " . ($beforeResultBranch === true ? "✅ true (bypass)" : ($beforeResultBranch === null ? "⏭️ null (continue)" : "❌ false")) . "\n";
    } catch (\Exception $e) {
        echo "   - BranchPolicy ERROR: " . $e->getMessage() . "\n";
    }

    echo "\n";

    // Role checks
    echo "Role Checks:\n";
    echo "   - hasRole('super_admin'): " . ($user->hasRole('super_admin') ? "✅" : "❌") . "\n";
    echo "   - hasRole('Super Admin'): " . ($user->hasRole('Super Admin') ? "✅" : "❌") . "\n";
    echo "   - hasRole('admin'): " . ($user->hasRole('admin') ? "✅" : "❌") . "\n";
    echo "   - hasRole('Admin'): " . ($user->hasRole('Admin') ? "✅" : "❌") . "\n";
    echo "   - hasAnyRole(['super_admin', 'Super Admin', 'admin', 'Admin']): " . ($user->hasAnyRole(['super_admin', 'Super Admin', 'admin', 'Admin']) ? "✅" : "❌") . "\n";

    echo "\n\n";
}

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                        TEST SUMMARY                            ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

// Summary check
$allPassed = true;
foreach ($adminUsers as $user) {
    auth()->guard('admin')->setUser($user);

    $canViewCompanies = CompanyResource::canViewAny();
    $canViewBranches = BranchResource::canViewAny();

    if (!$canViewCompanies || !$canViewBranches) {
        $allPassed = false;
        echo "❌ FAILED for {$user->name}: ";
        if (!$canViewCompanies) echo "Companies missing ";
        if (!$canViewBranches) echo "Branches missing";
        echo "\n";
    } else {
        echo "✅ PASSED for {$user->name}\n";
    }
}

echo "\n";

if ($allPassed) {
    echo "✅✅✅ ALL TESTS PASSED! ✅✅✅\n";
    echo "Both 'Unternehmen' and 'Filialen' should now be visible in Admin Panel!\n\n";
    echo "Next steps:\n";
    echo "1. Logout from Admin Panel: https://[YOUR_DOMAIN]/admin/logout\n";
    echo "2. Login again: https://[YOUR_DOMAIN]/admin/login\n";
    echo "3. Check sidebar → 'Stammdaten' → You should see both menu items\n\n";
    exit(0);
} else {
    echo "❌❌❌ SOME TESTS FAILED ❌❌❌\n";
    echo "Please check the Policy configuration and role names.\n\n";
    exit(1);
}
