<?php
/**
 * Web-based clean session test
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\User;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Clean Session Test</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Clean Session Test</h1>
        <p>Testing Laravel sessions with minimal middleware configuration.</p>
        
        <div class="info">
            <h2>📊 Current Configuration</h2>
            <?php
            $sessionId = Session::getId();
            $sessionDriver = config('session.driver');
            $sessionCookie = config('session.cookie');
            $cookieSecure = config('session.secure');
            
            echo "<p><strong>Session ID:</strong> <code>$sessionId</code></p>";
            echo "<p><strong>Session Driver:</strong> <code>$sessionDriver</code></p>";
            echo "<p><strong>Cookie Name:</strong> <code>$sessionCookie</code></p>";
            echo "<p><strong>Cookie Secure:</strong> <code>" . ($cookieSecure ? 'true' : 'false') . "</code></p>";
            ?>
        </div>
        
        <div class="info">
            <h2>🔍 Session Persistence Test</h2>
            <?php
            $testKey = 'page_loads';
            $pageLoads = Session::get($testKey, 0);
            $pageLoads++;
            Session::put($testKey, $pageLoads);
            Session::put('last_visit', date('Y-m-d H:i:s'));
            Session::save();
            
            echo "<p><strong>Page loads in this session:</strong> <span class='" . ($pageLoads > 1 ? "success" : "warning") . "'>$pageLoads</span></p>";
            
            if ($pageLoads > 1) {
                echo "<p class='success'>✅ Session is persisting between requests!</p>";
                echo "<p>Last visit: " . Session::get('last_visit') . "</p>";
            } else {
                echo "<p class='warning'>⚠️ First visit or session not persisting</p>";
            }
            ?>
        </div>
        
        <div class="info">
            <h2>🔐 Authentication Test</h2>
            <?php
            if (Auth::check()) {
                $user = Auth::user();
                echo "<p class='success'>✅ Authenticated as: <strong>" . $user->email . "</strong></p>";
                echo "<p>User ID: " . $user->id . "</p>";
                echo "<p>Company ID: " . $user->company_id . "</p>";
                ?>
                <form method="POST" action="/logout" style="display: inline;">
                    <?php echo csrf_field(); ?>
                    <button type="submit">Logout</button>
                </form>
                <?php
            } else {
                echo "<p class='error'>❌ Not authenticated</p>";
                ?>
                <button onclick="testLogin()">Login as Demo User</button>
                <?php
            }
            ?>
        </div>
        
        <div class="info">
            <h2>🍪 Cookie Analysis</h2>
            <p><strong>Request Cookies:</strong></p>
            <ul>
                <?php
                foreach ($_COOKIE as $name => $value) {
                    $isSessionCookie = ($name === $sessionCookie);
                    echo "<li>" . ($isSessionCookie ? "<strong>" : "") . htmlspecialchars($name) . " = " . htmlspecialchars(substr($value, 0, 30)) . "..." . ($isSessionCookie ? "</strong>" : "") . "</li>";
                }
                ?>
            </ul>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <button onclick="location.reload()">🔄 Reload Page</button>
            <button onclick="window.location.href='/web-session-login.php'">📝 Go to Login Form</button>
            <button onclick="clearCookies()">🗑️ Clear Cookies</button>
        </div>
    </div>
    
    <script>
    function testLogin() {
        fetch('/web-session-login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: 'email=demo@askproai.de&password=demo&_token=<?php echo csrf_token(); ?>'
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
            } else {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Login error:', error);
            alert('Login failed: ' + error.message);
        });
    }
    
    function clearCookies() {
        document.cookie.split(";").forEach(function(c) { 
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
        });
        alert('Cookies cleared! Reloading...');
        location.reload();
    }
    </script>
</body>
</html>