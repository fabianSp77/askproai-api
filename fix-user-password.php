<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = 'fabian@askproai.de';
$newPassword = 'Qwe421as1!1';

echo "Fixing password for user: {$email}\n";
echo "=====================================\n\n";

$user = User::where('email', $email)->first();

if (!$user) {
    echo "ERROR: User not found!\n";
    exit(1);
}

echo "Found user: {$user->name} (ID: {$user->id})\n";
echo "Current password hash: " . substr($user->password, 0, 30) . "...\n\n";

// Generate new password hash
$newHash = Hash::make($newPassword);
echo "New password hash: " . substr($newHash, 0, 30) . "...\n\n";

// Update the password
$user->password = $newHash;
$user->save();

echo "Password updated successfully!\n\n";

// Verify the new password works
$verified = Hash::check($newPassword, $user->password);
echo "Password verification: " . ($verified ? 'SUCCESS' : 'FAILED') . "\n";

// Test authentication
$attempt = \Illuminate\Support\Facades\Auth::attempt(['email' => $email, 'password' => $newPassword]);
echo "Auth attempt: " . ($attempt ? 'SUCCESS' : 'FAILED') . "\n";

if ($attempt) {
    echo "\n✅ Authentication successful! User can now login with the new password.\n";
} else {
    echo "\n❌ Authentication still failing. There might be another issue.\n";
}