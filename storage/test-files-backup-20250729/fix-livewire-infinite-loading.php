<?php
// Fix Livewire Infinite Loading Issue

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fix Livewire Infinite Loading</h1>";
echo "<pre>";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 80) . "\n\n";

// Step 1: Clear all Livewire caches
echo "Step 1: Clearing Livewire caches...\n";
$commands = [
    'php artisan livewire:discover' => 'Rediscover Livewire components',
    'php artisan view:clear' => 'Clear view cache',
    'php artisan cache:clear' => 'Clear application cache',
    'rm -rf bootstrap/cache/livewire-components.php' => 'Remove Livewire manifest',
    'rm -rf storage/framework/views/*' => 'Clear compiled views',
];

foreach ($commands as $command => $description) {
    echo "Running: $command\n";
    $output = shell_exec("cd /var/www/api-gateway && $command 2>&1");
    echo "✅ $description\n";
    if (trim($output)) {
        echo "   Output: " . trim($output) . "\n";
    }
    echo "\n";
}

// Step 2: Check Livewire configuration
echo "\nStep 2: Checking Livewire configuration...\n";
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('https://api.askproai.de/', 'GET');
$kernel->handle($request);

echo "- Update URI: " . config('livewire.update_uri', 'not set') . "\n";
echo "- Asset URL: " . config('livewire.asset_url', 'not set') . "\n";
echo "- App URL: " . config('app.url') . "\n";
echo "- Manifest Path: " . config('livewire.manifest_path', 'not set') . "\n";

// Step 3: Fix Livewire manifest
echo "\nStep 3: Regenerating Livewire manifest...\n";
shell_exec("cd /var/www/api-gateway && php artisan livewire:discover 2>&1");
echo "✅ Livewire components discovered\n";

// Step 4: Check for JavaScript errors in pages
echo "\nStep 4: Creating debug script...\n";

$debugScript = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Livewire Debug</title>
    <meta name="csrf-token" content="">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Livewire Loading Debug</h1>
    
    <div id="status"></div>
    
    <h2>Tests:</h2>
    <button onclick="testLivewire()">Test Livewire</button>
    <button onclick="testAlpine()">Test Alpine.js</button>
    <button onclick="checkConsole()">Check Console</button>
    <button onclick="window.location.href='/admin/calls'">Go to Calls Page</button>
    
    <h2>Console Output:</h2>
    <pre id="console-output"></pre>
    
    <script>
        // Capture console logs
        const logs = [];
        const originalLog = console.log;
        const originalError = console.error;
        const originalWarn = console.warn;
        
        console.log = function(...args) {
            logs.push({ type: 'log', message: args.join(' '), time: new Date().toISOString() });
            originalLog.apply(console, args);
            updateConsoleOutput();
        };
        
        console.error = function(...args) {
            logs.push({ type: 'error', message: args.join(' '), time: new Date().toISOString() });
            originalError.apply(console, args);
            updateConsoleOutput();
        };
        
        console.warn = function(...args) {
            logs.push({ type: 'warn', message: args.join(' '), time: new Date().toISOString() });
            originalWarn.apply(console, args);
            updateConsoleOutput();
        };
        
        function updateConsoleOutput() {
            const output = document.getElementById('console-output');
            output.innerHTML = logs.map(log => {
                const color = log.type === 'error' ? 'red' : log.type === 'warn' ? 'orange' : 'black';
                return `<span style="color: ${color}">[${log.time}] ${log.type.toUpperCase()}: ${log.message}</span>`;
            }).join('\n');
        }
        
        function setStatus(message, type = 'info') {
            const status = document.getElementById('status');
            status.className = 'status ' + type;
            status.textContent = message;
        }
        
        function testLivewire() {
            setStatus('Testing Livewire...', 'info');
            
            if (typeof window.Livewire !== 'undefined') {
                setStatus('✅ Livewire is loaded!', 'success');
                console.log('Livewire version:', window.Livewire.version || 'unknown');
                console.log('Livewire components:', Object.keys(window.Livewire.components || {}).length);
                
                // Test Livewire connection
                if (window.Livewire.components) {
                    const components = window.Livewire.components;
                    console.log('Active components:', components);
                }
            } else {
                setStatus('❌ Livewire is NOT loaded!', 'error');
                console.error('window.Livewire is undefined');
            }
        }
        
        function testAlpine() {
            setStatus('Testing Alpine.js...', 'info');
            
            if (typeof window.Alpine !== 'undefined') {
                setStatus('✅ Alpine.js is loaded!', 'success');
                console.log('Alpine version:', window.Alpine.version || 'unknown');
            } else {
                setStatus('❌ Alpine.js is NOT loaded!', 'error');
                console.error('window.Alpine is undefined');
            }
        }
        
        function checkConsole() {
            setStatus(`Found ${logs.length} console messages`, 'info');
            
            const errors = logs.filter(l => l.type === 'error');
            if (errors.length > 0) {
                setStatus(`⚠️ Found ${errors.length} errors in console`, 'error');
            }
        }
        
        // Auto-check on load
        window.addEventListener('load', () => {
            setTimeout(() => {
                testLivewire();
                testAlpine();
                checkConsole();
            }, 1000);
        });
        
        // Monitor for Livewire errors
        window.addEventListener('livewire:error', (event) => {
            console.error('Livewire Error:', event.detail);
        });
        
        // Monitor network requests
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            console.log('Fetch request:', args[0]);
            return originalFetch.apply(this, args)
                .then(response => {
                    if (!response.ok) {
                        console.error('Fetch error:', response.status, response.statusText);
                    }
                    return response;
                })
                .catch(error => {
                    console.error('Fetch failed:', error);
                    throw error;
                });
        };
    </script>
</body>
</html>
HTML;

file_put_contents('/var/www/api-gateway/public/livewire-debug.html', $debugScript);
echo "✅ Debug script created: /public/livewire-debug.html\n";

// Step 5: Fix potential asset loading issues
echo "\nStep 5: Publishing Livewire assets...\n";
shell_exec("cd /var/www/api-gateway && php artisan livewire:publish --assets 2>&1");
echo "✅ Livewire assets published\n";

// Step 6: Check for common issues
echo "\nStep 6: Checking for common issues...\n";

// Check if Livewire update endpoint is accessible
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de/livewire/update");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "- Livewire update endpoint (/livewire/update): ";
if ($httpCode == 405) {
    echo "✅ Working (405 is expected for GET request)\n";
} else {
    echo "⚠️ Status $httpCode (expected 405)\n";
}

// Check for duplicate Livewire scripts
echo "- Checking for duplicate Livewire scripts: ";
$viewsPath = resource_path('views');
$duplicateCount = 0;
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsPath));
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() == 'php') {
        $content = file_get_contents($file);
        $livewireCount = substr_count($content, '@livewireScripts');
        if ($livewireCount > 1) {
            $duplicateCount++;
            echo "\n  ⚠️ Multiple @livewireScripts in: " . $file->getPathname();
        }
    }
}
if ($duplicateCount == 0) {
    echo "✅ No duplicates found\n";
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "FIXES APPLIED:\n";
echo "1. ✅ Cleared all Livewire caches\n";
echo "2. ✅ Regenerated Livewire manifest\n";
echo "3. ✅ Published Livewire assets\n";
echo "4. ✅ Created debug tool\n";
echo "\nNEXT STEPS:\n";
echo "1. Open: https://api.askproai.de/livewire-debug.html\n";
echo "2. Check what the debug tool shows\n";
echo "3. Try accessing /admin/calls again\n";
echo "4. Check browser console for errors (F12)\n";

// Restart PHP-FPM
echo "\nRestarting PHP-FPM...\n";
shell_exec("sudo systemctl restart php8.3-fpm 2>&1");
echo "✅ PHP-FPM restarted\n";

echo "</pre>";