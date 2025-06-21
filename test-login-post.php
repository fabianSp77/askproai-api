<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Bootstrap the application
$kernel->bootstrap();

// Create a mock login request
$request = \Illuminate\Http\Request::create(
    '/admin/login',
    'POST',
    [
        'email' => 'fabian@askproai.de',
        'password' => 'Qwe421as1!1',
        'remember' => false,
    ]
);

// Add necessary headers
$request->headers->set('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
$request->headers->set('Content-Type', 'application/x-www-form-urlencoded');

// Set the session
$sessionId = \Illuminate\Support\Str::random(40);
$request->cookies->set(config('session.cookie'), $sessionId);

// Generate CSRF token
$token = csrf_token();
$request->merge(['_token' => $token]);

echo "Testing login POST request\n";
echo "=========================\n\n";
echo "Email: fabian@askproai.de\n";
echo "Password: Qwe421as1!1\n";
echo "CSRF Token: {$token}\n";
echo "Session ID: {$sessionId}\n\n";

// Process the request
try {
    $response = $kernel->handle($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Type: " . get_class($response) . "\n";
    
    if ($response instanceof \Illuminate\Http\RedirectResponse) {
        echo "Redirect To: " . $response->getTargetUrl() . "\n";
    }
    
    // Check if authenticated
    $authenticatedUserId = $request->session()->get('filament.admin.auth.user');
    if ($authenticatedUserId) {
        echo "\n✅ Login successful! User ID in session: {$authenticatedUserId}\n";
    } else {
        echo "\n❌ Login failed - no user in session\n";
        
        // Check for errors
        $errors = $request->session()->get('errors');
        if ($errors) {
            echo "Errors:\n";
            foreach ($errors->all() as $error) {
                echo "  - {$error}\n";
            }
        }
    }
    
    // Output headers
    echo "\nResponse Headers:\n";
    foreach ($response->headers->all() as $name => $values) {
        foreach ($values as $value) {
            echo "  {$name}: {$value}\n";
        }
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

$kernel->terminate($request, $response);