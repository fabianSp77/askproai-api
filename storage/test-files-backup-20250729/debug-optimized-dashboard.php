<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

// Login as admin for testing
$user = \App\Models\User::where('email', 'admin@admin.com')->first();
if (!$user) {
    $user = \App\Models\User::first();
}

if ($user) {
    auth()->login($user);
    echo "Logged in as: " . $user->email . "\n\n";
} else {
    die("No users found in database!\n");
}

// Now try to access the page
try {
    echo "Attempting to access /admin/optimized-dashboard...\n\n";
    
    // Create a request to the page
    $request = Illuminate\Http\Request::create('/admin/optimized-dashboard', 'GET');
    $request->setUserResolver(function () use ($user) {
        return $user;
    });
    
    // Handle the request
    $response = $kernel->handle($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() !== 200) {
        echo "\nResponse Headers:\n";
        foreach ($response->headers->all() as $key => $values) {
            echo "$key: " . implode(', ', $values) . "\n";
        }
        
        echo "\nResponse Content (first 1000 chars):\n";
        echo substr($response->getContent(), 0, 1000) . "\n";
        
        // Check for exceptions
        if ($response->exception ?? null) {
            echo "\nException: " . $response->exception->getMessage() . "\n";
            echo "File: " . $response->exception->getFile() . "\n";
            echo "Line: " . $response->exception->getLine() . "\n";
        }
    } else {
        echo "Page loaded successfully!\n";
    }
    
} catch (Exception $e) {
    echo "\nException caught:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}