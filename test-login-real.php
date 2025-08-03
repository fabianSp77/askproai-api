<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Testing Real Login Process ===\n\n";

// Create a login request exactly as it would come from the browser
$request = Illuminate\Http\Request::create(
    '/business/login',
    'POST',
    [
        '_token' => 'test-token',
        'email' => 'demo@askproai.de',
        'password' => 'password'
    ],
    [], // cookies
    [], // files
    [
        'HTTP_ACCEPT' => 'text/html,application/xhtml+xml',
        'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
    ]
);

// Ensure session is started
$request->setLaravelSession($app['session']->driver());

try {
    // Handle the request through the full middleware stack
    echo "Processing login request...\n";
    $response = $kernel->handle($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() == 302) {
        echo "Redirect to: " . $response->headers->get('Location') . "\n";
    } elseif ($response->getStatusCode() == 200) {
        // Check for error messages in the response
        $content = $response->getContent();
        if (strpos($content, 'Die angegebenen Zugangsdaten sind ungültig') !== false) {
            echo "❌ Login failed: Invalid credentials message found\n";
        }
    }
    
    // Check what actually happened in the controller
    echo "\nChecking database directly:\n";
    $user = \App\Models\PortalUser::withoutGlobalScope(\App\Scopes\CompanyScope::class)
        ->where('email', 'demo@askproai.de')
        ->first();
    
    if ($user) {
        echo "User found: ID " . $user->id . "\n";
        echo "Password check: " . (\Illuminate\Support\Facades\Hash::check('password', $user->password) ? 'VALID' : 'INVALID') . "\n";
    } else {
        echo "User NOT found\n";
    }
    
} catch (\Exception $e) {
    echo "Exception: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

$kernel->terminate($request, $response ?? null);