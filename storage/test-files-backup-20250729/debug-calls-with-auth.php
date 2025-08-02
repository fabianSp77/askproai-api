<?php
// Debug Calls Page with Authentication

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Calls Page with Authentication</h1>";
echo "<pre>";

try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Boot application
    $bootRequest = Illuminate\Http\Request::create('https://api.askproai.de/', 'GET');
    $kernel->handle($bootRequest);
    
    // Find user by email
    $user = \App\Models\User::where('email', 'fabian@askproai.de')->first();
    
    if (!$user) {
        echo "❌ User not found\n";
        exit;
    }
    
    echo "✅ Found user: " . $user->email . " (ID: " . $user->id . ")\n";
    echo "   Company ID: " . $user->company_id . "\n\n";
    
    // Login the user
    auth()->login($user);
    session()->put('password_hash_web', $user->getAuthPassword());
    
    // Set company context
    app()->instance('current_company_id', $user->company_id);
    app()->instance('company_context_source', 'debug_script');
    
    echo "Testing different approaches to access calls page...\n";
    echo str_repeat('=', 60) . "\n\n";
    
    // Test 1: Direct route access
    echo "Test 1: Direct Route Access\n";
    echo str_repeat('-', 30) . "\n";
    
    try {
        $route = app('router')->getRoutes()->getByName('filament.admin.resources.calls.index');
        if ($route) {
            echo "✅ Route found: " . $route->uri() . "\n";
            echo "   Action: " . $route->getActionName() . "\n";
        } else {
            echo "❌ Route not found\n";
        }
    } catch (\Exception $e) {
        echo "❌ Route error: " . $e->getMessage() . "\n";
    }
    
    // Test 2: Create authenticated request
    echo "\n\nTest 2: Authenticated Request\n";
    echo str_repeat('-', 30) . "\n";
    
    $callsRequest = Illuminate\Http\Request::create('https://api.askproai.de/admin/calls', 'GET', [], 
        ['askproai_session' => session()->getId()], 
        [], 
        [
            'HTTP_HOST' => 'api.askproai.de',
            'HTTPS' => 'on',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_COOKIE' => 'askproai_session=' . session()->getId(),
        ]
    );
    
    // Set user resolver
    $callsRequest->setUserResolver(function () use ($user) {
        return $user;
    });
    
    // Set session
    $callsRequest->setLaravelSession(session());
    
    // Replace request in container
    app()->instance('request', $callsRequest);
    
    try {
        $response = $kernel->handle($callsRequest);
        
        echo "Response Status: " . $response->getStatusCode() . "\n";
        
        if ($response->getStatusCode() == 302) {
            echo "🔄 Redirect to: " . $response->headers->get('Location') . "\n";
            echo "⚠️  Still being redirected to login despite authentication\n";
        } elseif ($response->getStatusCode() == 500) {
            echo "\n❌ 500 ERROR!\n";
            
            $content = $response->getContent();
            
            // Extract error message
            if (preg_match('/<title>(.*?)<\/title>/i', $content, $matches)) {
                echo "Title: " . strip_tags($matches[1]) . "\n";
            }
            
            if (preg_match('/<div[^>]*class="[^"]*message[^"]*"[^>]*>(.*?)<\/div>/s', $content, $matches)) {
                echo "Error: " . trim(strip_tags($matches[1])) . "\n";
            }
            
            // Look for specific errors
            if (strpos($content, 'company_id') !== false) {
                echo "\n⚠️  COMPANY CONTEXT ERROR DETECTED\n";
            }
            
            if (strpos($content, 'TenantScope') !== false) {
                echo "\n⚠️  TENANT SCOPE ERROR DETECTED\n";
            }
            
            // Save error
            $errorFile = '/var/www/api-gateway/storage/logs/calls-auth-error-' . time() . '.html';
            file_put_contents($errorFile, $content);
            echo "\nFull error saved to: $errorFile\n";
            
        } elseif ($response->getStatusCode() == 200) {
            echo "✅ Success!\n";
            
            // Check content
            $content = $response->getContent();
            if (strpos($content, 'wire:') !== false) {
                echo "✅ Livewire components found\n";
            }
            if (strpos($content, 'Anrufe') !== false || strpos($content, 'Calls') !== false) {
                echo "✅ Calls page content confirmed\n";
            }
        }
        
    } catch (\Throwable $e) {
        echo "\n❌ Exception: " . get_class($e) . "\n";
        echo "Message: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
        
        // Check if it's a database error
        if ($e instanceof \Illuminate\Database\QueryException) {
            echo "\n⚠️  DATABASE ERROR\n";
            echo "SQL: " . $e->getSql() . "\n";
        }
    }
    
    // Test 3: Check Livewire component
    echo "\n\nTest 3: Livewire Component Check\n";
    echo str_repeat('-', 30) . "\n";
    
    $componentClass = 'App\\Filament\\Admin\\Resources\\CallResource\\Pages\\ListCallsClean';
    if (class_exists($componentClass)) {
        echo "✅ Component class exists\n";
        
        try {
            $component = new $componentClass();
            echo "✅ Component instantiated\n";
            
            // Check if it's a Livewire component
            if (method_exists($component, 'render')) {
                echo "✅ Has render method\n";
            }
            
        } catch (\Exception $e) {
            echo "❌ Component error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Component class not found\n";
    }
    
    // Test 4: Direct table query
    echo "\n\nTest 4: Direct Database Query\n";
    echo str_repeat('-', 30) . "\n";
    
    try {
        // Set company context for query
        \App\Models\Call::addGlobalScope('company', function ($query) use ($user) {
            $query->where('company_id', $user->company_id);
        });
        
        $callCount = \App\Models\Call::count();
        echo "✅ Total calls for company {$user->company_id}: $callCount\n";
        
        $recentCall = \App\Models\Call::latest()->first();
        if ($recentCall) {
            echo "✅ Most recent call: " . $recentCall->created_at . "\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "\n";
    }
    
    // Check session and auth state
    echo "\n\nAuth & Session State:\n";
    echo str_repeat('-', 30) . "\n";
    echo "Auth check: " . (auth()->check() ? '✅ Authenticated' : '❌ Not authenticated') . "\n";
    echo "Auth user: " . (auth()->user() ? auth()->user()->email : 'None') . "\n";
    echo "Session ID: " . session()->getId() . "\n";
    echo "Session has 'password_hash_web': " . (session()->has('password_hash_web') ? 'Yes' : 'No') . "\n";
    echo "App has 'current_company_id': " . (app()->has('current_company_id') ? 'Yes (' . app('current_company_id') . ')' : 'No') . "\n";
    
} catch (\Throwable $e) {
    echo "\n❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString();
}

echo "</pre>";