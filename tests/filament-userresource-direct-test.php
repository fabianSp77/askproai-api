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

echo "🧪 DIRECT FILAMENT UserResource TEST\n\n";

$companyA = Company::find(1);
$companyB = Company::find(15);

echo "Company A: {$companyA->name} (ID: {$companyA->id})\n";
echo "Company B: {$companyB->name} (ID: {$companyB->id})\n\n";

$userA = User::where('company_id', $companyA->id)->first();
echo "Test User: {$userA->email} (Company A)\n";
echo "Is super_admin: " . ($userA->hasRole('super_admin') ? 'YES' : 'NO') . "\n\n";

Auth::login($userA);

echo "=== CALLING UserResource::getEloquentQuery() ===\n\n";

// Call the ACTUAL Filament method
$query = UserResource::getEloquentQuery();
$users = $query->get();

echo "Total users returned: " . $users->count() . "\n\n";

$companyAUsers = $users->where('company_id', $companyA->id);
$companyBUsers = $users->where('company_id', $companyB->id);

echo "Company A users: " . $companyAUsers->count() . "\n";
echo "Company B users: " . $companyBUsers->count() . "\n\n";

if ($companyBUsers->count() > 0) {
    echo RED . "❌ FAIL: Company B users STILL visible\n" . RESET;
    foreach ($companyBUsers as $user) {
        echo "  - {$user->name} ({$user->email})\n";
    }
    exit(1);
} else {
    echo GREEN . "✅ PASS: Only Company A users visible\n" . RESET;
    exit(0);
}
