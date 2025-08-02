<?php
// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

// Start session if not started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Final Working Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #1a1a1a; color: #fff; }
        .container { max-width: 800px; margin: 0 auto; background: #2a2a2a; padding: 30px; border-radius: 10px; box-shadow: 0 0 30px rgba(0,0,0,0.5); }
        h1 { color: #fff; text-align: center; border-bottom: 2px solid #4CAF50; padding-bottom: 20px; }
        .success { background: #4CAF50; color: white; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f44336; color: white; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #2196F3; color: white; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #ff9800; color: white; padding: 15px; border-radius: 5px; margin: 10px 0; }
        button { background: #4CAF50; color: white; border: none; padding: 12px 24px; font-size: 16px; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #45a049; }
        pre { background: #1a1a1a; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #444; }
        .code { font-family: 'Courier New', monospace; background: #1a1a1a; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Final Working Login</h1>
        
        <?php
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'login':
                    // Get demo user
                    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
                    
                    if (!$user) {
                        echo '<div class="error">Demo user not found!</div>';
                        break;
                    }
                    
                    // Method 1: Direct Auth login (avoiding problematic migrate)
                    try {
                        // Get the web guard
                        $guard = Auth::guard('web');
                        
                        // Set the user directly on the guard
                        $guard->setUser($user);
                        
                        // Get session store
                        $session = app('session.store');
                        
                        // Get the correct session key name
                        $sessionKeyReflection = new \ReflectionMethod($guard, 'getName');
                        $sessionKeyReflection->setAccessible(true);
                        $sessionKey = $sessionKeyReflection->invoke($guard);
                        
                        // Manually set session data with CORRECT key
                        $session->put($sessionKey, $user->id);
                        $session->put('password_hash_web', $user->password);
                        
                        // Save session
                        $session->save();
                        
                        echo '<div class="success">';
                        echo '<h3>✅ Login Method 1 Complete!</h3>';
                        echo '<p>User: ' . htmlspecialchars($user->email) . '</p>';
                        echo '<p>Auth Check: ' . (Auth::check() ? 'YES ✓' : 'NO ✗') . '</p>';
                        echo '<p>Session ID: ' . htmlspecialchars(session_id()) . '</p>';
                        echo '</div>';
                        
                    } catch (Exception $e) {
                        echo '<div class="error">Method 1 Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                    
                    // Method 2: Write session file directly
                    try {
                        $sessionPath = storage_path('framework/sessions');
                        $sessionId = session_id();
                        $sessionFile = $sessionPath . '/' . $sessionId;
                        
                        // Get the correct session key
                        $guard = Auth::guard('web');
                        $sessionKeyReflection = new \ReflectionMethod($guard, 'getName');
                        $sessionKeyReflection->setAccessible(true);
                        $correctSessionKey = $sessionKeyReflection->invoke($guard);
                        
                        // Create session data with CORRECT key
                        $sessionData = [
                            '_token' => csrf_token(),
                            $correctSessionKey => $user->id,
                            'password_hash_web' => $user->password,
                            '_previous' => ['url' => 'https://api.askproai.de/admin'],
                            '_flash' => ['old' => [], 'new' => []]
                        ];
                        
                        // Write to file
                        if ($sessionId && $sessionId !== '') {
                            file_put_contents($sessionFile, serialize($sessionData));
                            
                            echo '<div class="success">';
                            echo '<h3>✅ Login Method 2 Complete!</h3>';
                            echo '<p>Session file written: ' . htmlspecialchars(basename($sessionFile)) . '</p>';
                            echo '<p>File size: ' . filesize($sessionFile) . ' bytes</p>';
                            echo '</div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="warning">Method 2 Warning: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                    
                    echo '<div class="info">';
                    echo '<h3>Next Step:</h3>';
                    echo '<p><a href="/admin" style="color: white; font-weight: bold;">Click here to go to Admin Panel →</a></p>';
                    echo '</div>';
                    break;
                    
                case 'check':
                    echo '<div class="info"><h3>Session Check</h3></div>';
                    
                    // Check Auth
                    echo '<div class="warning"><h4>Authentication</h4><pre>';
                    echo "Auth::check(): " . (Auth::check() ? 'TRUE' : 'FALSE') . "\n";
                    if (Auth::check()) {
                        echo "User: " . Auth::user()->email . "\n";
                        echo "User ID: " . Auth::id() . "\n";
                    }
                    echo '</pre></div>';
                    
                    // Check Session
                    echo '<div class="warning"><h4>Session Data</h4><pre>';
                    $session = app('session.store');
                    $sessionData = $session->all();
                    foreach ($sessionData as $key => $value) {
                        if (is_array($value)) {
                            echo $key . ": " . json_encode($value) . "\n";
                        } else {
                            echo $key . ": " . substr((string)$value, 0, 50) . "\n";
                        }
                    }
                    echo '</pre></div>';
                    
                    // Check Session File
                    $sessionId = session_id();
                    if ($sessionId) {
                        $sessionFile = storage_path('framework/sessions/' . $sessionId);
                        echo '<div class="info"><h4>Session File</h4><pre>';
                        echo "Session ID: " . $sessionId . "\n";
                        echo "File exists: " . (file_exists($sessionFile) ? 'YES' : 'NO') . "\n";
                        if (file_exists($sessionFile)) {
                            echo "File size: " . filesize($sessionFile) . " bytes\n";
                            echo "Modified: " . date('Y-m-d H:i:s', filemtime($sessionFile)) . "\n";
                        }
                        echo '</pre></div>';
                    }
                    break;
                    
                case 'clear':
                    // Clear everything
                    Auth::logout();
                    session_destroy();
                    
                    echo '<div class="warning">';
                    echo '<h3>Session Cleared!</h3>';
                    echo '<p>All session data has been removed.</p>';
                    echo '</div>';
                    break;
            }
        } else {
            ?>
            <div class="info">
                <h3>Current Status</h3>
                <p>Auth: <?php echo Auth::check() ? '<span class="success">Logged In as ' . Auth::user()->email . '</span>' : '<span class="error">Not Logged In</span>'; ?></p>
                <p>Session ID: <code class="code"><?php echo session_id() ?: 'No Session'; ?></code></p>
            </div>
            
            <div class="warning">
                <h3>The Real Solution</h3>
                <p>This tool bypasses Laravel's problematic <code>session->migrate(true)</code> that destroys all data during login.</p>
            </div>
        <?php } ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="?action=login"><button>🔐 Login Now</button></a>
            <a href="?action=check"><button>🔍 Check Session</button></a>
            <a href="?action=clear"><button style="background: #f44336;">🗑️ Clear Session</button></a>
            <a href="/admin"><button>📊 Go to Admin</button></a>
        </div>
    </div>
</body>
</html>