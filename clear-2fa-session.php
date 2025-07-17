<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Clear all 2FA related sessions
echo "Clearing 2FA sessions...\n";

// Clear Laravel sessions that might contain 2FA data
DB::table('sessions')->where('payload', 'LIKE', '%portal_2fa_user%')->delete();

echo "✅ Sessions cleared!\n";

// Also ensure the user doesn't have 2FA requirements
$user = \App\Models\PortalUser::where('email', 'admin+1@askproai.de')->first();
if ($user) {
    $user->two_factor_enforced = false;
    $user->save();
    echo "✅ 2FA enforcement disabled for admin+1@askproai.de\n";
}

echo "\nYou should now be able to login directly without 2FA issues.\n";
echo "Try logging in again at: https://api.askproai.de/business/login\n";