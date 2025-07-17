<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\PortalUser;

echo "\n🔍 AUTH STATE DEBUG\n";
echo "==================\n\n";

// Start session
Session::start();

echo "Session Info:\n";
echo "- ID: " . Session::getId() . "\n";
echo "- Has portal_user_id: " . (Session::has("portal_user_id") ? "YES" : "NO") . "\n";
echo "- portal_user_id value: " . Session::get("portal_user_id", "NOT SET") . "\n";

// Check auth
echo "\nAuth Status:\n";
echo "- Portal guard check: " . (Auth::guard("portal")->check() ? "YES" : "NO") . "\n";

if (Session::has("portal_user_id")) {
    $userId = Session::get("portal_user_id");
    $user = PortalUser::find($userId);
    
    if ($user) {
        echo "\nUser from session:\n";
        echo "- Email: " . $user->email . "\n";
        echo "- Active: " . ($user->is_active ? "YES" : "NO") . "\n";
        echo "- Company: " . $user->company->name . "\n";
        echo "- Company Active: " . ($user->company->is_active ? "YES" : "NO") . "\n";
        
        // Try to login the user
        Auth::guard("portal")->login($user);
        echo "\nAfter manual login:\n";
        echo "- Auth check: " . (Auth::guard("portal")->check() ? "YES" : "NO") . "\n";
    }
}

echo "\nAll session data:\n";
print_r(Session::all());
