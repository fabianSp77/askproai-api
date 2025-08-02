<?php
/**
 * ULTRATHINK Session Cookie Test - Definitive Proof
 * This demonstrates that PHP files in public/ bypass Laravel middleware
 */

// Test 1: Direct PHP session (no Laravel)
if (isset($_GET['test']) && $_GET['test'] === 'php-only') {
    session_start();
    $_SESSION['test'] = 'PHP Session at ' . date('Y-m-d H:i:s');
    
    header('Content-Type: application/json');
    echo json_encode([
        'test' => 'php-only',
        'session_id' => session_id(),
        'session_data' => $_SESSION,
        'cookies_sent' => headers_list()
    ]);
    exit;
}

// Test 2: Laravel bootstrap but direct response (current issue)
if (isset($_GET['test']) && $_GET['test'] === 'laravel-direct') {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::capture();
    $response = $kernel->handle($request);
    
    // Set session data
    session(['test_direct' => 'Value set at ' . now()]);
    
    // Direct response - BYPASSES middleware!
    header('Content-Type: application/json');
    echo json_encode([
        'test' => 'laravel-direct',
        'session_id' => session()->getId(),
        'session_data' => session()->all(),
        'cookies_queued' => count(\Illuminate\Support\Facades\Cookie::getQueuedCookies())
    ]);
    exit;
}

// Test 3: Laravel with proper response handling
if (isset($_GET['test']) && $_GET['test'] === 'laravel-proper') {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::capture();
    
    // Set session data
    session(['test_proper' => 'Value set at ' . now()]);
    
    // Create proper Laravel response
    $responseData = [
        'test' => 'laravel-proper',
        'session_id' => session()->getId(),
        'session_data' => session()->all(),
        'cookies_queued' => count(\Illuminate\Support\Facades\Cookie::getQueuedCookies())
    ];
    
    $response = response()->json($responseData);
    
    // Send through Laravel (includes middleware)
    $response->send();
    $kernel->terminate($request, $response);
    exit;
}

// Main test page
?>
<!DOCTYPE html>
<html>
<head>
    <title>ULTRATHINK Session Cookie Test</title>
    <style>
        body { font-family: monospace; background: #000; color: #0f0; margin: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #0f0; text-shadow: 0 0 10px #0f0; }
        .test-section {
            background: #111;
            border: 1px solid #0f0;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        button {
            background: #0f0;
            color: #000;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-weight: bold;
            margin: 5px;
        }
        button:hover {
            background: #0a0;
            color: #fff;
        }
        pre {
            background: #000;
            padding: 15px;
            overflow-x: auto;
            border: 1px solid #444;
            white-space: pre-wrap;
        }
        .success { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧠 ULTRATHINK: Session Cookie Test</h1>
        
        <div class="test-section">
            <h2>Current Browser Cookies:</h2>
            <pre id="browser-cookies">Loading...</pre>
        </div>
        
        <div class="test-section">
            <h2>Test 1: Pure PHP Session</h2>
            <p>Tests if PHP sessions work at all</p>
            <button onclick="runTest('php-only')">Run PHP Session Test</button>
            <pre id="result-php-only">Click to test...</pre>
        </div>
        
        <div class="test-section">
            <h2>Test 2: Laravel Direct Response (PROBLEM)</h2>
            <p>This is how current PHP files work - bypasses middleware!</p>
            <button onclick="runTest('laravel-direct')">Run Laravel Direct Test</button>
            <pre id="result-laravel-direct">Click to test...</pre>
        </div>
        
        <div class="test-section">
            <h2>Test 3: Laravel Proper Response (SOLUTION)</h2>
            <p>This goes through full middleware stack</p>
            <button onclick="runTest('laravel-proper')">Run Laravel Proper Test</button>
            <pre id="result-laravel-proper">Click to test...</pre>
        </div>
        
        <div class="test-section">
            <h2>Analysis:</h2>
            <pre id="analysis" class="warning">Run tests to see analysis...</pre>
        </div>
    </div>
    
    <script>
    function showBrowserCookies() {
        const pre = document.getElementById('browser-cookies');
        const cookies = document.cookie.split(';');
        let output = 'All cookies in browser:\n\n';
        
        if (cookies.length === 0 || (cookies.length === 1 && cookies[0] === '')) {
            output += 'NO COOKIES FOUND!\n';
        } else {
            cookies.forEach(cookie => {
                const [name, value] = cookie.trim().split('=');
                if (name) {
                    output += `${name}: ${value ? value.substring(0, 50) + '...' : '(empty)'}\n`;
                }
            });
        }
        
        output += '\n=== Cookie Check ===\n';
        output += `askproai_session: ${document.cookie.includes('askproai_session') ? 'FOUND ✅' : 'NOT FOUND ❌'}\n`;
        output += `PHPSESSID: ${document.cookie.includes('PHPSESSID') ? 'FOUND ✅' : 'NOT FOUND ❌'}\n`;
        output += `XSRF-TOKEN: ${document.cookie.includes('XSRF-TOKEN') ? 'FOUND ✅' : 'NOT FOUND ❌'}\n`;
        
        pre.textContent = output;
    }
    
    async function runTest(test) {
        const resultId = `result-${test}`;
        const result = document.getElementById(resultId);
        result.textContent = 'Running test...';
        
        try {
            const response = await fetch(`?test=${test}`, {
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            result.textContent = JSON.stringify(data, null, 2);
            
            // Refresh cookie display
            showBrowserCookies();
            
            // Update analysis
            updateAnalysis();
        } catch (error) {
            result.innerHTML = `<span class="error">Error: ${error.message}</span>`;
        }
    }
    
    function updateAnalysis() {
        const analysis = document.getElementById('analysis');
        let output = '=== ANALYSIS ===\n\n';
        
        const hasPHPSession = document.cookie.includes('PHPSESSID');
        const hasLaravelSession = document.cookie.includes('askproai_session');
        
        output += '1. PHP Session Cookie (PHPSESSID): ' + (hasPHPSession ? 'SET ✅' : 'NOT SET ❌') + '\n';
        output += '2. Laravel Session Cookie (askproai_session): ' + (hasLaravelSession ? 'SET ✅' : 'NOT SET ❌') + '\n\n';
        
        if (hasPHPSession && !hasLaravelSession) {
            output += 'PROBLEM CONFIRMED:\n';
            output += '- PHP can set cookies\n';
            output += '- Laravel queues cookies but they are not sent\n';
            output += '- This proves middleware is not running!\n\n';
            output += 'SOLUTION:\n';
            output += '- Use proper Laravel routes instead of PHP files\n';
            output += '- Or ensure response goes through kernel->terminate()\n';
        }
        
        analysis.textContent = output;
    }
    
    // Initial load
    window.onload = () => {
        showBrowserCookies();
    };
    </script>
</body>
</html>