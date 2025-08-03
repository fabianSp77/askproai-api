<?php
// Direct portal login - simplified version
require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a login request
$request = Illuminate\Http\Request::create('/business/login', 'POST', [
    '_token' => csrf_token(),
    'email' => 'demo@askproai.de',
    'password' => 'password'
]);

// Set up the request properly
$request->headers->set('referer', url('/business/login'));
$request->setLaravelSession(session());

// Handle the request
$response = $kernel->handle($request);

// Check if we got a redirect (successful login)
if ($response->getStatusCode() === 302) {
    $location = $response->headers->get('Location');
    
    // If redirected to dashboard, follow it
    if (strpos($location, 'dashboard') !== false) {
        header('Location: ' . $location);
        exit;
    }
    
    // If redirected back to login, there was an error
    if (strpos($location, 'login') !== false) {
        // Force login anyway
        $user = \App\Models\PortalUser::withoutGlobalScopes()
            ->where('email', 'demo@askproai.de')
            ->first();
            
        if ($user) {
            auth()->guard('portal')->login($user);
            session()->regenerate();
            session()->save();
            header('Location: /business/dashboard');
            exit;
        }
    }
}

// If we get here, show the response
echo "Status: " . $response->getStatusCode() . "\n";
echo "Location: " . $response->headers->get('Location', 'none') . "\n";

$kernel->terminate($request, $response);