<?php
/**
 * Session Cleanup Fix
 * 
 * PROBLEM: Session files contain duplicate auth keys:
 * - login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d (Standard Laravel)
 * - login_web_f091f34ca659bece7fff5e7c0e9971e22d1ee510 (Our CustomSessionGuard)
 * 
 * This causes Auth::check() to fail because Laravel finds conflicting data.
 * 
 * SOLUTION: Clean up session to contain only ONE correct key.
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

// Initialize output
$output = [];
$output[] = "🧹 Session Cleanup Fix - " . date('Y-m-d H:i:s');
$output[] = str_repeat("=", 60);

// Get current session info
$session = app('session.store');
$sessionId = $session->getId();
$sessionPath = storage_path('framework/sessions');
$sessionFile = $sessionPath . '/' . $sessionId;

$output[] = "Session ID: " . $sessionId;
$output[] = "Session File: " . $sessionFile;
$output[] = "";

// Check if session file exists
if (!file_exists($sessionFile)) {
    $output[] = "❌ Session file does not exist!";
    displayOutput($output);
    exit;
}

// Read current session data
$fileContent = file_get_contents($sessionFile);
$sessionData = @unserialize($fileContent);

if (!$sessionData || !is_array($sessionData)) {
    $output[] = "❌ Could not read session data!";
    displayOutput($output);
    exit;
}

$output[] = "📋 Current session keys:";
foreach ($sessionData as $key => $value) {
    if (strpos($key, 'login_web_') === 0) {
        $output[] = "  - $key => " . (is_scalar($value) ? $value : gettype($value));
    }
}
$output[] = "";

// Find all login_web_ keys
$loginKeys = array_filter(array_keys($sessionData), function($key) {
    return strpos($key, 'login_web_') === 0;
});

if (count($loginKeys) > 1) {
    $output[] = "⚠️  MULTIPLE LOGIN KEYS FOUND! This is the problem!";
    $output[] = "";
    
    // Get the correct key from our CustomSessionGuard
    $guard = Auth::guard('web');
    $reflection = new ReflectionMethod($guard, 'getName');
    $reflection->setAccessible(true);
    $correctKey = $reflection->invoke($guard);
    
    $output[] = "🔍 Guard class: " . get_class($guard);
    $output[] = "🔑 Correct key: " . $correctKey;
    
    // Keep only the correct key
    $userId = null;
    foreach ($loginKeys as $key) {
        if ($key === $correctKey) {
            $userId = $sessionData[$key];
            $output[] = "✅ Keeping: $key => $userId";
        } else {
            unset($sessionData[$key]);
            $output[] = "🗑️  Removing: $key";
        }
    }
    
    // Write cleaned session data back
    $cleanedContent = serialize($sessionData);
    file_put_contents($sessionFile, $cleanedContent);
    
    $output[] = "";
    $output[] = "✅ Session cleaned and saved!";
    
    // Force Laravel to reload session
    $session->setId($sessionId);
    $session->start();
    
    // Verify auth works now
    $output[] = "";
    $output[] = "🔍 Verification:";
    $output[] = "Auth::check() = " . (Auth::check() ? 'TRUE ✅' : 'FALSE ❌');
    
    if (Auth::check()) {
        $output[] = "Auth::id() = " . Auth::id();
        $output[] = "Auth::user()->email = " . Auth::user()->email;
    }
    
} else if (count($loginKeys) == 1) {
    $output[] = "✅ Only one login key found (good)";
    $output[] = "Key: " . $loginKeys[0];
    $output[] = "Auth::check() = " . (Auth::check() ? 'TRUE ✅' : 'FALSE ❌');
} else {
    $output[] = "❌ No login keys found in session";
}

// Show all session data for debugging
$output[] = "";
$output[] = "📊 Full session data after cleanup:";
$output[] = print_r($sessionData, true);

// Test login if requested
if (isset($_GET['login'])) {
    $output[] = "";
    $output[] = "🔐 Testing login...";
    
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    if ($user) {
        Auth::login($user, true);
        
        // Clean session immediately after login
        cleanupSessionAfterLogin();
        
        $output[] = "✅ Login executed";
        $output[] = "Auth::check() = " . (Auth::check() ? 'TRUE ✅' : 'FALSE ❌');
        
        // Redirect after showing output
        $output[] = "";
        $output[] = "🔄 Redirecting to /admin in 2 seconds...";
        $output[] = '<script>setTimeout(() => window.location.href = "/admin", 2000);</script>';
    } else {
        $output[] = "❌ Demo user not found";
    }
}

function cleanupSessionAfterLogin() {
    $session = app('session.store');
    $sessionId = $session->getId();
    $sessionFile = storage_path('framework/sessions') . '/' . $sessionId;
    
    if (file_exists($sessionFile)) {
        $sessionData = @unserialize(file_get_contents($sessionFile));
        
        if ($sessionData && is_array($sessionData)) {
            // Get correct key
            $guard = Auth::guard('web');
            $reflection = new ReflectionMethod($guard, 'getName');
            $reflection->setAccessible(true);
            $correctKey = $reflection->invoke($guard);
            
            // Remove all login keys except the correct one
            $userId = null;
            foreach ($sessionData as $key => $value) {
                if (strpos($key, 'login_web_') === 0 && $key !== $correctKey) {
                    unset($sessionData[$key]);
                } else if ($key === $correctKey) {
                    $userId = $value;
                }
            }
            
            // Save cleaned data
            file_put_contents($sessionFile, serialize($sessionData));
        }
    }
}

function displayOutput($output) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Session Cleanup Fix</title>
        <style>
            body { 
                font-family: 'Courier New', monospace; 
                background: #000; 
                color: #0f0; 
                padding: 20px;
                line-height: 1.6;
            }
            .container { 
                max-width: 1000px; 
                margin: 0 auto; 
                background: #0a0a0a;
                border: 2px solid #0f0;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 0 20px rgba(0,255,0,0.5);
            }
            h1 { 
                color: #0f0; 
                text-align: center;
                text-shadow: 0 0 10px #0f0;
                margin-bottom: 30px;
            }
            pre { 
                white-space: pre-wrap; 
                word-wrap: break-word; 
                margin: 0;
            }
            .actions { 
                margin: 30px 0; 
                text-align: center;
            }
            a { 
                color: #0ff; 
                text-decoration: none; 
                padding: 15px 30px; 
                border: 2px solid #0ff; 
                display: inline-block; 
                margin: 10px;
                border-radius: 5px;
                transition: all 0.3s;
                font-weight: bold;
            }
            a:hover { 
                background: #0ff; 
                color: #000;
                box-shadow: 0 0 15px #0ff;
            }
            .warning { 
                color: #ff0; 
                font-weight: bold;
            }
            .error { 
                color: #f00; 
                font-weight: bold;
            }
            .success { 
                color: #0f0; 
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🧹 Session Cleanup Fix</h1>
            
            <div class="actions">
                <a href="?">Run Cleanup</a>
                <a href="?login=1">Cleanup + Login</a>
                <a href="/admin">Go to Admin</a>
                <a href="/session-lifecycle-tracker.php">Session Tracker</a>
            </div>
            
            <pre><?php 
                foreach ($output as $line) {
                    if (strpos($line, '❌') !== false) {
                        echo '<span class="error">' . htmlspecialchars($line) . '</span>' . "\n";
                    } else if (strpos($line, '⚠️') !== false) {
                        echo '<span class="warning">' . htmlspecialchars($line) . '</span>' . "\n";
                    } else if (strpos($line, '✅') !== false) {
                        echo '<span class="success">' . htmlspecialchars($line) . '</span>' . "\n";
                    } else if (strpos($line, '<script>') !== false) {
                        echo $line . "\n"; // Don't escape script tag
                    } else {
                        echo htmlspecialchars($line) . "\n";
                    }
                }
            ?></pre>
        </div>
    </body>
    </html>
    <?php
}

displayOutput($output);
?>