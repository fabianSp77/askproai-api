<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

// Force destroy the problematic session
$problematicSessionId = 'M4ZUnTnwei50fJOsMmEFKChtPi5pdtmRVt5V64oe';
$sessionPath = storage_path('framework/sessions/' . $problematicSessionId);

echo "<!DOCTYPE html>";
echo "<html><head><title>Force New Session</title>";
echo "<style>body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; }";
echo ".success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }";
echo ".error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }";
echo ".info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }";
echo "</style></head><body>";

echo "<h1>🔧 Force New Session</h1>";

// Step 1: Delete the problematic session file
echo "<div class='info'>";
echo "<h3>Step 1: Removing problematic session</h3>";
if (file_exists($sessionPath)) {
    unlink($sessionPath);
    echo "<p>✅ Deleted session file: $problematicSessionId</p>";
} else {
    echo "<p>ℹ️ Session file not found (already deleted or expired)</p>";
}
echo "</div>";

// Step 2: Clear all cookies programmatically
echo "<div class='info'>";
echo "<h3>Step 2: Clearing all cookies</h3>";
echo "<p>Setting all cookies to expire...</p>";

// Common cookie names to clear
$cookiesToClear = [
    'askproai_session',
    'XSRF-TOKEN',
    'laravel_session',
    session_name(),
];

// Add any remember cookies
foreach ($_COOKIE as $name => $value) {
    if (strpos($name, 'remember') !== false) {
        $cookiesToClear[] = $name;
    }
}

foreach ($cookiesToClear as $cookieName) {
    // Clear for all possible domains
    setcookie($cookieName, '', time() - 3600, '/');
    setcookie($cookieName, '', time() - 3600, '/', '.askproai.de');
    setcookie($cookieName, '', time() - 3600, '/', 'api.askproai.de');
    echo "<p>Cleared: $cookieName</p>";
}

echo "</div>";

// Step 3: Start completely fresh session
echo "<div class='info'>";
echo "<h3>Step 3: Creating fresh session</h3>";

// Destroy any existing session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Start new session with new ID
session_id(bin2hex(random_bytes(20)));
session_start();
$newSessionId = session_id();

echo "<p>✅ New session created: <code>$newSessionId</code></p>";
echo "</div>";

// Step 4: Provide clean login link
echo "<div class='success'>";
echo "<h3>✅ Session Reset Complete!</h3>";
echo "<p>Your browser session has been forcefully reset.</p>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Close this tab</li>";
echo "<li>Open a new incognito/private window</li>";
echo "<li>Navigate to: <code>https://api.askproai.de/business-portal-fixed.html</code></li>";
echo "<li>Try logging in again</li>";
echo "</ol>";
echo "</div>";

// JavaScript to clear client-side data
echo "<script>";
echo "// Clear all localStorage";
echo "localStorage.clear();";
echo "console.log('LocalStorage cleared');";
echo "";
echo "// Clear all sessionStorage";
echo "sessionStorage.clear();";
echo "console.log('SessionStorage cleared');";
echo "";
echo "// Try to delete all cookies via JS";
echo "document.cookie.split(';').forEach(function(c) {";
echo "    document.cookie = c.replace(/^ +/, '').replace(/=.*/, '=;expires=' + new Date().toUTCString() + ';path=/');";
echo "});";
echo "console.log('Cookies cleared via JS');";
echo "</script>";

echo "</body></html>";

$kernel->terminate(request(), response());