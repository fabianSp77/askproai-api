<?php

echo "🔍 Debugging Login Issues\n";
echo "========================\n\n";

// Test 1: Check route middleware
echo "1. Route Middleware Analysis:\n";

// Business login route
exec('php artisan route:list --path=business/login --columns=uri,method,middleware 2>&1', $businessLogin);
echo "   Business Login Route:\n";
foreach ($businessLogin as $line) {
    if (strpos($line, 'business/login') !== false || strpos($line, 'GET|HEAD') !== false) {
        echo "   $line\n";
    }
}

// Admin login route
exec('php artisan route:list --path=admin/login --columns=uri,method,middleware 2>&1', $adminLogin);
echo "\n   Admin Login Route:\n";
foreach ($adminLogin as $line) {
    if (strpos($line, 'admin/login') !== false || strpos($line, 'GET|HEAD') !== false) {
        echo "   $line\n";
    }
}

// Test 2: Create simple login test
echo "\n2. Creating simplified login test...\n";

$testFile = '/var/www/api-gateway/public/simple-login-test.php';
$content = <<<'PHP'
<?php
session_start();

// Simple session test
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['test_user'] = 'admin@test.com';
    $_SESSION['logged_in'] = true;
    echo json_encode([
        'status' => 'logged_in',
        'session_id' => session_id(),
        'session_data' => $_SESSION
    ]);
    exit;
}

// Check session
echo json_encode([
    'status' => 'checking',
    'session_id' => session_id(),
    'logged_in' => isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : false,
    'session_data' => $_SESSION
]);
PHP;

file_put_contents($testFile, $content);
echo "   ✅ Created: $testFile\n";

// Test 3: Check .env SESSION settings
echo "\n3. Environment Settings:\n";
$envFile = '/var/www/api-gateway/.env';
$envContent = file_get_contents($envFile);
$sessionSettings = [
    'SESSION_DRIVER',
    'SESSION_LIFETIME',
    'SESSION_COOKIE',
    'SESSION_DOMAIN',
    'SESSION_SECURE_COOKIE',
    'SESSION_HTTP_ONLY',
    'SESSION_SAME_SITE'
];

foreach ($sessionSettings as $setting) {
    if (preg_match("/^$setting=(.*)$/m", $envContent, $matches)) {
        echo "   - $setting = " . trim($matches[1]) . "\n";
    }
}

// Test 4: Fix recommendation
echo "\n4. Immediate Fix Steps:\n";
echo "   1. Clear all browser cookies for api.askproai.de\n";
echo "   2. Try in Incognito/Private mode\n";
echo "   3. Run: php artisan optimize:clear\n";
echo "   4. Run: sudo systemctl restart php8.3-fpm\n";

echo "\n✅ Debug analysis complete!\n";