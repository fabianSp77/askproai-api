<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

echo "🧹 CLEARING ALL SESSIONS AND DOING FRESH LOGIN\n";
echo "==============================================\n\n";

// 1. Clear all auth guards
echo "1️⃣ Clearing all auth guards...\n";
Auth::guard('web')->logout();
Auth::guard('portal')->logout();
Auth::guard('customer')->logout();
Session::flush();
echo "✅ All guards cleared\n";

// 2. Clear session table for current session
echo "\n2️⃣ Clearing session data...\n";
$currentSessionId = Session::getId();
if ($currentSessionId) {
    DB::table('sessions')->where('id', $currentSessionId)->delete();
    echo "✅ Current session deleted from database\n";
}

// 3. Start fresh session
Session::regenerate(true);
echo "✅ New session created: " . Session::getId() . "\n";

// 4. Get the portal user
echo "\n3️⃣ Getting portal user...\n";
$user = PortalUser::where('email', 'fabianspitzer@icloud.com')->first();

if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

echo "✅ User found: {$user->email} (ID: {$user->id})\n";

// 5. Login the user
echo "\n4️⃣ Logging in user...\n";
Auth::guard('portal')->login($user);

// Store in session
Session::put('portal_user_id', $user->id);
Session::put('portal_login', $user->id);
Session::save();

echo "✅ User logged in!\n";

// 6. Verify
echo "\n5️⃣ Verification:\n";
echo "- Portal auth check: " . (Auth::guard('portal')->check() ? 'YES' : 'NO') . "\n";
echo "- Portal user ID: " . (Auth::guard('portal')->user() ? Auth::guard('portal')->user()->id : 'none') . "\n";
echo "- Session portal_user_id: " . Session::get('portal_user_id', 'not set') . "\n";

echo "\n✅ Fresh login complete!\n";
echo "\nNow go to: https://api.askproai.de/business/dashboard\n";