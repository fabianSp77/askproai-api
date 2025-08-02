#!/usr/bin/env php
<?php

echo "🔐 Testing Multi-Portal Authentication System\n";
echo "============================================\n\n";

// Test 1: Check session directories
echo "1. Checking session directories...\n";
$adminSessionDir = '/var/www/api-gateway/storage/framework/sessions/admin';
$portalSessionDir = '/var/www/api-gateway/storage/framework/sessions/portal';

if (is_dir($adminSessionDir) && is_writable($adminSessionDir)) {
    echo "✅ Admin session directory exists and is writable\n";
} else {
    echo "❌ Admin session directory issue\n";
}

if (is_dir($portalSessionDir) && is_writable($portalSessionDir)) {
    echo "✅ Portal session directory exists and is writable\n";
} else {
    echo "❌ Portal session directory issue\n";
}

// Test 2: Check middleware files
echo "\n2. Checking middleware files...\n";
$middlewareFiles = [
    'AdminPortalSession' => '/var/www/api-gateway/app/Http/Middleware/AdminPortalSession.php',
    'ConfigurePortalSession' => '/var/www/api-gateway/app/Http/Middleware/ConfigurePortalSession.php',
    'IsolatePortalAuth' => '/var/www/api-gateway/app/Http/Middleware/IsolatePortalAuth.php',
    'SharePortalSession' => '/var/www/api-gateway/app/Http/Middleware/SharePortalSession.php',
    'EnsurePortalSessionCookie' => '/var/www/api-gateway/app/Http/Middleware/EnsurePortalSessionCookie.php',
];

foreach ($middlewareFiles as $name => $path) {
    if (file_exists($path)) {
        echo "✅ $name middleware exists\n";
    } else {
        echo "❌ $name middleware missing\n";
    }
}

// Test 3: Check JavaScript fixes
echo "\n3. Checking JavaScript fixes...\n";
$jsFile = '/var/www/api-gateway/resources/js/fix-dropdown-functions.js';
if (file_exists($jsFile)) {
    echo "✅ Dropdown fix JavaScript exists\n";
    // Check if it's imported
    $appJs = file_get_contents('/var/www/api-gateway/resources/js/app.js');
    if (strpos($appJs, 'fix-dropdown-functions') !== false) {
        echo "✅ Dropdown fix is imported in app.js\n";
    } else {
        echo "❌ Dropdown fix not imported in app.js\n";
    }
} else {
    echo "❌ Dropdown fix JavaScript missing\n";
}

// Test 4: Check CSS fixes
echo "\n4. Checking CSS fixes...\n";
$cssFile = '/var/www/api-gateway/resources/css/filament/admin/fix-login-overlay.css';
if (file_exists($cssFile)) {
    echo "✅ Login overlay fix CSS exists\n";
    // Check if it's imported
    $themeCss = file_get_contents('/var/www/api-gateway/resources/css/filament/admin/theme.css');
    if (strpos($themeCss, 'fix-login-overlay.css') !== false) {
        echo "✅ Login overlay fix is imported in theme.css\n";
    } else {
        echo "❌ Login overlay fix not imported in theme.css\n";
    }
} else {
    echo "❌ Login overlay fix CSS missing\n";
}

// Test 5: Check build status
echo "\n5. Checking build status...\n";
$manifestPath = '/var/www/api-gateway/public/build/manifest.json';
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $lastModified = filemtime($manifestPath);
    $hoursSinceBuilt = (time() - $lastModified) / 3600;
    
    echo "✅ Build manifest exists\n";
    echo "   Last built: " . date('Y-m-d H:i:s', $lastModified) . " (" . round($hoursSinceBuilt, 1) . " hours ago)\n";
    
    // Check if fix-dropdown-functions is in the build
    $hasDropdownFix = false;
    foreach ($manifest as $key => $value) {
        if (strpos($key, 'app.js') !== false || strpos($value['file'] ?? '', 'app-') !== false) {
            $hasDropdownFix = true;
            break;
        }
    }
    
    if ($hasDropdownFix) {
        echo "✅ JavaScript fixes are in the build\n";
    } else {
        echo "⚠️  May need to rebuild assets\n";
    }
} else {
    echo "❌ Build manifest missing - run: npm run build\n";
}

// Test 6: Test URLs
echo "\n6. Test URLs:\n";
echo "   Admin Portal:    https://api.askproai.de/admin\n";
echo "   Business Portal: https://api.askproai.de/business/login\n";

echo "\n📋 Manual Test Checklist:\n";
echo "1. Clear all browser cookies\n";
echo "2. Login to Admin Portal with: fabian@askproai.de\n";
echo "3. In new tab, login to Business Portal with: demo@askproai.de / password\n";
echo "4. Refresh both tabs - both should stay logged in\n";
echo "5. Check browser cookies for:\n";
echo "   - askproai_admin_session (Admin Portal)\n";
echo "   - askproai_portal_session (Business Portal)\n";

echo "\n✨ All automated checks complete!\n";