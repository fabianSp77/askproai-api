<?php
// Session Test & Debug

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

// Fehler anzeigen
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session Info
echo "<h2>Session Configuration</h2>";
echo "<pre>";
echo "Session Driver: " . config('session.driver') . "\n";
echo "Session Cookie: " . config('session.cookie') . "\n";
echo "Session Domain: " . (config('session.domain') ?: 'null') . "\n";
echo "Session Path: " . config('session.path') . "\n";
echo "Session Secure: " . (config('session.secure') ? 'true' : 'false') . "\n";
echo "Session Same Site: " . (config('session.same_site') ?: 'null') . "\n";
echo "</pre>";

// PHP Session Info
echo "<h2>PHP Session</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "</pre>";

// Laravel Session
echo "<h2>Laravel Session</h2>";
echo "<pre>";
echo "Laravel Session ID: " . Session::getId() . "\n";
echo "Session Data:\n";
print_r(Session::all());
echo "</pre>";

// Cookies
echo "<h2>Cookies</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

// Auth Status
echo "<h2>Authentication</h2>";
echo "<pre>";
echo "Auth Check: " . (Auth::check() ? 'true' : 'false') . "\n";
if (Auth::check()) {
    echo "User: " . Auth::user()->email . "\n";
}
echo "</pre>";

// Test Login
if (isset($_GET['login'])) {
    $admin = User::where('email', 'admin@askproai.de')
        ->orWhere('email', 'fabian@askproai.de')
        ->first();
        
    if ($admin) {
        Auth::guard('web')->loginUsingId($admin->id, true);
        Session::save();
        echo "<div style='background: green; color: white; padding: 10px;'>Login successful! Refresh page to see session.</div>";
    }
}

// Links
echo "<hr>";
echo "<a href='?login=1'>Test Login</a> | ";
echo "<a href='/admin'>Go to Admin</a> | ";
echo "<a href='test-session.php'>Refresh</a>";