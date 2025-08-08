<?php

// Bootstrap the application
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Create kernel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create request
$request = Illuminate\Http\Request::create(
    '/admin/calls',
    'GET',
    [],
    [],
    [],
    ['HTTP_HOST' => 'api.askproai.de']
);

// Authenticate user programmatically
app()->instance('request', $request);
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

// First, let's login
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
if ($user) {
    auth()->login($user);
    
    // Set session
    session()->put('password_hash_web', $user->password);
    session()->save();
    
    // Now try to access the page again
    $request2 = Illuminate\Http\Request::create(
        '/admin/calls',
        'GET',
        [],
        [],
        [],
        ['HTTP_HOST' => 'api.askproai.de']
    );
    
    $request2->setUserResolver(function () use ($user) {
        return $user;
    });
    
    $response2 = $kernel->handle($request2);
    
    echo "Response status: " . $response2->getStatusCode() . "\n";
    echo "Response size: " . strlen($response2->getContent()) . " bytes\n\n";
    
    $content = $response2->getContent();
    
    // Check content
    if (strpos($content, 'Anmelden') !== false) {
        echo "⚠ Login page detected\n";
    } else {
        echo "✓ Not login page\n";
    }
    
    if (strpos($content, 'fi-ta-table') !== false) {
        echo "✓ Filament table found\n";
    }
    
    if (strpos($content, 'Anrufe') !== false) {
        echo "✓ 'Anrufe' text found\n";
    }
    
    // Save for inspection
    file_put_contents('/tmp/test-calls-direct.html', $content);
    echo "\nSaved to /tmp/test-calls-direct.html\n";
    
} else {
    echo "User not found!\n";
}