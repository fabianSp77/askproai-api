<?php

/**
 * Fix Authentication System
 * This script fixes all authentication issues
 */

echo "\n===============================================\n";
echo "       FIXING AUTHENTICATION SYSTEM            \n";
echo "===============================================\n\n";

// 1. Remove dangerous routes
echo "1. REMOVING DANGEROUS ROUTES:\n";
echo "----------------------------------------\n";

$routeFiles = [
    '/var/www/api-gateway/routes/web.php',
    '/var/www/api-gateway/routes/admin-emergency.php',
];

foreach ($routeFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $originalSize = strlen($content);
        
        // Remove emergency routes
        $patterns = [
            '/Route::get\(\'\/emergency-login\'.*?\}\);/s',
            '/Route::get\(\'\/auto-admin-login\'.*?\}\);/s',
            '/Route::get\(\'\/admin-direct-auth\'.*?\}\);/s',
            '/Route::get\(\'\/fixed-login\'.*?\}\);/s',
            '/Route::post\(\'\/fixed-login\'.*?\}\);/s',
        ];
        
        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '// REMOVED FOR SECURITY', $content);
        }
        
        if (strlen($content) < $originalSize) {
            file_put_contents($file, $content);
            echo "   ✓ Cleaned $file\n";
        }
    }
}

// 2. Clear session files
echo "\n2. CLEARING OLD SESSIONS:\n";
echo "----------------------------------------\n";

$sessionDirs = [
    '/var/www/api-gateway/storage/framework/sessions' => 'Default sessions',
    '/var/www/api-gateway/storage/framework/sessions/admin' => 'Admin sessions',
    '/var/www/api-gateway/storage/framework/sessions/portal' => 'Portal sessions',
];

foreach ($sessionDirs as $dir => $name) {
    if (is_dir($dir)) {
        $files = glob($dir . '/*');
        $count = count($files);
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        echo "   ✓ Cleared $count files from $name\n";
    }
}

// 3. Create test login script
echo "\n3. CREATING TEST LOGIN SCRIPT:\n";
echo "----------------------------------------\n";

$testLoginScript = '<?php
// Test Login Script for AskProAI

echo "<h1>AskProAI Login Test</h1>";

// Admin Portal
echo "<h2>Admin Portal</h2>";
echo "<p>URL: <a href=\"/admin/login\">/admin/login</a></p>";
echo "<p>Use admin credentials from User model</p>";

// Business Portal  
echo "<h2>Business Portal</h2>";
echo "<p>URL: <a href=\"/business/login\">/business/login</a></p>";
echo "<p>Use portal user credentials from PortalUser model</p>";

// Clear Sessions
if (isset($_GET["clear"])) {
    setcookie("askproai_admin_session", "", time() - 3600, "/");
    setcookie("askproai_portal_session", "", time() - 3600, "/");
    setcookie("askproai_session", "", time() - 3600, "/");
    echo "<p style=\"color: green;\">✓ All cookies cleared!</p>";
}

echo "<hr>";
echo "<a href=\"?clear=1\">Clear All Cookies</a>";
';

file_put_contents('/var/www/api-gateway/public/test-login-portals.php', $testLoginScript);
echo "   ✓ Created test login script at /test-login-portals.php\n";

// 4. Update .env for proper session handling
echo "\n4. CHECKING SESSION CONFIGURATION:\n";
echo "----------------------------------------\n";

$envFile = '/var/www/api-gateway/.env';
if (file_exists($envFile)) {
    $env = file_get_contents($envFile);
    
    // Check SESSION_DOMAIN
    if (strpos($env, 'SESSION_DOMAIN=') === false) {
        $env .= "\n# Session Configuration\nSESSION_DOMAIN=\n";
        echo "   ✓ Added SESSION_DOMAIN (empty for separate cookies)\n";
    } else {
        echo "   ✓ SESSION_DOMAIN already configured\n";
    }
    
    file_put_contents($envFile, $env);
}

// 5. Clear all caches
echo "\n5. CLEARING ALL CACHES:\n";
echo "----------------------------------------\n";

$commands = [
    'php artisan optimize:clear' => 'All caches',
    'php artisan config:clear' => 'Config cache',
    'php artisan route:clear' => 'Route cache',
    'php artisan view:clear' => 'View cache',
];

foreach ($commands as $cmd => $desc) {
    exec($cmd . ' 2>&1', $output, $returnCode);
    if ($returnCode === 0) {
        echo "   ✓ Cleared $desc\n";
    } else {
        echo "   ✗ Failed to clear $desc\n";
    }
}

echo "\n===============================================\n";
echo "              FIX COMPLETE!                    \n";
echo "===============================================\n\n";

echo "NEXT STEPS:\n";
echo "-----------\n";
echo "1. Visit https://api.askproai.de/test-login-portals.php\n";
echo "2. Click 'Clear All Cookies'\n";
echo "3. Try logging in to Admin Portal: /admin/login\n";
echo "4. Try logging in to Business Portal: /business/login\n";
echo "\nBoth portals should now work correctly!\n\n";