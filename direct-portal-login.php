<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

// Find the user
$user = PortalUser::where('email', 'fabianspitzer@icloud.com')->first();

if (!$user) {
    die("User not found\n");
}

echo "User found: " . $user->email . "\n";
echo "User ID: " . $user->id . "\n";
echo "User active: " . ($user->is_active ? 'YES' : 'NO') . "\n";
echo "Company ID: " . $user->company_id . "\n";

// Check password
$passwordCorrect = Hash::check('demo123', $user->password);
echo "Password correct: " . ($passwordCorrect ? 'YES' : 'NO') . "\n";

if (!$passwordCorrect) {
    echo "Password hash: " . $user->password . "\n";
    
    // Try to set the password
    echo "\nSetting password to 'demo123'...\n";
    $user->password = Hash::make('demo123');
    $user->save();
    echo "Password updated!\n";
}

// Check 2FA
echo "2FA Secret: " . ($user->two_factor_secret ? 'SET' : 'NOT SET') . "\n";
echo "2FA Confirmed: " . ($user->two_factor_confirmed_at ? $user->two_factor_confirmed_at : 'NOT CONFIRMED') . "\n";
echo "Requires 2FA: " . ($user->requires2FA() ? 'YES' : 'NO') . "\n";

// If 2FA is causing issues, disable it temporarily
if ($user->two_factor_secret || $user->two_factor_confirmed_at) {
    echo "\nDisabling 2FA temporarily...\n";
    $user->two_factor_secret = null;
    $user->two_factor_confirmed_at = null;
    $user->save();
    echo "2FA disabled!\n";
}

echo "\nUser is ready for login without 2FA.\n";
echo "Use email: fabianspitzer@icloud.com\n";
echo "Use password: demo123\n";