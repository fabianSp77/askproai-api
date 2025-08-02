<?php
// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PortalUser;

// Create kernel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create request
$request = Request::create('/direct-login', 'GET');

// Handle request
$response = $kernel->handle($request);

// Direct login without going through routes
Auth::guard('portal')->logout();
session()->invalidate();
session()->regenerate();

// Find demo user
$user = PortalUser::withoutGlobalScopes()->where('email', 'demo@askproai.de')->first();

if (!$user) {
    die('Demo user not found!');
}

// Login user
Auth::guard('portal')->login($user);

// Set session data
session(['portal_user_id' => $user->id]);
$portalKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
session([$portalKey => $user->id]);

// Save session
session()->save();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Login Test</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .test { background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 10px 0; }
        button { padding: 10px 20px; margin: 5px; background: #2196f3; color: white; border: none; cursor: pointer; border-radius: 4px; }
        button:hover { background: #1976d2; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        .success { color: #4caf50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Direct Login Test</h1>
    
    <div class="info">
        <h3>Login Status:</h3>
        <p>User ID: <?php echo $user->id; ?></p>
        <p>Email: <?php echo $user->email; ?></p>
        <p>Auth Check: <?php echo Auth::guard('portal')->check() ? 'YES' : 'NO'; ?></p>
        <p>Session ID: <?php echo session()->getId(); ?></p>
        <p>Portal User ID in Session: <?php echo session('portal_user_id'); ?></p>
    </div>
    
    <div class="test">
        <h3>Test Routes:</h3>
        <button onclick="testRoute('/business/dashboard')">Test Dashboard</button>
        <button onclick="testRoute('/business/calls')">Test Calls</button>
        <button onclick="testRoute('/business/api/user')">Test API User</button>
        <button onclick="checkPersistence()">Check Persistence</button>
        
        <pre id="result">Click a button to test...</pre>
    </div>
    
    <script>
    async function testRoute(route) {
        const result = document.getElementById('result');
        result.textContent = 'Testing ' + route + '...';
        
        try {
            const response = await fetch(route, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
                redirect: 'manual'
            });
            
            result.textContent = 'Route: ' + route + '\n';
            result.textContent += 'Status: ' + response.status + '\n';
            result.textContent += 'Type: ' + response.type + '\n';
            
            if (response.status === 200) {
                result.innerHTML += '<span class="success">✅ Access granted!</span>\n';
                
                if (route.includes('/api/')) {
                    try {
                        const data = await response.json();
                        result.textContent += '\nData: ' + JSON.stringify(data, null, 2);
                    } catch (e) {
                        // Not JSON
                    }
                }
            } else if (response.status === 302 || response.type === 'opaqueredirect') {
                result.innerHTML += '<span class="error">❌ Redirected (not authenticated)</span>';
            } else if (response.status === 401) {
                result.innerHTML += '<span class="error">❌ Unauthorized</span>';
            } else {
                result.innerHTML += '<span class="error">❌ Error: ' + response.status + '</span>';
            }
        } catch (error) {
            result.innerHTML = '<span class="error">Error: ' + error.message + '</span>';
        }
    }
    
    async function checkPersistence() {
        const result = document.getElementById('result');
        result.textContent = 'Checking session persistence with new request...';
        
        try {
            const response = await fetch('/session-diagnosis.php?action=check-persistence');
            const text = await response.text();
            
            result.textContent = text;
            
            if (text.includes('Guard Check: YES')) {
                result.innerHTML += '\n\n<span class="success">✅ Session persisted!</span>';
            } else {
                result.innerHTML += '\n\n<span class="error">❌ Session lost!</span>';
            }
        } catch (error) {
            result.innerHTML = '<span class="error">Error: ' + error.message + '</span>';
        }
    }
    </script>
</body>
</html>
<?php

// Terminate kernel
$kernel->terminate($request, $response);