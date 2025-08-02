<?php
// ULTRATHINK SESSION FIX - Direkter Ansatz ohne Framework-Overhead
session_name('askproai_session');
session_set_cookie_params([
    'lifetime' => 7200,
    'path' => '/',
    'domain' => '',  // Leer für aktuelle Domain
    'secure' => false,  // HTTP erlauben
    'httponly' => false,  // JavaScript-Zugriff erlauben zum Debuggen
    'samesite' => 'Lax'
]);
session_start();

// Handle login
if (isset($_POST['login'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['email'] = 'demo@askproai.de';
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    // Force cookie
    setcookie(session_name(), session_id(), time() + 7200, '/');
    
    echo json_encode([
        'success' => true,
        'session_id' => session_id(),
        'cookie_set' => true
    ]);
    exit;
}

// Handle check
if (isset($_GET['check'])) {
    echo json_encode([
        'session_id' => session_id(),
        'logged_in' => $_SESSION['logged_in'] ?? false,
        'email' => $_SESSION['email'] ?? null,
        'session_data' => $_SESSION,
        'cookie' => $_COOKIE['askproai_session'] ?? null
    ]);
    exit;
}

// Bootstrap Laravel AFTER session is set
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

// Force login in Laravel if PHP session exists
if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $user = \App\Models\User::find(1);
    if ($user) {
        \Illuminate\Support\Facades\Auth::loginUsingId($user->id);
    }
}

// Handle Laravel login
if (isset($_POST['laravel_login'])) {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    if ($user) {
        \Illuminate\Support\Facades\Auth::login($user);
        session()->regenerate();
        
        // Also set PHP session
        $_SESSION['user_id'] = $user->id;
        $_SESSION['email'] = $user->email;
        $_SESSION['logged_in'] = true;
        
        echo json_encode([
            'success' => true,
            'laravel_auth' => auth()->check(),
            'laravel_session' => session()->getId(),
            'php_session' => session_id()
        ]);
        exit;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>UltraThink Session Fix</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 40px auto; padding: 20px; background: #f0f2f5; }
        .container { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .full-width { grid-column: 1 / -1; }
        h1 { color: #1a73e8; margin-bottom: 30px; text-align: center; }
        h2 { color: #333; margin-bottom: 20px; font-size: 20px; }
        .status { padding: 15px; margin: 10px 0; border-radius: 8px; }
        .success { background: #e8f5e9; color: #2e7d32; border: 1px solid #4caf50; }
        .error { background: #ffebee; color: #c62828; border: 1px solid #f44336; }
        .info { background: #e3f2fd; color: #1565c0; border: 1px solid #2196f3; }
        .warning { background: #fff3e0; color: #e65100; border: 1px solid #ff9800; }
        button { 
            width: 100%; 
            padding: 12px; 
            margin: 8px 0; 
            background: #1a73e8; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 16px;
            transition: all 0.3s;
        }
        button:hover { background: #1557b0; transform: translateY(-1px); }
        button.secondary { background: #5f6368; }
        button.secondary:hover { background: #3c4043; }
        input { 
            width: 100%; 
            padding: 10px; 
            margin: 5px 0 15px 0; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            font-size: 14px;
        }
        pre { 
            background: #263238; 
            color: #aed581; 
            padding: 15px; 
            border-radius: 4px; 
            overflow: auto; 
            font-size: 12px; 
            max-height: 300px;
        }
        .cookie-item { 
            background: #f8f9fa; 
            padding: 10px; 
            margin: 5px 0; 
            border-radius: 4px; 
            font-family: monospace; 
            font-size: 12px;
            word-break: break-all;
        }
        .badge { 
            display: inline-block; 
            padding: 3px 8px; 
            border-radius: 12px; 
            font-size: 11px; 
            font-weight: bold; 
            margin-left: 8px;
        }
        .badge.success { background: #4caf50; color: white; }
        .badge.error { background: #f44336; color: white; }
    </style>
</head>
<body>
    <h1>🚀 UltraThink Session Fix</h1>
    
    <div class="container">
        <!-- PHP Session Login -->
        <div class="box">
            <h2>1️⃣ PHP Native Session</h2>
            <p style="color: #666; font-size: 14px;">Direkter Session-Test ohne Laravel</p>
            <button onclick="phpLogin()">PHP Session Login</button>
            <button onclick="checkPhpSession()" class="secondary">Check PHP Session</button>
            <div id="phpResult"></div>
        </div>
        
        <!-- Laravel Auth -->
        <div class="box">
            <h2>2️⃣ Laravel Authentication</h2>
            <p style="color: #666; font-size: 14px;">Laravel Auth mit Session-Sync</p>
            <button onclick="laravelLogin()">Laravel Login</button>
            <button onclick="checkLaravelAuth()" class="secondary">Check Laravel Auth</button>
            <div id="laravelResult"></div>
        </div>
        
        <!-- Combined Test -->
        <div class="box">
            <h2>3️⃣ Combined Test</h2>
            <p style="color: #666; font-size: 14px;">Teste Admin-Zugriff</p>
            <button onclick="window.open('/admin', '_blank')">Open Admin Panel</button>
            <button onclick="window.open('/admin/calls', '_blank')">Open Calls Page</button>
            <div id="combinedResult"></div>
        </div>
        
        <!-- Cookie Inspector -->
        <div class="box full-width">
            <h2>🍪 Cookie Inspector</h2>
            <div id="cookieInspector"></div>
        </div>
        
        <!-- System Status -->
        <div class="box full-width">
            <h2>⚙️ System Status</h2>
            <pre><?php
            echo "=== PHP Session ===\n";
            echo "Session ID: " . session_id() . "\n";
            echo "Session Name: " . session_name() . "\n";
            echo "Session Data: " . json_encode($_SESSION, JSON_PRETTY_PRINT) . "\n\n";
            
            echo "=== Laravel Auth ===\n";
            echo "Authenticated: " . (auth()->check() ? 'YES' : 'NO') . "\n";
            echo "User: " . (auth()->user() ? auth()->user()->email : 'None') . "\n";
            echo "Laravel Session: " . session()->getId() . "\n\n";
            
            echo "=== Configuration ===\n";
            echo "Session Driver: " . config('session.driver') . "\n";
            echo "Session Cookie: " . config('session.cookie') . "\n";
            echo "Session Domain: " . (config('session.domain') ?: '(empty)') . "\n";
            echo "Secure: " . (config('session.secure') ? 'true' : 'false') . "\n";
            echo "HttpOnly: " . (config('session.http_only') ? 'true' : 'false') . "\n";
            ?></pre>
        </div>
    </div>
    
    <script>
        // Cookie helper
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }
        
        // Update cookie display
        function updateCookies() {
            const inspector = document.getElementById('cookieInspector');
            const cookies = document.cookie.split(';').filter(c => c.trim());
            
            let html = '';
            if (cookies.length === 0 || cookies[0] === '') {
                html = '<div class="status error">Keine Cookies gefunden!</div>';
            } else {
                cookies.forEach(cookie => {
                    const [name, value] = cookie.trim().split('=');
                    let badge = '';
                    if (name === 'askproai_session') {
                        badge = '<span class="badge success">Laravel</span>';
                    } else if (name === 'PHPSESSID') {
                        badge = '<span class="badge success">PHP</span>';
                    }
                    html += `<div class="cookie-item"><strong>${name}${badge}:</strong> ${value || '(empty)'}</div>`;
                });
            }
            
            inspector.innerHTML = html;
        }
        
        // PHP Session Login
        async function phpLogin() {
            const result = document.getElementById('phpResult');
            result.innerHTML = '<div class="status info">Logging in...</div>';
            
            const response = await fetch('ultrathink-session-fix.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'login=1',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            if (data.success) {
                result.innerHTML = `<div class="status success">✅ PHP Session created!<br>ID: ${data.session_id}</div>`;
                updateCookies();
            } else {
                result.innerHTML = '<div class="status error">❌ Login failed</div>';
            }
        }
        
        // Check PHP Session
        async function checkPhpSession() {
            const result = document.getElementById('phpResult');
            result.innerHTML = '<div class="status info">Checking...</div>';
            
            const response = await fetch('ultrathink-session-fix.php?check=1', {
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            if (data.logged_in) {
                result.innerHTML = `<div class="status success">✅ Logged in<br>Email: ${data.email}<br>Session: ${data.session_id}</div>`;
            } else {
                result.innerHTML = `<div class="status error">❌ Not logged in<br>Session: ${data.session_id}</div>`;
            }
        }
        
        // Laravel Login
        async function laravelLogin() {
            const result = document.getElementById('laravelResult');
            result.innerHTML = '<div class="status info">Logging in...</div>';
            
            const response = await fetch('ultrathink-session-fix.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'laravel_login=1',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            if (data.success) {
                result.innerHTML = `<div class="status success">✅ Laravel auth successful!<br>Laravel: ${data.laravel_session}<br>PHP: ${data.php_session}</div>`;
                updateCookies();
            } else {
                result.innerHTML = '<div class="status error">❌ Laravel login failed</div>';
            }
        }
        
        // Check Laravel Auth
        async function checkLaravelAuth() {
            const result = document.getElementById('laravelResult');
            result.innerHTML = '<div class="status info">Checking...</div>';
            
            const response = await fetch('/auth-debug', { credentials: 'same-origin' });
            const data = await response.json();
            
            if (data.auth_check) {
                result.innerHTML = `<div class="status success">✅ Authenticated<br>User: ${data.user.email}<br>Session: ${data.session_id}</div>`;
            } else {
                result.innerHTML = `<div class="status error">❌ Not authenticated<br>Session: ${data.session_id}</div>`;
            }
        }
        
        // Initial load
        updateCookies();
        setInterval(updateCookies, 2000);
    </script>
</body>
</html>