<?php
// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create request
$request = Request::capture();

// Handle through kernel to start session
$response = $kernel->handle($request);

// Start session if not started
if (!session()->isStarted()) {
    session()->start();
}

// Set test data
session(['force_test' => 'Cookie should be set now']);
session(['timestamp' => time()]);

// Get session info
$sessionId = session()->getId();
$sessionName = session()->getName();

// Create HTML response
$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Force Session Cookie Test</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .warning { background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        button { padding: 10px 20px; background: #2196f3; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Force Session Cookie Test</h1>
    
    <div class="info">
        <h3>Session Information:</h3>
        <pre>
Session ID: $sessionId
Session Name: $sessionName
Session Driver: {config('session.driver')}
Cookie Should Be Set: YES
        </pre>
    </div>
    
    <div class="warning">
        <h3>Expected Cookie:</h3>
        <p>Name: <code>$sessionName</code></p>
        <p>Value: <code>$sessionId</code> (unencrypted)</p>
    </div>
    
    <button onclick="checkCookie()">Check Cookie</button>
    <button onclick="testPersistence()">Test Persistence</button>
    
    <pre id="result">Click a button to test...</pre>
    
    <script>
    function checkCookie() {
        const result = document.getElementById('result');
        result.textContent = '=== Browser Cookies ===\\n\\n';
        
        const cookies = document.cookie.split(';');
        let found = false;
        
        cookies.forEach(cookie => {
            const [name, value] = cookie.trim().split('=');
            if (name) {
                result.textContent += name + ': ' + (value ? value.substring(0, 50) + '...' : '(empty)') + '\\n';
                if (name === '$sessionName') {
                    found = true;
                }
            }
        });
        
        result.textContent += '\\n=== Analysis ===\\n';
        result.textContent += 'Session Cookie Found: ' + (found ? 'YES ✅' : 'NO ❌');
    }
    
    async function testPersistence() {
        const result = document.getElementById('result');
        result.textContent = 'Testing persistence...';
        
        try {
            const response = await fetch('/test-session-get.php', {
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            result.textContent = JSON.stringify(data, null, 2);
            
            if (data.all_data && data.all_data.force_test) {
                result.textContent += '\\n\\n✅ Session persisted! Value: ' + data.all_data.force_test;
            } else {
                result.textContent += '\\n\\n❌ Session not persisted!';
            }
        } catch (error) {
            result.textContent = 'Error: ' + error.message;
        }
    }
    </script>
</body>
</html>
HTML;

// Create response with HTML
$htmlResponse = response($html, 200);

// Manually add session cookie if not already queued
if (!Cookie::hasQueued($sessionName)) {
    Cookie::queue(
        $sessionName,
        $sessionId,
        config('session.lifetime'),
        config('session.path'),
        config('session.domain'),
        config('session.secure'),
        config('session.http_only'),
        false, // raw
        config('session.same_site')
    );
}

// Send response
$htmlResponse->send();

// Terminate
$kernel->terminate($request, $htmlResponse);
exit;