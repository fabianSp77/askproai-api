<?php
/**
 * State-of-the-Art Session & Cookie Management System
 * 
 * Dieses System implementiert eine moderne, sichere Multi-Portal Session-Architektur
 * mit vollständiger Unterstützung für Admin->Business Portal Übergang.
 */

// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Lade notwendige Klassen
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Models\PortalUser;

// API Handler
if (isset($_SERVER['HTTP_CONTENT_TYPE']) && $_SERVER['HTTP_CONTENT_TYPE'] === 'application/json') {
    $input = json_decode(file_get_contents('php://input'), true);
    header('Content-Type: application/json');
    
    switch ($input['action'] ?? '') {
        case 'analyze':
            echo json_encode(analyzeSessionSystem());
            break;
            
        case 'fix':
            echo json_encode(fixSessionSystem($input['type'] ?? 'all'));
            break;
            
        case 'test':
            echo json_encode(testPortal($input['portal'] ?? 'admin'));
            break;
            
        case 'login':
            echo json_encode(performLogin($input['portal'] ?? 'admin', $input['credentials'] ?? []));
            break;
            
        case 'impersonate':
            echo json_encode(setupImpersonation($input['company_id'] ?? null));
            break;
    }
    
    $kernel->terminate($request, $response);
    exit;
}

// Hilfsfunktionen
function analyzeSessionSystem() {
    $analysis = [
        'configuration' => [
            'default_driver' => config('session.driver'),
            'default_cookie' => config('session.cookie'),
            'portal_cookie' => config('session_portal.cookie', 'not_configured'),
            'admin_cookie' => config('session_admin.cookie', 'not_configured'),
            'session_domain' => config('session.domain') ?: 'not_set',
            'secure_cookie' => config('session.secure'),
            'http_only' => config('session.http_only'),
            'same_site' => config('session.same_site'),
        ],
        'directories' => [
            'main' => [
                'path' => storage_path('framework/sessions'),
                'exists' => is_dir(storage_path('framework/sessions')),
                'writable' => is_writable(storage_path('framework/sessions')),
                'files' => count(glob(storage_path('framework/sessions/*'))),
            ],
            'portal' => [
                'path' => storage_path('framework/sessions/portal'),
                'exists' => is_dir(storage_path('framework/sessions/portal')),
                'writable' => is_dir(storage_path('framework/sessions/portal')) && is_writable(storage_path('framework/sessions/portal')),
                'files' => is_dir(storage_path('framework/sessions/portal')) ? count(glob(storage_path('framework/sessions/portal/*'))) : 0,
            ],
            'admin' => [
                'path' => storage_path('framework/sessions/admin'),
                'exists' => is_dir(storage_path('framework/sessions/admin')),
                'writable' => is_dir(storage_path('framework/sessions/admin')) && is_writable(storage_path('framework/sessions/admin')),
                'files' => is_dir(storage_path('framework/sessions/admin')) ? count(glob(storage_path('framework/sessions/admin/*'))) : 0,
            ],
        ],
        'current_session' => [
            'id' => session()->getId(),
            'name' => session()->getName(),
            'driver' => session()->getDefaultDriver(),
            'auth_web' => auth('web')->check(),
            'auth_portal' => auth('portal')->check(),
            'user_web' => auth('web')->user() ? auth('web')->user()->email : null,
            'user_portal' => auth('portal')->user() ? auth('portal')->user()->email : null,
            'impersonation' => session('admin_impersonation'),
        ],
        'cookies' => array_keys($_COOKIE),
        'problems' => [],
    ];
    
    // Identifiziere Probleme
    if (!$analysis['directories']['portal']['exists']) {
        $analysis['problems'][] = 'Portal session directory does not exist';
    }
    if (!$analysis['directories']['admin']['exists']) {
        $analysis['problems'][] = 'Admin session directory does not exist';
    }
    if (config('session.driver') === 'file' && !$analysis['directories']['main']['writable']) {
        $analysis['problems'][] = 'Main session directory is not writable';
    }
    if (!config('session_portal.cookie')) {
        $analysis['problems'][] = 'Portal session configuration not loaded';
    }
    
    return $analysis;
}

function fixSessionSystem($type = 'all') {
    $fixes = [];
    
    // 1. Session-Verzeichnisse erstellen
    if ($type === 'all' || $type === 'directories') {
        $dirs = [
            'framework/sessions/portal',
            'framework/sessions/admin',
        ];
        
        foreach ($dirs as $dir) {
            $path = storage_path($dir);
            if (!is_dir($path)) {
                if (mkdir($path, 0755, true)) {
                    $fixes[] = "Created directory: $path";
                    // .gitignore hinzufügen
                    file_put_contents($path . '/.gitignore', "*\n!.gitignore\n");
                } else {
                    $fixes[] = "Failed to create directory: $path";
                }
            }
        }
    }
    
    // 2. Session-Konfigurationen prüfen
    if ($type === 'all' || $type === 'config') {
        // Prüfe ob config files existieren
        $configFiles = [
            'config/session_portal.php' => file_exists(base_path('config/session_portal.php')),
            'config/session_admin.php' => file_exists(base_path('config/session_admin.php')),
        ];
        
        foreach ($configFiles as $file => $exists) {
            if (!$exists) {
                $fixes[] = "Missing config file: $file - Creating from template";
                createSessionConfig($file);
            }
        }
    }
    
    // 3. Middleware-Reihenfolge korrigieren
    if ($type === 'all' || $type === 'middleware') {
        $fixes[] = "Middleware order should be fixed in app/Http/Kernel.php";
        $fixes[] = "PortalSessionConfig must come BEFORE StartSession";
    }
    
    return [
        'success' => true,
        'fixes' => $fixes,
        'analysis_after' => analyzeSessionSystem(),
    ];
}

function createSessionConfig($file) {
    $isPortal = strpos($file, 'portal') !== false;
    $name = $isPortal ? 'portal' : 'admin';
    
    $content = "<?php

return [
    'driver' => env('SESSION_DRIVER', 'file'),
    'lifetime' => " . ($isPortal ? 480 : 120) . ",
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => storage_path('framework/sessions/$name'),
    'connection' => env('SESSION_CONNECTION'),
    'table' => '{$name}_sessions',
    'store' => env('SESSION_STORE'),
    'lottery' => [2, 100],
    'cookie' => env('" . strtoupper($name) . "_SESSION_COOKIE', 'askproai_{$name}_session'),
    'path' => " . ($isPortal ? "'/business'" : "'/'") . ",
    'domain' => env('SESSION_DOMAIN'),
    'secure' => env('SESSION_SECURE_COOKIE', false),
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
];
";
    
    file_put_contents(base_path($file), $content);
}

function performLogin($portal, $credentials) {
    if ($portal === 'admin') {
        $email = $credentials['email'] ?? 'demo@askproai.de';
        $password = $credentials['password'] ?? 'demo123';
        
        $user = User::where('email', $email)->first();
        if ($user && \Illuminate\Support\Facades\Hash::check($password, $user->password)) {
            Auth::guard('web')->login($user);
            session()->regenerate();
            
            return [
                'success' => true,
                'portal' => 'admin',
                'user' => $user->email,
                'session_id' => session()->getId(),
                'redirect' => '/admin',
            ];
        }
    } else if ($portal === 'business') {
        $email = $credentials['email'] ?? 'kundenacc@askproai.de';
        $password = $credentials['password'] ?? 'demo123';
        
        $user = PortalUser::where('email', $email)->first();
        if ($user && \Illuminate\Support\Facades\Hash::check($password, $user->password)) {
            // Nutze Portal Session Config
            config(['session' => config('session_portal')]);
            
            Auth::guard('portal')->login($user);
            session()->regenerate();
            
            // Zusätzliche Session-Daten für Portal
            session([
                'portal_user_id' => $user->id,
                'company_id' => $user->company_id,
            ]);
            
            return [
                'success' => true,
                'portal' => 'business',
                'user' => $user->email,
                'session_id' => session()->getId(),
                'redirect' => '/business/dashboard',
            ];
        }
    }
    
    return ['success' => false, 'error' => 'Invalid credentials'];
}

function setupImpersonation($companyId) {
    if (!auth('web')->check()) {
        return ['success' => false, 'error' => 'Admin not authenticated'];
    }
    
    $admin = auth('web')->user();
    $company = \App\Models\Company::find($companyId);
    
    if (!$company) {
        return ['success' => false, 'error' => 'Company not found'];
    }
    
    // Setup impersonation session
    $sessionData = [
        'admin_id' => $admin->id,
        'admin_email' => $admin->email,
        'company_id' => $company->id,
        'company_name' => $company->name,
        'started_at' => now()->toIso8601String(),
    ];
    
    session(['admin_impersonation' => $sessionData]);
    session(['is_admin_viewing' => true]);
    
    // Generate temporary access token
    $token = \Illuminate\Support\Str::random(64);
    \Illuminate\Support\Facades\Cache::put(
        'admin_viewing_' . $token,
        $sessionData,
        now()->addMinutes(15)
    );
    
    return [
        'success' => true,
        'token' => $token,
        'redirect_url' => "/admin-view-portal/{$token}",
        'impersonation_data' => $sessionData,
    ];
}

function testPortal($portal) {
    $tests = [];
    
    if ($portal === 'admin') {
        // Test Admin Portal
        $tests['auth_check'] = auth('web')->check();
        $tests['user'] = auth('web')->user() ? auth('web')->user()->email : null;
        $tests['session_cookie'] = $_COOKIE[config('session.cookie')] ?? null;
        $tests['can_access_admin'] = auth('web')->user() && auth('web')->user()->canAccessPanel(new \Filament\Panel());
    } else {
        // Test Business Portal
        $tests['auth_check'] = auth('portal')->check();
        $tests['user'] = auth('portal')->user() ? auth('portal')->user()->email : null;
        $tests['session_cookie'] = $_COOKIE[config('session_portal.cookie', 'askproai_portal_session')] ?? null;
        $tests['is_impersonating'] = session('is_admin_viewing', false);
        $tests['impersonation_data'] = session('admin_impersonation');
    }
    
    $tests['session_id'] = session()->getId();
    $tests['session_driver'] = session()->getDefaultDriver();
    
    return $tests;
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>State-of-the-Art Session Management</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: #f0f2f5; 
            color: #1a1a1a;
            line-height: 1.6;
        }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            text-align: center;
            margin-bottom: 40px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header h1 { font-size: 2.5rem; margin-bottom: 10px; }
        .header p { font-size: 1.1rem; opacity: 0.9; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; }
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .card:hover { box-shadow: 0 8px 16px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .card h2 { 
            font-size: 1.25rem; 
            margin-bottom: 16px; 
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card h2 .icon { font-size: 1.5rem; }
        
        .btn {
            background: #5e72e4;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn:hover { background: #4c63d2; transform: translateY(-1px); }
        .btn:active { transform: translateY(0); }
        .btn.secondary { background: #6c757d; }
        .btn.secondary:hover { background: #5a6268; }
        .btn.success { background: #2dce89; }
        .btn.success:hover { background: #26b877; }
        .btn.danger { background: #f5365c; }
        .btn.danger:hover { background: #ec0c38; }
        .btn.small { padding: 8px 16px; font-size: 0.875rem; }
        
        .btn-group { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; }
        
        .status {
            padding: 16px;
            border-radius: 8px;
            margin: 12px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .status.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        .code {
            background: #f6f8fa;
            border: 1px solid #e1e4e8;
            border-radius: 6px;
            padding: 16px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.875rem;
            overflow-x: auto;
            margin: 12px 0;
        }
        
        pre { margin: 0; white-space: pre-wrap; word-wrap: break-word; }
        
        .cookie-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }
        .cookie-item {
            background: #e9ecef;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-family: monospace;
        }
        .cookie-item.active { background: #28a745; color: white; }
        
        .test-results {
            margin-top: 16px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .loader {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #5e72e4;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            animation: slideIn 0.3s;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-close {
            cursor: pointer;
            font-size: 1.5rem;
            color: #999;
            transition: color 0.2s;
        }
        .modal-close:hover { color: #333; }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideIn { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #495057;
        }
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #5e72e4;
            box-shadow: 0 0 0 3px rgba(94, 114, 228, 0.1);
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            font-weight: 500;
            color: #6c757d;
        }
        .tab:hover { color: #495057; }
        .tab.active {
            color: #5e72e4;
            border-bottom-color: #5e72e4;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>🚀 State-of-the-Art Session Management</h1>
            <p>Moderne Multi-Portal Authentication mit Best Practices</p>
        </div>
    </div>
    
    <div class="container">
        <!-- System Analysis -->
        <div class="grid">
            <div class="card">
                <h2><span class="icon">🔍</span> System-Analyse</h2>
                <p>Vollständige Analyse des aktuellen Session-Systems</p>
                <div class="btn-group">
                    <button class="btn" onclick="analyzeSystem()">
                        <span class="loader" style="display:none"></span>
                        Analysieren
                    </button>
                    <button class="btn secondary" onclick="showAnalysisDetails()">Details</button>
                </div>
                <div id="analysisResult" class="test-results"></div>
            </div>
            
            <!-- Auto-Fix -->
            <div class="card">
                <h2><span class="icon">🔧</span> Automatische Reparatur</h2>
                <p>Behebe erkannte Probleme automatisch</p>
                <div class="btn-group">
                    <button class="btn success" onclick="fixSystem('all')">Alles reparieren</button>
                    <button class="btn secondary small" onclick="fixSystem('directories')">Nur Verzeichnisse</button>
                    <button class="btn secondary small" onclick="fixSystem('config')">Nur Config</button>
                </div>
                <div id="fixResult" class="test-results"></div>
            </div>
        </div>
        
        <!-- Portal Tests -->
        <div class="grid" style="margin-top: 20px;">
            <div class="card">
                <h2><span class="icon">👨‍💼</span> Admin Portal</h2>
                <div class="tabs">
                    <div class="tab active" onclick="switchTab('admin', 'login')">Login</div>
                    <div class="tab" onclick="switchTab('admin', 'test')">Test</div>
                    <div class="tab" onclick="switchTab('admin', 'impersonate')">Impersonate</div>
                </div>
                
                <div id="admin-login" class="tab-content active">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="adminEmail" value="demo@askproai.de">
                    </div>
                    <div class="form-group">
                        <label>Passwort</label>
                        <input type="password" id="adminPassword" value="demo123">
                    </div>
                    <button class="btn" onclick="login('admin')">Admin Login</button>
                </div>
                
                <div id="admin-test" class="tab-content">
                    <button class="btn" onclick="testPortal('admin')">Test Admin Portal</button>
                    <button class="btn secondary" onclick="window.open('/admin', '_blank')">Öffne Admin Panel</button>
                </div>
                
                <div id="admin-impersonate" class="tab-content">
                    <div class="form-group">
                        <label>Company ID</label>
                        <input type="number" id="companyId" value="1">
                    </div>
                    <button class="btn success" onclick="impersonate()">Als Kunde anmelden</button>
                </div>
                
                <div id="adminResult" class="test-results"></div>
            </div>
            
            <div class="card">
                <h2><span class="icon">🏢</span> Business Portal</h2>
                <div class="tabs">
                    <div class="tab active" onclick="switchTab('business', 'login')">Login</div>
                    <div class="tab" onclick="switchTab('business', 'test')">Test</div>
                </div>
                
                <div id="business-login" class="tab-content active">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="businessEmail" value="kundenacc@askproai.de">
                    </div>
                    <div class="form-group">
                        <label>Passwort</label>
                        <input type="password" id="businessPassword" value="demo123">
                    </div>
                    <button class="btn" onclick="login('business')">Business Login</button>
                </div>
                
                <div id="business-test" class="tab-content">
                    <button class="btn" onclick="testPortal('business')">Test Business Portal</button>
                    <button class="btn secondary" onclick="window.open('/business', '_blank')">Öffne Business Portal</button>
                </div>
                
                <div id="businessResult" class="test-results"></div>
            </div>
        </div>
        
        <!-- Cookie Inspector -->
        <div class="card" style="margin-top: 20px;">
            <h2><span class="icon">🍪</span> Cookie Inspector</h2>
            <div id="cookieInspector" class="cookie-list"></div>
        </div>
        
        <!-- System Status -->
        <div class="card" style="margin-top: 20px;">
            <h2><span class="icon">📊</span> System Status</h2>
            <div class="code">
                <pre id="systemStatus">Lade System-Status...</pre>
            </div>
        </div>
    </div>
    
    <!-- Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Details</h2>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <div id="modalBody"></div>
        </div>
    </div>
    
    <script>
        // State Management
        let currentAnalysis = null;
        
        // API Functions
        async function apiCall(action, data = {}) {
            try {
                const response = await fetch('state-of-art-session-fix.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action, ...data }),
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return await response.json();
            } catch (error) {
                console.error('API Error:', error);
                return { success: false, error: error.message };
            }
        }
        
        // System Analysis
        async function analyzeSystem() {
            const button = event.target;
            const loader = button.querySelector('.loader');
            const resultDiv = document.getElementById('analysisResult');
            
            loader.style.display = 'inline-block';
            button.disabled = true;
            
            const result = await apiCall('analyze');
            currentAnalysis = result;
            
            loader.style.display = 'none';
            button.disabled = false;
            
            let html = '';
            
            if (result.problems && result.problems.length > 0) {
                html += '<div class="status error">⚠️ Probleme gefunden:</div>';
                html += '<ul style="margin-left: 20px;">';
                result.problems.forEach(problem => {
                    html += `<li>${problem}</li>`;
                });
                html += '</ul>';
            } else {
                html += '<div class="status success">✅ Keine Probleme gefunden!</div>';
            }
            
            html += '<div class="status info">Session Info: ' + result.current_session.id + '</div>';
            
            resultDiv.innerHTML = html;
            updateSystemStatus();
        }
        
        // Show Analysis Details
        function showAnalysisDetails() {
            if (!currentAnalysis) {
                alert('Bitte zuerst eine Analyse durchführen!');
                return;
            }
            
            document.getElementById('modalTitle').textContent = 'Analyse-Details';
            document.getElementById('modalBody').innerHTML = `<pre>${JSON.stringify(currentAnalysis, null, 2)}</pre>`;
            document.getElementById('modal').style.display = 'block';
        }
        
        // Fix System
        async function fixSystem(type) {
            const button = event.target;
            const resultDiv = document.getElementById('fixResult');
            
            button.disabled = true;
            resultDiv.innerHTML = '<div class="status info">🔧 Repariere System...</div>';
            
            const result = await apiCall('fix', { type });
            
            button.disabled = false;
            
            let html = '';
            if (result.success) {
                html += '<div class="status success">✅ Reparatur erfolgreich!</div>';
                if (result.fixes && result.fixes.length > 0) {
                    html += '<ul style="margin-left: 20px;">';
                    result.fixes.forEach(fix => {
                        html += `<li>${fix}</li>`;
                    });
                    html += '</ul>';
                }
            } else {
                html += '<div class="status error">❌ Reparatur fehlgeschlagen</div>';
            }
            
            resultDiv.innerHTML = html;
            
            // Re-analyze
            await analyzeSystem();
        }
        
        // Login
        async function login(portal) {
            const resultDiv = document.getElementById(portal + 'Result');
            
            let credentials = {};
            if (portal === 'admin') {
                credentials = {
                    email: document.getElementById('adminEmail').value,
                    password: document.getElementById('adminPassword').value
                };
            } else {
                credentials = {
                    email: document.getElementById('businessEmail').value,
                    password: document.getElementById('businessPassword').value
                };
            }
            
            resultDiv.innerHTML = '<div class="status info">🔐 Anmeldung läuft...</div>';
            
            const result = await apiCall('login', { portal, credentials });
            
            if (result.success) {
                resultDiv.innerHTML = `
                    <div class="status success">
                        ✅ Erfolgreich angemeldet!<br>
                        User: ${result.user}<br>
                        Session: ${result.session_id}
                    </div>
                `;
                
                if (result.redirect) {
                    setTimeout(() => {
                        window.open(result.redirect, '_blank');
                    }, 1000);
                }
            } else {
                resultDiv.innerHTML = `<div class="status error">❌ Anmeldung fehlgeschlagen: ${result.error}</div>`;
            }
            
            updateCookies();
            updateSystemStatus();
        }
        
        // Test Portal
        async function testPortal(portal) {
            const resultDiv = document.getElementById(portal + 'Result');
            
            resultDiv.innerHTML = '<div class="status info">🧪 Teste Portal...</div>';
            
            const result = await apiCall('test', { portal });
            
            let html = '<div class="code"><pre>' + JSON.stringify(result, null, 2) + '</pre></div>';
            
            if (result.auth_check) {
                html = `<div class="status success">✅ Authentifiziert als: ${result.user}</div>` + html;
            } else {
                html = `<div class="status warning">⚠️ Nicht authentifiziert</div>` + html;
            }
            
            resultDiv.innerHTML = html;
        }
        
        // Impersonate
        async function impersonate() {
            const companyId = document.getElementById('companyId').value;
            const resultDiv = document.getElementById('adminResult');
            
            resultDiv.innerHTML = '<div class="status info">🎭 Richte Impersonation ein...</div>';
            
            const result = await apiCall('impersonate', { company_id: companyId });
            
            if (result.success) {
                resultDiv.innerHTML = `
                    <div class="status success">
                        ✅ Impersonation eingerichtet!<br>
                        Company: ${result.impersonation_data.company_name}<br>
                        Token: ${result.token}
                    </div>
                `;
                
                setTimeout(() => {
                    window.open(result.redirect_url, '_blank');
                }, 1000);
            } else {
                resultDiv.innerHTML = `<div class="status error">❌ Fehler: ${result.error}</div>`;
            }
        }
        
        // Tab Switching
        function switchTab(portal, tabName) {
            // Update tab classes
            const tabs = document.querySelectorAll(`#${portal}Result`).length > 0 
                ? event.target.parentElement.querySelectorAll('.tab')
                : document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // Update content
            const contents = event.target.parentElement.parentElement.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            document.getElementById(`${portal}-${tabName}`).classList.add('active');
        }
        
        // Cookie Inspector
        function updateCookies() {
            const inspector = document.getElementById('cookieInspector');
            const cookies = document.cookie.split(';').filter(c => c.trim());
            
            if (cookies.length === 0 || cookies[0] === '') {
                inspector.innerHTML = '<div class="status warning">Keine Cookies gefunden</div>';
                return;
            }
            
            let html = '';
            cookies.forEach(cookie => {
                const [name, value] = cookie.trim().split('=');
                const isActive = name && (name.includes('session') || name.includes('remember'));
                html += `<div class="cookie-item ${isActive ? 'active' : ''}">${name}</div>`;
            });
            
            inspector.innerHTML = html;
        }
        
        // System Status
        async function updateSystemStatus() {
            const statusDiv = document.getElementById('systemStatus');
            
            const adminTest = await apiCall('test', { portal: 'admin' });
            const businessTest = await apiCall('test', { portal: 'business' });
            
            const status = {
                'Admin Portal': {
                    'Authenticated': adminTest.auth_check ? '✅ Yes' : '❌ No',
                    'User': adminTest.user || 'None',
                    'Session': adminTest.session_id
                },
                'Business Portal': {
                    'Authenticated': businessTest.auth_check ? '✅ Yes' : '❌ No',
                    'User': businessTest.user || 'None',
                    'Session': businessTest.session_id,
                    'Impersonating': businessTest.is_impersonating ? '✅ Yes' : '❌ No'
                },
                'Cookies': Object.keys(document.cookie.split(';').reduce((acc, c) => {
                    const [name] = c.trim().split('=');
                    if (name) acc[name] = true;
                    return acc;
                }, {}))
            };
            
            statusDiv.textContent = JSON.stringify(status, null, 2);
        }
        
        // Modal
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }
        
        // Click outside modal to close
        window.onclick = function(event) {
            const modal = document.getElementById('modal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
        
        // Initialize
        updateCookies();
        updateSystemStatus();
        
        // Auto-refresh
        setInterval(() => {
            updateCookies();
        }, 2000);
    </script>
</body>
</html>