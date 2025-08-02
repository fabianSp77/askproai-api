<?php
/**
 * Verify Session Fix - Direct Test
 * 
 * Simple test to verify login now works correctly
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Auth;

// Style
echo '<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.info { color: blue; }
pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
.button { display: inline-block; padding: 10px 20px; margin: 10px 5px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
.button:hover { background: #0056b3; }
</style>';

echo "<h1>Verify Session Fix</h1>";

// Current status
echo "<h2>Current Status:</h2>";
echo "<p>Auth::check() = <span class='" . (Auth::check() ? "success" : "error") . "'>" . (Auth::check() ? "TRUE" : "FALSE") . "</span></p>";

if (Auth::check()) {
    echo "<p class='success'>✅ You are logged in as: " . Auth::user()->email . "</p>";
    echo '<a href="/admin" class="button">Go to Admin Panel</a>';
} else {
    echo "<p class='info'>Not logged in. Click below to test login.</p>";
}

// Login test
if (isset($_GET['login'])) {
    echo "<h2>Login Test:</h2>";
    echo "<pre>";
    
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    
    if (!$user) {
        echo "❌ Demo user not found\n";
        exit;
    }
    
    echo "✅ Found user: " . $user->email . "\n";
    
    // Clear any existing session
    Auth::logout();
    session()->flush();
    session()->regenerate();
    echo "✅ Cleared existing session\n";
    
    // Attempt login
    Auth::login($user, true);
    echo "✅ Called Auth::login()\n";
    
    // Check result
    if (Auth::check()) {
        echo "✅ Auth::check() = TRUE\n";
        echo "✅ Auth::id() = " . Auth::id() . "\n";
        
        // Check session file
        $sessionId = session()->getId();
        $sessionFile = storage_path('framework/sessions/' . $sessionId);
        
        if (file_exists($sessionFile)) {
            $sessionData = unserialize(file_get_contents($sessionFile));
            $loginKeys = array_filter(array_keys($sessionData), function($key) {
                return strpos($key, 'login_web_') === 0;
            });
            
            echo "✅ Session file contains " . count($loginKeys) . " login key(s)\n";
            foreach ($loginKeys as $key) {
                echo "   - $key => " . $sessionData[$key] . "\n";
            }
        }
        
        echo "\n<span class='success'>🎉 LOGIN SUCCESSFUL!</span>\n";
        echo "</pre>";
        
        echo '<p><a href="/admin" class="button">Go to Admin Panel</a></p>';
        
        // Auto redirect
        echo '<p class="info">Redirecting to admin panel in 3 seconds...</p>';
        echo '<script>setTimeout(() => window.location.href = "/admin", 3000);</script>';
        
    } else {
        echo "❌ Auth::check() = FALSE\n";
        echo "❌ Login failed!\n";
        echo "</pre>";
    }
} else {
    echo '<p><a href="?login=1" class="button">Test Login</a></p>';
}

// Debug info
echo "<h2>Debug Information:</h2>";
echo "<pre>";
echo "Session Driver: " . config('session.driver') . "\n";
echo "Session Cookie: " . config('session.cookie') . "\n";
echo "Guard Class: " . get_class(Auth::guard('web')) . "\n";

// Check middleware
$kernel = app(\App\Http\Kernel::class);
$reflection = new ReflectionClass($kernel);
$prop = $reflection->getProperty('middlewareGroups');
$prop->setAccessible(true);
$groups = $prop->getValue($kernel);

$hasCleanup = false;
foreach ($groups['web'] ?? [] as $mw) {
    if (strpos($mw, 'CleanDuplicateSessionKeys') !== false) {
        $hasCleanup = true;
        break;
    }
}

echo "CleanDuplicateSessionKeys: " . ($hasCleanup ? "✅ Active" : "❌ Not found") . "\n";
echo "</pre>";
?>