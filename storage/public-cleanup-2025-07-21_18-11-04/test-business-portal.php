<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Initialize the application
$request = Illuminate\Http\Request::capture();

// Login demo user automatically for testing
$user = \App\Models\PortalUser::withoutGlobalScopes()->where('email', 'demo@askproai.de')->first();
if ($user) {
    Auth::guard('portal')->login($user);
    session(['portal_user_id' => $user->id]);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Business Portal Test</title>
    <script>
        // Capture console errors
        window.addEventListener('error', function(e) {
            document.getElementById('errors').innerHTML += 
                '<div style="color: red;">Error: ' + e.message + ' at ' + e.filename + ':' + e.lineno + '</div>';
        });
    </script>
</head>
<body>
    <h1>Business Portal Debug</h1>
    
    <div id="status">
        <h2>Server Status:</h2>
        <ul>
            <li>Auth Status: <?php echo Auth::guard('portal')->check() ? 'Logged in as ' . Auth::guard('portal')->user()->email : 'Not logged in'; ?></li>
            <li>Session ID: <?php echo session()->getId(); ?></li>
            <li>CSRF Token: <?php echo csrf_token(); ?></li>
            <li>Vite Manifest: <?php echo file_exists(public_path('build/manifest.json')) ? 'Found' : 'Missing'; ?></li>
        </ul>
    </div>
    
    <div id="errors">
        <h2>JavaScript Errors:</h2>
    </div>
    
    <div>
        <h2>Test Actions:</h2>
        <a href="/business" target="_blank"><button>Open Business Portal</button></a>
        <a href="/business/login" target="_blank"><button>Open Login Page</button></a>
        <button onclick="testAPI()">Test API Call</button>
    </div>
    
    <div id="api-result"></div>
    
    <script>
        function testAPI() {
            fetch('/business/api/auth-debug-open')
                .then(r => r.json())
                .then(data => {
                    document.getElementById('api-result').innerHTML = 
                        '<h3>API Response:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(err => {
                    document.getElementById('api-result').innerHTML = 
                        '<h3>API Error:</h3><pre style="color: red;">' + err + '</pre>';
                });
        }
    </script>
</body>
</html>