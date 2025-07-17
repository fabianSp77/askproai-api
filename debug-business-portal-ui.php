#!/usr/bin/env php
<?php

echo "=== Business Portal Admin UI Debug ===\n\n";

// Check if scripts exist
$scripts = [
    'public/js/unified-ui-fix.js',
    'public/js/unified-ui-fix-v2.js',
    'public/js/debug-loading-sequence.js',
    'public/js/emergency-button-fix.js',
];

echo "1. Checking JavaScript files:\n";
foreach ($scripts as $script) {
    $path = __DIR__ . '/' . $script;
    if (file_exists($path)) {
        $size = filesize($path);
        $mtime = date('Y-m-d H:i:s', filemtime($path));
        echo "   ✓ $script (Size: $size bytes, Modified: $mtime)\n";
    } else {
        echo "   ✗ $script - NOT FOUND\n";
    }
}

// Check CSS files
$cssFiles = [
    'public/css/filament-button-fixes.css',
    'public/css/admin-emergency-fix.css',
];

echo "\n2. Checking CSS files:\n";
foreach ($cssFiles as $css) {
    $path = __DIR__ . '/' . $css;
    if (file_exists($path)) {
        $size = filesize($path);
        $mtime = date('Y-m-d H:i:s', filemtime($path));
        echo "   ✓ $css (Size: $size bytes, Modified: $mtime)\n";
    } else {
        echo "   ✗ $css - NOT FOUND\n";
    }
}

// Check view files
$views = [
    'resources/views/filament/admin/pages/business-portal-admin.blade.php',
    'resources/views/vendor/filament-panels/components/layout/base.blade.php',
    'resources/views/admin-emergency-fix.blade.php',
];

echo "\n3. Checking view files:\n";
foreach ($views as $view) {
    $path = __DIR__ . '/' . $view;
    if (file_exists($path)) {
        $size = filesize($path);
        $mtime = date('Y-m-d H:i:s', filemtime($path));
        echo "   ✓ $view (Size: $size bytes, Modified: $mtime)\n";
    } else {
        echo "   ✗ $view - NOT FOUND\n";
    }
}

// Check Laravel cache
echo "\n4. Checking Laravel cache:\n";
$cacheFiles = [
    'bootstrap/cache/config.php',
    'bootstrap/cache/routes-v7.php',
    'bootstrap/cache/packages.php',
    'bootstrap/cache/services.php',
];

foreach ($cacheFiles as $cache) {
    $path = __DIR__ . '/' . $cache;
    if (file_exists($path)) {
        $mtime = date('Y-m-d H:i:s', filemtime($path));
        echo "   ✓ $cache (Modified: $mtime)\n";
    } else {
        echo "   - $cache - Not cached\n";
    }
}

// Check if storage is writable
echo "\n5. Checking storage permissions:\n";
$storageDirs = [
    'storage/app',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
];

foreach ($storageDirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_writable($path)) {
        echo "   ✓ $dir - Writable\n";
    } else {
        echo "   ✗ $dir - NOT WRITABLE\n";
    }
}

// Check web server config
echo "\n6. Web server info:\n";
if (php_sapi_name() === 'cli') {
    echo "   Running from CLI\n";
    
    // Check nginx error log
    $errorLog = '/var/log/nginx/error.log';
    if (file_exists($errorLog)) {
        echo "\n   Recent nginx errors:\n";
        $errors = shell_exec("tail -20 $errorLog | grep -E '(unified-ui-fix|emergency-button|business-portal)' 2>/dev/null");
        if ($errors) {
            echo $errors;
        } else {
            echo "   No recent relevant errors\n";
        }
    }
}

// Check for any build process
echo "\n7. Build process check:\n";
if (file_exists(__DIR__ . '/package.json')) {
    echo "   ✓ package.json exists\n";
    
    if (file_exists(__DIR__ . '/node_modules')) {
        echo "   ✓ node_modules exists\n";
    } else {
        echo "   - node_modules not found (npm install needed?)\n";
    }
    
    if (file_exists(__DIR__ . '/vite.config.js')) {
        echo "   ✓ vite.config.js exists\n";
        
        // Check for built assets
        if (file_exists(__DIR__ . '/public/build/manifest.json')) {
            echo "   ✓ Build manifest exists\n";
            $manifest = json_decode(file_get_contents(__DIR__ . '/public/build/manifest.json'), true);
            echo "   Built assets: " . count($manifest) . " files\n";
        } else {
            echo "   - No build manifest (npm run build needed?)\n";
        }
    }
}

echo "\n8. Recommendations:\n";
echo "   1. Clear all caches: php artisan optimize:clear\n";
echo "   2. Clear browser cache and hard refresh (Ctrl+Shift+R)\n";
echo "   3. Check browser console for JavaScript errors\n";
echo "   4. Use window.unifiedUIFixV2.status() in console to check script status\n";
echo "   5. Use window.emergencyButtonFix.applyAll() to manually trigger fixes\n";

echo "\nDone!\n";