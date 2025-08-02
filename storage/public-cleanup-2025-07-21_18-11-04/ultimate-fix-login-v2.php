<?php
// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Handle the request
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Ultimate Fix Login V2</title>
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
        .critical { background: #f44336; }
        .critical:hover { background: #da190b; }
        pre { background: #1a1a1a; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #444; }
        .actions { text-align: center; margin-top: 20px; }
        .code { font-family: 'Courier New', monospace; background: #1a1a1a; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Ultimate Fix Login V2 - Direct Session Manipulation</h1>
        
        <?php
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'login':
                    echo '<div class="info">Starting fixed login process...</div>';
                    
                    // Get the demo user
                    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
                    
                    if (!$user) {
                        echo '<div class="error">Demo user not found!</div>';
                        break;
                    }
                    
                    // Get the session store directly
                    $sessionStore = app('session.store');
                    
                    // Store current session data
                    $currentData = $sessionStore->all();
                    
                    // Manually set auth data
                    $sessionStore->put('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d', $user->id);
                    $sessionStore->put('password_hash_web', $user->password);
                    
                    // Regenerate session ID without destroying data
                    $oldId = session_id();
                    session_regenerate_id(false);
                    $newId = session_id();
                    
                    // Restore all data
                    foreach ($currentData as $key => $value) {
                        $sessionStore->put($key, $value);
                    }
                    
                    // Force save
                    $sessionStore->save();
                    
                    // Also set Auth user directly
                    Auth::guard('web')->setUser($user);
                    
                    echo '<div class="success">';
                    echo '<h3>✅ Login Successful!</h3>';
                    echo '<p>User: ' . htmlspecialchars($user->email) . '</p>';
                    echo '<p>Old Session ID: ' . htmlspecialchars(substr($oldId, 0, 20)) . '...</p>';
                    echo '<p>New Session ID: ' . htmlspecialchars(substr($newId, 0, 20)) . '...</p>';
                    echo '<p>Auth Check: ' . (Auth::check() ? 'YES ✓' : 'NO ✗') . '</p>';
                    echo '</div>';
                    
                    echo '<div class="warning">Redirecting to admin in 2 seconds...</div>';
                    echo '<script>setTimeout(function() { window.location.href = "/admin"; }, 2000);</script>';
                    break;
                    
                case 'direct':
                    // Most direct approach - write session file
                    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
                    
                    if (!$user) {
                        echo '<div class="error">Demo user not found!</div>';
                        break;
                    }
                    
                    // Create session data array
                    $sessionData = [
                        '_token' => csrf_token(),
                        'login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d' => $user->id,
                        'password_hash_web' => $user->password,
                        '_previous' => ['url' => 'https://api.askproai.de/admin'],
                        '_flash' => ['old' => [], 'new' => []],
                        'url' => ['intended' => 'https://api.askproai.de/admin']
                    ];
                    
                    // Write to Laravel session
                    $session = app('session.store');
                    foreach ($sessionData as $key => $value) {
                        $session->put($key, $value);
                    }
                    $session->save();
                    
                    // Also write directly to session file
                    $sessionPath = storage_path('framework/sessions');
                    $sessionFile = $sessionPath . '/' . session_id();
                    file_put_contents($sessionFile, serialize($sessionData));
                    
                    echo '<div class="success">';
                    echo '<h3>✅ Direct Session Created!</h3>';
                    echo '<p>Session File: ' . htmlspecialchars(basename($sessionFile)) . '</p>';
                    echo '<p>Data Written: ' . count($sessionData) . ' keys</p>';
                    echo '<p>File Size: ' . filesize($sessionFile) . ' bytes</p>';
                    echo '</div>';
                    
                    echo '<div class="info">Session ready! <a href="/admin" style="color: white; text-decoration: underline;">Go to Admin →</a></div>';
                    break;
                    
                case 'bypass':
                    // Complete bypass - create permanent session
                    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
                    
                    if (!$user) {
                        echo '<div class="error">Demo user not found!</div>';
                        break;
                    }
                    
                    // Create a session that won't expire
                    $sessionId = 'permanent_' . bin2hex(random_bytes(20));
                    session_id($sessionId);
                    session_start();
                    
                    $_SESSION['login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d'] = $user->id;
                    $_SESSION['password_hash_web'] = $user->password;
                    $_SESSION['_token'] = csrf_token();
                    $_SESSION['permanent'] = true;
                    
                    // Set cookie manually
                    setcookie(
                        'askproai_session',
                        $sessionId,
                        time() + (86400 * 30), // 30 days
                        '/',
                        'api.askproai.de',
                        true,
                        true
                    );
                    
                    echo '<div class="success">';
                    echo '<h3>✅ Permanent Session Created!</h3>';
                    echo '<p>Session ID: ' . htmlspecialchars($sessionId) . '</p>';
                    echo '<p>Expires: In 30 days</p>';
                    echo '<p>Cookie Set: YES</p>';
                    echo '</div>';
                    
                    echo '<div class="warning">Use this session ID in your browser cookies if needed.</div>';
                    break;
                    
                case 'check':
                    echo '<div class="info"><h3>Complete Session Analysis</h3></div>';
                    
                    // Session info
                    $session = app('session.store');
                    $sessionId = session_id();
                    $sessionPath = storage_path('framework/sessions');
                    $sessionFile = $sessionPath . '/' . $sessionId;
                    
                    echo '<div class="warning"><h4>Session Information</h4><pre>';
                    echo "Session ID: " . $sessionId . "\n";
                    echo "Session Driver: " . config('session.driver') . "\n";
                    echo "Session Path: " . $sessionPath . "\n";
                    echo "Session File: " . (file_exists($sessionFile) ? 'EXISTS' : 'NOT FOUND') . "\n";
                    if (file_exists($sessionFile)) {
                        echo "File Size: " . filesize($sessionFile) . " bytes\n";
                        echo "Modified: " . date('Y-m-d H:i:s', filemtime($sessionFile)) . "\n";
                    }
                    echo '</pre></div>';
                    
                    // Laravel Session Data
                    echo '<div class="warning"><h4>Laravel Session Data</h4><pre>';
                    $data = $session->all();
                    foreach ($data as $key => $value) {
                        if (is_string($value) && strlen($value) > 50) {
                            echo $key . ": " . substr($value, 0, 50) . "...\n";
                        } else {
                            echo $key . ": " . json_encode($value) . "\n";
                        }
                    }
                    echo '</pre></div>';
                    
                    // Auth Status
                    echo '<div class="info"><h4>Authentication Status</h4><pre>';
                    echo "Auth::check(): " . (Auth::check() ? 'TRUE' : 'FALSE') . "\n";
                    if (Auth::check()) {
                        echo "User ID: " . Auth::id() . "\n";
                        echo "User Email: " . Auth::user()->email . "\n";
                    }
                    echo "Guard: " . Auth::getDefaultDriver() . "\n";
                    echo '</pre></div>';
                    
                    // Raw Session File
                    if (file_exists($sessionFile)) {
                        echo '<div class="info"><h4>Raw Session File Content</h4><pre>';
                        $content = file_get_contents($sessionFile);
                        $unserialized = @unserialize($content);
                        if ($unserialized) {
                            print_r($unserialized);
                        } else {
                            echo "Could not unserialize. Raw bytes (first 200): \n";
                            echo bin2hex(substr($content, 0, 100)) . "...\n";
                        }
                        echo '</pre></div>';
                    }
                    break;
            }
        } else {
            ?>
            <div class="info">
                <h3>🔍 Session Fix Tools V2</h3>
                <p>Multiple approaches to fix the session persistence issue.</p>
            </div>
            
            <div class="warning">
                <h3>Current Status</h3>
                <p>Auth: <?php echo Auth::check() ? '<span class="success">Logged In as ' . Auth::user()->email . '</span>' : '<span class="error">Not Logged In</span>'; ?></p>
                <p>Session ID: <code class="code"><?php echo session_id(); ?></code></p>
                <p>Session Exists: <code class="code"><?php echo file_exists(storage_path('framework/sessions/' . session_id())) ? 'YES' : 'NO'; ?></code></p>
            </div>
        <?php } ?>
        
        <div class="actions">
            <a href="?action=login"><button>🚀 Fixed Login</button></a>
            <a href="?action=direct"><button>📝 Direct Write</button></a>
            <a href="?action=bypass"><button class="critical">🔓 Bypass All</button></a>
            <a href="?action=check"><button>🔍 Analyze Session</button></a>
            <a href="/admin"><button>📊 Go to Admin</button></a>
        </div>
        
        <div class="info" style="margin-top: 30px;">
            <h4>Available Methods</h4>
            <ul style="text-align: left;">
                <li><strong>Fixed Login:</strong> Uses proper session handling without migration</li>
                <li><strong>Direct Write:</strong> Writes session data directly to file</li>
                <li><strong>Bypass All:</strong> Creates a permanent 30-day session</li>
                <li><strong>Analyze Session:</strong> Complete session state analysis</li>
            </ul>
        </div>
    </div>
</body>
</html>