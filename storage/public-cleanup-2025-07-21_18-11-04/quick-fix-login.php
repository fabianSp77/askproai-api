<?php
// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Handle the request
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Start PHP session directly
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Fix Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        button { background: #28a745; color: white; border: none; padding: 12px 24px; font-size: 16px; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #218838; }
        .critical { background: #dc3545; }
        .critical:hover { background: #c82333; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .actions { text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Quick Fix Login</h1>
        
        <?php if (isset($_GET['action'])): ?>
            <?php if ($_GET['action'] === 'login'): ?>
                <?php
                // Force login without any middleware
                $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
                
                if ($user) {
                    // Direct session manipulation
                    $_SESSION['user_id'] = $user->id;
                    $_SESSION['user_email'] = $user->email;
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    
                    // Also set Laravel auth
                    \Illuminate\Support\Facades\Auth::guard('web')->loginUsingId($user->id, true);
                    
                    // Set Laravel session data
                    $session = app('session');
                    $session->put('login_web_' . sha1(get_class($user)), $user->id);
                    $session->put('password_hash_web', $user->password);
                    $session->save();
                    
                    // Create session cookie manually
                    $sessionName = config('session.cookie');
                    $sessionId = session_id();
                    setcookie(
                        $sessionName,
                        $sessionId,
                        time() + (120 * 60), // 2 hours
                        '/',
                        'api.askproai.de',
                        true, // secure
                        true  // httponly
                    );
                    
                    echo '<div class="success">';
                    echo '<h3>✅ Login Successful!</h3>';
                    echo '<p>User: ' . htmlspecialchars($user->email) . '</p>';
                    echo '<p>Session ID: ' . htmlspecialchars($sessionId) . '</p>';
                    echo '<p>Laravel Auth: ' . (\Illuminate\Support\Facades\Auth::check() ? 'YES' : 'NO') . '</p>';
                    echo '</div>';
                    
                    echo '<script>setTimeout(function() { window.location.href = "/admin"; }, 2000);</script>';
                } else {
                    echo '<div class="error">Demo user not found!</div>';
                }
                ?>
            <?php elseif ($_GET['action'] === 'check'): ?>
                <div class="info">
                    <h3>Current Session State</h3>
                    <pre><?php
                    echo "PHP Session:\n";
                    print_r($_SESSION);
                    echo "\n\nLaravel Auth: " . (\Illuminate\Support\Facades\Auth::check() ? 'YES - ' . \Illuminate\Support\Facades\Auth::user()->email : 'NO');
                    echo "\n\nCookies:\n";
                    print_r($_COOKIE);
                    ?></pre>
                </div>
            <?php elseif ($_GET['action'] === 'fix'): ?>
                <?php
                // Fix session directory permissions
                $sessionPath = session_save_path() ?: '/tmp';
                shell_exec("chmod -R 777 $sessionPath 2>&1");
                
                // Clear old session files
                $oldFiles = glob($sessionPath . '/sess_*');
                $deleted = 0;
                foreach ($oldFiles as $file) {
                    if (filemtime($file) < time() - 3600) { // older than 1 hour
                        unlink($file);
                        $deleted++;
                    }
                }
                
                echo '<div class="success">';
                echo '<h3>✅ Session Directory Fixed!</h3>';
                echo '<p>Path: ' . htmlspecialchars($sessionPath) . '</p>';
                echo '<p>Deleted ' . $deleted . ' old session files</p>';
                echo '</div>';
                ?>
            <?php endif; ?>
        <?php else: ?>
            <div class="info">
                <h3>Quick Fix Login Tool</h3>
                <p>This tool bypasses all middleware and creates a direct session.</p>
                <p>Current Auth Status: <?php echo \Illuminate\Support\Facades\Auth::check() ? '<span class="success">Logged In</span>' : '<span class="error">Not Logged In</span>'; ?></p>
            </div>
        <?php endif; ?>
        
        <div class="actions">
            <a href="?action=login"><button>🔐 Force Login</button></a>
            <a href="?action=check"><button>🔍 Check Session</button></a>
            <a href="?action=fix"><button>🔧 Fix Permissions</button></a>
            <a href="/admin"><button>📊 Go to Admin</button></a>
            <a href="/session-validation-tool.php"><button>🛠️ Validation Tool</button></a>
        </div>
        
        <div class="info" style="margin-top: 20px;">
            <h4>Debug Information</h4>
            <pre><?php
            echo "Session Driver: " . config('session.driver') . "\n";
            echo "Session Domain: " . config('session.domain') . "\n";
            echo "Session Cookie: " . config('session.cookie') . "\n";
            echo "Session Path: " . session_save_path() . "\n";
            echo "Session ID: " . session_id() . "\n";
            ?></pre>
        </div>
    </div>
</body>
</html>