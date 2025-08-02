<?php
// Final Test - Admin Calls Page

echo "<h1>Final Test - Admin Calls Page</h1>";
echo "<pre>";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 80) . "\n\n";

// Load Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Boot the app
$dummyRequest = Illuminate\Http\Request::create('https://api.askproai.de/', 'GET');
$kernel->handle($dummyRequest);

echo "Configuration Summary:\n";
echo "- Session Secure Cookie: " . (config('session.secure') ? '✅ Enabled' : '❌ Disabled') . "\n";
echo "- APP_URL: " . config('app.url') . "\n";
echo "- Session Domain: " . config('session.domain') . "\n";
echo "\n";

// Test 1: Login Page
echo "Test 1: Login Page\n";
$loginRequest = Illuminate\Http\Request::create('https://api.askproai.de/admin/login', 'GET', [], [], [], [
    'HTTP_HOST' => 'api.askproai.de',
    'HTTPS' => 'on',
    'HTTP_X_FORWARDED_PROTO' => 'https',
]);

$loginResponse = $kernel->handle($loginRequest);
echo "- Status: " . $loginResponse->getStatusCode() . "\n";
echo "- Result: " . ($loginResponse->getStatusCode() == 200 ? '✅ Success' : '❌ Failed') . "\n";
echo "\n";

// Test 2: Admin Calls Page (Unauthenticated)
echo "Test 2: Admin Calls Page (Unauthenticated)\n";
$callsRequest = Illuminate\Http\Request::create('https://api.askproai.de/admin/calls', 'GET', [], [], [], [
    'HTTP_HOST' => 'api.askproai.de',
    'HTTPS' => 'on',
    'HTTP_X_FORWARDED_PROTO' => 'https',
]);

$callsResponse = $kernel->handle($callsRequest);
echo "- Status: " . $callsResponse->getStatusCode() . "\n";
if ($callsResponse->isRedirect()) {
    echo "- Redirect to: " . $callsResponse->headers->get('Location') . "\n";
    echo "- Result: ✅ Correctly redirects to login\n";
} else {
    echo "- Result: ⚠️  Unexpected status\n";
}
echo "\n";

// Test 3: Check Error Logs
echo "Test 3: Recent Error Check\n";
$logFile = storage_path('logs/laravel.log');
$recentTime = time() - 300; // Last 5 minutes
$recentErrors = [];

if (file_exists($logFile)) {
    $handle = fopen($logFile, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                $logTime = strtotime($matches[1]);
                if ($logTime > $recentTime && (strpos($line, 'ERROR') !== false || strpos($line, 'CRITICAL') !== false)) {
                    $recentErrors[] = $line;
                }
            }
        }
        fclose($handle);
    }
}

if (count($recentErrors) > 0) {
    echo "❌ Found " . count($recentErrors) . " recent errors\n";
    foreach (array_slice($recentErrors, -3) as $error) {
        echo "  " . substr(trim($error), 0, 120) . "...\n";
    }
} else {
    echo "✅ No recent errors in the last 5 minutes\n";
}
echo "\n";

// Summary
echo str_repeat('=', 80) . "\n";
echo "SUMMARY:\n";
echo "\n";

$issues = [];
$actions = [];

if (!config('session.secure')) {
    $issues[] = "Session secure cookie is disabled";
    $actions[] = "Check SESSION_SECURE_COOKIE=true in .env";
}

if ($loginResponse->getStatusCode() != 200) {
    $issues[] = "Login page returns error " . $loginResponse->getStatusCode();
    $actions[] = "Check Filament installation and configuration";
}

if ($callsResponse->getStatusCode() == 500) {
    $issues[] = "Admin calls page returns 500 error";
    $actions[] = "Check Laravel error logs for details";
}

if (count($recentErrors) > 0) {
    $issues[] = "Recent errors found in logs";
    $actions[] = "Review error logs: tail -f storage/logs/laravel.log";
}

if (count($issues) == 0) {
    echo "✅ ALL TESTS PASSED!\n";
    echo "\nThe system appears to be working correctly.\n";
    echo "\nTo test in browser:\n";
    echo "1. Clear all browser cookies for api.askproai.de\n";
    echo "2. Open incognito/private window\n";
    echo "3. Go to https://api.askproai.de/admin/calls\n";
    echo "4. You should be redirected to login page\n";
    echo "5. After login, you should see the calls page\n";
} else {
    echo "❌ ISSUES FOUND:\n\n";
    foreach ($issues as $i => $issue) {
        echo ($i + 1) . ". " . $issue . "\n";
    }
    echo "\nRECOMMENDED ACTIONS:\n\n";
    foreach ($actions as $i => $action) {
        echo ($i + 1) . ". " . $action . "\n";
    }
}

echo "\n";
echo "Test completed at " . date('Y-m-d H:i:s') . "\n";
echo "</pre>";