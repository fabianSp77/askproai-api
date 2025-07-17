<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;

// Disable 2FA for test user
$email = 'admin+1@askproai.de';

$user = PortalUser::where('email', $email)->first();

if ($user) {
    $user->two_factor_secret = null;
    $user->two_factor_recovery_codes = null;
    $user->two_factor_confirmed_at = null;
    $user->two_factor_enforced = false;
    $user->save();
    
    echo "2FA disabled for user!\n";
    echo "Email: {$email}\n";
    echo "You can now login without 2FA.\n";
} else {
    echo "User not found!\n";
}