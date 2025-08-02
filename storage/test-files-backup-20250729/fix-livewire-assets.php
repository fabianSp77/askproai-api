<?php
// Fix Livewire & Alpine Assets

echo "<h1>Fix Livewire & Alpine Assets</h1>";
echo "<pre>";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 80) . "\n\n";

// Check current situation
echo "CHECKING CURRENT ASSETS...\n\n";

// Check Livewire JS
$livewireAssets = [
    '/vendor/livewire/livewire.js' => 'Livewire Core JS',
    '/vendor/livewire/livewire.min.js' => 'Livewire Core JS (minified)',
    '/livewire/livewire.js' => 'Livewire JS (alt location)',
];

foreach ($livewireAssets as $path => $name) {
    $fullPath = public_path() . $path;
    if (file_exists($fullPath)) {
        echo "✅ $name found at: $path\n";
        echo "   Size: " . number_format(filesize($fullPath)) . " bytes\n";
    } else {
        echo "❌ $name NOT FOUND at: $path\n";
    }
}

// Check vendor directory
echo "\nChecking vendor directories...\n";
$vendorLivewire = base_path('vendor/livewire/livewire');
if (is_dir($vendorLivewire)) {
    echo "✅ Livewire vendor directory exists\n";
    
    // Find dist files
    $distPath = $vendorLivewire . '/dist';
    if (is_dir($distPath)) {
        echo "✅ Livewire dist directory exists\n";
        $files = scandir($distPath);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                echo "   - $file (" . number_format(filesize($distPath . '/' . $file)) . " bytes)\n";
            }
        }
    }
} else {
    echo "❌ Livewire not installed in vendor\n";
}

// APPLY FIXES
echo "\n" . str_repeat('=', 80) . "\n";
echo "APPLYING FIXES...\n\n";

// Fix 1: Force publish Livewire assets
echo "1. Publishing Livewire assets...\n";
$output = shell_exec("cd /var/www/api-gateway && php artisan vendor:publish --tag=livewire:assets --force 2>&1");
echo $output . "\n";

// Fix 2: Create vendor directory if missing
$publicVendor = public_path('vendor');
if (!is_dir($publicVendor)) {
    mkdir($publicVendor, 0755, true);
    echo "✅ Created public/vendor directory\n";
}

// Fix 3: Manually copy Livewire assets if publish didn't work
$livewireDistPath = base_path('vendor/livewire/livewire/dist');
$livewirePublicPath = public_path('vendor/livewire');

if (is_dir($livewireDistPath) && !file_exists($livewirePublicPath . '/livewire.js')) {
    echo "\n2. Manually copying Livewire assets...\n";
    
    if (!is_dir($livewirePublicPath)) {
        mkdir($livewirePublicPath, 0755, true);
    }
    
    $files = ['livewire.js', 'livewire.min.js', 'livewire.js.map', 'livewire.min.js.map', 'manifest.json'];
    
    foreach ($files as $file) {
        $source = $livewireDistPath . '/' . $file;
        $dest = $livewirePublicPath . '/' . $file;
        
        if (file_exists($source)) {
            copy($source, $dest);
            echo "✅ Copied $file\n";
        }
    }
}

// Fix 4: Check Alpine.js
echo "\n3. Checking Alpine.js...\n";
$alpineInFilament = public_path('js/filament/support/support.js');
if (file_exists($alpineInFilament)) {
    $content = file_get_contents($alpineInFilament);
    if (strpos($content, 'Alpine') !== false) {
        echo "✅ Alpine.js found in Filament support.js\n";
    } else {
        echo "⚠️  Alpine.js might not be in support.js\n";
    }
} else {
    echo "❌ Filament support.js not found\n";
}

// Fix 5: Publish Filament assets
echo "\n4. Publishing Filament assets...\n";
shell_exec("cd /var/www/api-gateway && php artisan filament:assets 2>&1");
echo "✅ Filament assets command executed\n";

// Fix 6: Clear views to ensure proper rendering
echo "\n5. Clearing compiled views...\n";
shell_exec("cd /var/www/api-gateway && php artisan view:clear 2>&1");
echo "✅ Views cleared\n";

// Create test page
echo "\n6. Creating test page...\n";
$testHtml = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Asset Loading Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .status { padding: 10px; margin: 5px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Asset Loading Test</h1>
    
    <h2>Attempting to load Livewire & Alpine:</h2>
    
    <div id="results"></div>
    
    <script>
        const results = document.getElementById('results');
        
        function addResult(text, success) {
            const div = document.createElement('div');
            div.className = 'status ' + (success ? 'success' : 'error');
            div.textContent = text;
            results.appendChild(div);
        }
        
        // Test 1: Load Livewire directly
        addResult('Loading Livewire from /vendor/livewire/livewire.js...', true);
        
        const script1 = document.createElement('script');
        script1.src = '/vendor/livewire/livewire.js';
        script1.onload = () => {
            addResult('✅ Livewire script loaded successfully!', true);
            if (typeof window.Livewire !== 'undefined') {
                addResult('✅ window.Livewire is available!', true);
            } else {
                addResult('❌ window.Livewire is still undefined', false);
            }
        };
        script1.onerror = () => {
            addResult('❌ Failed to load Livewire script', false);
        };
        document.head.appendChild(script1);
        
        // Test 2: Check Alpine after a delay
        setTimeout(() => {
            if (typeof window.Alpine !== 'undefined') {
                addResult('✅ Alpine.js is available!', true);
            } else {
                addResult('❌ Alpine.js not found', false);
                
                // Try loading Filament support
                const script2 = document.createElement('script');
                script2.src = '/js/filament/support/support.js';
                script2.onload = () => {
                    addResult('✅ Filament support loaded', true);
                    setTimeout(() => {
                        if (typeof window.Alpine !== 'undefined') {
                            addResult('✅ Alpine.js now available!', true);
                        }
                    }, 500);
                };
                script2.onerror = () => {
                    addResult('❌ Failed to load Filament support', false);
                };
                document.head.appendChild(script2);
            }
        }, 1000);
        
        // Show loaded scripts
        setTimeout(() => {
            const scripts = Array.from(document.scripts).map(s => s.src).filter(s => s);
            addResult('\nLoaded scripts:\n' + scripts.join('\n'), true);
        }, 2000);
    </script>
</body>
</html>
HTML;

file_put_contents(public_path('asset-test.html'), $testHtml);
echo "✅ Test page created: /asset-test.html\n";

// Final check
echo "\n" . str_repeat('=', 80) . "\n";
echo "FINAL STATUS:\n\n";

// Re-check assets
foreach ($livewireAssets as $path => $name) {
    $fullPath = public_path() . $path;
    if (file_exists($fullPath)) {
        echo "✅ $name: AVAILABLE\n";
    } else {
        echo "❌ $name: MISSING\n";
    }
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "NEXT STEPS:\n";
echo "1. Test the asset loading: https://api.askproai.de/asset-test.html\n";
echo "2. Clear browser cache (Ctrl+Shift+Delete)\n";
echo "3. Try admin panel again: https://api.askproai.de/admin/calls\n";
echo "4. Check browser console for errors\n";

// Restart PHP-FPM
echo "\nRestarting services...\n";
shell_exec("sudo systemctl restart php8.3-fpm");
shell_exec("sudo systemctl restart nginx");
echo "✅ Services restarted\n";

echo "</pre>";