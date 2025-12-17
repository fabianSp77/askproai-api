#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\UserResource;

const RED = "\033[31m";
const GREEN = "\033[32m";
const RESET = "\033[0m";

echo "🔒 POST-FIX SECURITY VALIDATION\n";
echo str_repeat('=', 60) . "\n\n";

$results = [];

// SCENARIO 1: Normal Company A User (non-super_admin)
echo "📋 SCENARIO 1: Company A Normal User\n";
$normalUser = User::where('email', 'fabian@askproai.de')->first();
Auth::login($normalUser);

$users = UserResource::getEloquentQuery()->get();
$companyACount = $users->where('company_id', 1)->count();
$otherCompanies = $users->where('company_id', '!=', 1)->count();

echo "   User: {$normalUser->email}\n";
echo "   Company: {$normalUser->company_id}\n";
echo "   Is super_admin: " . ($normalUser->hasRole('super_admin') ? 'YES' : 'NO') . "\n";
echo "   Total users: {$users->count()}\n";
echo "   Own company users: $companyACount\n";
echo "   Other company users: $otherCompanies\n";

if ($otherCompanies === 0 && $users->count() > 0) {
    echo GREEN . "   ✅ PASS: Only own company users visible\n" . RESET;
    $results['normal_user'] = 'PASS';
} else {
    echo RED . "   ❌ FAIL: Cross-company users visible\n" . RESET;
    $results['normal_user'] = 'FAIL';
}
echo "\n";

// SCENARIO 2: Super Admin
echo "📋 SCENARIO 2: Super Admin User\n";
$superAdmin = User::where('email', 'superadmin@askproai.de')->first();
Auth::login($superAdmin);

$users = UserResource::getEloquentQuery()->get();
$totalCompanies = $users->pluck('company_id')->unique()->count();
$companyIds = $users->pluck('company_id')->unique()->sort()->values()->toArray();

echo "   User: {$superAdmin->email}\n";
echo "   Company: {$superAdmin->company_id}\n";
echo "   Is super_admin: " . ($superAdmin->hasRole('super_admin') ? 'YES' : 'NO') . "\n";
echo "   Total users: {$users->count()}\n";
echo "   Companies represented: $totalCompanies\n";
echo "   Company IDs: " . implode(', ', $companyIds) . "\n";

if ($totalCompanies > 1) {
    echo GREEN . "   ✅ PASS: Super admin sees multiple companies\n" . RESET;
    $results['super_admin'] = 'PASS';
} else {
    echo RED . "   ❌ FAIL: Super admin restricted to one company\n" . RESET;
    $results['super_admin'] = 'FAIL';
}
echo "\n";

// SCENARIO 3: Cross-Company Access Test
echo "📋 SCENARIO 3: Cross-Company Access Prevention\n";
Auth::login($normalUser);

// Try to access user from another company directly
$otherCompanyUser = User::where('company_id', '!=', $normalUser->company_id)->first();
$accessibleUsers = UserResource::getEloquentQuery()->get();
$canAccessOther = $accessibleUsers->contains('id', $otherCompanyUser->id);

echo "   Test: Normal user trying to access Company {$otherCompanyUser->company_id} user\n";
echo "   Other user: {$otherCompanyUser->email}\n";
echo "   Accessible via UserResource: " . ($canAccessOther ? 'YES' : 'NO') . "\n";

if (!$canAccessOther) {
    echo GREEN . "   ✅ PASS: Cross-company user blocked\n" . RESET;
    $results['cross_company'] = 'PASS';
} else {
    echo RED . "   ❌ FAIL: Cross-company user accessible\n" . RESET;
    $results['cross_company'] = 'FAIL';
}
echo "\n";

// SUMMARY
echo str_repeat('=', 60) . "\n";
echo "📊 SECURITY FIX VALIDATION SUMMARY\n";
echo str_repeat('=', 60) . "\n";

foreach ($results as $scenario => $result) {
    $icon = $result === 'PASS' ? GREEN . '✅' : RED . '❌';
    echo "$icon " . strtoupper(str_replace('_', ' ', $scenario)) . ": $result" . RESET . "\n";
}

$failCount = count(array_filter($results, fn($r) => $r === 'FAIL'));

echo "\n";
if ($failCount > 0) {
    echo RED . "VERDICT: SECURITY FIX FAILED ($failCount failures)\n" . RESET;
    exit(1);
} else {
    echo GREEN . "VERDICT: SECURITY FIX VERIFIED ✅\n" . RESET;
    exit(0);
}
