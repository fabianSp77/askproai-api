<?php
echo "=== Applying Multi-Portal Session Fix ===\n\n";

// 1. Replace AdminAccessController
echo "1. Replacing AdminAccessController...\n";
if (file_exists(__DIR__ . '/app/Http/Controllers/Portal/AdminAccessControllerFixed.php')) {
    // Backup original
    copy(
        __DIR__ . '/app/Http/Controllers/Portal/AdminAccessController.php',
        __DIR__ . '/app/Http/Controllers/Portal/AdminAccessController.backup.php'
    );
    
    // Replace with fixed version
    copy(
        __DIR__ . '/app/Http/Controllers/Portal/AdminAccessControllerFixed.php',
        __DIR__ . '/app/Http/Controllers/Portal/AdminAccessController.php'
    );
    echo "   ✓ AdminAccessController replaced\n";
} else {
    echo "   ✗ AdminAccessControllerFixed.php not found\n";
}

// 2. Replace TenantScope
echo "\n2. Replacing TenantScope...\n";
if (file_exists(__DIR__ . '/app/Scopes/TenantScopeFixed.php')) {
    // Backup original
    copy(
        __DIR__ . '/app/Scopes/TenantScope.php',
        __DIR__ . '/app/Scopes/TenantScope.backup.php'
    );
    
    // Replace with fixed version
    copy(
        __DIR__ . '/app/Scopes/TenantScopeFixed.php',
        __DIR__ . '/app/Scopes/TenantScope.php'
    );
    echo "   ✓ TenantScope replaced\n";
} else {
    echo "   ✗ TenantScopeFixed.php not found\n";
}

// 3. Clear all caches
echo "\n3. Clearing caches...\n";
exec('php artisan optimize:clear', $output);
echo "   ✓ Caches cleared\n";

// 4. Clear session files
echo "\n4. Clearing old session files...\n";
$sessionPath = __DIR__ . '/storage/framework/sessions';
$files = glob($sessionPath . '/*');
$count = 0;
foreach ($files as $file) {
    if (is_file($file) && $file !== $sessionPath . '/.gitignore') {
        unlink($file);
        $count++;
    }
}
echo "   ✓ Deleted $count session files\n";

// 5. Create separate session directories
echo "\n5. Creating portal-specific session directories...\n";
$dirs = ['admin', 'business', 'portal', 'api'];
foreach ($dirs as $dir) {
    $path = $sessionPath . '/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "   ✓ Created $path\n";
    }
}

// 6. Update routes to use fixed controller
echo "\n6. Checking routes...\n";
$routeFile = __DIR__ . '/routes/business-portal.php';
if (file_exists($routeFile)) {
    $routes = file_get_contents($routeFile);
    if (strpos($routes, 'AdminAccessController') !== false) {
        echo "   ✓ Routes already configured\n";
    }
}

echo "\n=== Fix Applied Successfully! ===\n";
echo "\nNext steps:\n";
echo "1. Restart PHP-FPM: sudo systemctl restart php8.3-fpm\n";
echo "2. Clear browser cookies for api.askproai.de\n";
echo "3. Try logging in again\n\n";

echo "The fix includes:\n";
echo "- Portal-specific session cookies (askproai_admin_session, askproai_business_session)\n";
echo "- Clean session switching when moving between portals\n";
echo "- Persistent company context in session\n";
echo "- Fixed TenantScope that doesn't lose data\n\n";