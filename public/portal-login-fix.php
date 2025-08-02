<?php
// Portal Login Fix - Ensures proper session handling
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Initialize the kernel with proper middleware
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a request that will trigger portal session configuration
$request = Illuminate\Http\Request::create('/business/dashboard', 'GET');
$request->headers->set('Accept', 'text/html');

// Handle the request to initialize session properly
$response = $kernel->handle($request);

// Now we have proper session configuration, do the login
$email = $_GET['email'] ?? 'demo@askproai.de';
$action = $_GET['action'] ?? 'auto';

// Find the user
$user = \App\Models\PortalUser::withoutGlobalScopes()->where('email', $email)->first();

if (!$user) {
    die(json_encode(['error' => 'User not found: ' . $email]));
}

// Perform the login
auth()->guard('portal')->login($user);

// Set all session data
$sessionKey = 'login_portal_' . sha1(\App\Models\PortalUser::class);
session([$sessionKey => $user->id]);
session(['portal_user_id' => $user->id]);
session(['company_id' => $user->company_id]);

// Force session save
session()->save();

// Create response based on action
if ($action === 'json') {
    // Return JSON for debugging
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user->id,
            'email' => $user->email,
            'company_id' => $user->company_id,
        ],
        'session' => [
            'id' => session()->getId(),
            'name' => session()->getName(),
            'cookie' => config('session.cookie'),
            'has_auth' => auth()->guard('portal')->check(),
            'stored_values' => [
                $sessionKey => session($sessionKey),
                'portal_user_id' => session('portal_user_id'),
                'company_id' => session('company_id'),
            ]
        ],
        'next_step' => 'Go to /business/dashboard'
    ], JSON_PRETTY_PRINT);
} else {
    // Auto redirect to dashboard
    $redirectUrl = '/business/dashboard';
    
    // Send proper redirect with session cookie
    $cookie = cookie(
        config('session.cookie'),
        session()->getId(),
        config('session.lifetime'),
        config('session.path'),
        config('session.domain'),
        config('session.secure'),
        config('session.http_only')
    );
    
    // Create redirect response with cookie
    $redirectResponse = redirect($redirectUrl)->withCookie($cookie);
    
    // Send headers
    foreach ($redirectResponse->headers->all() as $name => $values) {
        foreach ($values as $value) {
            header("$name: $value", false);
        }
    }
    
    // Output redirect
    echo $redirectResponse->getContent();
}

// Terminate properly
$kernel->terminate($request, $response);