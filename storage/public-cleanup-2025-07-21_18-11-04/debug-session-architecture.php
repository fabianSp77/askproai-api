<?php
/**
 * ULTRATHINK: Session Architecture Debug
 * Verstehen warum Sessions nicht funktionieren
 */

// Laravel Bootstrap
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Force login
$user = \App\Models\PortalUser::withoutGlobalScopes()
    ->where('email', 'demo@askproai.de')
    ->first();

if ($user) {
    \Illuminate\Support\Facades\Auth::guard('portal')->login($user, true);
    session(['portal_authenticated' => true]);
    session(['portal_user_id' => $user->id]);
    session()->save();
}

$sessionId = session()->getId();
$sessionName = session()->getName();
$sessionPath = session_save_path();
$sessionFile = $sessionPath . '/sess_' . $sessionId;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Architecture Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>🔍 ULTRATHINK: Session Architecture Analysis</h1>
    
    <div class="section">
        <h2>1. PHP Session Status</h2>
        <pre>
Session ID: <?php echo $sessionId; ?>
Session Name: <?php echo $sessionName; ?>
Session Save Path: <?php echo $sessionPath; ?>
Session File: <?php echo $sessionFile; ?>
Session File Exists: <?php echo file_exists($sessionFile) ? '<span class="success">YES</span>' : '<span class="error">NO</span>'; ?>
Session Data: <?php print_r($_SESSION); ?>
Auth Check: <?php echo \Illuminate\Support\Facades\Auth::guard('portal')->check() ? '<span class="success">AUTHENTICATED</span>' : '<span class="error">NOT AUTHENTICATED</span>'; ?>
        </pre>
    </div>
    
    <div class="section">
        <h2>2. Cookie Analysis</h2>
        <pre>
PHP Cookies: <?php print_r($_COOKIE); ?>
        </pre>
        <div id="js-cookies"></div>
    </div>
    
    <div class="section">
        <h2>3. Session Cookie Configuration</h2>
        <pre>
<?php
$cookieParams = session_get_cookie_params();
echo "Domain: " . ($cookieParams['domain'] ?: 'not set') . "\n";
echo "Path: " . $cookieParams['path'] . "\n";
echo "Secure: " . ($cookieParams['secure'] ? 'YES' : 'NO') . "\n";
echo "HttpOnly: " . ($cookieParams['httponly'] ? 'YES' : 'NO') . "\n";
echo "SameSite: " . ($cookieParams['samesite'] ?: 'not set') . "\n";
?>
        </pre>
    </div>
    
    <div class="section">
        <h2>4. Laravel Session Config</h2>
        <pre>
Driver: <?php echo config('session.driver'); ?>
Path: <?php echo config('session.path'); ?>
Domain: <?php echo config('session.domain') ?: 'not set'; ?>
Secure: <?php echo config('session.secure') ? 'YES' : 'NO'; ?>
HttpOnly: <?php echo config('session.http_only') ? 'YES' : 'NO'; ?>
Same Site: <?php echo config('session.same_site') ?: 'not set'; ?>
        </pre>
    </div>
    
    <div class="section">
        <h2>5. Test API Call</h2>
        <button onclick="testAPI()">Test /business/api/user</button>
        <pre id="api-result"></pre>
    </div>
    
    <div class="section">
        <h2>6. React Session Test</h2>
        <button onclick="testReactSession()">Test React Session</button>
        <pre id="react-result"></pre>
    </div>
    
    <div class="section">
        <h2>7. Problem Analysis</h2>
        <div id="analysis"></div>
    </div>
    
    <script>
        // Show JavaScript cookies
        document.getElementById('js-cookies').innerHTML = '<pre>JavaScript Cookies:\n' + document.cookie + '</pre>';
        
        // Test API
        async function testAPI() {
            const result = document.getElementById('api-result');
            result.textContent = 'Testing...';
            
            try {
                const response = await fetch('/business/api/user', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                result.innerHTML = `
Status: ${response.status}
Response: ${JSON.stringify(data, null, 2)}
Headers:
${Array.from(response.headers.entries()).map(([k,v]) => `  ${k}: ${v}`).join('\n')}`;
            } catch (error) {
                result.innerHTML = `<span class="error">Error: ${error.message}</span>`;
            }
        }
        
        // Test React Session
        async function testReactSession() {
            const result = document.getElementById('react-result');
            result.textContent = 'Creating React environment...';
            
            // Simulate React environment
            localStorage.setItem('portal_user', JSON.stringify({
                id: <?php echo $user->id; ?>,
                name: '<?php echo $user->name; ?>',
                email: '<?php echo $user->email; ?>'
            }));
            
            // Test session check
            try {
                const response = await fetch('/business/api/session-debug-open', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '<?php echo csrf_token(); ?>'
                    }
                });
                
                const data = await response.json();
                result.innerHTML = `
React Session Test:
${JSON.stringify(data, null, 2)}`;
            } catch (error) {
                result.innerHTML = `<span class="error">Error: ${error.message}</span>`;
            }
        }
        
        // Analysis
        function analyzeProblems() {
            const analysis = document.getElementById('analysis');
            const problems = [];
            
            // Check session cookie
            const sessionCookie = document.cookie.match(/<?php echo $sessionName; ?>=([^;]+)/);
            if (!sessionCookie) {
                problems.push('❌ Session cookie not found in JavaScript');
            } else {
                problems.push('✅ Session cookie found: ' + sessionCookie[1].substring(0, 20) + '...');
            }
            
            // Check CSRF
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (!csrfMeta) {
                problems.push('❌ CSRF meta tag not found');
            } else {
                problems.push('✅ CSRF token present');
            }
            
            // Check localStorage
            const storedUser = localStorage.getItem('portal_user');
            if (!storedUser) {
                problems.push('⚠️ No user in localStorage');
            } else {
                problems.push('✅ User in localStorage');
            }
            
            analysis.innerHTML = '<h3>Problems Found:</h3><ul>' + 
                problems.map(p => `<li>${p}</li>`).join('') + 
                '</ul>';
            
            // Root cause
            analysis.innerHTML += `
<h3>Root Cause Analysis:</h3>
<p>The main issue is likely one of:</p>
<ol>
    <li><strong>HttpOnly Cookie:</strong> If the session cookie is HttpOnly, JavaScript cannot read it, but it should still be sent with requests.</li>
    <li><strong>Path Mismatch:</strong> The session cookie path might not match the API endpoints.</li>
    <li><strong>CORS/SameSite:</strong> Browser security policies might be blocking the cookie.</li>
    <li><strong>Session Driver:</strong> The session might not be persisting between requests.</li>
</ol>`;
        }
        
        // Run analysis
        analyzeProblems();
    </script>
</body>
</html>