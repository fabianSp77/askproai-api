<?php
// Test login with proper CSRF handling
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Business Portal - Test Login</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #f0fdf4; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #fef2f2; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { background: #f0f9ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        button { background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #2563eb; }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
    </style>
</head>
<body>
    <h1>Business Portal - Test Login</h1>
    
    <div class="info">
        <p><strong>Quick Actions:</strong></p>
        <button onclick="window.location.href='/business-portal-fix-login.php'" class="btn-success">
            ✅ Direct Login (Bypass Errors)
        </button>
        <button onclick="window.location.href='/business/login'" class="btn">
            📝 Go to Login Page
        </button>
        <button onclick="clearAndReload()" class="btn-danger">
            🗑️ Clear All Sessions
        </button>
    </div>
    
    <?php
    // Get current session info
    if (isset($_SESSION)) {
        echo '<div class="info">';
        echo '<h3>Current Session Info:</h3>';
        echo '<p>Session ID: ' . session_id() . '</p>';
        echo '<p>Session Name: ' . session_name() . '</p>';
        
        // Check if authenticated
        require_once __DIR__.'/../vendor/autoload.php';
        $app = require_once __DIR__.'/../bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        $request = Illuminate\Http\Request::capture();
        $response = $kernel->handle($request);
        
        if (auth()->guard('portal')->check()) {
            echo '<div class="success">';
            echo '<p>✅ Already authenticated as: ' . auth()->guard('portal')->user()->email . '</p>';
            echo '<p><a href="/business/dashboard">Go to Dashboard</a></p>';
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '<p>❌ Not authenticated</p>';
            echo '</div>';
        }
        
        $kernel->terminate($request, $response);
        echo '</div>';
    }
    ?>
    
    <h2>Test Results:</h2>
    <div id="results"></div>
    
    <script>
    function clearAndReload() {
        // Clear all cookies
        document.cookie.split(";").forEach(function(c) { 
            const eqPos = c.indexOf("=");
            const name = eqPos > -1 ? c.substr(0, eqPos).trim() : c.trim();
            ['', '.askproai.de', 'askproai.de', 'api.askproai.de'].forEach(domain => {
                document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;domain=" + domain;
                document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
            });
        });
        
        // Clear storage
        localStorage.clear();
        sessionStorage.clear();
        
        alert('All sessions cleared. Reloading...');
        window.location.reload();
    }
    
    function addResult(message, type = 'info') {
        const div = document.createElement('div');
        div.className = type;
        div.innerHTML = message;
        document.getElementById('results').appendChild(div);
    }
    
    // Check authentication status
    window.addEventListener('load', async () => {
        try {
            const response = await fetch('/business/api/auth/check', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.authenticated) {
                    addResult('✅ API confirms authentication as: ' + data.user.email, 'success');
                } else {
                    addResult('❌ API confirms not authenticated', 'error');
                }
            } else {
                addResult('❌ API auth check failed: ' + response.status, 'error');
            }
        } catch (error) {
            addResult('Error checking auth: ' + error.message, 'error');
        }
    });
    </script>
</body>
</html>