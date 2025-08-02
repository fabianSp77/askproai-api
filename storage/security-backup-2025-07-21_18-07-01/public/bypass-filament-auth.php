<?php
/**
 * Bypass Filament Auth
 * 
 * This bypasses Filament's authentication checks
 */

// Start PHP session first
session_name('BYPASS_FILAMENT');
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Override Filament's auth check
\Filament\Facades\Filament::auth(function() {
    return new class {
        public function check() {
            return isset($_SESSION['bypass_active']);
        }
        public function user() {
            if (isset($_SESSION['bypass_user_id'])) {
                return \App\Models\User::find($_SESSION['bypass_user_id']);
            }
            return null;
        }
        public function id() {
            return $_SESSION['bypass_user_id'] ?? null;
        }
        public function guard($guard = null) {
            return $this;
        }
    };
});

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

// Force authentication for Laravel too
if (isset($_SESSION['bypass_user_id'])) {
    $user = \App\Models\User::find($_SESSION['bypass_user_id']);
    if ($user) {
        \Illuminate\Support\Facades\Auth::setUser($user);
    }
}

$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

$action = $_GET['action'] ?? '';

if ($action === 'activate-bypass') {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    if ($user) {
        $_SESSION['bypass_active'] = true;
        $_SESSION['bypass_user_id'] = $user->id;
        $_SESSION['bypass_email'] = $user->email;
        $_SESSION['bypass_time'] = time();
        
        header('Location: ?action=status');
        exit;
    }
}

if ($action === 'deactivate') {
    $_SESSION = [];
    session_destroy();
    header('Location: ?');
    exit;
}

if ($action === 'go-admin') {
    if (isset($_SESSION['bypass_active'])) {
        // Set a cookie that Filament might check
        setcookie('filament_bypass', 'active', time() + 3600, '/');
        
        // Try to set Laravel session too
        $user = \App\Models\User::find($_SESSION['bypass_user_id']);
        if ($user) {
            Auth::login($user);
            session()->put('filament_user', $user->id);
            session()->save();
        }
    }
    header('Location: /admin');
    exit;
}

$isActive = isset($_SESSION['bypass_active']);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Bypass Filament Auth</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #0f0f23;
            color: #fff;
        }
        .container {
            background: #1a1a2e;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
            border: 1px solid #16213e;
        }
        h1 {
            color: #fff;
            text-align: center;
            font-size: 42px;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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
        .active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(102,126,234,0.4);
        }
        .inactive {
            background: #2a2a2a;
            color: #888;
            border: 2px dashed #444;
        }
        .big-icon {
            font-size: 80px;
            margin: 20px 0;
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
            background: linear-gradient(135deg, #00dbde 0%, #00b4d8 100%);
            box-shadow: 0 4px 15px rgba(0,219,222,0.4);
        }
        .button-success:hover {
            box-shadow: 0 6px 25px rgba(0,219,222,0.6);
        }
        .button-danger {
            background: linear-gradient(135deg, #fc00ff 0%, #d100d1 100%);
            box-shadow: 0 4px 15px rgba(252,0,255,0.4);
        }
        .button-danger:hover {
            box-shadow: 0 6px 25px rgba(252,0,255,0.6);
        }
        .info-box {
            background: #2a2a2a;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            border: 1px solid #444;
        }
        .info-box h2 {
            color: #00dbde;
            margin-bottom: 20px;
        }
        .info-box ul {
            line-height: 1.8;
            color: #ccc;
            list-style: none;
            padding: 0;
        }
        .info-box li {
            margin-bottom: 10px;
            padding-left: 25px;
            position: relative;
        }
        .info-box li:before {
            content: "▸";
            position: absolute;
            left: 0;
            color: #00dbde;
        }
        code {
            background: #0f0f23;
            padding: 4px 10px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            color: #00dbde;
            border: 1px solid #00dbde;
        }
        .warning {
            background: #2a2a2a;
            border: 2px solid #fc00ff;
            color: #fc00ff;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
            font-weight: 600;
        }
        .session-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        .session-box {
            background: #0f0f23;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #444;
        }
        .session-box h4 {
            margin: 0 0 10px 0;
            color: #00dbde;
        }
        .session-box p {
            margin: 5px 0;
            color: #ccc;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛡️ Bypass Filament Auth</h1>
        <div class="subtitle">Override Filament's Authentication System</div>
        
        <?php if ($action === 'status'): ?>
            <div class="status active">
                <div class="big-icon">⚡</div>
                <h2>Bypass Activated!</h2>
                <p>Filament authentication has been overridden</p>
            </div>
        <?php else: ?>
            <div class="status <?= $isActive ? 'active' : 'inactive' ?>">
                <h2><?= $isActive ? '🟢 Bypass Active' : '🔴 Bypass Inactive' ?></h2>
                <p>Status: <strong><?= $isActive ? 'OVERRIDING FILAMENT AUTH' : 'NORMAL OPERATION' ?></strong></p>
                <?php if ($isActive): ?>
                    <p>User: <strong><?= $_SESSION['bypass_email'] ?? 'Unknown' ?></strong></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="warning">
            ⚠️ Filament has its own authentication system that needs to be bypassed!
        </div>
        
        <div style="text-align: center; margin: 40px 0;">
            <?php if (!$isActive): ?>
                <a href="?action=activate-bypass" class="button button-success">
                    ⚡ ACTIVATE BYPASS
                </a>
            <?php else: ?>
                <a href="?action=go-admin" class="button button-success">
                    🚀 GO TO ADMIN PANEL
                </a>
                <a href="/admin" class="button" target="_blank">
                    📊 Direct Admin Link (New Tab)
                </a>
                <a href="?action=deactivate" class="button button-danger">
                    ❌ DEACTIVATE
                </a>
            <?php endif; ?>
            <a href="?" class="button">
                🔄 REFRESH
            </a>
        </div>
        
        <div class="info-box">
            <h2>🔍 How Filament Auth Works</h2>
            <ul>
                <li>Filament uses <code>Filament\Http\Middleware\Authenticate</code></li>
                <li>It checks authentication independently from Laravel</li>
                <li>Redirects to <code>/admin/login</code> when not authenticated</li>
                <li>Has its own auth guard configuration</li>
                <li>This bypass overrides Filament's auth() method</li>
            </ul>
        </div>
        
        <?php if ($isActive): ?>
        <div class="session-info">
            <div class="session-box">
                <h4>📌 PHP Session</h4>
                <p>ID: <code><?= substr(session_id(), 0, 20) ?>...</code></p>
                <p>User ID: <code><?= $_SESSION['bypass_user_id'] ?? 'Not set' ?></code></p>
            </div>
            <div class="session-box">
                <h4>🔐 Laravel Auth</h4>
                <p>Check: <code><?= Auth::check() ? 'TRUE' : 'FALSE' ?></code></p>
                <p>User: <code><?= Auth::check() ? Auth::user()->email : 'None' ?></code></p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="info-box" style="margin-top: 40px;">
            <h2>💡 Alternative: Disable Filament Auth</h2>
            <p>To permanently disable Filament authentication, edit:</p>
            <p><code>app/Providers/Filament/AdminPanelProvider.php</code></p>
            <p>Comment out: <code>->authMiddleware([...])</code> on line 139</p>
        </div>
    </div>
</body>
</html>