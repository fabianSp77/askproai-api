<?php
// Test Calls Page Directly

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Calls Page Directly</h1>";
echo "<pre>";

try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    
    // Boot the app
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::create('https://api.askproai.de/', 'GET');
    $kernel->handle($request);
    
    // Find an admin user
    $adminUser = \App\Models\User::where('is_admin', true)->first();
    if (!$adminUser) {
        echo "❌ No admin user found\n";
        exit;
    }
    
    echo "✅ Found admin user: " . $adminUser->email . "\n";
    echo "   Company ID: " . $adminUser->company_id . "\n\n";
    
    // Login as admin
    auth()->login($adminUser);
    
    // Set company context
    app()->instance('current_company_id', $adminUser->company_id);
    
    echo "Testing calls page route...\n";
    echo str_repeat('-', 50) . "\n";
    
    // Create request for calls page
    $callsRequest = Illuminate\Http\Request::create('https://api.askproai.de/admin/calls', 'GET', [], [], [], [
        'HTTP_HOST' => 'api.askproai.de',
        'HTTPS' => 'on',
        'HTTP_X_FORWARDED_PROTO' => 'https',
    ]);
    
    // Set authenticated user
    $callsRequest->setUserResolver(function () use ($adminUser) {
        return $adminUser;
    });
    
    // Replace request in container
    $app->instance('request', $callsRequest);
    
    // Try to handle the request
    $response = $kernel->handle($callsRequest);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() == 500) {
        echo "\n❌ 500 ERROR DETECTED!\n\n";
        
        // Get the actual exception
        $content = $response->getContent();
        
        // Try to extract error from response
        if (preg_match('/<div[^>]*class="[^"]*message[^"]*"[^>]*>(.*?)<\/div>/s', $content, $matches)) {
            echo "Error Message: " . trim(strip_tags($matches[1])) . "\n\n";
        }
        
        // Look for stack trace
        if (preg_match('/<pre[^>]*class="[^"]*trace[^"]*"[^>]*>(.*?)<\/pre>/s', $content, $matches)) {
            $trace = strip_tags($matches[1]);
            $lines = explode("\n", $trace);
            echo "Stack Trace (first 5 lines):\n";
            foreach (array_slice($lines, 0, 5) as $line) {
                if (trim($line)) {
                    echo "  " . trim($line) . "\n";
                }
            }
        }
        
        // Save full error
        $errorFile = '/var/www/api-gateway/storage/logs/calls-page-error-' . time() . '.html';
        file_put_contents($errorFile, $content);
        echo "\nFull error saved to: $errorFile\n";
        
    } else if ($response->getStatusCode() == 200) {
        echo "✅ Calls page loaded successfully!\n";
        
        // Check if it's the right page
        $content = $response->getContent();
        if (strpos($content, 'calls') !== false || strpos($content, 'Calls') !== false) {
            echo "✅ Content appears to be the calls page\n";
        }
        
        // Check for Livewire components
        $livewireCount = substr_count($content, 'wire:');
        echo "Livewire components found: $livewireCount\n";
    }
    
    // Check the specific resource class
    echo "\n\nChecking CallResource...\n";
    $resourceClass = 'App\\Filament\\Admin\\Resources\\CallResource';
    if (class_exists($resourceClass)) {
        echo "✅ CallResource class exists\n";
        
        $listClass = $resourceClass . '\\Pages\\ListCallsClean';
        if (class_exists($listClass)) {
            echo "✅ ListCallsClean page exists\n";
        } else {
            echo "❌ ListCallsClean page not found\n";
            
            // Check other possible page classes
            $possiblePages = ['ListCalls', 'ListCallsSimple', 'ListCallsFixed'];
            foreach ($possiblePages as $page) {
                $pageClass = $resourceClass . '\\Pages\\' . $page;
                if (class_exists($pageClass)) {
                    echo "✅ Found alternative: $page\n";
                }
            }
        }
    } else {
        echo "❌ CallResource class not found\n";
    }
    
    // Check routes
    echo "\n\nChecking routes...\n";
    $routes = $app->make('router')->getRoutes();
    $callRoutes = [];
    foreach ($routes as $route) {
        if (strpos($route->uri(), 'admin/calls') !== false) {
            $callRoutes[] = [
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => $route->getActionName()
            ];
        }
    }
    
    if (count($callRoutes) > 0) {
        echo "Found " . count($callRoutes) . " call routes:\n";
        foreach ($callRoutes as $route) {
            echo "- " . $route['uri'] . " => " . $route['action'] . "\n";
        }
    } else {
        echo "❌ No call routes found\n";
    }
    
} catch (\Throwable $e) {
    echo "\n❌ EXCEPTION: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    $trace = explode("\n", $e->getTraceAsString());
    foreach (array_slice($trace, 0, 10) as $line) {
        echo $line . "\n";
    }
}

echo "</pre>";