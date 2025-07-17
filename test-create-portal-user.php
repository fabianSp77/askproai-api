<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

// Get the first company
$company = Company::first();

if (!$company) {
    echo "No company found.\n";
    exit(1);
}

// Check if test user already exists
$testUser = PortalUser::where('email', 'test@askproai.de')->first();

if ($testUser) {
    echo "Test user already exists:\n";
    echo "Email: {$testUser->email}\n";
    echo "Company: {$testUser->company->name}\n";
    
    // Update password
    $testUser->password = Hash::make('test123');
    $testUser->save();
    echo "\nPassword reset to: test123\n";
} else {
    // Create test user
    $testUser = PortalUser::create([
        'name' => 'Test User',
        'email' => 'test@askproai.de',
        'password' => Hash::make('test123'),
        'company_id' => $company->id,
        'branch_id' => $company->branches()->first()?->id,
        'is_active' => true,
        'role' => 'admin'
    ]);
    
    echo "Test user created:\n";
    echo "Email: {$testUser->email}\n";
    echo "Password: test123\n";
    echo "Company: {$company->name}\n";
}

echo "\nLogin URL: https://api.askproai.de/business/login\n";