<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/', 'GET');
$kernel->handle($request);

// Check authentication
$user = Auth::user();
if (!$user) {
    die("Not authenticated. Please login first.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Live Debug - Calls Page</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .debug-box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
        .iframe-container { width: 100%; height: 600px; border: 2px solid #ddd; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="debug-box">
            <h1>🔍 Live Debug - Calls Page</h1>
            <p>User: <?= $user->email ?> (Company: <?= $user->company_id ?>)</p>
        </div>

        <div class="debug-box">
            <h2>1. Backend Tests</h2>
            <button onclick="testBackend()">Run Backend Tests</button>
            <div id="backend-results"></div>
        </div>

        <div class="debug-box">
            <h2>2. Frontend Tests</h2>
            <button onclick="testFrontend()">Run Frontend Tests</button>
            <div id="frontend-results"></div>
        </div>

        <div class="debug-box">
            <h2>3. Live Page Monitor</h2>
            <button onclick="loadCallsPage()">Load Calls Page in iFrame</button>
            <button onclick="checkConsoleErrors()">Check Console Errors</button>
            <div id="console-errors"></div>
            <iframe id="calls-frame" class="iframe-container" style="display:none;"></iframe>
        </div>

        <div class="debug-box">
            <h2>4. Network Monitor</h2>
            <div id="network-log"></div>
        </div>
    </div>

    <script>
        const log = (target, message, type = 'info') => {
            const div = document.getElementById(target);
            const timestamp = new Date().toLocaleTimeString();
            const html = `<div class="${type}">[${timestamp}] ${message}</div>`;
            div.innerHTML += html;
        };

        async function testBackend() {
            const results = document.getElementById('backend-results');
            results.innerHTML = '<h3>Testing...</h3>';
            
            try {
                // Test 1: CallResource access
                const response1 = await fetch('/admin/calls', {
                    credentials: 'include',
                    headers: { 'Accept': 'text/html' }
                });
                log('backend-results', `Calls page status: ${response1.status}`, response1.ok ? 'success' : 'error');
                
                // Test 2: Livewire component
                const response2 = await fetch('/livewire/update', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '<?= csrf_token() ?>',
                        'X-Livewire': ''
                    },
                    body: JSON.stringify({
                        fingerprint: { 
                            id: 'test-component',
                            name: 'filament.admin.resources.call-resource.pages.list-calls'
                        },
                        serverMemo: { data: {} },
                        updates: []
                    })
                });
                log('backend-results', `Livewire update: ${response2.status}`, response2.ok ? 'success' : 'error');
                
                // Test 3: Check company context
                const response3 = await fetch('/test-company-context', { credentials: 'include' });
                const context = await response3.text();
                log('backend-results', `Company context: ${context}`, 'info');
                
            } catch (error) {
                log('backend-results', `Error: ${error.message}`, 'error');
            }
        }

        async function testFrontend() {
            const results = document.getElementById('frontend-results');
            results.innerHTML = '<h3>Checking frontend...</h3>';
            
            // Check Livewire
            if (typeof window.Livewire !== 'undefined') {
                log('frontend-results', '✅ Livewire is loaded', 'success');
                log('frontend-results', `Livewire version: ${window.Livewire.version || 'unknown'}`, 'info');
            } else {
                log('frontend-results', '❌ Livewire NOT loaded', 'error');
            }
            
            // Check Alpine
            if (typeof window.Alpine !== 'undefined') {
                log('frontend-results', '✅ Alpine.js is loaded', 'success');
            } else {
                log('frontend-results', '❌ Alpine.js NOT loaded', 'error');
            }
            
            // Check critical assets
            const assets = [
                '/vendor/livewire/livewire.js',
                '/js/filament/filament/app.js',
                '/css/filament/filament/app.css'
            ];
            
            for (const asset of assets) {
                try {
                    const response = await fetch(asset, { method: 'HEAD' });
                    log('frontend-results', `${asset}: ${response.status}`, response.ok ? 'success' : 'error');
                } catch (e) {
                    log('frontend-results', `${asset}: Failed`, 'error');
                }
            }
        }

        function loadCallsPage() {
            const frame = document.getElementById('calls-frame');
            frame.style.display = 'block';
            frame.src = '/admin/calls';
            
            // Monitor iframe
            frame.onload = () => {
                log('console-errors', 'iFrame loaded', 'success');
                
                try {
                    const iframeDoc = frame.contentDocument || frame.contentWindow.document;
                    const title = iframeDoc.title;
                    log('console-errors', `Page title: "${title}"`, 'info');
                    
                    // Try to access Livewire in iframe
                    const iframeLivewire = frame.contentWindow.Livewire;
                    if (iframeLivewire) {
                        log('console-errors', 'Livewire found in iframe', 'success');
                    } else {
                        log('console-errors', 'Livewire NOT found in iframe', 'error');
                    }
                } catch (e) {
                    log('console-errors', `Cannot access iframe content: ${e.message}`, 'warning');
                }
            };
        }

        function checkConsoleErrors() {
            // This would need browser extension access to truly capture console
            log('console-errors', 'Open browser console (F12) and check for red errors', 'warning');
            log('console-errors', 'Common errors to look for:', 'info');
            log('console-errors', '- Livewire is not defined', 'info');
            log('console-errors', '- Alpine is not defined', 'info');
            log('console-errors', '- Cannot read property of undefined', 'info');
            log('console-errors', '- Network errors (404, 500)', 'info');
        }

        // Auto-run some tests
        window.onload = () => {
            testFrontend();
        };
        
        // Monitor network requests
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            const url = args[0];
            log('network-log', `Fetch: ${url}`, 'info');
            
            return originalFetch.apply(this, args)
                .then(response => {
                    if (!response.ok) {
                        log('network-log', `Error ${response.status}: ${url}`, 'error');
                    }
                    return response;
                })
                .catch(error => {
                    log('network-log', `Failed: ${url} - ${error.message}`, 'error');
                    throw error;
                });
        };
    </script>
</body>
</html>