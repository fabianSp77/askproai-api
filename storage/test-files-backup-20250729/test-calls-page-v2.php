<?php
// Test Calls Page V2

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Calls Page V2</h1>";
echo "<pre>";

try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    
    // Boot the app
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::create('https://api.askproai.de/', 'GET');
    $kernel->handle($request);
    
    // Find any user
    $user = \App\Models\User::first();
    if (!$user) {
        echo "❌ No users found in database\n";
        exit;
    }
    
    echo "✅ Found user: " . $user->email . "\n";
    echo "   ID: " . $user->id . "\n";
    echo "   Company ID: " . $user->company_id . "\n\n";
    
    // Login as user
    auth()->login($user);
    
    // Set company context
    if ($user->company_id) {
        app()->instance('current_company_id', $user->company_id);
        echo "✅ Company context set: " . $user->company_id . "\n\n";
    }
    
    echo "Testing calls page route...\n";
    echo str_repeat('-', 50) . "\n";
    
    // Create request for calls page
    $callsRequest = Illuminate\Http\Request::create('https://api.askproai.de/admin/calls', 'GET', [], [], [], [
        'HTTP_HOST' => 'api.askproai.de',
        'HTTPS' => 'on',
        'HTTP_X_FORWARDED_PROTO' => 'https',
    ]);
    
    // Set authenticated user
    $callsRequest->setUserResolver(function () use ($user) {
        return $user;
    });
    
    // Replace request in container
    $app->instance('request', $callsRequest);
    
    try {
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
            
            // Look for specific error patterns
            if (strpos($content, 'Class') !== false && strpos($content, 'not found') !== false) {
                echo "⚠️  CLASS NOT FOUND ERROR\n";
                if (preg_match('/Class[^"]*"([^"]+)"[^"]*not found/', $content, $matches)) {
                    echo "Missing class: " . $matches[1] . "\n";
                }
            }
            
            if (strpos($content, 'View') !== false && strpos($content, 'not found') !== false) {
                echo "⚠️  VIEW NOT FOUND ERROR\n";
                if (preg_match('/View \[([^\]]+)\] not found/', $content, $matches)) {
                    echo "Missing view: " . $matches[1] . "\n";
                }
            }
            
            if (strpos($content, 'Target class') !== false && strpos($content, 'does not exist') !== false) {
                echo "⚠️  TARGET CLASS ERROR\n";
                if (preg_match('/Target class \[([^\]]+)\] does not exist/', $content, $matches)) {
                    echo "Missing target class: " . $matches[1] . "\n";
                }
            }
            
            // Save full error
            $errorFile = '/var/www/api-gateway/storage/logs/calls-page-error-' . time() . '.html';
            file_put_contents($errorFile, $content);
            echo "\nFull error saved to: $errorFile\n";
            
        } else if ($response->getStatusCode() == 302) {
            echo "🔄 Redirect to: " . $response->headers->get('Location') . "\n";
        } else if ($response->getStatusCode() == 200) {
            echo "✅ Calls page loaded successfully!\n";
        }
    } catch (\Exception $e) {
        echo "\n❌ Exception during request: " . $e->getMessage() . "\n";
        echo "Class: " . get_class($e) . "\n";
        
        // If it's a view error, show which view
        if (strpos($e->getMessage(), 'View') !== false) {
            echo "\nThis is a VIEW error - a Blade template is missing\n";
        }
    }
    
    // Check the CallResource structure
    echo "\n\nChecking CallResource structure...\n";
    echo str_repeat('-', 50) . "\n";
    
    $resourcePath = app_path('Filament/Admin/Resources/CallResource.php');
    if (file_exists($resourcePath)) {
        echo "✅ CallResource.php exists\n";
        
        // Check for page files
        $pagesDir = app_path('Filament/Admin/Resources/CallResource/Pages');
        if (is_dir($pagesDir)) {
            echo "✅ Pages directory exists\n";
            $pages = scandir($pagesDir);
            echo "Available pages:\n";
            foreach ($pages as $page) {
                if ($page !== '.' && $page !== '..' && strpos($page, '.php') !== false) {
                    echo "  - $page\n";
                    
                    // Check the class name
                    $className = str_replace('.php', '', $page);
                    $fullClass = "App\\Filament\\Admin\\Resources\\CallResource\\Pages\\$className";
                    if (class_exists($fullClass)) {
                        echo "    ✅ Class exists: $fullClass\n";
                    } else {
                        echo "    ❌ Class not found: $fullClass\n";
                    }
                }
            }
        } else {
            echo "❌ Pages directory not found\n";
        }
    } else {
        echo "❌ CallResource.php not found\n";
    }
    
    // Check Livewire components
    echo "\n\nChecking Livewire components...\n";
    $livewireManifest = base_path('bootstrap/cache/livewire-components.php');
    if (file_exists($livewireManifest)) {
        $components = include $livewireManifest;
        $callComponents = array_filter($components, function($component) {
            return strpos($component, 'call') !== false || strpos($component, 'Call') !== false;
        });
        
        if (count($callComponents) > 0) {
            echo "Found " . count($callComponents) . " call-related Livewire components\n";
        }
    }
    
} catch (\Throwable $e) {
    echo "\n❌ EXCEPTION: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "</pre>";