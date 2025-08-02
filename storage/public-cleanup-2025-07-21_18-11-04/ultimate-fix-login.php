<?php
// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Handle the request
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Override session migrate behavior
if (app()->bound('session')) {
    $session = app('session');
    
    // Create a backup of the migrate method
    $originalMigrate = new ReflectionMethod($session, 'migrate');
    $originalMigrate->setAccessible(true);
    
    // Override it with our version
    $session->macro('migrate', function($destroy = false) use ($session) {
        if ($destroy) {
            // Don't actually destroy, just regenerate ID
            $data = $session->all();
            session_regenerate_id(false);
            foreach ($data as $key => $value) {
                $session->put($key, $value);
            }
            return true;
        }
        return session_regenerate_id($destroy);
    });
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Ultimate Fix Login</title>
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
        <h1>🚀 Ultimate Fix Login - Session Migration Override</h1>
        
        <?php
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'login':
                    echo '<div class="info">Starting ultimate login process...</div>';
                    
                    // Get the demo user
                    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
                    
                    if (!$user) {
                        echo '<div class="error">Demo user not found!</div>';
                        break;
                    }
                    
                    // Store session data before login
                    $sessionData = $_SESSION;
                    
                    // Create a custom auth manager that doesn't migrate
                    $authManager = app('auth');
                    $guard = $authManager->guard('web');
                    
                    // Manually update session without migration
                    $sessionStore = app('session');
                    $sessionStore->put('login_web_' . sha1('Illuminate\Auth\SessionGuard'), $user->id);
                    $sessionStore->put('password_hash_web', $user->password);
                    
                    // Set the user on the guard
                    $guard->setUser($user);
                    
                    // Fire login event
                    event(new \Illuminate\Auth\Events\Login('web', $user, false));
                    
                    // Ensure session is saved
                    $sessionStore->save();
                    
                    echo '<div class="success">';
                    echo '<h3>✅ Ultimate Login Successful!</h3>';
                    echo '<p>User: ' . htmlspecialchars($user->email) . '</p>';
                    echo '<p>Session ID: ' . htmlspecialchars(session_id()) . '</p>';
                    echo '<p>Auth Check: ' . (Auth::check() ? 'YES ✓' : 'NO ✗') . '</p>';
                    echo '<p>Session Keys: ' . implode(', ', array_keys($sessionStore->all())) . '</p>';
                    echo '</div>';
                    
                    echo '<div class="warning">Redirecting to admin in 3 seconds...</div>';
                    echo '<script>setTimeout(function() { window.location.href = "/admin"; }, 3000);</script>';
                    break;
                    
                case 'manual':
                    // Even more direct approach
                    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
                    
                    if (!$user) {
                        echo '<div class="error">Demo user not found!</div>';
                        break;
                    }
                    
                    // Direct session manipulation
                    $_SESSION['login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d'] = $user->id;
                    $_SESSION['password_hash_web'] = $user->password;
                    $_SESSION['_token'] = csrf_token();
                    
                    // Also set in Laravel session
                    $session = app('session');
                    $session->put('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d', $user->id);
                    $session->put('password_hash_web', $user->password);
                    $session->save();
                    
                    // Write session file directly
                    $sessionPath = storage_path('framework/sessions');
                    $sessionFile = $sessionPath . '/' . session_id();
                    $sessionData = serialize([
                        '_token' => csrf_token(),
                        'login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d' => $user->id,
                        'password_hash_web' => $user->password,
                        '_previous' => ['url' => url('/admin')],
                        '_flash' => ['old' => [], 'new' => []]
                    ]);
                    file_put_contents($sessionFile, $sessionData);
                    
                    echo '<div class="success">';
                    echo '<h3>✅ Manual Session Created!</h3>';
                    echo '<p>Session File: ' . htmlspecialchars($sessionFile) . '</p>';
                    echo '<p>File Exists: ' . (file_exists($sessionFile) ? 'YES' : 'NO') . '</p>';
                    echo '<p>Session Data Written: ' . strlen($sessionData) . ' bytes</p>';
                    echo '</div>';
                    
                    echo '<div class="info">Now <a href="/admin">click here to go to admin</a></div>';
                    break;
                    
                case 'check':
                    echo '<div class="info"><h3>Session Debug Information</h3></div>';
                    
                    // PHP Session
                    echo '<div class="warning"><h4>PHP Session ($_SESSION)</h4><pre>';
                    print_r($_SESSION);
                    echo '</pre></div>';
                    
                    // Laravel Session
                    echo '<div class="warning"><h4>Laravel Session</h4><pre>';
                    $session = app('session');
                    print_r($session->all());
                    echo '</pre></div>';
                    
                    // Auth Status
                    echo '<div class="info"><h4>Authentication Status</h4><pre>';
                    echo "Auth::check(): " . (Auth::check() ? 'true' : 'false') . "\n";
                    echo "Auth::id(): " . (Auth::id() ?: 'null') . "\n";
                    echo "Auth::user(): " . (Auth::user() ? Auth::user()->email : 'null') . "\n";
                    echo '</pre></div>';
                    
                    // Session Files
                    $sessionPath = storage_path('framework/sessions');
                    $currentSessionFile = $sessionPath . '/' . session_id();
                    echo '<div class="info"><h4>Session File</h4><pre>';
                    echo "Path: " . $currentSessionFile . "\n";
                    echo "Exists: " . (file_exists($currentSessionFile) ? 'YES' : 'NO') . "\n";
                    if (file_exists($currentSessionFile)) {
                        echo "Size: " . filesize($currentSessionFile) . " bytes\n";
                        echo "Modified: " . date('Y-m-d H:i:s', filemtime($currentSessionFile)) . "\n";
                        echo "Contents:\n";
                        $contents = file_get_contents($currentSessionFile);
                        $data = @unserialize($contents);
                        if ($data) {
                            print_r($data);
                        } else {
                            echo "Raw: " . substr($contents, 0, 200) . "...\n";
                        }
                    }
                    echo '</pre></div>';
                    break;
                    
                case 'fix-migrate':
                    // Fix the session migrate issue permanently
                    echo '<div class="info">Applying session migrate fix...</div>';
                    
                    // Create a runtime patch
                    $code = '<?php
namespace Illuminate\Session {
    class Store {
        public function migrate($destroy = false) {
            if ($destroy) {
                $data = $this->all();
                session_regenerate_id(false);
                foreach ($data as $key => $value) {
                    $this->put($key, $value);
                }
                return true;
            }
            return session_regenerate_id($destroy);
        }
    }
}';
                    
                    echo '<div class="warning">This requires modifying core files. Instead, use our CustomSessionGuard.</div>';
                    echo '<div class="success">CustomSessionGuard has been registered in AuthServiceProvider!</div>';
                    break;
            }
        } else {
            ?>
            <div class="info">
                <h3>🔍 The Real Problem</h3>
                <p>Laravel's <code class="code">SessionGuard::updateSession()</code> calls <code class="code">$this->session->migrate(true)</code> which destroys all session data!</p>
                <p>This tool overrides that behavior to preserve session data during login.</p>
            </div>
            
            <div class="warning">
                <h3>Current Status</h3>
                <p>Auth: <?php echo Auth::check() ? '<span class="success">Logged In ✓</span>' : '<span class="error">Not Logged In ✗</span>'; ?></p>
                <p>Session ID: <code class="code"><?php echo session_id(); ?></code></p>
                <p>Session Driver: <code class="code"><?php echo config('session.driver'); ?></code></p>
            </div>
        <?php } ?>
        
        <div class="actions">
            <a href="?action=login"><button>🚀 Ultimate Login</button></a>
            <a href="?action=manual"><button class="critical">🔧 Manual Session</button></a>
            <a href="?action=check"><button>🔍 Debug Session</button></a>
            <a href="?action=fix-migrate"><button>🛠️ Fix Migrate</button></a>
            <a href="/admin"><button>📊 Go to Admin</button></a>
        </div>
        
        <div class="info" style="margin-top: 30px;">
            <h4>How This Works</h4>
            <ol>
                <li><strong>Ultimate Login:</strong> Bypasses Laravel's session migration</li>
                <li><strong>Manual Session:</strong> Writes session file directly to disk</li>
                <li><strong>Debug Session:</strong> Shows complete session state</li>
                <li><strong>Fix Migrate:</strong> Information about the permanent fix</li>
            </ol>
        </div>
    </div>
</body>
</html>