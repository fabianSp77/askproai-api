<?php
/**
 * Advanced External Request Debugger
 * Captures the exact error occurring on external HTTPS requests
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/external-debug.log');

// Custom error handler
set_error_handler(function($severity, $message, $file, $line) {
    $log = sprintf(
        "[%s] ERROR %d: %s in %s:%d\n",
        date('Y-m-d H:i:s'),
        $severity,
        $message,
        $file,
        $line
    );
    file_put_contents('/tmp/external-debug.log', $log, FILE_APPEND);
    return false;
});

// Exception handler
set_exception_handler(function($e) {
    $log = sprintf(
        "[%s] EXCEPTION: %s in %s:%d\nTrace:\n%s\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    file_put_contents('/tmp/external-debug.log', $log, FILE_APPEND);
});

// Start output buffering
ob_start();

try {
    echo "[" . date('Y-m-d H:i:s') . "] Starting external request simulation...\n";

    // Bootstrap Laravel
    require '/var/www/api-gateway/vendor/autoload.php';
    $app = require_once '/var/www/api-gateway/bootstrap/app.php';

    // Force production environment
    $app->bind('env', fn() => 'production');

    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

    // Simulate external HTTPS request exactly as browser would send it
    $request = Illuminate\Http\Request::create('/admin/login', 'GET');

    // Set headers exactly as they come from external HTTPS
    $request->headers->set('Host', 'api.askproai.de');
    $request->headers->set('X-Forwarded-Proto', 'https');
    $request->headers->set('X-Forwarded-Host', 'api.askproai.de');
    $request->headers->set('X-Forwarded-For', '212.91.238.41');
    $request->headers->set('X-Real-IP', '212.91.238.41');
    $request->headers->set('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8');
    $request->headers->set('Accept-Language', 'de,en-US;q=0.7,en;q=0.3');
    $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Firefox/120.0');

    // Force HTTPS
    $request->server->set('HTTPS', 'on');
    $request->server->set('SERVER_PORT', '443');
    $request->server->set('SERVER_NAME', 'api.askproai.de');
    $request->server->set('REQUEST_SCHEME', 'https');
    $request->server->set('HTTP_HOST', 'api.askproai.de');

    echo "[" . date('Y-m-d H:i:s') . "] Request created, handling...\n";

    // Handle the request
    $response = $kernel->handle($request);

    echo "[" . date('Y-m-d H:i:s') . "] Response Status: " . $response->getStatusCode() . "\n";

    if ($response->getStatusCode() == 500) {
        echo "\n🔴 500 ERROR DETECTED!\n";
        echo "=".str_repeat("=", 70)."\n";

        // Get response content
        $content = $response->getContent();

        // Try to extract error from HTML
        if (preg_match('/<title>(.*?)<\/title>/i', $content, $matches)) {
            echo "Page Title: " . $matches[1] . "\n";
        }

        if (preg_match('/class="exception-message"[^>]*>(.*?)<\/div>/si', $content, $matches)) {
            echo "Exception Message: " . strip_tags($matches[1]) . "\n";
        }

        if (preg_match('/<!--(.*?)-->/s', $content, $matches)) {
            echo "HTML Comment (may contain error): " . $matches[1] . "\n";
        }

        // Check for common Laravel error patterns
        if (strpos($content, 'Whoops') !== false) {
            echo "Laravel Whoops error page detected\n";
        }

        if (strpos($content, 'Server Error') !== false) {
            echo "Generic server error page\n";
        }

        // Save full response for analysis
        file_put_contents('/tmp/external-500-response.html', $content);
        echo "\nFull response saved to: /tmp/external-500-response.html\n";

        // Check Laravel log
        $logFile = '/var/www/api-gateway/storage/logs/laravel.log';
        if (file_exists($logFile)) {
            $recentLogs = shell_exec("tail -20 '$logFile' | grep -A 5 -B 5 ERROR");
            if ($recentLogs) {
                echo "\nRecent Laravel errors:\n";
                echo $recentLogs;
            }
        }
    } else {
        echo "✅ Request successful (Status: " . $response->getStatusCode() . ")\n";
    }

    // Terminate response
    $kernel->terminate($request, $response);

} catch (\Throwable $e) {
    echo "\n🔴 EXCEPTION CAUGHT:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";

    // Log to file
    file_put_contents('/tmp/external-debug-exception.txt',
        "Exception: " . $e->getMessage() . "\n" .
        "File: " . $e->getFile() . ":" . $e->getLine() . "\n" .
        "Trace:\n" . $e->getTraceAsString()
    );
}

// Get output
$output = ob_get_clean();
echo $output;

// Also save to file
file_put_contents('/tmp/external-debug-output.txt', $output);

echo "\n📋 Debug files created:\n";
echo "- /tmp/external-debug.log\n";
echo "- /tmp/external-debug-output.txt\n";
echo "- /tmp/external-debug-exception.txt\n";
echo "- /tmp/external-500-response.html\n";