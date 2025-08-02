<?php
/**
 * ULTRATHINK Session Cookie Debug
 * Comprehensive analysis of why session cookie is not being set
 */

// Method 1: Raw PHP Session Test
if (isset($_GET['test']) && $_GET['test'] === 'raw-php') {
    session_name('test_php_session');
    session_start();
    $_SESSION['test'] = 'Raw PHP Session Works';
    
    header('Content-Type: text/plain');
    echo "=== RAW PHP SESSION TEST ===\n";
    echo "Session ID: " . session_id() . "\n";
    echo "Session Name: " . session_name() . "\n";
    echo "Headers to be sent:\n";
    foreach (headers_list() as $header) {
        echo "  " . $header . "\n";
    }
    echo "\nSession data: " . print_r($_SESSION, true);
    exit;
}

// Method 2: Laravel with Manual Cookie Setting
if (isset($_GET['test']) && $_GET['test'] === 'manual-cookie') {
    // Bootstrap Laravel
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::capture();
    $response = $kernel->handle($request);
    
    // Manually set a test cookie
    setcookie('manual_test_cookie', 'This_should_work', time() + 3600, '/', '', true, true);
    
    header('Content-Type: text/plain');
    echo "=== MANUAL COOKIE TEST ===\n";
    echo "Cookie set via setcookie(): manual_test_cookie\n";
    echo "This should appear in browser if cookies work at all\n";
    exit;
}

// Method 3: Debug Laravel Response Headers
if (isset($_GET['test']) && $_GET['test'] === 'debug-headers') {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::capture();
    
    // Create a test response
    $response = new Illuminate\Http\Response('Test Response');
    
    // Add session cookie manually
    $sessionCookie = new Symfony\Component\HttpFoundation\Cookie(
        'debug_session_cookie',
        'test_value_123',
        time() + 3600,
        '/',
        null,
        true,
        true,
        false,
        'lax'
    );
    
    $response->headers->setCookie($sessionCookie);
    
    header('Content-Type: text/plain');
    echo "=== DEBUG RESPONSE HEADERS ===\n";
    echo "Response cookies before send:\n";
    foreach ($response->headers->getCookies() as $cookie) {
        echo "  Cookie: " . $cookie->getName() . " = " . $cookie->getValue() . "\n";
    }
    
    echo "\nResponse headers:\n";
    foreach ($response->headers->all() as $name => $values) {
        foreach ($values as $value) {
            echo "  $name: $value\n";
        }
    }
    
    // Send the response
    $response->send();
    exit;
}

// Method 4: Check PHP Configuration
if (isset($_GET['test']) && $_GET['test'] === 'php-config') {
    header('Content-Type: text/plain');
    echo "=== PHP SESSION CONFIGURATION ===\n";
    echo "session.use_cookies: " . ini_get('session.use_cookies') . "\n";
    echo "session.use_only_cookies: " . ini_get('session.use_only_cookies') . "\n";
    echo "session.use_trans_sid: " . ini_get('session.use_trans_sid') . "\n";
    echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "\n";
    echo "session.cookie_path: " . ini_get('session.cookie_path') . "\n";
    echo "session.cookie_domain: " . ini_get('session.cookie_domain') . "\n";
    echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
    echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
    echo "session.cookie_samesite: " . ini_get('session.cookie_samesite') . "\n";
    echo "\nOther settings:\n";
    echo "output_buffering: " . ini_get('output_buffering') . "\n";
    echo "headers_sent: " . (headers_sent() ? 'YES' : 'NO') . "\n";
    exit;
}

// Method 5: Test Laravel Session Through Proper Route
if (isset($_GET['test']) && $_GET['test'] === 'laravel-route') {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Create a proper request that goes through all middleware
    $request = Illuminate\Http\Request::create('/test-session-route', 'GET');
    
    // Copy cookies from actual request
    foreach ($_COOKIE as $name => $value) {
        $request->cookies->set($name, $value);
    }
    
    // Handle the request
    $response = $kernel->handle($request);
    
    // Add test content
    $content = "=== LARAVEL ROUTE TEST ===\n";
    $content .= "Session ID: " . session()->getId() . "\n";
    $content .= "Session Driver: " . config('session.driver') . "\n";
    $content .= "Has Session: " . ($request->hasSession() ? 'YES' : 'NO') . "\n";
    $content .= "Session Started: " . (session()->isStarted() ? 'YES' : 'NO') . "\n";
    
    // Set test data
    session(['route_test' => 'This is from proper route']);
    
    $content .= "\nCookies queued:\n";
    $cookies = Illuminate\Support\Facades\Cookie::getQueuedCookies();
    foreach ($cookies as $cookie) {
        $content .= "  " . $cookie->getName() . "\n";
    }
    
    $response->setContent($content);
    $response->headers->set('Content-Type', 'text/plain');
    
    // Send response properly
    $response->send();
    
    // Terminate
    $kernel->terminate($request, $response);
    exit;
}

// Main test interface
?>
<!DOCTYPE html>
<html>
<head>
    <title>ULTRATHINK Session Debug</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #1a1a1a; color: #0f0; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #0f0; text-shadow: 0 0 10px #0f0; }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .test-card {
            background: #0a0a0a;
            border: 1px solid #0f0;
            padding: 20px;
            border-radius: 8px;
        }
        button {
            background: #0f0;
            color: #000;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-family: monospace;
            font-weight: bold;
            margin: 5px;
            width: 100%;
        }
        button:hover {
            background: #0a0;
            color: #fff;
        }
        pre {
            background: #000;
            padding: 15px;
            overflow-x: auto;
            border: 1px solid #0f0;
            font-size: 12px;
            white-space: pre-wrap;
        }
        .critical {
            color: #f00;
            font-weight: bold;
        }
        .success {
            color: #0f0;
            font-weight: bold;
        }
        .warning {
            color: #ff0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧠 ULTRATHINK: Session Cookie Debug</h1>
        
        <div class="test-grid">
            <div class="test-card">
                <h3>1. Raw PHP Session</h3>
                <p>Test if PHP sessions work at all</p>
                <button onclick="runTest('raw-php')">Test Raw PHP</button>
            </div>
            
            <div class="test-card">
                <h3>2. Manual Cookie</h3>
                <p>Test if ANY cookie can be set</p>
                <button onclick="runTest('manual-cookie')">Test Manual Cookie</button>
            </div>
            
            <div class="test-card">
                <h3>3. Debug Headers</h3>
                <p>Check response headers</p>
                <button onclick="runTest('debug-headers')">Debug Headers</button>
            </div>
            
            <div class="test-card">
                <h3>4. PHP Config</h3>
                <p>Check PHP session settings</p>
                <button onclick="runTest('php-config')">Check Config</button>
            </div>
            
            <div class="test-card">
                <h3>5. Laravel Route</h3>
                <p>Test through proper routing</p>
                <button onclick="runTest('laravel-route')">Test Route</button>
            </div>
            
            <div class="test-card">
                <h3>6. Browser Cookies</h3>
                <p>What cookies are in browser?</p>
                <button onclick="showCookies()">Show Cookies</button>
            </div>
        </div>
        
        <h2>Results:</h2>
        <pre id="results">Run tests to see results...</pre>
        
        <h2>Analysis:</h2>
        <pre id="analysis" class="warning">Waiting for test results...</pre>
    </div>
    
    <script>
    async function runTest(test) {
        const results = document.getElementById('results');
        results.textContent = `Running ${test} test...`;
        
        try {
            const response = await fetch(`?test=${test}`, {
                credentials: 'same-origin'
            });
            const text = await response.text();
            
            results.textContent = text;
            
            // Analyze results
            analyzeResults(test, text);
        } catch (error) {
            results.textContent = 'ERROR: ' + error.message;
        }
    }
    
    function showCookies() {
        const results = document.getElementById('results');
        results.textContent = '=== BROWSER COOKIES ===\n\n';
        
        const cookies = document.cookie.split(';');
        if (cookies.length === 0 || (cookies.length === 1 && cookies[0] === '')) {
            results.textContent += 'NO COOKIES FOUND!\n';
        } else {
            cookies.forEach(cookie => {
                const [name, value] = cookie.trim().split('=');
                if (name) {
                    results.textContent += `${name}: ${value ? value.substring(0, 50) + '...' : '(empty)'}\n`;
                }
            });
        }
        
        // Check specific cookies
        results.textContent += '\n=== COOKIE ANALYSIS ===\n';
        results.textContent += `askproai_session: ${document.cookie.includes('askproai_session') ? 'FOUND' : 'NOT FOUND'}\n`;
        results.textContent += `XSRF-TOKEN: ${document.cookie.includes('XSRF-TOKEN') ? 'FOUND' : 'NOT FOUND'}\n`;
        results.textContent += `manual_test_cookie: ${document.cookie.includes('manual_test_cookie') ? 'FOUND' : 'NOT FOUND'}\n`;
        results.textContent += `test_php_session: ${document.cookie.includes('test_php_session') ? 'FOUND' : 'NOT FOUND'}\n`;
        results.textContent += `debug_session_cookie: ${document.cookie.includes('debug_session_cookie') ? 'FOUND' : 'NOT FOUND'}\n`;
    }
    
    function analyzeResults(test, results) {
        const analysis = document.getElementById('analysis');
        let findings = '=== ANALYSIS ===\n\n';
        
        switch(test) {
            case 'raw-php':
                if (results.includes('Set-Cookie')) {
                    findings += '✅ PHP can set cookies\n';
                } else {
                    findings += '❌ PHP cannot set cookies - SERVER ISSUE!\n';
                }
                break;
                
            case 'manual-cookie':
                findings += 'Check browser cookies to see if manual_test_cookie was set\n';
                break;
                
            case 'debug-headers':
                if (results.includes('Cookie:')) {
                    findings += '✅ Laravel is trying to set cookies\n';
                } else {
                    findings += '❌ No cookies in response\n';
                }
                break;
                
            case 'php-config':
                if (results.includes('session.use_cookies: 1')) {
                    findings += '✅ PHP sessions configured to use cookies\n';
                } else {
                    findings += '❌ PHP sessions NOT using cookies!\n';
                }
                break;
                
            case 'laravel-route':
                if (results.includes('Cookies queued')) {
                    findings += '✅ Laravel queuing cookies\n';
                } else {
                    findings += '❌ Laravel not queuing cookies\n';
                }
                break;
        }
        
        analysis.textContent = findings;
    }
    </script>
</body>
</html>