<?php
/**
 * Final session test - Web accessible
 */

session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Test After Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .test-section { 
            background: #f5f5f5; 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        code { 
            background: #e9ecef; 
            padding: 2px 5px; 
            border-radius: 3px;
            font-family: monospace;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
        }
        button:hover { background: #0056b3; }
        .warning { 
            background: #fff3cd; 
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>🔍 Final Session Test After ULTRATHINK Fix</h1>
    
    <div class="test-section">
        <h2>📋 Current Configuration</h2>
        <?php
        require_once __DIR__ . '/../vendor/autoload.php';
        $app = require_once __DIR__ . '/../bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        $request = Illuminate\Http\Request::capture();
        $response = $kernel->handle($request);
        
        echo "<p><strong>Session Configuration:</strong></p>";
        echo "<ul>";
        echo "<li>Driver: <code>" . config('session.driver') . "</code></li>";
        echo "<li>Cookie Name: <code>" . config('session.cookie') . "</code></li>";
        echo "<li>Domain: <code>" . (config('session.domain') ?: 'not set') . "</code></li>";
        echo "<li>Secure: <code>" . (config('session.secure') ? 'true' : 'false') . "</code></li>";
        echo "<li>HttpOnly: <code>" . (config('session.http_only') ? 'true' : 'false') . "</code></li>";
        echo "<li>SameSite: <code>" . config('session.same_site') . "</code></li>";
        echo "</ul>";
        ?>
    </div>
    
    <div class="test-section">
        <h2>🔐 Authentication Test</h2>
        <?php
        use Illuminate\Support\Facades\Auth;
        use Illuminate\Support\Facades\Session;
        
        $sessionId = Session::getId();
        echo "<p>Laravel Session ID: <code>$sessionId</code></p>";
        echo "<p>PHP Session ID: <code>" . session_id() . "</code></p>";
        
        if (Auth::check()) {
            echo "<p class='success'>✅ You are logged in as: " . Auth::user()->email . "</p>";
            echo "<p>User ID: " . Auth::id() . "</p>";
            echo "<p>Company ID: " . Auth::user()->company_id . "</p>";
        } else {
            echo "<p class='error'>❌ You are NOT logged in</p>";
        }
        ?>
        
        <div style="margin-top: 20px;">
            <button onclick="testLogin()">Test Login</button>
            <button onclick="checkAuth()">Check Auth Status</button>
            <button onclick="testLogout()">Test Logout</button>
        </div>
    </div>
    
    <div class="test-section">
        <h2>🍪 Cookie Analysis</h2>
        <div id="cookie-info">
            <p>Checking cookies...</p>
        </div>
    </div>
    
    <div class="test-section">
        <h2>🧪 Test Results</h2>
        <div id="test-results"></div>
    </div>
    
    <div class="warning">
        <h3>⚠️ Important Notes:</h3>
        <ul>
            <li>SESSION_DOMAIN should be <code>.askproai.de</code> for subdomain support</li>
            <li>Clear all cookies before testing</li>
            <li>Make sure you're accessing via HTTPS</li>
            <li>Session driver changed to <code>database</code> for better reliability</li>
        </ul>
    </div>
    
    <script>
    function updateCookieInfo() {
        const cookieDiv = document.getElementById('cookie-info');
        const cookies = document.cookie.split(';').map(c => c.trim());
        
        let html = '<h4>Current Cookies:</h4><ul>';
        cookies.forEach(cookie => {
            const [name, value] = cookie.split('=');
            if (name) {
                const isSession = name.includes('session');
                html += `<li ${isSession ? 'style="color: #007bff; font-weight: bold;"' : ''}>`;
                html += `${name} = ${value ? value.substring(0, 20) + '...' : 'empty'}`;
                html += `</li>`;
            }
        });
        html += '</ul>';
        
        if (cookies.length === 0 || cookies[0] === '') {
            html = '<p class="error">No cookies found!</p>';
        }
        
        cookieDiv.innerHTML = html;
    }
    
    function addResult(message, isSuccess = true) {
        const results = document.getElementById('test-results');
        const time = new Date().toLocaleTimeString();
        const className = isSuccess ? 'success' : 'error';
        results.innerHTML += `<p>[${time}] <span class="${className}">${message}</span></p>`;
    }
    
    function testLogin() {
        addResult('Testing login...');
        
        // Try the correct API login endpoint
        fetch('/api/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                email: 'demo@askproai.de',
                password: 'demo'
            })
        })
        .then(response => {
            if (response.ok) {
                addResult('✅ Login successful! Reloading page...');
                setTimeout(() => location.reload(), 1000);
            } else {
                addResult('❌ Login failed: ' + response.status + ' ' + response.statusText, false);
                return response.text().then(text => {
                    console.log('Error response:', text);
                    addResult('Response: ' + text.substring(0, 200), false);
                });
            }
            return response.text();
        })
        .then(text => console.log('Response:', text))
        .catch(error => {
            addResult('❌ Error: ' + error.message, false);
        });
    }
    
    function checkAuth() {
        addResult('Checking authentication status...');
        location.reload();
    }
    
    function testLogout() {
        addResult('Testing logout...');
        
        fetch('/logout', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            credentials: 'same-origin'
        })
        .then(response => {
            addResult('✅ Logout executed! Reloading...');
            setTimeout(() => location.reload(), 1000);
        })
        .catch(error => {
            addResult('❌ Logout error: ' + error.message, false);
        });
    }
    
    // Initial cookie check
    updateCookieInfo();
    setInterval(updateCookieInfo, 2000);
    
    // Add CSRF token if missing
    if (!document.querySelector('meta[name="csrf-token"]')) {
        const meta = document.createElement('meta');
        meta.name = 'csrf-token';
        meta.content = '<?php echo csrf_token(); ?>';
        document.head.appendChild(meta);
    }
    </script>
</body>
</html>