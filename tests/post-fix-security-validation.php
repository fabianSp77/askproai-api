#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\UserResource;

const RED = "\033[31m";
const GREEN = "\033[32m";
const YELLOW = "\033[33m";
const RESET = "\033[0m";

echo "🔒 POST-FIX SECURITY VALIDATION\n";
echo str_repeat('=', 60) . "\n\n";

$companyA = Company::find(1); // Krückeberg
$companyB = Company::find(15); // AskProAI

$results = [];

// SCENARIO 1: Company A Admin
echo "📋 SCENARIO 1: Company A Admin\n";
$userA = User::where('company_id', $companyA->id)->first();
Auth::login($userA);

$users = UserResource::getEloquentQuery()->get();
$companyACount = $users->where('company_id', $companyA->id)->count();
$companyBCount = $users->where('company_id', $companyB->id)->count();

echo "   User: {$userA->email}\n";
echo "   Total users: {$users->count()}\n";
echo "   Company A users: $companyACount\n";
echo "   Company B users: $companyBCount\n";

if ($companyBCount === 0) {
    echo GREEN . "   ✅ PASS: No cross-company users visible\n" . RESET;
    $results['scenario_1'] = 'PASS';
} else {
    echo RED . "   ❌ FAIL: Cross-company users visible\n" . RESET;
    $results['scenario_1'] = 'FAIL';
}
echo "\n";

// SCENARIO 2: Company B Admin
echo "📋 SCENARIO 2: Company B Admin\n";
$userB = User::where('company_id', $companyB->id)->first();
Auth::login($userB);

$users = UserResource::getEloquentQuery()->get();
$companyACount = $users->where('company_id', $companyA->id)->count();
$companyBCount = $users->where('company_id', $companyB->id)->count();

echo "   User: {$userB->email}\n";
echo "   Total users: {$users->count()}\n";
echo "   Company A users: $companyACount\n";
echo "   Company B users: $companyBCount\n";

if ($companyACount === 0) {
    echo GREEN . "   ✅ PASS: No cross-company users visible\n" . RESET;
    $results['scenario_2'] = 'PASS';
} else {
    echo RED . "   ❌ FAIL: Cross-company users visible\n" . RESET;
    $results['scenario_2'] = 'FAIL';
}
echo "\n";

// SCENARIO 3: Super Admin (if exists)
echo "📋 SCENARIO 3: Super Admin\n";
$superAdmin = User::whereHas('roles', function($q) {
    $q->where('name', 'super_admin');
})->first();

if ($superAdmin) {
    Auth::login($superAdmin);

    $users = UserResource::getEloquentQuery()->get();
    $totalCompanies = $users->pluck('company_id')->unique()->count();

    echo "   User: {$superAdmin->email}\n";
    echo "   Total users: {$users->count()}\n";
    echo "   Companies represented: $totalCompanies\n";

    if ($totalCompanies > 1) {
        echo GREEN . "   ✅ PASS: Super admin sees all companies\n" . RESET;
        $results['scenario_3'] = 'PASS';
    } else {
        echo YELLOW . "   ⚠️ WARN: Super admin scoped (expected all companies)\n" . RESET;
        $results['scenario_3'] = 'WARN';
    }
} else {
    // Create super_admin role if doesn't exist, then test with regular admin
    $adminUser = User::first();
    Auth::login($adminUser);

    $users = UserResource::getEloquentQuery()->get();

    echo "   User: {$adminUser->email} (no super_admin role exists)\n";
    echo "   Total users: {$users->count()}\n";
    echo YELLOW . "   ℹ️ INFO: Testing without super_admin role\n" . RESET;
    $results['scenario_3'] = 'SKIP';
}
echo "\n";

// SUMMARY
echo str_repeat('=', 60) . "\n";
echo "📊 SECURITY FIX VALIDATION SUMMARY\n";
echo str_repeat('=', 60) . "\n";

$passCount = count(array_filter($results, fn($r) => $r === 'PASS'));
$failCount = count(array_filter($results, fn($r) => $r === 'FAIL'));

foreach ($results as $scenario => $result) {
    $icon = match($result) {
        'PASS' => GREEN . '✅',
        'FAIL' => RED . '❌',
        'WARN' => YELLOW . '⚠️',
        'SKIP' => YELLOW . 'ℹ️',
    };
    echo "$icon " . strtoupper(str_replace('_', ' ', $scenario)) . ": $result" . RESET . "\n";
}

echo "\n";
if ($failCount > 0) {
    echo RED . "VERDICT: SECURITY FIX FAILED\n" . RESET;
    exit(1);
} else {
    echo GREEN . "VERDICT: SECURITY FIX VERIFIED ✅\n" . RESET;
    exit(0);
}
