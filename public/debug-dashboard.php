<?php
// Debug Dashboard with full error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';

try {
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    
    // Simulate admin dashboard request
    $request = \Illuminate\Http\Request::create('/admin', 'GET');
    
    // Add auth session from current user
    $request->setLaravelSession(
        $app['session.store']
    );
    
    // Manually authenticate as Fabian
    $user = \App\Models\User::where('email', 'fabian@askproai.de')->first();
    if ($user) {
        \Illuminate\Support\Facades\Auth::login($user);
        echo "Logged in as: " . $user->email . "\n";
    }
    
    // Handle request
    try {
        $response = $kernel->handle($request);
        echo "Response status: " . $response->getStatusCode() . "\n";
        
        if ($response->getStatusCode() >= 400) {
            echo "\nERROR RESPONSE:\n";
            $content = $response->getContent();
            
            // Extract error message from HTML if present
            if (preg_match('/<div class="message">(.*?)<\/div>/s', $content, $matches)) {
                echo "Error message: " . strip_tags($matches[1]) . "\n";
            }
            
            // Look for exception details
            if (preg_match('/<h1 class="exception-message">(.*?)<\/h1>/s', $content, $matches)) {
                echo "Exception: " . strip_tags($matches[1]) . "\n";
            }
            
            // Show first 500 chars of response
            echo "\nResponse preview:\n";
            echo substr(strip_tags($content), 0, 500) . "\n";
        }
    } catch (\Throwable $e) {
        echo "\nEXCEPTION CAUGHT:\n";
        echo get_class($e) . ": " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "\nStack trace:\n";
        echo $e->getTraceAsString() . "\n";
    }
    
} catch (\Throwable $e) {
    echo "BOOTSTRAP ERROR:\n";
    echo get_class($e) . ": " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}