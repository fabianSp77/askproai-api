<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;

echo "🔧 Resetting Demo Account Password\n";
echo "==================================\n\n";

$user = PortalUser::withoutGlobalScopes()->where('email', 'demo@example.com')->first();

if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

echo "User found: {$user->email}\n";

// Reset password to password123
$newPassword = 'password123';
$user->password = Hash::make($newPassword);
$user->is_active = 1;
$user->save();

echo "✅ Password reset to: $newPassword\n";
echo "✅ User is active\n";

// Verify
$isValid = Hash::check($newPassword, $user->password);
echo "✅ Password verification: " . ($isValid ? 'SUCCESS' : 'FAILED') . "\n";

// Also make sure company is active
if ($user->company) {
    $user->company->is_active = 1;
    $user->company->save();
    echo "✅ Company is active\n";
}

echo "\n✅ Everything is ready!\n";
echo "\nYou can now login at:\n";
echo "1. https://api.askproai.de/business/test-login (Quick test)\n";
echo "2. https://api.askproai.de/business/login (Normal login)\n";
echo "\nCredentials:\n";
echo "Email: demo@example.com\n";
echo "Password: password123\n";