<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

// Find the test company
$company = Company::where('name', 'LIKE', '%Krückeberg%')->first();

if (!$company) {
    echo "Company 'Krückeberg Servicegruppe' not found!\n";
    
    // List available companies
    $companies = Company::all();
    echo "\nAvailable companies:\n";
    foreach ($companies as $c) {
        echo "- ID: {$c->id}, Name: {$c->name}\n";
    }
    
    // Use first company as fallback
    $company = Company::first();
    if ($company) {
        echo "\nUsing company: {$company->name}\n";
    } else {
        die("No companies found in database!\n");
    }
}

// Create or update test user
$testEmail = 'test@askproai.de';
$testPassword = 'Test123!';

$user = PortalUser::where('email', $testEmail)->first();

if ($user) {
    // Update existing user
    $user->password = Hash::make($testPassword);
    // Don't change company_id for existing users
    $user->is_active = true;
    // No email_verified_at field in portal_users table
    $user->save();
    echo "Updated existing user\n";
    echo "Using existing company: " . $user->company->name . "\n";
    $company = $user->company; // Use the user's existing company
} else {
    // Create new user
    $user = PortalUser::create([
        'name' => 'Test User',
        'email' => $testEmail,
        'password' => Hash::make($testPassword),
        'company_id' => $company->id,
        'is_active' => true,
        'role' => 'admin',
        'permissions' => json_encode([
            'calls.view_all' => true,
            'calls.edit_all' => true,
            'billing.view' => true,
            'billing.manage' => true,
            'settings.manage' => true,
        ])
    ]);
    echo "Created new user\n";
}

echo "\n✅ Test user ready!\n";
echo "=====================================\n";
echo "Email: {$testEmail}\n";
echo "Password: {$testPassword}\n";
echo "Company: {$company->name}\n";
echo "Company ID: {$company->id}\n";
echo "User ID: {$user->id}\n";
echo "=====================================\n";
echo "\nLogin URL: https://api.askproai.de/business/login\n";

// Verify user can be found
$verifyUser = PortalUser::where('email', $testEmail)->first();
if ($verifyUser && Hash::check($testPassword, $verifyUser->password)) {
    echo "\n✅ Password verification successful!\n";
} else {
    echo "\n❌ Password verification failed!\n";
}