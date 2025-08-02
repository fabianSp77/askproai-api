<?php
/**
 * UltraThink Complete Session Fix
 * Behebt alle Session- und Cookie-Probleme
 */

// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// WICHTIG: Session config VOR kernel handle setzen!
if (request()->is('business/*') || request()->is('portal/*')) {
    $portalConfig = config('session_portal');
    if ($portalConfig) {
        foreach ($portalConfig as $key => $value) {
            config(['session.' . $key => $value]);
        }
    }
}

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\PortalUser;

// API Handlers
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'diagnose':
            echo json_encode(diagnoseSystem());
            break;
            
        case 'fix_permissions':
            echo json_encode(fixPermissions());
            break;
            
        case 'test_session':
            echo json_encode(testSessionCreation());
            break;
            
        case 'force_login':
            echo json_encode(forceLogin($_POST['type'] ?? 'admin'));
            break;
            
        case 'clear_sessions':
            echo json_encode(clearAllSessions());
            break;
            
        case 'create_test_cookie':
            echo json_encode(createTestCookie());
            break;
    }
    
    $kernel->terminate($request, $response);
    exit;
}

// Diagnostic Functions
function diagnoseSystem() {
    $diagnosis = [
        'php_session' => [
            'save_path' => ini_get('session.save_path'),
            'cookie_params' => session_get_cookie_params(),
            'session_id' => session_id() ?: 'none',
            'session_status' => session_status(),
        ],
        'laravel_session' => [
            'driver' => config('session.driver'),
            'cookie' => config('session.cookie'),
            'domain' => config('session.domain'),
            'path' => config('session.path'),
            'secure' => config('session.secure'),
            'http_only' => config('session.http_only'),
            'same_site' => config('session.same_site'),
            'lifetime' => config('session.lifetime'),
            'encrypt' => config('session.encrypt'),
            'files_path' => config('session.files'),
            'current_id' => session()->getId(),
            'is_started' => session()->isStarted(),
        ],
        'directories' => [
            'main' => checkDirectory(storage_path('framework/sessions')),
            'portal' => checkDirectory(storage_path('framework/sessions/portal')),
            'admin' => checkDirectory(storage_path('framework/sessions/admin')),
        ],
        'cookies' => $_COOKIE,
        'headers' => getallheaders(),
        'server' => [
            'REQUEST_SCHEME' => $_SERVER['REQUEST_SCHEME'] ?? 'unknown',
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'SERVER_PORT' => $_SERVER['SERVER_PORT'] ?? 'unknown',
        ],
        'problems' => [],
    ];
    
    // Identify problems
    if ($diagnosis['php_session']['session_status'] !== PHP_SESSION_ACTIVE) {
        $diagnosis['problems'][] = 'PHP session not active';
    }
    
    if (!$diagnosis['directories']['main']['writable']) {
        $diagnosis['problems'][] = 'Main session directory not writable';
    }
    
    if (!$diagnosis['directories']['portal']['writable']) {
        $diagnosis['problems'][] = 'Portal session directory not writable';
    }
    
    if (!$diagnosis['directories']['admin']['writable']) {
        $diagnosis['problems'][] = 'Admin session directory not writable';
    }
    
    if (config('session.secure') && $_SERVER['REQUEST_SCHEME'] !== 'https') {
        $diagnosis['problems'][] = 'Secure cookies enabled but not on HTTPS';
    }
    
    if (empty($_COOKIE[config('session.cookie')])) {
        $diagnosis['problems'][] = 'Laravel session cookie not found';
    }
    
    return $diagnosis;
}

function checkDirectory($path) {
    return [
        'path' => $path,
        'exists' => is_dir($path),
        'writable' => is_dir($path) && is_writable($path),
        'owner' => is_dir($path) ? posix_getpwuid(fileowner($path))['name'] : 'n/a',
        'group' => is_dir($path) ? posix_getgrgid(filegroup($path))['name'] : 'n/a',
        'permissions' => is_dir($path) ? substr(sprintf('%o', fileperms($path)), -4) : 'n/a',
        'files' => is_dir($path) ? count(glob($path . '/*')) : 0,
    ];
}

function fixPermissions() {
    $results = [];
    
    $directories = [
        storage_path('framework/sessions'),
        storage_path('framework/sessions/portal'),
        storage_path('framework/sessions/admin'),
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0775, true)) {
                $results[] = "Created: $dir";
            } else {
                $results[] = "Failed to create: $dir";
            }
        }
        
        // Fix permissions
        if (chmod($dir, 0775)) {
            $results[] = "Set permissions 0775: $dir";
        }
        
        // Try to change owner to www-data
        $wwwDataUser = posix_getpwnam('www-data');
        if ($wwwDataUser && chown($dir, $wwwDataUser['uid'])) {
            $results[] = "Changed owner to www-data: $dir";
        }
        
        // Add .gitignore
        $gitignore = $dir . '/.gitignore';
        if (!file_exists($gitignore)) {
            file_put_contents($gitignore, "*\n!.gitignore\n");
            $results[] = "Created .gitignore in: $dir";
        }
    }
    
    return [
        'success' => true,
        'actions' => $results,
        'diagnosis_after' => diagnoseSystem(),
    ];
}

function testSessionCreation() {
    // Start fresh session
    session()->flush();
    session()->regenerate();
    
    // Set test data
    session(['test_key' => 'test_value_' . time()]);
    session(['counter' => session('counter', 0) + 1]);
    session()->save();
    
    // Try to create session file manually
    $sessionId = session()->getId();
    $sessionFile = storage_path('framework/sessions/' . $sessionId);
    
    $result = [
        'session_id' => $sessionId,
        'session_file' => $sessionFile,
        'file_exists' => file_exists($sessionFile),
        'session_data' => session()->all(),
        'cookie_set' => isset($_COOKIE[config('session.cookie')]),
    ];
    
    // Try manual cookie setting
    $cookieName = config('session.cookie');
    $cookieParams = [
        'lifetime' => config('session.lifetime') * 60,
        'path' => config('session.path'),
        'domain' => config('session.domain'),
        'secure' => config('session.secure'),
        'httponly' => config('session.http_only'),
        'samesite' => config('session.same_site'),
    ];
    
    setcookie(
        $cookieName,
        $sessionId,
        time() + $cookieParams['lifetime'],
        $cookieParams['path'],
        $cookieParams['domain'],
        $cookieParams['secure'],
        $cookieParams['httponly']
    );
    
    $result['manual_cookie_set'] = true;
    $result['cookie_params'] = $cookieParams;
    
    return $result;
}

function forceLogin($type = 'admin') {
    session()->flush();
    session()->regenerate();
    
    if ($type === 'admin') {
        $user = User::where('email', 'demo@askproai.de')->first();
        if (!$user) {
            // Create demo user if not exists
            $user = User::create([
                'name' => 'Demo Admin',
                'email' => 'demo@askproai.de',
                'password' => bcrypt('demo123'),
                'company_id' => 1,
            ]);
        }
        
        Auth::guard('web')->login($user);
        session(['forced_login' => true]);
        session()->save();
        
        // Force cookie
        $sessionId = session()->getId();
        setcookie(
            config('session.cookie'),
            $sessionId,
            time() + (120 * 60),
            '/',
            '',
            false,
            true
        );
        
        return [
            'success' => true,
            'type' => 'admin',
            'user' => $user->email,
            'session_id' => $sessionId,
            'auth_check' => auth()->check(),
            'redirect' => '/admin',
        ];
    } else {
        $user = PortalUser::where('email', 'kundenacc@askproai.de')->first();
        if (!$user) {
            return ['success' => false, 'error' => 'Portal user not found'];
        }
        
        Auth::guard('portal')->login($user);
        session(['portal_user_id' => $user->id, 'company_id' => $user->company_id]);
        session()->save();
        
        return [
            'success' => true,
            'type' => 'portal',
            'user' => $user->email,
            'session_id' => session()->getId(),
            'auth_check' => auth('portal')->check(),
            'redirect' => '/business/dashboard',
        ];
    }
}

function clearAllSessions() {
    $cleared = 0;
    
    // Clear all session files
    $paths = [
        storage_path('framework/sessions/*.php'),
        storage_path('framework/sessions/portal/*.php'),
        storage_path('framework/sessions/admin/*.php'),
    ];
    
    foreach ($paths as $path) {
        $files = glob($path);
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $cleared++;
            }
        }
    }
    
    // Clear current session
    session()->flush();
    
    return [
        'success' => true,
        'cleared' => $cleared,
        'message' => "Cleared $cleared session files",
    ];
}

function createTestCookie() {
    // Create multiple test cookies with different settings
    $cookies = [
        [
            'name' => 'test_basic',
            'value' => 'basic_value',
            'expire' => time() + 3600,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => false,
        ],
        [
            'name' => 'test_httponly',
            'value' => 'httponly_value',
            'expire' => time() + 3600,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
        ],
        [
            'name' => 'test_session',
            'value' => session()->getId(),
            'expire' => 0, // Session cookie
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => false,
        ],
    ];
    
    $results = [];
    foreach ($cookies as $cookie) {
        $success = setcookie(
            $cookie['name'],
            $cookie['value'],
            $cookie['expire'],
            $cookie['path'],
            $cookie['domain'],
            $cookie['secure'],
            $cookie['httponly']
        );
        $results[$cookie['name']] = $success ? 'set' : 'failed';
    }
    
    return [
        'success' => true,
        'cookies_set' => $results,
        'current_cookies' => $_COOKIE,
    ];
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UltraThink Complete Session Fix</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: #0f0f23; 
            color: #e0e0e0;
            line-height: 1.6;
        }
        .container { max-width: 1600px; margin: 0 auto; padding: 20px; }
        
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 40px 20px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .header h1 { 
            font-size: 2.5rem; 
            margin-bottom: 10px; 
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .header p { font-size: 1.1rem; opacity: 0.9; }
        
        .grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px;
        }
        
        .card {
            background: #1a1a2e;
            border: 1px solid #16213e;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .card h2 { 
            color: #4fbdba;
            margin-bottom: 20px; 
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
            margin: 5px;
            display: inline-block;
        }
        .btn:hover { 
            background: #1d4ed8; 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.4);
        }
        .btn.danger { background: #dc2626; }
        .btn.danger:hover { background: #b91c1c; }
        .btn.success { background: #059669; }
        .btn.success:hover { background: #047857; }
        .btn.warning { background: #d97706; }
        .btn.warning:hover { background: #b45309; }
        
        .status {
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 0.9rem;
        }
        .status.success { 
            background: #065f46; 
            border: 1px solid #10b981; 
            color: #10b981;
        }
        .status.error { 
            background: #7f1d1d; 
            border: 1px solid #ef4444; 
            color: #ef4444;
        }
        .status.warning { 
            background: #78350f; 
            border: 1px solid #f59e0b; 
            color: #f59e0b;
        }
        .status.info { 
            background: #1e3a8a; 
            border: 1px solid #3b82f6; 
            color: #60a5fa;
        }
        
        .code {
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 15px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.85rem;
            overflow-x: auto;
            margin: 10px 0;
            color: #c9d1d9;
        }
        
        pre { 
            margin: 0; 
            white-space: pre-wrap; 
            word-wrap: break-word; 
        }
        
        .problem-list {
            list-style: none;
            padding: 0;
        }
        .problem-list li {
            padding: 8px 12px;
            margin: 5px 0;
            background: #7f1d1d;
            border-left: 4px solid #ef4444;
            border-radius: 4px;
        }
        
        .success-list {
            list-style: none;
            padding: 0;
        }
        .success-list li {
            padding: 8px 12px;
            margin: 5px 0;
            background: #065f46;
            border-left: 4px solid #10b981;
            border-radius: 4px;
        }
        
        .cookie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .cookie-item {
            background: #374151;
            padding: 10px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85rem;
            word-break: break-all;
        }
        .cookie-item.session { 
            background: #065f46; 
            border: 1px solid #10b981;
        }
        
        .loader {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #3b82f6;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .terminal {
            background: #000;
            color: #0f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            margin: 20px 0;
            max-height: 400px;
            overflow-y: auto;
        }
        .terminal-line {
            margin: 2px 0;
        }
        .terminal-prompt {
            color: #0ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 UltraThink Complete Session Fix</h1>
            <p>Vollständige Diagnose und Reparatur des Session-Systems</p>
        </div>
        
        <!-- Quick Actions -->
        <div class="card" style="margin-bottom: 30px;">
            <h2>⚡ Quick Actions</h2>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <button class="btn success" onclick="runCompleteFix()">
                    🔧 Complete Fix (Empfohlen)
                </button>
                <button class="btn" onclick="diagnose()">
                    🔍 System Diagnose
                </button>
                <button class="btn warning" onclick="fixPermissions()">
                    🔐 Fix Permissions
                </button>
                <button class="btn danger" onclick="clearSessions()">
                    🗑️ Clear All Sessions
                </button>
            </div>
            <div id="quickResult" style="margin-top: 20px;"></div>
        </div>
        
        <div class="grid">
            <!-- Diagnosis Panel -->
            <div class="card">
                <h2>🔍 System Diagnose</h2>
                <div id="diagnosisResult">
                    <div class="status info">Klicke auf "System Diagnose" um zu starten...</div>
                </div>
            </div>
            
            <!-- Session Test Panel -->
            <div class="card">
                <h2>🧪 Session Tests</h2>
                <button class="btn" onclick="testSession()">Test Session Creation</button>
                <button class="btn" onclick="testCookies()">Test Cookie Setting</button>
                <button class="btn success" onclick="forceAdminLogin()">Force Admin Login</button>
                <button class="btn success" onclick="forcePortalLogin()">Force Portal Login</button>
                <div id="sessionTestResult" style="margin-top: 20px;"></div>
            </div>
        </div>
        
        <!-- Terminal Output -->
        <div class="card">
            <h2>📟 Terminal Output</h2>
            <div class="terminal" id="terminal">
                <div class="terminal-line">
                    <span class="terminal-prompt">$</span> UltraThink Session Fix v2.0 initialized...
                </div>
            </div>
        </div>
        
        <!-- Cookie Inspector -->
        <div class="card">
            <h2>🍪 Cookie Inspector</h2>
            <div id="cookieInspector">
                <div class="cookie-grid" id="cookieGrid"></div>
            </div>
        </div>
        
        <!-- Manual Actions -->
        <div class="card">
            <h2>🔨 Manuelle Aktionen</h2>
            <div class="code">
                <pre># 1. Session-Verzeichnisse manuell erstellen:
sudo mkdir -p storage/framework/sessions/portal
sudo mkdir -p storage/framework/sessions/admin
sudo chmod -R 775 storage/framework/sessions
sudo chown -R www-data:www-data storage/framework/sessions

# 2. Cache und Config löschen:
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear

# 3. Session-Treiber testen:
php artisan tinker
>>> session()->put('test', 'value');
>>> session()->save();
>>> session()->get('test');</pre>
            </div>
        </div>
    </div>
    
    <script>
        // Terminal output helper
        function terminalLog(message, type = 'info') {
            const terminal = document.getElementById('terminal');
            const line = document.createElement('div');
            line.className = 'terminal-line';
            
            const colors = {
                'info': '#0f0',
                'error': '#f00',
                'warning': '#ff0',
                'success': '#0ff'
            };
            
            line.innerHTML = `<span class="terminal-prompt">$</span> <span style="color: ${colors[type]}">${message}</span>`;
            terminal.appendChild(line);
            terminal.scrollTop = terminal.scrollHeight;
        }
        
        // Update cookie display
        function updateCookies() {
            const grid = document.getElementById('cookieGrid');
            const cookies = document.cookie.split(';').filter(c => c.trim());
            
            if (cookies.length === 0 || cookies[0] === '') {
                grid.innerHTML = '<div class="status warning">Keine Cookies gefunden!</div>';
                return;
            }
            
            grid.innerHTML = '';
            cookies.forEach(cookie => {
                const [name, value] = cookie.trim().split('=');
                const div = document.createElement('div');
                div.className = 'cookie-item';
                if (name && name.includes('session')) {
                    div.className += ' session';
                }
                div.innerHTML = `<strong>${name}:</strong><br>${value ? value.substring(0, 50) + '...' : '(empty)'}`;
                grid.appendChild(div);
            });
        }
        
        // API call helper
        async function apiCall(action, data = {}) {
            terminalLog(`Executing: ${action}...`, 'info');
            
            const formData = new FormData();
            formData.append('action', action);
            for (const key in data) {
                formData.append(key, data[key]);
            }
            
            try {
                const response = await fetch('ultrathink-complete-fix.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                terminalLog(`${action} completed`, 'success');
                return result;
            } catch (error) {
                terminalLog(`Error in ${action}: ${error.message}`, 'error');
                return { success: false, error: error.message };
            }
        }
        
        // Complete fix
        async function runCompleteFix() {
            const resultDiv = document.getElementById('quickResult');
            resultDiv.innerHTML = '<div class="status info">🔧 Running complete fix...</div>';
            
            terminalLog('Starting complete fix procedure...', 'warning');
            
            // Step 1: Diagnose
            const diagnosis = await apiCall('diagnose');
            
            // Step 2: Fix permissions if needed
            if (diagnosis.problems && diagnosis.problems.length > 0) {
                terminalLog('Problems detected, fixing permissions...', 'warning');
                await apiCall('fix_permissions');
            }
            
            // Step 3: Clear old sessions
            terminalLog('Clearing old sessions...', 'info');
            await apiCall('clear_sessions');
            
            // Step 4: Test session creation
            terminalLog('Testing session creation...', 'info');
            const sessionTest = await apiCall('test_session');
            
            if (sessionTest.file_exists) {
                resultDiv.innerHTML = '<div class="status success">✅ Complete fix erfolgreich! Sessions funktionieren.</div>';
                terminalLog('Fix completed successfully!', 'success');
            } else {
                resultDiv.innerHTML = '<div class="status error">❌ Sessions funktionieren noch nicht. Siehe Terminal für Details.</div>';
                terminalLog('Fix completed with issues', 'error');
            }
            
            updateCookies();
        }
        
        // Diagnose
        async function diagnose() {
            const resultDiv = document.getElementById('diagnosisResult');
            resultDiv.innerHTML = '<div class="loader"></div> Analysiere...';
            
            const result = await apiCall('diagnose');
            
            let html = '';
            
            // Problems
            if (result.problems && result.problems.length > 0) {
                html += '<h3 style="color: #ef4444;">🚨 Gefundene Probleme:</h3>';
                html += '<ul class="problem-list">';
                result.problems.forEach(problem => {
                    html += `<li>${problem}</li>`;
                });
                html += '</ul>';
            } else {
                html += '<div class="status success">✅ Keine Probleme gefunden!</div>';
            }
            
            // Session info
            html += '<h3 style="color: #3b82f6; margin-top: 20px;">📊 Session Configuration:</h3>';
            html += '<div class="code"><pre>' + JSON.stringify(result.laravel_session, null, 2) + '</pre></div>';
            
            // Directory info
            html += '<h3 style="color: #3b82f6; margin-top: 20px;">📁 Verzeichnis-Status:</h3>';
            for (const [key, dir] of Object.entries(result.directories)) {
                const status = dir.writable ? '✅' : '❌';
                html += `<div class="status ${dir.writable ? 'success' : 'error'}">`;
                html += `${status} ${key}: ${dir.path}<br>`;
                html += `Permissions: ${dir.permissions}, Owner: ${dir.owner}:${dir.group}, Files: ${dir.files}`;
                html += '</div>';
            }
            
            resultDiv.innerHTML = html;
        }
        
        // Fix permissions
        async function fixPermissions() {
            const result = await apiCall('fix_permissions');
            
            if (result.success) {
                terminalLog('Permissions fixed successfully', 'success');
                result.actions.forEach(action => {
                    terminalLog(action, 'info');
                });
            }
            
            // Re-run diagnosis
            await diagnose();
        }
        
        // Test session
        async function testSession() {
            const resultDiv = document.getElementById('sessionTestResult');
            const result = await apiCall('test_session');
            
            let html = '<h3>Session Test Results:</h3>';
            html += '<div class="code"><pre>' + JSON.stringify(result, null, 2) + '</pre></div>';
            
            if (result.file_exists) {
                html += '<div class="status success">✅ Session file created successfully!</div>';
            } else {
                html += '<div class="status error">❌ Session file not created!</div>';
            }
            
            resultDiv.innerHTML = html;
            updateCookies();
        }
        
        // Test cookies
        async function testCookies() {
            const resultDiv = document.getElementById('sessionTestResult');
            const result = await apiCall('create_test_cookie');
            
            let html = '<h3>Cookie Test Results:</h3>';
            html += '<div class="code"><pre>' + JSON.stringify(result, null, 2) + '</pre></div>';
            
            resultDiv.innerHTML = html;
            
            // Reload page after 1 second to see new cookies
            setTimeout(() => {
                terminalLog('Reloading to check cookies...', 'info');
                location.reload();
            }, 1000);
        }
        
        // Force login
        async function forceAdminLogin() {
            const result = await apiCall('force_login', { type: 'admin' });
            
            if (result.success) {
                terminalLog(`Admin login forced: ${result.user}`, 'success');
                terminalLog(`Redirecting to ${result.redirect}...`, 'info');
                
                setTimeout(() => {
                    window.open(result.redirect, '_blank');
                }, 1000);
            } else {
                terminalLog('Failed to force admin login', 'error');
            }
            
            updateCookies();
        }
        
        async function forcePortalLogin() {
            const result = await apiCall('force_login', { type: 'portal' });
            
            if (result.success) {
                terminalLog(`Portal login forced: ${result.user}`, 'success');
                terminalLog(`Redirecting to ${result.redirect}...`, 'info');
                
                setTimeout(() => {
                    window.open(result.redirect, '_blank');
                }, 1000);
            } else {
                terminalLog('Failed to force portal login', 'error');
            }
            
            updateCookies();
        }
        
        // Clear sessions
        async function clearSessions() {
            if (!confirm('Wirklich alle Sessions löschen?')) return;
            
            const result = await apiCall('clear_sessions');
            terminalLog(result.message, 'warning');
            
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
        
        // Initialize
        updateCookies();
        setInterval(updateCookies, 2000);
        
        // Auto-run diagnosis on load
        setTimeout(() => {
            diagnose();
        }, 500);
    </script>
</body>
</html>