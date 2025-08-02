<?php
// Debug Login Error - Direct test

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Debug Login Error</h1>";
echo "<pre>";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 80) . "\n\n";

try {
    // Load Laravel
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    
    // Create kernel
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Create login request
    $request = Illuminate\Http\Request::create('https://api.askproai.de/admin/login', 'GET', [], [], [], [
        'HTTP_HOST' => 'api.askproai.de',
        'HTTPS' => 'on',
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
    ]);
    
    echo "Handling request for: " . $request->fullUrl() . "\n\n";
    
    // Handle the request
    $response = $kernel->handle($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Headers:\n";
    foreach ($response->headers->all() as $key => $values) {
        echo "  $key: " . implode(', ', $values) . "\n";
    }
    echo "\n";
    
    if ($response->getStatusCode() == 500) {
        echo "❌ 500 ERROR DETECTED\n\n";
        
        // Try to extract error from response
        $content = $response->getContent();
        
        // Look for Laravel error page content
        if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/i', $content, $matches)) {
            echo "Error Title: " . strip_tags($matches[1]) . "\n";
        }
        
        if (preg_match('/<div class="message"[^>]*>(.*?)<\/div>/s', $content, $matches)) {
            echo "Error Message: " . strip_tags($matches[1]) . "\n";
        }
        
        // Look for stack trace
        if (preg_match('/<pre[^>]*class="[^"]*trace[^"]*"[^>]*>(.*?)<\/pre>/s', $content, $matches)) {
            $trace = strip_tags($matches[1]);
            $lines = explode("\n", $trace);
            echo "\nStack Trace (first 10 lines):\n";
            foreach (array_slice($lines, 0, 10) as $line) {
                if (trim($line)) {
                    echo "  " . trim($line) . "\n";
                }
            }
        }
        
        // Save full response
        $filename = '/var/www/api-gateway/storage/logs/login-error-' . time() . '.html';
        file_put_contents($filename, $content);
        echo "\nFull error response saved to: $filename\n";
        
        // Check if it's a view error
        if (strpos($content, 'View [') !== false && strpos($content, '] not found') !== false) {
            echo "\n⚠️  VIEW NOT FOUND ERROR DETECTED\n";
            echo "This suggests a missing Blade template file.\n";
        }
        
        // Check if it's a class not found error
        if (strpos($content, 'Class') !== false && strpos($content, 'not found') !== false) {
            echo "\n⚠️  CLASS NOT FOUND ERROR DETECTED\n";
            echo "This suggests a missing or misconfigured class.\n";
        }
    } else {
        echo "✅ Login page loaded successfully (Status: " . $response->getStatusCode() . ")\n";
    }
    
    // Check error log file
    echo "\nChecking Laravel Error Log:\n";
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $lines = explode("\n", $logContent);
        $recentLines = array_slice($lines, -20);
        
        $errorFound = false;
        foreach ($recentLines as $line) {
            if (strpos($line, date('Y-m-d')) !== false && (strpos($line, 'ERROR') !== false || strpos($line, 'CRITICAL') !== false)) {
                echo "Recent Error: " . substr($line, 0, 200) . "...\n";
                $errorFound = true;
            }
        }
        
        if (!$errorFound) {
            echo "No recent errors in log file.\n";
        }
    }
    
    // Check Filament installation
    echo "\nChecking Filament Installation:\n";
    
    // Check if Filament views exist
    $filamentViews = [
        resource_path('views/vendor/filament'),
        resource_path('views/vendor/filament-panels'),
    ];
    
    foreach ($filamentViews as $viewPath) {
        if (is_dir($viewPath)) {
            echo "✅ Found: $viewPath\n";
        } else {
            echo "❌ Missing: $viewPath\n";
        }
    }
    
    // Check Filament assets
    if (is_dir(public_path('filament'))) {
        echo "✅ Filament assets published\n";
    } else {
        echo "❌ Filament assets not published\n";
    }
    
    // Check route
    echo "\nChecking Route:\n";
    try {
        $routes = $app->make('router')->getRoutes();
        $loginRoute = $routes->getByName('filament.admin.auth.login');
        if ($loginRoute) {
            echo "✅ Login route exists: " . $loginRoute->uri() . "\n";
            echo "  Controller: " . $loginRoute->getActionName() . "\n";
        } else {
            echo "❌ Login route not found\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error checking routes: " . $e->getMessage() . "\n";
    }
    
    $kernel->terminate($request, $response);
    
} catch (\Throwable $e) {
    echo "\n❌ EXCEPTION CAUGHT\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";