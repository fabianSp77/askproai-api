<?php
// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use Illuminate\Http\Request;
use Illuminate\Http\Response;

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a proper request/response cycle
$request = Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

// Get current session configuration
$sessionConfig = config('session');

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'set':
            session(['test_value' => 'Session working at ' . date('Y-m-d H:i:s')]);
            session(['counter' => session('counter', 0) + 1]);
            session()->save();
            
            echo json_encode([
                'success' => true,
                'session_id' => session()->getId(),
                'test_value' => session('test_value'),
                'counter' => session('counter'),
                'cookie_set' => isset($_COOKIE[$sessionConfig['cookie']])
            ]);
            exit;
            
        case 'get':
            echo json_encode([
                'session_id' => session()->getId(),
                'test_value' => session('test_value'),
                'counter' => session('counter'),
                'all_data' => session()->all(),
                'cookie_exists' => isset($_COOKIE[$sessionConfig['cookie']]),
                'cookie_value' => $_COOKIE[$sessionConfig['cookie']] ?? null
            ]);
            exit;
            
        case 'login':
            $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
            if ($user) {
                \Illuminate\Support\Facades\Auth::login($user);
                session()->regenerate();
                session()->save();
                
                echo json_encode([
                    'success' => true,
                    'user' => $user->email,
                    'session_id' => session()->getId(),
                    'auth_check' => auth()->check()
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'User not found']);
            }
            exit;
            
        case 'check':
            echo json_encode([
                'logged_in' => auth()->check(),
                'user' => auth()->user() ? auth()->user()->email : null,
                'session_id' => session()->getId(),
                'session_data' => session()->all()
            ]);
            exit;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Cookie Fix</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .warning { background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px; margin: 10px 0; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 5px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Session Cookie Diagnostic & Fix</h1>
    
    <div class="info">
        <h3>Current Session Configuration:</h3>
        <pre><?php print_r($sessionConfig); ?></pre>
    </div>
    
    <?php
    // Check for issues
    $issues = [];
    
    if ($sessionConfig['encrypt'] === false && $sessionConfig['driver'] === 'file') {
        $issues[] = "Session encryption is disabled but using encrypted cookies middleware";
    }
    
    if (!isset($_COOKIE[$sessionConfig['cookie']])) {
        $issues[] = "Session cookie '" . $sessionConfig['cookie'] . "' not found in request";
    }
    
    if ($sessionConfig['secure'] === true && $_SERVER['REQUEST_SCHEME'] !== 'https') {
        $issues[] = "Secure cookies enabled but not on HTTPS";
    }
    
    // Check if session file exists
    $sessionId = session()->getId();
    $sessionFile = storage_path('framework/sessions/' . $sessionId);
    if (!file_exists($sessionFile)) {
        $issues[] = "Session file does not exist: " . $sessionFile;
    }
    ?>
    
    <?php if (!empty($issues)): ?>
    <div class="warning">
        <h3>Issues Found:</h3>
        <ul>
            <?php foreach ($issues as $issue): ?>
            <li><?php echo $issue; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="info">
        <h3>Test Session Operations:</h3>
        <button onclick="testSetSession()">1. Set Session Value</button>
        <button onclick="testGetSession()">2. Get Session Value</button>
        <button onclick="testLogin()">3. Test Login</button>
        <button onclick="showCookies()">4. Show Cookies</button>
        
        <pre id="result">Click a button to test...</pre>
    </div>
    
    <script>
    async function testSetSession() {
        const result = document.getElementById('result');
        result.textContent = 'Setting session value...';
        
        try {
            const response = await fetch('/fix-session-cookie.php?action=set', {
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            result.textContent = JSON.stringify(data, null, 2);
            
            if (data.success) {
                result.textContent += '\n\n✅ Session value set. Now try "Get Session Value"';
            }
        } catch (error) {
            result.textContent = '❌ Error: ' + error.message;
        }
    }
    
    async function testGetSession() {
        const result = document.getElementById('result');
        result.textContent = 'Getting session value...';
        
        try {
            const response = await fetch('/fix-session-cookie.php?action=get', {
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            result.textContent = JSON.stringify(data, null, 2);
            
            if (data.test_value) {
                result.textContent += '\n\n✅ Session persisted!';
            } else {
                result.textContent += '\n\n❌ Session not persisted!';
            }
        } catch (error) {
            result.textContent = '❌ Error: ' + error.message;
        }
    }
    
    async function testLogin() {
        const result = document.getElementById('result');
        result.textContent = 'Testing login...';
        
        try {
            const response = await fetch('/fix-session-cookie.php?action=login', {
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            result.textContent = JSON.stringify(data, null, 2);
            
            if (data.success) {
                result.textContent += '\n\n✅ Login successful! Try accessing /admin now.';
            }
        } catch (error) {
            result.textContent = '❌ Error: ' + error.message;
        }
    }
    
    function showCookies() {
        const result = document.getElementById('result');
        
        result.textContent = '=== Current Cookies ===\n\n';
        const cookies = document.cookie.split(';');
        
        if (cookies.length === 0 || (cookies.length === 1 && cookies[0] === '')) {
            result.textContent += 'No cookies found!\n';
        } else {
            cookies.forEach(cookie => {
                const [name, value] = cookie.trim().split('=');
                if (name) {
                    result.textContent += name + ': ' + (value ? value.substring(0, 50) + '...' : '(empty)') + '\n';
                }
            });
        }
        
        result.textContent += '\n=== Expected Session Cookie ===\n';
        result.textContent += 'Name: <?php echo $sessionConfig['cookie']; ?>\n';
        result.textContent += 'Found: ' + (document.cookie.includes('<?php echo $sessionConfig['cookie']; ?>') ? 'YES' : 'NO');
    }
    </script>
</body>
</html>