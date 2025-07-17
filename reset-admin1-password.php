<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;

$email = 'admin+1@askproai.de';
$newPassword = 'Test123!';

echo "Resetting password for: $email\n";
echo "=====================================\n";

// Find user
$user = PortalUser::where('email', $email)->first();

if (!$user) {
    die("❌ User not found!\n");
}

echo "✅ User found:\n";
echo "   - ID: {$user->id}\n";
echo "   - Name: {$user->name}\n";
echo "   - Company: {$user->company->name}\n";
echo "   - Billing Type: {$user->company->billing_type}\n";
echo "   - Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";

// Reset password
$user->password = Hash::make($newPassword);

// Disable 2FA
$user->two_factor_secret = null;
$user->two_factor_recovery_codes = null;
$user->two_factor_confirmed_at = null;
$user->two_factor_enforced = false;

// Save
$user->save();

echo "\n✅ Password reset successfully!\n";
echo "✅ 2FA disabled!\n";

// Verify
if (Hash::check($newPassword, $user->fresh()->password)) {
    echo "✅ Password verification successful!\n";
} else {
    echo "❌ Password verification failed!\n";
}

echo "\n=====================================\n";
echo "📋 Login Details:\n";
echo "   Email: $email\n";
echo "   Password: $newPassword\n";
echo "   Company: {$user->company->name}\n";
echo "   Login URL: https://api.askproai.de/business/login\n";
echo "=====================================\n";