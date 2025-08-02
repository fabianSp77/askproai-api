<?php
/**
 * Debug session cookie issues - Web version
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Session;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Cookie Debug</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #1e1e1e; color: #d4d4d4; }
        .section { margin: 20px 0; padding: 15px; background: #2d2d2d; border-radius: 5px; }
        .success { color: #4ec9b0; }
        .error { color: #f44747; }
        .warning { color: #ffcc00; }
        .info { color: #569cd6; }
        h2 { color: #569cd6; margin-top: 0; }
        pre { margin: 0; white-space: pre-wrap; }
        code { color: #ce9178; }
    </style>
</head>
<body>
    <h1>🍪 SESSION COOKIE DEBUG</h1>
    
    <div class="section">
        <h2>1️⃣ REQUEST INFO</h2>
        <pre><?php
        echo "Host: <code>" . $_SERVER['HTTP_HOST'] . "</code>\n";
        echo "HTTPS: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? '<span class="success">YES</span>' : '<span class="error">NO</span>') . "\n";
        echo "Request URI: <code>" . $_SERVER['REQUEST_URI'] . "</code>\n";
        echo "HTTP_X_FORWARDED_PROTO: <code>" . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'not set') . "</code>\n";
        echo "HTTP_X_FORWARDED_FOR: <code>" . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'not set') . "</code>\n";
        echo "Request secure (Laravel): " . (request()->secure() ? '<span class="success">YES</span>' : '<span class="error">NO</span>') . "\n";
        echo "Scheme: <code>" . request()->getScheme() . "</code>\n";
        ?></pre>
    </div>
    
    <div class="section">
        <h2>2️⃣ SESSION CONFIGURATION</h2>
        <pre><?php
        $sessionConfig = config('session');
        foreach (['driver', 'cookie', 'domain', 'path', 'secure', 'http_only', 'same_site'] as $key) {
            $value = $sessionConfig[$key] ?? 'not set';
            $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
            
            // Highlight problematic settings
            if ($key === 'secure' && $value === true && !request()->secure()) {
                echo "$key: <span class='error'>$displayValue ⚠️ PROBLEM: Secure cookie on non-HTTPS!</span>\n";
            } elseif ($key === 'domain' && empty($value)) {
                echo "$key: <span class='warning'>$displayValue (empty - might cause issues)</span>\n";
            } else {
                echo "$key: <code>$displayValue</code>\n";
            }
        }
        ?></pre>
    </div>
    
    <div class="section">
        <h2>3️⃣ CURRENT SESSION</h2>
        <pre><?php
        $sessionId = Session::getId();
        echo "Session ID: <code>$sessionId</code>\n";
        echo "Session started: " . (Session::isStarted() ? '<span class="success">YES</span>' : '<span class="error">NO</span>') . "\n";
        
        // Save test data
        Session::put('test_key', 'test_value_' . time());
        Session::put('test_time', date('Y-m-d H:i:s'));
        Session::save();
        echo "Added test data to session\n";
        
        // Check if we can read it back
        $testValue = Session::get('test_key');
        echo "Can read test data: " . ($testValue ? '<span class="success">YES</span>' : '<span class="error">NO</span>') . "\n";
        ?></pre>
    </div>
    
    <div class="section">
        <h2>4️⃣ REQUEST COOKIES</h2>
        <pre><?php
        if (empty($_COOKIE)) {
            echo "<span class='error'>No cookies received!</span>\n";
        } else {
            foreach ($_COOKIE as $name => $value) {
                $isSessionCookie = ($name === config('session.cookie'));
                $prefix = $isSessionCookie ? '<span class="success">→</span> ' : '  ';
                echo $prefix . "$name = " . substr($value, 0, 40) . "...\n";
            }
        }
        ?></pre>
    </div>
    
    <div class="section">
        <h2>5️⃣ LARAVEL SESSION COOKIE</h2>
        <pre><?php
        $cookieName = config('session.cookie');
        $hasCookie = isset($_COOKIE[$cookieName]);
        echo "Cookie name: <code>$cookieName</code>\n";
        echo "Cookie exists in request: " . ($hasCookie ? '<span class="success">YES</span>' : '<span class="error">NO</span>') . "\n";
        if ($hasCookie) {
            echo "Cookie value: <code>" . substr($_COOKIE[$cookieName], 0, 40) . "...</code>\n";
            
            // Check if session ID matches cookie
            if ($_COOKIE[$cookieName] === $sessionId) {
                echo "Cookie matches current session: <span class='success'>YES</span>\n";
            } else {
                echo "Cookie matches current session: <span class='error'>NO - Session regenerated!</span>\n";
            }
        }
        ?></pre>
    </div>
    
    <div class="section">
        <h2>6️⃣ RESPONSE HEADERS</h2>
        <pre><?php
        $headers = headers_list();
        $foundSetCookie = false;
        foreach ($headers as $header) {
            if (stripos($header, 'set-cookie') !== false) {
                echo "<span class='info'>$header</span>\n";
                $foundSetCookie = true;
            }
        }
        if (!$foundSetCookie) {
            echo "<span class='warning'>No Set-Cookie headers found</span>\n";
        }
        ?></pre>
    </div>
    
    <div class="section">
        <h2>🔍 DIAGNOSIS</h2>
        <pre><?php
        $problems = [];
        
        if (!$hasCookie) {
            $problems[] = "<span class='error'>❌ Session cookie NOT found in request!</span>";
            $problems[] = "   This is why every request gets a new session.";
        } else {
            echo "<span class='success'>✅ Session cookie found in request</span>\n";
        }
        
        if (config('session.secure') && !request()->secure()) {
            $problems[] = "<span class='error'>❌ SECURE COOKIE ON NON-HTTPS CONNECTION!</span>";
            $problems[] = "   Browser won't send secure cookies over HTTP";
            $problems[] = "   Either use HTTPS or set SESSION_SECURE_COOKIE=false";
        }
        
        if (!config('session.domain')) {
            $problems[] = "<span class='warning'>⚠️  SESSION_DOMAIN not set</span>";
            $problems[] = "   Cookies might not persist across subdomains";
        }
        
        if ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '' === 'https' && !request()->secure()) {
            $problems[] = "<span class='warning'>⚠️  Behind HTTPS proxy but Laravel doesn't know</span>";
            $problems[] = "   TRUSTED_PROXIES might not be configured correctly";
        }
        
        if (empty($problems)) {
            echo "<span class='success'>✅ No obvious problems detected</span>\n";
        } else {
            foreach ($problems as $problem) {
                echo $problem . "\n";
            }
        }
        ?></pre>
    </div>
    
    <div class="section">
        <h2>💡 RECOMMENDATIONS</h2>
        <pre><?php
        if (config('session.secure') && !request()->secure()) {
            echo "1. <span class='error'>CRITICAL:</span> Disable secure cookies for HTTP:\n";
            echo "   In .env: <code>SESSION_SECURE_COOKIE=false</code>\n\n";
        }
        
        echo "2. Clear all browser cookies for this domain\n";
        echo "3. Check browser DevTools → Application → Cookies\n";
        echo "4. Look for cookie warnings in browser console\n";
        echo "5. Try in incognito/private mode\n";
        ?>
        
<span class='info'>Quick test:</span>
<a href="javascript:location.reload()" style="color: #569cd6;">Reload page</a> - Session ID should stay the same
<a href="/web-session-login.php" style="color: #569cd6;">Try Web Login</a> - Test authentication
        </pre>
    </div>
    
    <script>
    // Auto-refresh every 5 seconds
    setTimeout(() => {
        console.log('Session debug data:');
        console.log('Cookies:', document.cookie);
        console.log('Location:', window.location);
    }, 1000);
    </script>
</body>
</html>