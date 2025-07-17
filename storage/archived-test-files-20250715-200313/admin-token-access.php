<?php
require_once __DIR__ . "/../vendor/autoload.php";
$app = require_once __DIR__ . "/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\User;

$token = $_GET["token"] ?? "";

if ($token) {
    $userId = \Illuminate\Support\Facades\Cache::get("admin_access_token_" . $token);
    
    if ($userId) {
        $user = User::find($userId);
        if ($user) {
            // Start session
            Session::start();
            
            // Login user
            Auth::login($user);
            
            // Force session save
            Session::save();
            
            // Set additional cookie for bypass
            setcookie("admin_bypass", $user->id, time() + 7200, "/", "", true, true);
            
            // Clear token
            \Illuminate\Support\Facades\Cache::forget("admin_access_token_" . $token);
            
            // Redirect to admin
            header("Location: /admin");
            exit;
        }
    }
}

echo "Invalid or expired token";
