<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

// Get AskProAI company
$company = Company::where('name', 'AskProAI')->first();

if (!$company) {
    echo "Company not found!\n";
    exit;
}

// Create new test user
$email = 'test@askproai.de';
$password = 'test123';

// Delete if exists
PortalUser::where('email', $email)->delete();

// Create new user
$user = PortalUser::create([
    'name' => 'Test User',
    'email' => $email,
    'password' => Hash::make($password),
    'company_id' => $company->id,
    'is_active' => true,
    'is_primary' => false,
    'email_verified_at' => now(),
]);

echo "Test user created successfully!\n";
echo "Email: {$email}\n";
echo "Password: {$password}\n";
echo "Company: {$company->name}\n";
echo "\nLogin URL: https://api.askproai.de/business/login\n";