<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a request to /admin/calls
$request = Illuminate\Http\Request::create('/admin/calls', 'GET');
$request->setLaravelSession(app('session.store'));

// Login
$adminUser = \App\Models\User::where('email', 'fabian@askproai.de')->first();
if ($adminUser) {
    auth()->login($adminUser);
    $request->setUserResolver(function() use ($adminUser) {
        return $adminUser;
    });
}

// Handle the request
try {
    $response = $kernel->handle($request);
    $content = $response->getContent();
    
    echo "=== Response Status: " . $response->getStatusCode() . " ===\n\n";
    
    // Check for common issues
    if (strpos($content, 'wire:snapshot') !== false) {
        echo "✅ Livewire snapshot found\n";
    } else {
        echo "❌ No Livewire snapshot found\n";
    }
    
    if (strpos($content, 'wire:initial-data') !== false) {
        echo "✅ Livewire initial data found\n";
    } else {
        echo "❌ No Livewire initial data found\n";
    }
    
    if (strpos($content, '@livewireScripts') !== false || strpos($content, 'livewire/livewire.js') !== false) {
        echo "✅ Livewire scripts included\n";
    } else {
        echo "❌ Livewire scripts NOT included\n";
    }
    
    // Check for errors
    if (strpos($content, 'Exception') !== false || strpos($content, 'Error') !== false) {
        echo "\n⚠️ Possible error in response\n";
        
        // Extract error message
        preg_match('/<div class="text-2xl">(.*?)<\/div>/s', $content, $matches);
        if (isset($matches[1])) {
            echo "Error: " . strip_tags($matches[1]) . "\n";
        }
    }
    
    // Check for JavaScript errors
    if (strpos($content, 'console.error') !== false) {
        echo "\n⚠️ JavaScript console.error found\n";
    }
    
    // Save response for manual inspection
    file_put_contents('/var/www/api-gateway/storage/logs/admin-calls-content.html', $content);
    echo "\n📄 Full response saved to: storage/logs/admin-calls-content.html\n";
    
    // Extract key parts
    echo "\n=== Key Parts ===\n";
    
    // Title
    preg_match('/<title>(.*?)<\/title>/', $content, $matches);
    echo "Title: " . ($matches[1] ?? 'Not found') . "\n";
    
    // Check for empty content
    $bodyContent = preg_match('/<body[^>]*>(.*?)<\/body>/s', $content, $matches) ? $matches[1] : '';
    $bodyLength = strlen(strip_tags($bodyContent));
    echo "Body content length: $bodyLength characters\n";
    
    if ($bodyLength < 100) {
        echo "⚠️ Body seems empty or too short!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

$kernel->terminate($request, $response ?? new \Illuminate\Http\Response());