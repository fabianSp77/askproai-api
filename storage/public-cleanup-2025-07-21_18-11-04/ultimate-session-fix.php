<?php
// Ultimate Session Fix - Direkte Session-Verwaltung
ini_set('session.save_path', __DIR__ . '/../storage/framework/sessions');
ini_set('session.cookie_httponly', 0);
ini_set('session.cookie_samesite', 'Lax');

// Starte PHP-Session direkt
session_name('askproai_session');
session_start();

// Jetzt Laravel bootstrappen
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Force Laravel to use our session
\Illuminate\Support\Facades\Session::setId(session_id());

if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'login') {
        $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
        if ($user) {
            // PHP Session
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_email'] = $user->email;
            $_SESSION['logged_in'] = true;
            
            // Laravel Auth
            \Illuminate\Support\Facades\Auth::login($user);
            
            // Force session save
            session_write_close();
            session_start();
            
            echo json_encode([
                'success' => true,
                'php_session_id' => session_id(),
                'laravel_session_id' => session()->getId(),
                'user' => $user->email,
                'session_data' => $_SESSION,
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'User not found']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'check') {
        echo json_encode([
            'php_session' => $_SESSION,
            'php_session_id' => session_id(),
            'laravel_auth' => \Illuminate\Support\Facades\Auth::check(),
            'laravel_user' => \Illuminate\Support\Facades\Auth::user() ? \Illuminate\Support\Facades\Auth::user()->email : null,
            'cookies' => $_COOKIE,
        ]);
        exit;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Ultimate Session Fix</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 40px auto; 
            padding: 20px;
            background: #1a1a2e;
            color: #eee;
        }
        .container {
            background: #16213e;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
        }
        h1 { 
            color: #0f4c75;
            text-align: center;
            margin-bottom: 30px;
        }
        .btn {
            background: #3282b8;
            color: white;
            border: none;
            padding: 12px 24px;
            margin: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #0f4c75;
            transform: translateY(-2px);
        }
        .status {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .success { 
            background: rgba(40, 167, 69, 0.2); 
            border-color: #28a745;
            color: #28a745;
        }
        .error { 
            background: rgba(220, 53, 69, 0.2); 
            border-color: #dc3545;
            color: #dc3545;
        }
        pre {
            background: #0f3460;
            padding: 15px;
            border-radius: 5px;
            overflow: auto;
            font-size: 12px;
            color: #00ff00;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Ultimate Session Fix</h1>
        
        <div class="grid">
            <div>
                <button class="btn" onclick="doLogin()">🔐 Force Login</button>
                <button class="btn" onclick="checkStatus()">📊 Check Status</button>
            </div>
            <div>
                <button class="btn" onclick="window.open('/admin', '_blank')">🏛️ Test Admin</button>
                <button class="btn" onclick="location.reload()">🔄 Reload Page</button>
            </div>
        </div>
        
        <div id="result" style="margin-top: 20px;"></div>
        
        <div style="margin-top: 30px;">
            <h3>Current State:</h3>
            <pre><?php
echo "=== PHP Session ===\n";
echo "ID: " . session_id() . "\n";
echo "Name: " . session_name() . "\n";
echo "Save Path: " . session_save_path() . "\n";
echo "Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'NOT ACTIVE') . "\n";
echo "Data: " . json_encode($_SESSION, JSON_PRETTY_PRINT) . "\n\n";

echo "=== Laravel Auth ===\n";
echo "Authenticated: " . (\Illuminate\Support\Facades\Auth::check() ? 'YES' : 'NO') . "\n";
echo "User: " . (\Illuminate\Support\Facades\Auth::user() ? \Illuminate\Support\Facades\Auth::user()->email : 'None') . "\n\n";

echo "=== Cookies ===\n";
foreach ($_COOKIE as $name => $value) {
    echo "$name: " . substr($value, 0, 50) . "...\n";
}
            ?></pre>
        </div>
    </div>
    
    <script>
        async function doLogin() {
            const result = document.getElementById('result');
            result.innerHTML = '<div class="status">🔄 Logging in...</div>';
            
            const response = await fetch('ultimate-session-fix.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=login',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                result.innerHTML = `
                    <div class="status success">
                        ✅ Login successful!<br>
                        PHP Session: ${data.php_session_id}<br>
                        Laravel Session: ${data.laravel_session_id}<br>
                        User: ${data.user}<br><br>
                        Opening Admin Panel in 2 seconds...
                    </div>
                `;
                
                setTimeout(() => {
                    window.open('/admin', '_blank');
                }, 2000);
            } else {
                result.innerHTML = `<div class="status error">❌ Error: ${data.error}</div>`;
            }
        }
        
        async function checkStatus() {
            const result = document.getElementById('result');
            result.innerHTML = '<div class="status">🔄 Checking...</div>';
            
            const response = await fetch('ultimate-session-fix.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=check',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            result.innerHTML = `
                <div class="status">
                    <h3>Status Check:</h3>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                </div>
            `;
        }
    </script>
</body>
</html>

<?php
$kernel->terminate($request, $response);
?>