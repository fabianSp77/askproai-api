<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

// Use existing user ID 1 (admin+1)
$user = PortalUser::find(1);

if (!$user) {
    die("User not found!\n");
}

$simplePassword = 'test123';

echo "Updating user admin+1@askproai.de...\n";
echo "=====================================\n";

// Update password
$user->password = Hash::make($simplePassword);
$user->save();

echo "✅ Password updated!\n";
echo "   - User: {$user->name}\n";
echo "   - Company: {$user->company->name}\n";
echo "   - Billing Type: {$user->company->billing_type}\n";

// Verify
if (Hash::check($simplePassword, $user->fresh()->password)) {
    echo "✅ Password verification successful!\n";
}

echo "\n=====================================\n";
echo "🔐 SIMPLE LOGIN CREDENTIALS:\n";
echo "=====================================\n";
echo "Email: admin+1@askproai.de\n";
echo "Password: test123\n";
echo "=====================================\n";
echo "\nLogin at: https://api.askproai.de/business/login\n";