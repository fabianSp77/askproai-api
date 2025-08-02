<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Debug Admin Calls Page V2 - Full Analysis</h1>";
echo "<pre>";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo str_repeat('=', 80) . "\n\n";

try {
    // Load Laravel
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    
    echo "✅ Laravel loaded successfully\n\n";
    
    // Make kernel
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "✅ Kernel created\n\n";
    
    // Create request for admin/calls
    $request = Illuminate\Http\Request::create('https://api.askproai.de/admin/calls', 'GET', [], [], [], [
        'HTTP_HOST' => 'api.askproai.de',
        'HTTPS' => 'on',
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 Debug Script',
    ]);
    
    // Copy session from current request if available
    $sessionCookieName = 'askproai_session'; // Default value
    if (isset($_COOKIE[$sessionCookieName])) {
        $request->cookies->set($sessionCookieName, $_COOKIE[$sessionCookieName]);
        echo "✅ Session cookie found and set\n\n";
    } else {
        echo "❌ No session cookie found in \$_COOKIE\n\n";
    }
    
    echo "✅ Request created for: " . $request->fullUrl() . "\n\n";
    
    // Bootstrap the app
    $app->instance('request', $request);
    echo "✅ Request instance set\n\n";
    
    // Handle the request
    echo "Attempting to handle request...\n";
    echo str_repeat('-', 40) . "\n";
    
    try {
        $response = $kernel->handle($request);
        
        echo "\n" . str_repeat('-', 40) . "\n";
        echo "✅ Request handled successfully!\n\n";
        
        // Response info
        echo "Response Info:\n";
        echo "- Status Code: " . $response->getStatusCode() . "\n";
        echo "- Content Type: " . $response->headers->get('Content-Type') . "\n";
        echo "- Content Length: " . strlen($response->getContent()) . " bytes\n";
        echo "\n";
        
        // Check if it's a redirect
        if ($response->isRedirect()) {
            echo "🔄 Response is a redirect to: " . $response->headers->get('Location') . "\n\n";
        }
        
        // Check for errors in response
        if ($response->getStatusCode() >= 400) {
            echo "❌ Error Response: " . $response->getStatusCode() . "\n";
            
            // Try to get error details
            $content = $response->getContent();
            if ($response->getStatusCode() == 500) {
                // For 500 errors, try to extract the actual error message
                if (preg_match('/<div class="message"[^>]*>(.*?)<\/div>/s', $content, $matches)) {
                    echo "\nError Message: " . strip_tags($matches[1]) . "\n";
                }
                if (preg_match('/<pre[^>]*>(.*?)<\/pre>/s', $content, $matches)) {
                    echo "\nError Details:\n" . strip_tags($matches[1]) . "\n";
                }
            }
            
            if (strlen($content) < 5000) {
                echo "\nFull Response Content:\n";
                echo htmlspecialchars($content);
            } else {
                echo "\nResponse too large. First 2000 chars:\n";
                echo htmlspecialchars(substr($content, 0, 2000)) . "...";
                
                // Save full response to file
                $filename = '/var/www/api-gateway/storage/logs/debug-response-' . time() . '.html';
                file_put_contents($filename, $content);
                echo "\n\nFull response saved to: $filename\n";
            }
        } else {
            echo "✅ Success Response: " . $response->getStatusCode() . "\n";
            echo "- Livewire Components Found: " . substr_count($response->getContent(), 'wire:') . "\n";
            echo "- Alpine Components Found: " . substr_count($response->getContent(), 'x-data') . "\n";
        }
        
        // Check config after kernel boot
        echo "\n\nConfiguration Check (after boot):\n";
        echo "- Session Driver: " . config('session.driver') . "\n";
        echo "- Session Cookie: " . config('session.cookie') . "\n";
        echo "- Session Domain: " . config('session.domain') . "\n";
        echo "- Session Secure: " . (config('session.secure') ? 'Yes' : 'No') . "\n";
        echo "- APP_ENV: " . config('app.env') . "\n";
        echo "- APP_DEBUG: " . (config('app.debug') ? 'Yes' : 'No') . "\n";
        
        // Check middleware
        echo "\n\nMiddleware Analysis:\n";
        try {
            $route = $app->make('router')->getRoutes()->match($request);
            if ($route) {
                echo "Route: " . ($route->getName() ?: 'unnamed') . " (" . $route->getActionName() . ")\n";
                echo "Middleware: " . implode(', ', $route->gatherMiddleware()) . "\n";
            }
        } catch (\Exception $e) {
            echo "Could not analyze route: " . $e->getMessage() . "\n";
        }
        
        // Check auth
        echo "\n\nAuth Status:\n";
        $guards = ['admin', 'web'];
        foreach ($guards as $guard) {
            try {
                $user = auth($guard)->user();
                if ($user) {
                    echo "- Guard '$guard': ✅ Authenticated as " . $user->email . " (ID: " . $user->id . ")\n";
                    if (isset($user->company_id)) {
                        echo "  Company ID: " . $user->company_id . "\n";
                    }
                } else {
                    echo "- Guard '$guard': ❌ Not authenticated\n";
                }
            } catch (\Exception $e) {
                echo "- Guard '$guard': ⚠️  Error: " . $e->getMessage() . "\n";
            }
        }
        
        // Check database
        echo "\n\nDatabase Check:\n";
        try {
            $callCount = \App\Models\Call::count();
            echo "✅ Database connected - Calls count: " . $callCount . "\n";
        } catch (\Exception $e) {
            echo "❌ Database error: " . $e->getMessage() . "\n";
        }
        
        // Check Livewire
        echo "\n\nLivewire Status:\n";
        if (class_exists(\Livewire\Livewire::class)) {
            echo "✅ Livewire loaded\n";
            echo "- Update Endpoint: " . config('livewire.update_uri', '/livewire/update') . "\n";
            echo "- Asset URL: " . config('livewire.asset_url') . "\n";
        } else {
            echo "❌ Livewire not loaded\n";
        }
        
        // Check app container
        echo "\n\nApp Container Check:\n";
        echo "- Has current_company_id: " . ($app->has('current_company_id') ? 'Yes' : 'No') . "\n";
        if ($app->has('current_company_id')) {
            echo "- Current Company ID: " . $app->get('current_company_id') . "\n";
        }
        
        $kernel->terminate($request, $response);
        
    } catch (\Exception $e) {
        echo "\n❌ Error during request handling: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
        echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
    }
    
} catch (\Throwable $e) {
    echo "\n\n❌ EXCEPTION CAUGHT ❌\n";
    echo str_repeat('=', 80) . "\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString();
    
    // Additional debug for specific errors
    if (strpos($e->getMessage(), 'company_id') !== false) {
        echo "\n\n⚠️  Company Context Issue Detected!\n";
        echo "This might be related to ForceCompanyContext middleware\n";
    }
    
    if (strpos($e->getMessage(), 'Session') !== false) {
        echo "\n\n⚠️  Session Issue Detected!\n";
        echo "Check session configuration and cookies\n";
    }
}

echo "</pre>";