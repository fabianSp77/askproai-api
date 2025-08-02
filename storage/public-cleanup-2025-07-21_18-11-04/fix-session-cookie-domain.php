<?php
/**
 * Fix Session Cookie Domain Issue
 * 
 * This temporarily modifies session config to test if domain is the issue
 */

require_once __DIR__ . '/../vendor/autoload.php';

// BEFORE bootstrapping, override session config
$_ENV['SESSION_DOMAIN'] = null; // Allow cookie on any subdomain
$_ENV['SESSION_SECURE_COOKIE'] = 'false'; // Allow on HTTP for testing

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Auth;

echo "<h1>Session Cookie Domain Fix Test</h1>";

echo "<h2>Current Configuration:</h2>";
echo "<pre>";
echo "SESSION_DOMAIN: " . var_export(config('session.domain'), true) . "\n";
echo "SESSION_SECURE_COOKIE: " . var_export(config('session.secure'), true) . "\n";
echo "REQUEST_HOST: " . request()->getHost() . "\n";
echo "REQUEST_SECURE: " . (request()->isSecure() ? 'YES' : 'NO') . "\n";
echo "</pre>";

if (isset($_GET['login'])) {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    
    if ($user) {
        Auth::logout();
        session()->flush();
        session()->regenerate();
        
        // Override session config at runtime
        config(['session.domain' => null]);
        config(['session.secure' => false]);
        
        Auth::login($user, true);
        session()->save();
        
        echo "<h2>Login Test:</h2>";
        echo "<p>Auth::check() = " . (Auth::check() ? 'TRUE ✅' : 'FALSE ❌') . "</p>";
        
        if (Auth::check()) {
            echo "<p>Logged in as: " . Auth::user()->email . "</p>";
            
            // Show what cookie will be set
            echo "<h3>Cookie that will be set:</h3>";
            echo "<pre>";
            $cookieParams = session_get_cookie_params();
            print_r($cookieParams);
            echo "</pre>";
            
            echo '<p><a href="/admin" style="padding: 10px 20px; background: green; color: white; text-decoration: none;">Go to Admin (Test if session persists)</a></p>';
        }
    }
} else {
    echo '<p><a href="?login=1" style="padding: 10px 20px; background: blue; color: white; text-decoration: none;">Test Login with Fixed Config</a></p>';
}

echo "<h2>Debug Info:</h2>";
echo "<pre>";
echo "Cookies received:\n";
print_r($_COOKIE);
echo "\nSession ID: " . session()->getId() . "\n";
echo "</pre>";
?>