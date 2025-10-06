#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

const RED = "\033[31m";
const GREEN = "\033[32m";
const RESET = "\033[0m";

echo "🧪 FILAMENT UserResource SECURITY TEST\n\n";

// Get two companies
$companyA = Company::find(1); // Krückeberg Servicegruppe
$companyB = Company::find(15); // AskProAI

echo "Company A: {$companyA->name} (ID: {$companyA->id})\n";
echo "Company B: {$companyB->name} (ID: {$companyB->id})\n\n";

// Get Company A user
$userA = User::where('company_id', $companyA->id)->first();
echo "Test User: {$userA->email} (Company A)\n\n";

// Login as Company A user
Auth::login($userA);

echo "=== SIMULATING FILAMENT UserResource ===\n\n";

// This is EXACTLY what UserResource::getEloquentQuery() does
$query = User::query();

// Check if super_admin
$isSuperAdmin = auth()->user()?->hasRole('super_admin');
echo "Is super_admin: " . ($isSuperAdmin ? 'YES' : 'NO') . "\n\n";

if (!$isSuperAdmin) {
    // For non-super_admin, UserResource returns: parent::getEloquentQuery()
    // which is just User::query() with NO company filtering
    echo "Query used: User::query() - NO company filter applied\n\n";
}

// Execute the query (this is what Filament table shows)
$users = $query->get();

echo "=== RESULTS ===\n\n";
echo "Total users returned: " . $users->count() . "\n\n";

$companyAUsers = $users->where('company_id', $companyA->id);
$companyBUsers = $users->where('company_id', $companyB->id);

echo "Company A users: " . $companyAUsers->count() . "\n";
echo "Company B users: " . $companyBUsers->count() . "\n\n";

if ($companyBUsers->count() > 0) {
    echo RED . "❌ SECURITY LEAK: Company B users visible in Filament!\n" . RESET;
    echo "\nCompany B users shown:\n";
    foreach ($companyBUsers as $user) {
        echo "  - {$user->name} ({$user->email})\n";
    }
} else {
    echo GREEN . "✅ PASS: Only Company A users visible\n" . RESET;
}

echo "\n=== VERDICT ===\n";
if ($companyBUsers->count() > 0) {
    echo RED . "FAIL: Filament UserResource zeigt cross-company users\n" . RESET;
    echo RED . "Das ist ein SICHERHEITSLECK, kein 'acceptable design'\n" . RESET;
    exit(1);
} else {
    echo GREEN . "PASS: Filament properly filters by company\n" . RESET;
    exit(0);
}
