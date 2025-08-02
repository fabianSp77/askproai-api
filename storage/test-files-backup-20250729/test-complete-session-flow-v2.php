<?php
// Complete Session Flow Test V2 - Fixed

echo "<h1>Complete Session Flow Test V2</h1>";
echo "<pre>";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 80) . "\n\n";

try {
    // Step 1: Load Laravel and boot the app
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Boot the application by handling a dummy request
    $dummyRequest = Illuminate\Http\Request::create('https://api.askproai.de/', 'GET');
    $kernel->handle($dummyRequest);
    
    echo "Step 1: Configuration Check\n";
    echo "- APP_URL: " . config('app.url') . "\n";
    echo "- Session Driver: " . config('session.driver') . "\n";
    echo "- Session Cookie: " . config('session.cookie') . "\n";
    echo "- Session Domain: " . config('session.domain') . "\n";
    echo "- Session Secure: " . (config('session.secure') ? 'Yes' : 'No') . "\n";
    echo "- Session HTTP Only: " . (config('session.http_only') ? 'Yes' : 'No') . "\n";
    echo "- Session Same Site: " . config('session.same_site') . "\n";
    echo "\n";
    
    // Step 2: Test Login Page
    echo "Step 2: Testing Login Page\n";
    $loginRequest = Illuminate\Http\Request::create('https://api.askproai.de/admin/login', 'GET', [], [], [], [
        'HTTP_HOST' => 'api.askproai.de',
        'HTTPS' => 'on',
        'HTTP_X_FORWARDED_PROTO' => 'https',
    ]);
    
    $loginResponse = $kernel->handle($loginRequest);
    echo "- Login page status: " . $loginResponse->getStatusCode() . "\n";
    echo "- Is secure request: " . ($loginRequest->secure() ? 'Yes' : 'No') . "\n";
    
    // Check for set-cookie headers
    $cookies = $loginResponse->headers->getCookies();
    if (count($cookies) > 0) {
        echo "- Cookies set: " . count($cookies) . "\n";
        foreach ($cookies as $cookie) {
            if (strpos($cookie->getName(), 'session') !== false) {
                echo "  Session Cookie:\n";
                echo "    Name: " . $cookie->getName() . "\n";
                echo "    Domain: " . $cookie->getDomain() . "\n";
                echo "    Secure: " . ($cookie->isSecure() ? 'Yes' : 'No') . "\n";
                echo "    HttpOnly: " . ($cookie->isHttpOnly() ? 'Yes' : 'No') . "\n";
                echo "    SameSite: " . $cookie->getSameSite() . "\n";
            }
        }
    } else {
        echo "- No cookies set\n";
    }
    
    echo "\n";
    
    // Step 3: Test Admin Calls Page (should redirect to login)
    echo "Step 3: Testing Admin Calls Page (unauthenticated)\n";
    $callsRequest = Illuminate\Http\Request::create('https://api.askproai.de/admin/calls', 'GET', [], [], [], [
        'HTTP_HOST' => 'api.askproai.de',
        'HTTPS' => 'on',
        'HTTP_X_FORWARDED_PROTO' => 'https',
    ]);
    
    $callsResponse = $kernel->handle($callsRequest);
    echo "- Calls page status: " . $callsResponse->getStatusCode() . "\n";
    if ($callsResponse->isRedirect()) {
        echo "- Redirects to: " . $callsResponse->headers->get('Location') . "\n";
        echo "✅ Correctly redirects unauthenticated users to login\n";
    } else {
        echo "⚠️  Did not redirect - might be accessible without auth\n";
    }
    
    echo "\n";
    
    // Step 4: Check Laravel Logs for errors
    echo "Step 4: Checking Laravel Logs\n";
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $lines = explode("\n", $logContent);
        $recentErrors = [];
        
        // Get last 20 lines
        $lastLines = array_slice($lines, -20);
        foreach ($lastLines as $line) {
            if (strpos($line, 'ERROR') !== false || strpos($line, 'CRITICAL') !== false) {
                $recentErrors[] = $line;
            }
        }
        
        if (count($recentErrors) > 0) {
            echo "Recent errors found:\n";
            foreach (array_slice($recentErrors, -5) as $error) {
                echo "  " . substr($error, 0, 150) . "...\n";
            }
        } else {
            echo "✅ No recent errors in log file\n";
        }
    } else {
        echo "Log file not found\n";
    }
    
    echo "\n";
    
    // Step 5: Test with real browser simulation
    echo "Step 5: Browser Simulation Test\n";
    
    // Create a session
    $sessionId = bin2hex(random_bytes(20));
    $sessionCookie = config('session.cookie') . '=' . $sessionId;
    
    $browserRequest = Illuminate\Http\Request::create('https://api.askproai.de/admin/calls', 'GET', [], [], [], [
        'HTTP_HOST' => 'api.askproai.de',
        'HTTPS' => 'on',
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_COOKIE' => $sessionCookie,
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    ]);
    
    $browserResponse = $kernel->handle($browserRequest);
    echo "- Browser simulation status: " . $browserResponse->getStatusCode() . "\n";
    
    if ($browserResponse->getStatusCode() == 500) {
        echo "❌ Still getting 500 error with session cookie\n";
        
        // Save the error response for debugging
        $errorFile = '/var/www/api-gateway/storage/logs/browser-500-error-' . time() . '.html';
        file_put_contents($errorFile, $browserResponse->getContent());
        echo "  Error response saved to: $errorFile\n";
    }
    
    echo "\n";
    
    // Step 6: Summary
    echo "Step 6: Summary and Recommendations\n";
    echo str_repeat('-', 40) . "\n";
    
    $issues = [];
    $fixes = [];
    
    if (!config('session.secure')) {
        $issues[] = "Session secure cookie is disabled";
        $fixes[] = "Set SESSION_SECURE_COOKIE=true in .env";
    }
    
    if (config('session.domain') == '.askproai.de' && !strpos($_SERVER['HTTP_HOST'] ?? '', 'askproai.de')) {
        $issues[] = "Session domain mismatch";
        $fixes[] = "Check SESSION_DOMAIN in .env matches your domain";
    }
    
    if ($loginResponse->getStatusCode() != 200) {
        $issues[] = "Login page not accessible (status: " . $loginResponse->getStatusCode() . ")";
        $fixes[] = "Check if Filament is properly installed";
    }
    
    if (count($issues) > 0) {
        echo "Issues found:\n";
        foreach ($issues as $i => $issue) {
            echo ($i + 1) . ". " . $issue . "\n";
        }
        echo "\nRecommended fixes:\n";
        foreach ($fixes as $i => $fix) {
            echo ($i + 1) . ". " . $fix . "\n";
        }
    } else {
        echo "✅ No configuration issues found\n";
        echo "\nIf still experiencing 500 errors:\n";
        echo "1. Clear all caches: php artisan optimize:clear\n";
        echo "2. Check permissions: chown -R www-data:www-data storage bootstrap/cache\n";
        echo "3. Check error logs: tail -f storage/logs/laravel.log\n";
        echo "4. Try incognito mode to rule out browser cache issues\n";
    }
    
    $kernel->terminate($browserRequest, $browserResponse);
    
} catch (\Exception $e) {
    echo "\n❌ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "</pre>";