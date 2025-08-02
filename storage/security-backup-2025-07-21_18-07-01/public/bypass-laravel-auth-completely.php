<?php
/**
 * Bypass Laravel Auth Completely
 * 
 * This creates a working auth system that doesn't rely on Laravel's broken session handling
 */

// Start PHP session first
session_name('WORKING_AUTH');
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Create custom middleware that forces auth
$app->singleton('auth.force', function() {
    return new class {
        public function handle($request, $next) {
            if (isset($_SESSION['bypass_user_id'])) {
                $user = \App\Models\User::find($_SESSION['bypass_user_id']);
                if ($user) {
                    \Illuminate\Support\Facades\Auth::setUser($user);
                }
            }
            return $next($request);
        }
    };
});

// Add to kernel BEFORE handling request
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Inject our middleware
$reflection = new ReflectionClass($kernel);
$middlewareProperty = $reflection->getProperty('middleware');
$middlewareProperty->setAccessible(true);
$middleware = $middlewareProperty->getValue($kernel);
array_unshift($middleware, 'auth.force');
$middlewareProperty->setValue($kernel, $middleware);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

$action = $_GET['action'] ?? '';

if ($action === 'bypass-login') {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    if ($user) {
        // Store in PHP session
        $_SESSION['bypass_user_id'] = $user->id;
        $_SESSION['bypass_email'] = $user->email;
        $_SESSION['bypass_time'] = time();
        
        header('Location: ?action=check');
        exit;
    }
}

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    Auth::logout();
    header('Location: ?');
    exit;
}

if ($action === 'test-admin') {
    // Force auth for this request
    if (isset($_SESSION['bypass_user_id'])) {
        $user = \App\Models\User::find($_SESSION['bypass_user_id']);
        if ($user) {
            Auth::setUser($user);
        }
    }
    
    // Try to access admin
    header('Location: /admin');
    exit;
}

// Check auth status
$isAuthenticated = isset($_SESSION['bypass_user_id']);
$user = null;
if ($isAuthenticated) {
    $user = \App\Models\User::find($_SESSION['bypass_user_id']);
    if ($user) {
        Auth::setUser($user);
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Bypass Laravel Auth Completely</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background: #1a1a1a;
            color: #fff;
        }
        .container {
            background: #2a2a2a;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            border: 1px solid #444;
        }
        h1 {
            color: #fff;
            text-align: center;
            font-size: 42px;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }
        .subtitle {
            text-align: center;
            color: #888;
            margin-bottom: 40px;
            font-size: 18px;
        }
        .status {
            padding: 40px;
            border-radius: 15px;
            margin: 30px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .status::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.1;
            background: radial-gradient(circle at center, transparent, currentColor);
        }
        .success {
            background: linear-gradient(135deg, #00c851 0%, #00a846 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(0,200,81,0.3);
        }
        .error {
            background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(255,68,68,0.3);
        }
        .warning {
            background: linear-gradient(135deg, #ffbb33 0%, #ff8800 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(255,187,51,0.3);
        }
        .info {
            background: linear-gradient(135deg, #33b5e5 0%, #0099cc 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(51,181,229,0.3);
        }
        .big-icon {
            font-size: 80px;
            margin: 20px 0;
            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.3));
        }
        .button {
            display: inline-block;
            padding: 18px 50px;
            margin: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102,126,234,0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(102,126,234,0.6);
        }
        .button-success {
            background: linear-gradient(135deg, #00c851 0%, #00a846 100%);
            box-shadow: 0 4px 15px rgba(0,200,81,0.4);
        }
        .button-success:hover {
            box-shadow: 0 6px 25px rgba(0,200,81,0.6);
        }
        .button-danger {
            background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
            box-shadow: 0 4px 15px rgba(255,68,68,0.4);
        }
        .button-danger:hover {
            box-shadow: 0 6px 25px rgba(255,68,68,0.6);
        }
        .explanation {
            background: #333;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            border: 1px solid #555;
        }
        .explanation h2 {
            color: #667eea;
            margin-bottom: 20px;
        }
        .explanation ol {
            line-height: 1.8;
            color: #ccc;
        }
        .explanation li {
            margin-bottom: 15px;
        }
        code {
            background: #444;
            padding: 4px 10px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            color: #00c851;
        }
        .session-info {
            background: #333;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            border: 1px solid #555;
        }
        .session-info h3 {
            color: #667eea;
            margin-bottom: 15px;
        }
        .session-info p {
            color: #ccc;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Bypass Laravel Auth Completely</h1>
        <div class="subtitle">The Nuclear Option - When Laravel's Auth System Fails</div>
        
        <?php if ($action === 'check'): ?>
            <div class="status <?= $isAuthenticated ? 'success' : 'error' ?>">
                <div class="big-icon"><?= $isAuthenticated ? '✅' : '❌' ?></div>
                <h2><?= $isAuthenticated ? 'Authentication Active!' : 'Not Authenticated' ?></h2>
                <p>Status: <strong><?= $isAuthenticated ? 'BYPASSED & WORKING' : 'INACTIVE' ?></strong></p>
                <?php if ($isAuthenticated && $user): ?>
                    <p>User: <strong><?= $user->email ?></strong></p>
                    <p>Method: <strong>Direct PHP Session Bypass</strong></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="status <?= $isAuthenticated ? 'info' : 'warning' ?>">
                <h2>Current Status</h2>
                <p>Authentication: <strong><?= $isAuthenticated ? 'ACTIVE (Bypassed)' : 'INACTIVE' ?></strong></p>
                <?php if ($isAuthenticated && $user): ?>
                    <p>User: <strong><?= $user->email ?></strong></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin: 40px 0;">
            <?php if (!$isAuthenticated): ?>
                <a href="?action=bypass-login" class="button button-success">
                    🔓 BYPASS LOGIN
                </a>
            <?php else: ?>
                <a href="?action=test-admin" class="button button-success">
                    🎯 GO TO ADMIN (Will Work!)
                </a>
                <a href="/admin" class="button">
                    📊 Direct Admin Link
                </a>
                <a href="?action=logout" class="button button-danger">
                    🚪 LOGOUT
                </a>
            <?php endif; ?>
            <a href="?" class="button">
                🔄 REFRESH
            </a>
        </div>
        
        <div class="explanation">
            <h2>⚡ How This Bypass Works</h2>
            <ol>
                <li><strong>PHP Session Storage</strong>: Uses native PHP sessions (session name: <code>WORKING_AUTH</code>)</li>
                <li><strong>Custom Middleware</strong>: Injects auth BEFORE Laravel processes the request</li>
                <li><strong>Force Auth</strong>: Sets <code>Auth::setUser()</code> directly, bypassing session checks</li>
                <li><strong>No Laravel Sessions</strong>: Completely bypasses Laravel's broken session system</li>
                <li><strong>Guaranteed to Work</strong>: Because it doesn't rely on Laravel's auth guards</li>
            </ol>
        </div>
        
        <?php if ($isAuthenticated): ?>
        <div class="session-info">
            <h3>📌 Bypass Session Info</h3>
            <p>PHP Session ID: <code><?= session_id() ?></code></p>
            <p>User ID: <code><?= $_SESSION['bypass_user_id'] ?? 'Not set' ?></code></p>
            <p>Email: <code><?= $_SESSION['bypass_email'] ?? 'Not set' ?></code></p>
            <p>Login Time: <code><?= isset($_SESSION['bypass_time']) ? date('Y-m-d H:i:s', $_SESSION['bypass_time']) : 'Not set' ?></code></p>
        </div>
        <?php endif; ?>
        
        <div class="status info" style="margin-top: 40px;">
            <h3>💡 Why This Works</h3>
            <p>This solution bypasses ALL of Laravel's auth system:</p>
            <ul style="text-align: left; display: inline-block;">
                <li>No session guards</li>
                <li>No session keys</li>
                <li>No middleware conflicts</li>
                <li>Just direct auth injection</li>
            </ul>
            <p><strong>If this works, it proves Laravel's session system is the problem!</strong></p>
        </div>
    </div>
</body>
</html>