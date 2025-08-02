<?php
/**
 * Raw Session Test - Bypass Laravel to test PHP sessions
 */

// Test 1: Raw PHP Session
session_start();

echo "<h1>Raw PHP Session Test</h1>";

// Increment counter
if (!isset($_SESSION['counter'])) {
    $_SESSION['counter'] = 0;
}
$_SESSION['counter']++;

echo "<p>PHP Session Counter: " . $_SESSION['counter'] . " (should increment on refresh)</p>";
echo "<p>PHP Session ID: " . session_id() . "</p>";

// Test 2: Set a test cookie
$cookieName = 'test_cookie';
$cookieValue = 'test_value_' . time();
setcookie($cookieName, $cookieValue, time() + 3600, '/', '', false, true);

echo "<h2>Cookie Test</h2>";
echo "<p>Setting cookie: $cookieName = $cookieValue</p>";
echo "<p>Existing cookies:</p>";
echo "<pre>" . print_r($_COOKIE, true) . "</pre>";

// Test 3: Laravel Session WITHOUT full bootstrap
echo "<h2>Laravel Session Test</h2>";

require_once __DIR__ . '/../vendor/autoload.php';

// Minimal Laravel bootstrap
$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Now test Laravel session
$laravelSession = app('session.store');
echo "<p>Laravel Session Driver: " . config('session.driver') . "</p>";
echo "<p>Laravel Session ID: " . $laravelSession->getId() . "</p>";

// Test write
$laravelSession->put('test_key', 'test_value');
$laravelSession->save();

echo "<p>Laravel Session Data:</p>";
echo "<pre>" . print_r($laravelSession->all(), true) . "</pre>";

// Check session file
$sessionFile = storage_path('framework/sessions/' . $laravelSession->getId());
echo "<p>Session File: " . $sessionFile . "</p>";
echo "<p>File Exists: " . (file_exists($sessionFile) ? 'YES' : 'NO') . "</p>";

if (file_exists($sessionFile)) {
    $content = file_get_contents($sessionFile);
    echo "<p>File Size: " . strlen($content) . " bytes</p>";
    $data = @unserialize($content);
    if ($data) {
        echo "<p>File Contents (keys): " . implode(', ', array_keys($data)) . "</p>";
    }
}

echo "<h2>Headers Status</h2>";
echo "<p>Headers Sent: " . (headers_sent($file, $line) ? "YES from $file:$line" : "NO") . "</p>";
echo "<p>Output Buffering Level: " . ob_get_level() . "</p>";

echo '<p><a href="?">Refresh Page</a></p>';
?>