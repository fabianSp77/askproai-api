<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking Session Configuration\n";
echo "==============================\n\n";

// Check current URL
$currentUrl = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'CLI';
$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

echo "Current environment:\n";
echo "- Host: {$currentUrl}\n";
echo "- HTTPS: " . ($isHttps ? "YES" : "NO") . "\n";
echo "- APP_ENV: " . env('APP_ENV') . "\n\n";

echo "Session configuration:\n";
echo "- Driver: " . config('session.driver') . "\n";
echo "- Secure cookies: " . (config('session.secure') ? "YES" : "NO") . "\n";
echo "- Cookie name: " . config('session.cookie') . "\n";
echo "- Domain: " . (config('session.domain') ?: '(not set)') . "\n\n";

if (config('session.secure') && !$isHttps) {
    echo "⚠️  WARNING: Secure cookies are enabled but you're not using HTTPS!\n";
    echo "This will prevent login from working properly.\n\n";
    
    echo "Solutions:\n";
    echo "1. Access the site via HTTPS\n";
    echo "2. Or temporarily disable secure cookies for testing:\n";
    echo "   - Edit .env and set: SESSION_SECURE_COOKIE=false\n";
    echo "   - Then run: php artisan config:clear\n";
}

// Check if we're behind a proxy
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    echo "\nProxy detected:\n";
    echo "- X-Forwarded-Proto: " . $_SERVER['HTTP_X_FORWARDED_PROTO'] . "\n";
    echo "- X-Forwarded-For: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'not set') . "\n";
}

// Check trusted proxies configuration
echo "\nTrusted proxies configuration:\n";
$trustedProxies = config('trustedproxy.proxies');
if (is_array($trustedProxies)) {
    echo "- Proxies: " . implode(', ', $trustedProxies) . "\n";
} else {
    echo "- Proxies: " . ($trustedProxies ?: 'none configured') . "\n";
}

// Test session functionality
echo "\nTesting session functionality...\n";
try {
    // Start a session
    session_start();
    $_SESSION['test'] = 'works';
    
    if ($_SESSION['test'] === 'works') {
        echo "✅ PHP sessions are working\n";
    } else {
        echo "❌ PHP sessions are NOT working properly\n";
    }
} catch (\Exception $e) {
    echo "❌ Session error: " . $e->getMessage() . "\n";
}