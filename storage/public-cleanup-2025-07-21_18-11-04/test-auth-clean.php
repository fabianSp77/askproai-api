<?php
/**
 * Clean Authentication Test
 * Tests authentication without any JavaScript hacks
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Clean Auth Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 15px 0; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin: 5px; font-size: 16px; }
        button:hover { background: #0056b3; }
        h2 { color: #333; margin-top: 30px; }
        .error-box { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .success-box { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Clean Authentication Test</h1>
        <p>Testing Laravel authentication without any JavaScript bypasses.</p>
        
        <div class="info">
            <h2>📊 Session Status</h2>
            <?php
            $sessionId = Session::getId();
            $sessionDriver = config('session.driver');
            $sessionCookie = config('session.cookie');
            
            echo "<p><strong>Session ID:</strong> <code>$sessionId</code></p>";
            echo "<p><strong>Session Driver:</strong> <code>$sessionDriver</code></p>";
            echo "<p><strong>Cookie Name:</strong> <code>$sessionCookie</code></p>";
            ?>
        </div>
        
        <div class="info">
            <h2>🔐 Authentication Guards</h2>
            <?php
            $guards = ['web', 'portal', 'customer'];
            foreach ($guards as $guard) {
                $isAuth = Auth::guard($guard)->check();
                $user = Auth::guard($guard)->user();
                echo "<h3>Guard: <code>$guard</code></h3>";
                if ($isAuth && $user) {
                    echo "<div class='success-box'>";
                    echo "<p class='success'>✅ Authenticated</p>";
                    echo "<p>User: " . ($user->email ?? 'N/A') . "</p>";
                    echo "<p>ID: " . ($user->id ?? 'N/A') . "</p>";
                    echo "</div>";
                } else {
                    echo "<div class='error-box'>";
                    echo "<p class='error'>❌ Not authenticated</p>";
                    echo "</div>";
                }
            }
            ?>
        </div>
        
        <div class="info">
            <h2>🧹 JavaScript Status</h2>
            <p id="js-status">Checking for auth bypass scripts...</p>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <button onclick="location.reload()">🔄 Reload Page</button>
            <button onclick="window.location.href='/admin/login'">👤 Admin Login</button>
            <button onclick="window.location.href='/business/login'">💼 Business Login</button>
            <button onclick="clearAll()">🗑️ Clear All Sessions</button>
        </div>
    </div>
    
    <script>
    // Check if any auth bypass scripts are active
    function checkAuthBypass() {
        const status = document.getElementById('js-status');
        const issues = [];
        
        // Check for auth override in window
        if (window.__AUTH_USER__ || window.DEMO_MODE) {
            issues.push('Auth override variables detected in window');
        }
        
        // Check localStorage
        if (localStorage.getItem('portal_auth') || localStorage.getItem('auth_override')) {
            issues.push('Auth override in localStorage');
        }
        
        // Check if fetch is overridden
        if (window.fetch.toString().includes('mock') || window.fetch.toString().includes('demo')) {
            issues.push('Fetch API appears to be overridden');
        }
        
        if (issues.length > 0) {
            status.innerHTML = '<div class="error-box">⚠️ Auth bypass detected:<br>' + issues.join('<br>') + '</div>';
        } else {
            status.innerHTML = '<div class="success-box">✅ No auth bypass scripts detected - Clean authentication!</div>';
        }
    }
    
    function clearAll() {
        // Clear localStorage
        localStorage.clear();
        // Clear all cookies
        document.cookie.split(";").forEach(function(c) { 
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
        });
        alert('Cleared all client-side data. Reloading...');
        location.reload();
    }
    
    // Run check on load
    checkAuthBypass();
    </script>
</body>
</html>